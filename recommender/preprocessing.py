"""PySpark preprocessing: read CSVs from MinIO, build interactions, write Parquet to MinIO.

Usage:
    cd recommender
    .\\venv\\Scripts\\activate
    python preprocessing.py
"""
import os
import shutil
import tempfile
from pathlib import Path

import pandas as pd
from minio import Minio
from pyspark.sql import SparkSession
from pyspark.sql.types import IntegerType, DoubleType, TimestampType
from pyspark.sql.functions import lit, col, row_number, to_timestamp
from pyspark.sql.window import Window
import sys
import traceback

from config import MINIO_ENDPOINT, MINIO_BUCKET, MINIO_ACCESS_KEY, MINIO_SECRET_KEY


def get_minio_client():
    return Minio(
        MINIO_ENDPOINT,
        access_key=MINIO_ACCESS_KEY,
        secret_key=MINIO_SECRET_KEY,
        secure=False,
    )


def download_from_minio(client, object_name: str, local_path: Path):
    local_path.parent.mkdir(parents=True, exist_ok=True)
    client.fget_object(MINIO_BUCKET, object_name, str(local_path))
    print(f"Downloaded {object_name} to {local_path}")
    return local_path


def download_minio_csvs(base_dir: Path):
    client = get_minio_client()
    events_local = base_dir / "events.csv"
    items_local = base_dir / "item_properties.csv"
    download_from_minio(client, "events.csv", events_local)
    download_from_minio(client, "item_properties.csv", items_local)
    return events_local, items_local


def upload_parquet_dir_to_minio(base_dir: Path, target_prefix: str):
    client = get_minio_client()
    for file_path in sorted(base_dir.rglob("*")):
        if file_path.is_file():
            rel_path = file_path.relative_to(base_dir).as_posix()
            object_name = f"{target_prefix}/{rel_path}"
            client.fput_object(MINIO_BUCKET, object_name, str(file_path))
            print(f"Uploaded {object_name}")


def create_spark_session():
    # Ensure Java is available for PySpark
    java_home = os.environ.get("JAVA_HOME")
    if not java_home:
        java_path = shutil.which("java")
        if java_path:
            java_home = str(Path(java_path).resolve().parent.parent)
        else:
            java_home = str(Path.home() / "AppData" / "Local" / "Programs" / "Microsoft" / "jdk-17.0.10.7-hotspot")

    if java_home and Path(java_home).exists():
        os.environ["JAVA_HOME"] = java_home
        java_bin = Path(java_home) / "bin"
        os.environ["PATH"] = f"{java_bin}{os.pathsep}{os.environ.get('PATH','')}"

    # Ensure Spark can find Hadoop winutils on Windows
    hadoop_home = os.environ.get("HADOOP_HOME", r"C:\hadoop")
    os.environ["HADOOP_HOME"] = hadoop_home
    os.environ["hadoop.home.dir"] = hadoop_home

    builder = SparkSession.builder.appName("ecommerce-preprocessing")
    # Add Hadoop AWS / S3A packages for MinIO access
    builder = builder.config(
        "spark.jars.packages",
        "org.apache.hadoop:hadoop-aws:3.3.4,com.amazonaws:aws-java-sdk-bundle:1.12.509",
    )

    ivy_jars = Path.home() / ".ivy2.5.2" / "jars"
    local_jars = [
        ivy_jars / "org.apache.hadoop_hadoop-aws-3.3.4.jar",
        ivy_jars / "com.amazonaws_aws-java-sdk-bundle-1.12.509.jar",
    ]
    existing_jars = [str(p) for p in local_jars if p.exists()]
    if existing_jars:
        jar_list = ",".join(existing_jars)
        builder = builder.config("spark.jars", jar_list)
        builder = builder.config("spark.driver.extraClassPath", jar_list)
        builder = builder.config("spark.executor.extraClassPath", jar_list)

    # Configure S3A / MinIO access
    endpoint = MINIO_ENDPOINT if MINIO_ENDPOINT.startswith("http") else f"http://{MINIO_ENDPOINT}"
    builder = builder.config("spark.hadoop.fs.s3a.endpoint", endpoint)
    builder = builder.config("spark.hadoop.fs.s3a.access.key", MINIO_ACCESS_KEY)
    builder = builder.config("spark.hadoop.fs.s3a.secret.key", MINIO_SECRET_KEY)
    builder = builder.config("spark.hadoop.fs.s3a.path.style.access", "true")
    builder = builder.config("spark.hadoop.fs.s3a.connection.ssl.enabled", "false")
    builder = builder.config("spark.hadoop.fs.s3a.impl", "org.apache.hadoop.fs.s3a.S3AFileSystem")
    builder = builder.config("spark.hadoop.fs.s3a.connection.maximum", "100")
    builder = builder.config("spark.hadoop.fs.s3a.attempts.maximum", "3")
    builder = builder.config("spark.hadoop.fs.s3a.connection.establish.timeout", "60000")
    builder = builder.config("spark.hadoop.fs.s3a.connection.timeout", "60000")
    builder = builder.config("spark.hadoop.fs.s3a.connection.request.timeout", "120000")
    builder = builder.config("spark.hadoop.fs.s3a.socket.send.buffer", "65536")
    builder = builder.config("spark.hadoop.fs.s3a.socket.recv.buffer", "65536")
    builder = builder.config("spark.hadoop.home.dir", hadoop_home)
    builder = builder.config("spark.ui.showConsoleProgress", "false")
    spark = builder.getOrCreate()
    return spark


def main():
    spark = create_spark_session()
    local_temp_dir = Path(tempfile.mkdtemp(prefix="preprocessing_"))
    local_parquet_dir = local_temp_dir / "interactions.parquet"
    try:
        events_path = f"s3a://{MINIO_BUCKET}/events.csv"
        items_path = f"s3a://{MINIO_BUCKET}/item_properties.csv"
        events_df = None
        items_df = None

        try:
            print(f"Attempting direct S3A read from MinIO: {events_path}")
            events_df = (
                spark.read.options(header=True, inferSchema=True, sep=",")
                .csv(events_path)
            )
            print("Direct S3A events read succeeded.")

            print(f"Attempting direct S3A read from MinIO: {items_path}")
            items_df = (
                spark.read.options(header=True, inferSchema=True, sep=",")
                .csv(items_path)
            )
            print("Direct S3A item_properties read succeeded.")
        except Exception as s3a_error:
            print("Direct S3A read failed, falling back to local MinIO download:", str(s3a_error))
            local_csv_dir = local_temp_dir / "csv"
            events_local, items_local = download_minio_csvs(local_csv_dir)
            print(f"Reading events from local file: {events_local}")
            events_df = (
                spark.read.options(header=True, inferSchema=True, sep=",")
                .csv(str(events_local))
            )
            print(f"Reading item properties from local file: {items_local}")
            items_df = (
                spark.read.options(header=True, inferSchema=True, sep=",")
                .csv(str(items_local))
            )

        print("Events schema:")
        events_df.printSchema()
        events_df.show(5, truncate=False)

        print("Items schema:")
        items_df.printSchema()
        items_df.show(5, truncate=False)

        # Clean events
        events_df = events_df.dropna(subset=["user_id", "item_id"])
        events_df = events_df.withColumn("user_id", events_df["user_id"].cast(IntegerType()))
        events_df = events_df.withColumn("item_id", events_df["item_id"].cast(IntegerType()))

        # Parse timestamp column if present
        if "timestamp" in events_df.columns:
            events_df = events_df.withColumn("timestamp", to_timestamp(col("timestamp"), "yyyy-MM-dd HH:mm:ss"))

        # Add rating
        events_df = events_df.withColumn("rating", lit(3.0).cast(DoubleType()))

        # Deduplicate: keep latest event per user_id,item_id
        window_spec = Window.partitionBy("user_id", "item_id").orderBy(col("timestamp").desc())
        events_dedup = (
            events_df.withColumn("rn", row_number().over(window_spec))
            .filter(col("rn") == 1)
            .drop("rn")
        )

        total = events_dedup.count()
        users = events_dedup.select("user_id").distinct().count()
        items = events_dedup.select("item_id").distinct().count()
        print(f"Cleaned events: rows={total}, distinct_users={users}, distinct_items={items}")

        if "item_id" in items_df.columns:
            items_df = items_df.withColumn("item_id", items_df["item_id"].cast(IntegerType()))
            events_filtered = events_dedup.join(items_df.select("item_id"), on="item_id", how="inner")
            print(f"After join filter: {events_filtered.count()} rows")
            interactions_df = events_filtered.select("user_id", "item_id", "rating")
        else:
            interactions_df = events_dedup.select("user_id", "item_id", "rating")

        local_parquet_dir.mkdir(parents=True, exist_ok=True)
        local_parquet_file = local_parquet_dir / "part-00000.parquet"
        print(f"Writing interactions to local Parquet file: {local_parquet_file}")

        interactions_pdf = interactions_df.toPandas()
        interactions_pdf.to_parquet(str(local_parquet_file), engine="pyarrow", index=False)

        print("Uploading Parquet results to MinIO")
        upload_parquet_dir_to_minio(local_parquet_dir, "processed/interactions.parquet")
        written = len(interactions_pdf)
        print(f"Wrote {written} interaction rows to s3a://{MINIO_BUCKET}/processed/interactions.parquet")

    except Exception as e:
        print("Error during preprocessing:", str(e))
        traceback.print_exc()
        sys.exit(1)
    finally:
        try:
            spark.stop()
        except Exception:
            pass
        try:
            shutil.rmtree(local_temp_dir)
        except Exception:
            pass


if __name__ == "__main__":
    main()

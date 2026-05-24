import os
import shutil
import sys
import traceback
from pathlib import Path

import pandas as pd
from pyspark.sql import SparkSession
from pyspark.sql import functions as F
from pyspark.sql.types import IntegerType, DoubleType
from pyspark.sql.window import Window
from pyspark.ml.recommendation import ALS
from pyspark.ml.evaluation import RegressionEvaluator


def create_spark_session():
    java_home = os.environ.get("JAVA_HOME")
    if not java_home:
        java_path = shutil.which("java")
        if java_path:
            java_home = str(Path(java_path).resolve().parent.parent)
        else:
            default_jdk = Path.home() / "AppData" / "Local" / "Programs" / "Microsoft" / "jdk-17.0.10.7-hotspot"
            if default_jdk.exists():
                java_home = str(default_jdk)

    if java_home and Path(java_home).exists():
        os.environ["JAVA_HOME"] = java_home
        java_bin = Path(java_home) / "bin"
        os.environ["PATH"] = f"{java_bin}{os.pathsep}{os.environ.get('PATH','')}"
        print(f"Using JAVA_HOME={java_home}")
    else:
        print("WARNING: JAVA_HOME not set and Java not found on PATH. Spark may fail to start.")

    hadoop_home = os.environ.get("HADOOP_HOME", r"C:\hadoop")
    if Path(hadoop_home).exists():
        os.environ["HADOOP_HOME"] = hadoop_home
        os.environ["hadoop.home.dir"] = hadoop_home
        print(f"Using HADOOP_HOME={hadoop_home}")

    os.environ["PYSPARK_PYTHON"] = sys.executable
    os.environ["PYSPARK_DRIVER_PYTHON"] = sys.executable

    spark = (
        SparkSession.builder
        .appName("ALSRecommenderTraining")
        .config("spark.pyspark.python", sys.executable)
        .config("spark.pyspark.driver.python", sys.executable)
        .config("spark.driver.bindAddress", "127.0.0.1")
        .getOrCreate()
    )
    return spark


def find_interactions_path():
    candidates = [
        Path("recommender") / "data" / "interactions.parquet",
        Path("recommender") / "interactions" / "interactions.parquet",
        Path("recommender") / "interactions.parquet",
        Path("data") / "interactions.parquet",
    ]
    for c in candidates:
        if c.exists():
            return c

    base = Path("recommender")
    if base.exists():
        for p in base.rglob("interactions.parquet"):
            return p

    return None


def prepare_interactions_from_csv(spark, path_parquet):
    events_csv = Path("recommender") / "data" / "events.csv"
    items_csv = Path("recommender") / "data" / "item_properties.csv"

    if not events_csv.exists() or not items_csv.exists():
        print("ERROR: interactions Parquet not found and CSV source files are missing.")
        print(f"Missing: {events_csv if not events_csv.exists() else ''} {items_csv if not items_csv.exists() else ''}")
        sys.exit(2)

    print(f"Preparing interactions from CSV files: {events_csv} and {items_csv}")
    events_df = spark.read.options(header=True, inferSchema=True, sep=",").csv(str(events_csv))
    items_df = spark.read.options(header=True, inferSchema=True, sep=",").csv(str(items_csv))

    events_df = events_df.dropna(subset=["user_id", "item_id"])
    events_df = events_df.withColumn("user_id", events_df["user_id"].cast(IntegerType()))
    events_df = events_df.withColumn("item_id", events_df["item_id"].cast(IntegerType()))

    if "timestamp" in events_df.columns:
        events_df = events_df.withColumn("timestamp", F.to_timestamp(F.col("timestamp"), "yyyy-MM-dd HH:mm:ss"))

    # Map event types to interaction weights to create informative ratings
    # purchase -> 5.0, add_to_cart/checkout -> 4.0, view/click -> 1.0, default 1.0
    events_df = events_df.withColumn(
        "weight",
        F.when(F.col("event_type") == "purchase", F.lit(5.0))
        .when(F.col("event_type").isin("add_to_cart", "checkout"), F.lit(4.0))
        .when(F.col("event_type").isin("view", "click"), F.lit(1.0))
        .otherwise(F.lit(1.0))
    )

    # Aggregate multiple interactions per (user_id, item_id) into a summed weight
    events_df = events_df.groupBy("user_id", "item_id").agg(F.sum("weight").alias("rating"))
    events_df = events_df.withColumn("rating", events_df["rating"].cast(DoubleType()))

    if "item_id" in items_df.columns:
        items_df = items_df.withColumn("item_id", items_df["item_id"].cast(IntegerType()))
        events_df = events_df.join(items_df.select("item_id"), on="item_id", how="inner")

    output_df = events_df.select("user_id", "item_id", "rating")
    pandas_df = output_df.toPandas()

    if path_parquet.exists():
        if path_parquet.is_dir():
            shutil.rmtree(path_parquet)
        else:
            path_parquet.unlink()

    path_parquet.parent.mkdir(parents=True, exist_ok=True)
    pandas_df.to_parquet(str(path_parquet), engine="pyarrow", index=False)
    print(f"Saved prepared interactions to {path_parquet} via pandas/pyarrow")

    return spark.createDataFrame(pandas_df)


def load_parquet_with_pandas(spark, path_parquet):
    print(f"Loading Parquet via pandas/pyarrow fallback: {path_parquet}")
    pandas_df = pd.read_parquet(str(path_parquet), engine="pyarrow")
    return spark.createDataFrame(pandas_df)


def verify_recommendations_output(spark):
    recs_path = Path("recommender") / "data" / "user_recs.parquet"
    print(f"Verification: loading user_recs.parquet from {recs_path}...")
    if not recs_path.exists():
        print(f"ERROR: user_recs.parquet not found at {recs_path}")
        return

    try:
        df = spark.read.parquet(str(recs_path))
        print("Verification: loaded user_recs.parquet with Spark.")
        total_rows = df.count()
        distinct_users = df.select("user_id").distinct().count()
        print(f"Total recommendation rows: {total_rows}")
        print(f"Distinct users with recs: {distinct_users}")
        print("Sample recommendations:")
        df.select("user_id", "item_id", "pred_rating").show(10, truncate=False)

        first_user_row = df.select("user_id").orderBy("user_id").limit(1).collect()
        if first_user_row:
            first_user = first_user_row[0]["user_id"]
            print(f"Recommendations for smallest user_id={first_user}:")
            df.filter(F.col("user_id") == first_user).show(10, truncate=False)
    except Exception as spark_err:
        print("Spark verification failed, falling back to pandas/pyarrow:", str(spark_err))
        try:
            pandas_df = pd.read_parquet(str(recs_path), engine="pyarrow")
            total_rows = len(pandas_df)
            distinct_users = pandas_df["user_id"].nunique()
            print("Verification: loaded user_recs.parquet with pandas.")
            print(f"Total recommendation rows: {total_rows}")
            print(f"Distinct users with recs: {distinct_users}")
            print("Sample recommendations:")
            print(pandas_df[["user_id", "item_id", "pred_rating"]].head(10).to_string(index=False))
            if total_rows > 0:
                first_user = pandas_df["user_id"].min()
                print(f"Recommendations for smallest user_id={first_user}:")
                user_df = pandas_df[pandas_df["user_id"] == first_user][["user_id", "item_id", "pred_rating"]]
                print(user_df.head(10).to_string(index=False))
        except Exception as pandas_err:
            print("Verification failed with pandas as well:", str(pandas_err))


def main():
    spark = create_spark_session()
    try:
        interactions_path = find_interactions_path() or Path("recommender") / "data" / "interactions.parquet"
        print(f"Looking for interactions Parquet at: {interactions_path}")
        if interactions_path.exists() and interactions_path.is_dir() and not any(interactions_path.iterdir()):
            print(f"Found empty interactions Parquet directory at {interactions_path}; removing stale directory.")
            shutil.rmtree(interactions_path)

        if not interactions_path.exists():
            interactions_df = prepare_interactions_from_csv(spark, interactions_path)
        else:
            print("Loading interactions from existing Parquet...")
            try:
                interactions_df = spark.read.parquet(str(interactions_path))
            except Exception as e:
                print("Spark parquet read failed, falling back to pandas/pyarrow:", str(e))
                interactions_df = load_parquet_with_pandas(spark, interactions_path)

        print("Interactions schema:")
        interactions_df.printSchema()
        interactions_df.show(5, truncate=False)

        # Ensure correct dtypes
        interactions_df = (
            interactions_df
            .withColumn("user_id", interactions_df["user_id"].cast(IntegerType()))
            .withColumn("item_id", interactions_df["item_id"].cast(IntegerType()))
            .withColumn("rating", interactions_df["rating"].cast(DoubleType()))
        )

        # Drop rows with nulls in essential columns
        interactions_df = interactions_df.dropna(subset=["user_id", "item_id", "rating"])

        total_rows = interactions_df.count()
        print(f"Total interactions rows: {total_rows}")

        # Train/test split
        train_df, test_df = interactions_df.randomSplit([0.8, 0.2], seed=42)
        train_count = train_df.count()
        test_count = test_df.count()
        print(f"Train count: {train_count}, Test count: {test_count}")

        if test_count == 0:
            print("Warning: test split is empty. Using a small sample of the data for testing.")
            test_df = interactions_df.limit(1)

        # Configure ALS
        als = ALS(
            maxIter=10,
            regParam=0.1,
            rank=10,
            userCol="user_id",
            itemCol="item_id",
            ratingCol="rating",
            coldStartStrategy="drop",
            nonnegative=True,
        )

        # Fit model
        try:
            print("Training ALS model...")
            model = als.fit(train_df)
        except Exception as e:
            print("Error during ALS training:", str(e))
            traceback.print_exc()
            sys.exit(3)

        # Evaluate
        print("Evaluating model on test set...")
        predictions = model.transform(test_df).dropna(subset=["prediction"])
        evaluator = RegressionEvaluator(metricName="rmse", labelCol="rating", predictionCol="prediction")
        try:
            rmse = evaluator.evaluate(predictions)
            print(f"Test RMSE = {rmse}")
        except Exception as e:
            print("Could not compute RMSE:", str(e))

        # MAE
        try:
            mae_row = predictions.select(F.avg(F.abs(F.col("prediction") - F.col("rating"))).alias("mae")).collect()
            mae = mae_row[0]["mae"] if mae_row else None
            print(f"Test MAE = {mae}")
        except Exception as e:
            print("Could not compute MAE:", str(e))

        # Generate top-N recommendations per user
        top_n = 10
        print(f"Generating top-{top_n} recommendations for all users...")
        user_recs = model.recommendForAllUsers(top_n)
        user_recs.show(5, truncate=False)

        recs_exploded = (
            user_recs
            .select(F.col("user_id"), F.explode(F.col("recommendations")).alias("rec"))
            .select(
                F.col("user_id"),
                F.col("rec.item_id").alias("item_id"),
                F.col("rec.rating").alias("pred_rating"),
            )
        )

        recs_exploded.show(10, truncate=False)

        # Save recommendations via pandas/pyarrow to avoid Spark local Parquet write issues on Windows
        recs_path = Path("recommender") / "data" / "user_recs.parquet"
        recs_path.parent.mkdir(parents=True, exist_ok=True)
        recs_pdf = recs_exploded.toPandas()
        if recs_path.exists():
            if recs_path.is_dir():
                shutil.rmtree(recs_path)
            else:
                recs_path.unlink()
        recs_pdf.to_parquet(str(recs_path), engine="pyarrow", index=False)
        print(f"Saved user recommendations to {recs_path} via pandas/pyarrow")

        verify_recommendations_output(spark)

        # Optionally save model
        model_path = Path("recommender") / "data" / "als_model"
        try:
            print(f"Saving ALS model to {model_path}")
            model.write().overwrite().save(str(model_path))
            print(f"Saved ALS model to {model_path}")
        except Exception as e:
            print("Could not save ALS model:", str(e))

    finally:
        try:
            spark.stop()
        except Exception:
            pass


if __name__ == "__main__":
    main()

"""Phase 1: Export MySQL -> CSV -> MinIO

Writes `events.csv` and `item_properties.csv` to a local data directory
and uploads them to the configured MinIO bucket.
"""

from pathlib import Path
import re
import sys
import traceback

import pandas as pd
import pymysql
from minio import Minio

from config import (
    MYSQL_HOST,
    MYSQL_DB,
    MYSQL_USER,
    MYSQL_PASSWORD,
    MINIO_ENDPOINT,
    MINIO_ACCESS_KEY,
    MINIO_SECRET_KEY,
    MINIO_BUCKET,
)


BASE_DIR = Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / "data"
DATA_DIR.mkdir(parents=True, exist_ok=True)


def get_connection():
    try:
        conn = pymysql.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=True,
        )
        print("MySQL connection established.")
        return conn
    except Exception as e:
        print("Failed to connect to MySQL:", str(e))
        traceback.print_exc()
        return None


def load_orders_and_users(conn):
    query = (
        "SELECT c.id_commande, c.id_client, c.date_commande, c.statut_commande,"
        " lc.id_produit, lc.quantite, lc.prix_unitaire"
        " FROM commande c JOIN ligne_commande lc ON c.id_commande = lc.id_commande"
    )
    try:
        with conn.cursor() as cursor:
            cursor.execute(query)
            rows = cursor.fetchall()
        df = pd.DataFrame(rows)
        print(f"Loaded {len(df)} order lines from database.")
        return df
    except Exception as e:
        print("Failed to load orders:", str(e))
        traceback.print_exc()
        return pd.DataFrame()


def load_products(conn):
    query = "SELECT id, name, description, price, category, subcategory, brand, image_url FROM products"
    try:
        with conn.cursor() as cursor:
            cursor.execute(query)
            rows = cursor.fetchall()
        df = pd.DataFrame(rows)
        print(f"Loaded {len(df)} products from database.")
        return df
    except Exception as e:
        print("Failed to load products:", str(e))
        traceback.print_exc()
        return pd.DataFrame()


def build_events_df(orders_df: pd.DataFrame) -> pd.DataFrame:
    if orders_df.empty:
        return pd.DataFrame(columns=["user_id", "item_id", "event_type", "timestamp"])

    events = orders_df[["id_client", "id_produit", "date_commande"]].copy()
    events = events.rename(columns={"id_client": "user_id", "id_produit": "item_id", "date_commande": "timestamp"})
    events["timestamp"] = pd.to_datetime(events["timestamp"]).dt.strftime("%Y-%m-%d %H:%M:%S")
    events["event_type"] = "purchase"
    events = events[["user_id", "item_id", "event_type", "timestamp"]]
    events = events.dropna(subset=["user_id", "item_id"]) 

    print(f"Events: rows={len(events)}, distinct_users={events['user_id'].nunique()}, distinct_items={events['item_id'].nunique()}")
    return events


def _normalize_name_key(value: str) -> str:
    if not value or not isinstance(value, str):
        return ""
    return re.sub(r"[^a-z0-9]", "", value.lower())


def _resolve_local_image_url(product_name: str) -> str:
    if not product_name or not isinstance(product_name, str):
        return ""
    image_dir = BASE_DIR.parent / "images" / "prod_images"
    sanitized = _normalize_name_key(product_name)
    if not image_dir.exists():
        return ""
    for candidate in image_dir.iterdir():
        if not candidate.is_file():
            continue
        if _normalize_name_key(candidate.stem) == sanitized:
            return str(Path("images") / "prod_images" / candidate.name)
    return ""


def build_item_props_df(products_df: pd.DataFrame) -> pd.DataFrame:
    if products_df.empty:
        return pd.DataFrame(columns=["item_id", "name", "category", "subcategory", "brand", "price", "image_url"])

    items = products_df.copy()
    items = items.rename(columns={
        "id": "item_id",
        "nom": "name",
        "categorie": "category",
        "sous_categorie": "subcategory",
        "marque": "brand",
        "prix": "price",
        "image_url": "image_url",
    })
    items = items[["item_id", "name", "category", "subcategory", "brand", "price", "image_url"]]
    items = items.dropna(subset=["item_id", "price"]) 
    if "image_url" not in items.columns:
        items["image_url"] = ""
    else:
        items["image_url"] = items["image_url"].fillna("")

    for idx, row in items.iterrows():
        if not str(row["image_url"]).strip():
            local_path = _resolve_local_image_url(row["name"])
            if local_path:
                items.at[idx, "image_url"] = local_path

    print(f"Item properties: {len(items)} products (sample 5):")
    print(items.head(5).to_dict(orient="records"))
    return items


def save_csvs(events_df: pd.DataFrame, item_props_df: pd.DataFrame):
    events_path = DATA_DIR / "events.csv"
    items_path = DATA_DIR / "item_properties.csv"
    events_df.to_csv(events_path, index=False, encoding="utf-8")
    item_props_df.to_csv(items_path, index=False, encoding="utf-8")
    print(f"Saved {events_path} ({len(events_df)} rows)")
    print(f"Saved {items_path} ({len(item_props_df)} rows)")
    return events_path, items_path


def get_minio_client():
    try:
        client = Minio(
            MINIO_ENDPOINT,
            access_key=MINIO_ACCESS_KEY,
            secret_key=MINIO_SECRET_KEY,
            secure=False,
        )
        print("MinIO client initialized.")
        return client
    except Exception as e:
        print("Failed to initialize MinIO client:", str(e))
        traceback.print_exc()
        return None


def ensure_bucket_exists(client, bucket_name: str):
    try:
        if not client.bucket_exists(bucket_name):
            client.make_bucket(bucket_name)
            print(f"Created bucket: {bucket_name}")
        else:
            print(f"Bucket exists: {bucket_name}")
        return True
    except Exception as e:
        print(f"Failed to ensure bucket {bucket_name}:", str(e))
        traceback.print_exc()
        return False


def upload_file(client, bucket_name: str, path: Path, object_name: str):
    try:
        client.fput_object(bucket_name, object_name, str(path))
        print(f"Uploaded {object_name} to bucket {bucket_name}.")
        return True
    except Exception as e:
        print(f"Failed to upload {object_name}:", str(e))
        traceback.print_exc()
        return False


def main():
    conn = get_connection()
    if conn is None:
        print("Aborting: cannot connect to database.")
        sys.exit(1)

    try:
        orders_df = load_orders_and_users(conn)
        products_df = load_products(conn)

        if orders_df.empty:
            print("Warning: no orders found. Exiting.")
            return
        if products_df.empty:
            print("Warning: no products found. Exiting.")
            return

        events_df = build_events_df(orders_df)
        item_props_df = build_item_props_df(products_df)

        events_path, items_path = save_csvs(events_df, item_props_df)

        client = get_minio_client()
        if client is None:
            print("MinIO client unavailable. Skipping upload.")
            return

        ok = ensure_bucket_exists(client, MINIO_BUCKET)
        if not ok:
            print("Could not ensure bucket exists. Skipping upload.")
            return

        upload_file(client, MINIO_BUCKET, events_path, events_path.name)
        upload_file(client, MINIO_BUCKET, items_path, items_path.name)

        print("Export to MinIO completed successfully.")

    finally:
        try:
            conn.close()
        except Exception:
            pass


if __name__ == "__main__":
    main()

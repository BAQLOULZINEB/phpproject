import csv
from pathlib import Path

import pymysql

from config import MYSQL_HOST, MYSQL_DB, MYSQL_USER, MYSQL_PASSWORD
from minio_client import get_minio_client, ensure_bucket

TMP_DIR = Path('/tmp')
TMP_DIR.mkdir(parents=True, exist_ok=True)

events_path = TMP_DIR / 'events.csv'
item_properties_path = TMP_DIR / 'item_properties.csv'

print('Connecting to MySQL...')
connection = pymysql.connect(
    host=MYSQL_HOST,
    user=MYSQL_USER,
    password=MYSQL_PASSWORD,
    database=MYSQL_DB,
    cursorclass=pymysql.cursors.DictCursor,
)
print('Connected to MySQL.')

with connection:
    with connection.cursor() as cursor:
        print('Querying purchase events...')
        cursor.execute(
            """
            SELECT c.id_user AS user_id,
                   lc.id_produit AS item_id,
                   'purchase' AS event_type,
                   UNIX_TIMESTAMP(c.date_commande) AS timestamp
            FROM ligne_commande lc
            JOIN commande c ON lc.id_commande = c.id_commande
            """
        )
        events = cursor.fetchall()

        print(f'Fetched {len(events)} purchase events.')
        print(f'Writing {events_path}...')
        with open(events_path, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=['user_id', 'item_id', 'event_type', 'timestamp'])
            writer.writeheader()
            writer.writerows(events)
        print(f'Wrote {events_path}.')

        print('Querying item properties...')
        cursor.execute(
            """
            SELECT id AS item_id,
                   categorie AS category,
                   marque AS brand,
                   prix AS price
            FROM products
            """
        )
        items = cursor.fetchall()

        print(f'Fetched {len(items)} item properties.')
        print(f'Writing {item_properties_path}...')
        with open(item_properties_path, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=['item_id', 'category', 'brand', 'price'])
            writer.writeheader()
            writer.writerows(items)
        print(f'Wrote {item_properties_path}.')

print('Initializing MinIO client...')
minio_client = get_minio_client()
print('Ensuring bucket exists...')
bucket_name = ensure_bucket(minio_client)
print(f'Bucket ready: {bucket_name}')

for path in [events_path, item_properties_path]:
    object_name = path.name
    print(f'Uploading {object_name} to bucket {bucket_name}...')
    minio_client.fput_object(bucket_name, object_name, str(path))
    print(f'Uploaded {object_name}.')

print('Export and upload complete.')

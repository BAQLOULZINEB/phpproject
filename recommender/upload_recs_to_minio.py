"""Upload user_recs.parquet to MinIO"""
from pathlib import Path
from minio import Minio
from config import MINIO_ENDPOINT, MINIO_ACCESS_KEY, MINIO_SECRET_KEY, MINIO_BUCKET

BASE_DIR = Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / "data"

# Initialize MinIO client
client = Minio(
    MINIO_ENDPOINT,
    access_key=MINIO_ACCESS_KEY,
    secret_key=MINIO_SECRET_KEY,
    secure=False,
)

# Upload user_recs.parquet
recs_path = DATA_DIR / "user_recs.parquet"
if recs_path.exists():
    print(f"Uploading {recs_path}...")
    file_size = recs_path.stat().st_size
    with open(str(recs_path), 'rb') as f:
        client.put_object(MINIO_BUCKET, "user_recs.parquet", f, file_size)
    print(f"✓ Successfully uploaded user_recs.parquet to MinIO")
else:
    print(f"ERROR: {recs_path} not found")

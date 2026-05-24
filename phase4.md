# Phase 4 – FastAPI Recommendation API Prompt for Copilot

Copy this whole prompt into Copilot (or any AI coding assistant) to generate `recommender/api/main.py`.

---

**PROMPT START**

I am continuing my PFA Big Data project built on top of an existing PHP e-commerce cosmetics store.

## Context

- **Phase 1**: Exported MySQL data into:
  - `events.csv` (user_id, item_id, event_type, timestamp)
  - `item_properties.csv` (item_id, category, brand, price, ...)
  - Both uploaded to MinIO bucket `ecommerce-data`.

- **Phase 2**: `preprocessing.py` with PySpark cleaned interactions and saved:
  - Local Parquet: `recommender/data/interactions.parquet`.

- **Phase 3**: `train_als.py` with Spark MLlib:
  - Trained ALS model on 737 interactions (619 train, 118 test).
  - RMSE ≈ 0.0927, MAE ≈ 0.0927.
  - Generated top‑10 recommendations for 16 users.
  - Saved flat recommendations to:
    - `recommender/data/user_recs.parquet`
    - Columns: `user_id` (int), `item_id` (int), `pred_rating` (float)
    - 160 rows total (16 users × 10 recs each).

- **MySQL database**: `projects` (XAMPP, localhost):

  ```sql
  users(
    ID INT PRIMARY KEY,
    NOM VARCHAR(...),
    PRENOM VARCHAR(...),
    EMAIL VARCHAR(...),
    PASSWORD VARCHAR(...),
    role VARCHAR(...)
  );

  products(
    id INT PRIMARY KEY,
    nom VARCHAR(255),
    description TEXT,
    prix DECIMAL(10,2),
    categorie VARCHAR(100),
    sous_categorie VARCHAR(100),
    marque VARCHAR(100),
    image_url VARCHAR(255)
  );

  commande(
    id_commande INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT,
    date_commande DATETIME,
    statut_commande VARCHAR(50),
    mode_paiement VARCHAR(50)
  );

  ligne_commande(
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_commande INT,
    id_produit INT,
    quantite INT,
    prix_unitaire DECIMAL(10,2)
  );
  ```

- **config.py**:

  ```python
  MYSQL_HOST = "localhost"
  MYSQL_DB = "projects"
  MYSQL_USER = "root"
  MYSQL_PASSWORD = ""

  MINIO_ENDPOINT = "localhost:9000"
  MINIO_ACCESS_KEY = "minioadmin"
  MINIO_SECRET_KEY = "minioadmin"
  MINIO_BUCKET = "ecommerce-data"

  API_PORT = 8000
  ```

- System: Windows, XAMPP, Python venv at  
  `C:\xampp\htdocs\Glow-E.web .1.0.1\venv`.

## Goal – Phase 4: FastAPI Recommendation API

Implement `recommender/api/main.py` as a FastAPI application that:

- Loads `user_recs.parquet` at startup.
- Loads product info from MySQL at startup.
- Exposes:
  - `GET /recommend/{user_id}` → returns top recommended products for a user.
  - `GET /health` → returns `{"status": "ok"}`.
  - `GET /users` → list user_ids with available recommendations.
- Is callable from the PHP frontend (via cURL or JS).

## Task: Create `recommender/api/main.py`

### 1. Imports and config

Use:

```python
import os
import sys
from pathlib import Path
from contextlib import asynccontextmanager
from typing import List

import pandas as pd
import pymysql
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn
```

Import config (ensure parent folder is on sys.path):

```python
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from config import MYSQL_HOST, MYSQL_DB, MYSQL_USER, MYSQL_PASSWORD, API_PORT, MINIO_BUCKET
```

### 2. Pydantic models

```python
class ProductRecommendation(BaseModel):
    id: int
    nom: str
    prix: float
    categorie: str
    sous_categorie: str = ""
    marque: str = ""
    image_url: str = ""

class RecommendationResponse(BaseModel):
    user_id: int
    recommendations: List[ProductRecommendation]
    source: str  # "model" or "fallback"
```

### 3. In-memory stores

At module level:

```python
user_recommendations: dict = {}  # user_id -> list[item_id]
product_catalog: dict = {}       # item_id -> dict with product fields
popular_products: list = []      # fallback: list[item_id]
```

### 4. Data loading functions

#### a) Load `user_recs.parquet`

```python
def load_recommendations():
    """
    Load user_recs.parquet and build user_recommendations dict:
    {user_id: [item_id1, item_id2, ...]} sorted by pred_rating desc.
    """
    recs_path = Path(__file__).resolve().parent.parent / "data" / "user_recs.parquet"
    if not recs_path.exists():
        print(f"WARNING: user_recs.parquet not found at {recs_path}")
        return

    df = pd.read_parquet(str(recs_path), engine="pyarrow")
    df = df.sort_values(["user_id", "pred_rating"], ascending=[True, False])

    user_recommendations.clear()
    for user_id, group in df.groupby("user_id"):
        user_recommendations[int(user_id)] = [int(x) for x in group["item_id"].tolist()]

    print(f"Loaded recommendations for {len(user_recommendations)} users from {recs_path}.")
```

#### b) Load product catalog and popular products from MySQL

```python
def load_product_catalog():
    """
    Load all products into product_catalog and compute popular_products fallback.
    """
    try:
        conn = pymysql.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            charset="utf8mb4"
        )
        with conn:
            with conn.cursor() as cursor:
                # Products
                cursor.execute("""
                    SELECT id, nom, description, prix,
                           categorie, sous_categorie, marque, image_url
                    FROM products
                """)
                rows = cursor.fetchall()
                product_catalog.clear()
                for p in rows:
                    pid = int(p["id"])
                    product_catalog[pid] = {
                        "id": pid,
                        "nom": p["nom"] or "",
                        "prix": float(p["prix"]) if p["prix"] is not None else 0.0,
                        "categorie": p["categorie"] or "",
                        "sous_categorie": p["sous_categorie"] or "",
                        "marque": p["marque"] or "",
                        "image_url": p["image_url"] or "",
                    }
                print(f"Loaded {len(product_catalog)} products into catalog.")

                # Popular products (fallback)
                cursor.execute("""
                    SELECT lc.id_produit, SUM(lc.quantite) AS total_qty
                    FROM ligne_commande lc
                    GROUP BY lc.id_produit
                    ORDER BY total_qty DESC
                    LIMIT 10
                """)
                rows = cursor.fetchall()
                popular_products.clear()
                for row in rows:
                    popular_products.append(int(row["id_produit"]))
                print(f"Loaded {len(popular_products)} popular products for fallback.")

    except Exception as e:
        print(f"ERROR loading product catalog from MySQL: {e}")
```

### 5. Lifespan (startup/shutdown)

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Loading recommendation data and product catalog...")
    load_recommendations()
    load_product_catalog()
    print("Startup complete. API is ready.")
    yield
    print("Shutting down API.")
```

### 6. Create FastAPI app and add CORS

```python
app = FastAPI(
    title="Glow-E Recommendation API",
    description="Personalized cosmetics recommendations powered by ALS (PySpark MLlib)",
    version="1.0.0",
    lifespan=lifespan
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
```

### 7. Endpoints

#### a) Health check

```python
@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "users_with_recs": len(user_recommendations),
        "products_in_catalog": len(product_catalog)
    }
```

#### b) List users with recs (for debugging)

```python
@app.get("/users")
def list_users_with_recs():
    return {
        "users_with_recommendations": sorted(list(user_recommendations.keys())),
        "count": len(user_recommendations)
    }
```

#### c) Main recommendation endpoint

```python
@app.get("/recommend/{user_id}", response_model=RecommendationResponse)
def get_recommendations(user_id: int, top_n: int = 10):
    """
    Return top-N product recommendations for a given user_id.
    If the user has no personalized recs, return fallback popular products.
    """
    source = "model"

    if user_id in user_recommendations:
        item_ids = user_recommendations[user_id][:top_n]
    else:
        item_ids = popular_products[:top_n]
        source = "fallback"

    recommendations = []
    for item_id in item_ids:
        if item_id in product_catalog:
            p = product_catalog[item_id]
            recommendations.append(ProductRecommendation(
                id=p["id"],
                nom=p["nom"],
                prix=p["prix"],
                categorie=p["categorie"],
                sous_categorie=p["sous_categorie"],
                marque=p["marque"],
                image_url=p["image_url"]
            ))

    if not recommendations:
        raise HTTPException(
            status_code=404,
            detail=f"No recommendations found for user_id={user_id}"
        )

    return RecommendationResponse(
        user_id=user_id,
        recommendations=recommendations,
        source=source
    )
```

### 8. Run the server

```python
if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=API_PORT,
        reload=False
    )
```

### 9. Testing instructions (as comments)

At the bottom of `main.py`, add:

```python
# === TESTING ===
# 1. Start the API:
#    cd C:\xampp\htdocs\Glow-E.web .1.0.1
#    .\venv\Scripts\activate
#    python recommender/api/main.py
# 2. Health check:
#    http://localhost:8000/health
# 3. List users with recommendations:
#    http://localhost:8000/users
# 4. Recommendations for a known user (e.g. user_id=2):
#    http://localhost:8000/recommend/2
# 5. Fallback recommendations for a non-existent user:
#    http://localhost:8000/recommend/9999
# 6. Interactive docs:
#    http://localhost:8000/docs
```

Please generate the complete `recommender/api/main.py` file based on all these instructions.

**PROMPT END**
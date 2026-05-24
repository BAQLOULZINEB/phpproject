import os
import sys
from pathlib import Path
from contextlib import asynccontextmanager
from typing import List
import io

import pandas as pd
import pymysql
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn
from minio import Minio

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from config import (
    MYSQL_HOST, MYSQL_DB, MYSQL_USER, MYSQL_PASSWORD, API_PORT, MINIO_BUCKET,
    MINIO_ENDPOINT, MINIO_ACCESS_KEY, MINIO_SECRET_KEY
)


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


user_recommendations: dict = {}
product_catalog: dict = {}
popular_products: list = []
# In-memory user interaction history and item metadata for light re-ranking
user_interactions: dict = {}
item_categories: dict = {}


def debug_print_products_schema():
    """Print actual column names and types from products table."""
    try:
        conn = pymysql.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            charset="utf8mb4",
        )
        with conn:
            with conn.cursor() as cursor:
                cursor.execute("SHOW COLUMNS FROM products")
                rows = cursor.fetchall()
                print("\n" + "="*60)
                print(f"Actual schema for 'products' table in database '{MYSQL_DB}':")
                print("="*60)
                for row in rows:
                    col_name = row["Field"]
                    col_type = row["Type"]
                    print(f"  {col_name:30} {col_type}")
                print("="*60 + "\n")
    except Exception as e:
        print(f"ERROR inspecting products schema: {e}")


def load_recommendations():
    """Load recommendations from MinIO, fallback to local file if not available."""
    try:
        # Try to load from MinIO first
        client = Minio(
            MINIO_ENDPOINT,
            access_key=MINIO_ACCESS_KEY,
            secret_key=MINIO_SECRET_KEY,
            secure=False,
        )
        
        print(f"  Attempting to load user_recs.parquet from MinIO: {MINIO_ENDPOINT}/{MINIO_BUCKET}")
        
        try:
            response = client.get_object(MINIO_BUCKET, "user_recs.parquet")
            parquet_data = io.BytesIO(response.read())
            df = pd.read_parquet(parquet_data, engine="pyarrow")
            print(f"  ✓ Successfully loaded user_recs.parquet from MinIO")
            print(f"  DataFrame shape: {df.shape} rows (columns: {list(df.columns)})")
            print(f"  Distinct user_ids: {df['user_id'].nunique()}")
        except Exception as e:
            print(f"  ✗ Could not load from MinIO: {e}")
            print(f"  Falling back to local file...")
            raise
    except Exception as minio_error:
        # Fallback to local file
        try:
            recs_path = Path(__file__).resolve().parent.parent / "data" / "user_recs.parquet"
            if not recs_path.exists():
                print(f"  WARNING: user_recs.parquet not found at {recs_path}")
                print("  user_recommendations will be empty.")
                return

            df = pd.read_parquet(str(recs_path), engine="pyarrow")
            print(f"  ✓ Loaded user_recs.parquet from local file: {recs_path}")
            print(f"  DataFrame shape: {df.shape} rows (columns: {list(df.columns)})")
            print(f"  Distinct user_ids: {df['user_id'].nunique()}")
        except Exception as local_error:
            print(f"  ERROR: Could not load recommendations from MinIO or local file: {local_error}")
            return
    
    # Keep pred_rating information to enable re-ranking
    df = df.sort_values(["user_id", "pred_rating"], ascending=[True, False])

    user_recommendations.clear()
    for user_id, group in df.groupby("user_id"):
        user_recommendations[int(user_id)] = [
            {"item_id": int(row.item_id), "pred_rating": float(row.pred_rating)}
            for row in group.itertuples()
        ]

    all_user_ids = sorted(list(user_recommendations.keys()))
    print(f"  Loaded {len(user_recommendations)} users with recommendations.")
    print(f"  User IDs: {all_user_ids[:10]}" + (" ..." if len(all_user_ids) > 10 else ""))
    print(f"  Data types: user_id={type(all_user_ids[0]) if all_user_ids else 'N/A'}")


def load_product_catalog():
    """Load product catalog from MySQL, auto-detecting column names."""
    try:
        conn = pymysql.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DB,
            cursorclass=pymysql.cursors.DictCursor,
            charset="utf8mb4",
        )
        with conn:
            with conn.cursor() as cursor:
                # First, detect actual column names
                cursor.execute("SHOW COLUMNS FROM products")
                schema_rows = cursor.fetchall()
                schema_cols = {row["Field"].lower(): row["Field"] for row in schema_rows}
                
                print(f"Detected {len(schema_cols)} columns in products table.")
                print(f"Column names (case-insensitive mapping): {list(schema_cols.keys())}")
                
                # Map expected Python field names to actual MySQL column names
                col_map = {
                    "id": schema_cols.get("id"),
                    "nom": schema_cols.get("nom") or schema_cols.get("nom_produit") or schema_cols.get("name"),
                    "description": schema_cols.get("description") or schema_cols.get("desc"),
                    "prix": schema_cols.get("prix") or schema_cols.get("price") or schema_cols.get("cost"),
                    "categorie": schema_cols.get("categorie") or schema_cols.get("category"),
                    "sous_categorie": schema_cols.get("sous_categorie") or schema_cols.get("subcategory"),
                    "marque": schema_cols.get("marque") or schema_cols.get("brand"),
                    "image_url": schema_cols.get("image_url") or schema_cols.get("img_url") or schema_cols.get("image_path"),
                }
                
                print(f"Column mapping: {col_map}")
                
                # Verify all critical columns exist
                missing = [k for k, v in col_map.items() if v is None]
                if missing:
                    raise ValueError(f"Missing critical columns: {missing}")
                
                # Build SELECT query with actual column names
                select_parts = [f"{col_map['id']} AS id"]
                select_parts.append(f"{col_map['nom']} AS nom")
                select_parts.append(f"{col_map['description']} AS description")
                select_parts.append(f"{col_map['prix']} AS prix")
                select_parts.append(f"{col_map['categorie']} AS categorie")
                select_parts.append(f"{col_map['sous_categorie']} AS sous_categorie")
                select_parts.append(f"{col_map['marque']} AS marque")
                select_parts.append(f"{col_map['image_url']} AS image_url")
                
                query = f"SELECT {', '.join(select_parts)} FROM products"
                print(f"\nExecuting query: {query}\n")
                
                cursor.execute(query)
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
                all_product_ids = sorted(product_catalog.keys())
                print(f"Product catalog size: {len(product_catalog)}")
                print(f"Sample product IDs: {all_product_ids[:10]}" + (" ..." if len(all_product_ids) > 10 else ""))
                print(f"Data types: product_id={type(all_product_ids[0]) if all_product_ids else 'N/A'}")

                cursor.execute(
                    """
                    SELECT lc.id_produit, SUM(lc.quantite) AS total_qty
                    FROM ligne_commande lc
                    GROUP BY lc.id_produit
                    ORDER BY total_qty DESC
                    LIMIT 10
                    """
                )
                rows = cursor.fetchall()
                popular_products.clear()
                for row in rows:
                    popular_products.append(int(row["id_produit"]))
                print(f"Popular products fallback list: {popular_products}")
    except Exception as e:
        print(f"ERROR loading product catalog from MySQL: {e}")
        import traceback
        traceback.print_exc()
        product_catalog.clear()
        popular_products.clear()
        print("Product catalog and popular products cleared due to error.")


def _get_recommendations_internal(user_id: int, top_n: int = 10):
    """
    Internal helper to get recommendations for a user.
    Returns (item_ids, source) tuple.
    Applies a light re-ranking that boosts items matching the user's most frequent category
    and penalizes items the user already interacted with. This is a demo-focused personalization
    step that improves apparent per-user diversity without re-training the model.
    """
    model_entries = user_recommendations.get(user_id, [])

    # Build candidate list with base scores
    candidates = []
    for e in model_entries:
        if isinstance(e, dict):
            pid = int(e.get('item_id'))
            base = float(e.get('pred_rating', 0.0))
        else:
            pid = int(e)
            base = 0.0
        candidates.append({'item_id': pid, 'base_score': base})

    # User's past interactions (set)
    interacted = set(user_interactions.get(user_id, []))

    # Determine user's top category from past interactions
    from collections import Counter
    cats = [item_categories.get(i) for i in interacted if i in item_categories]
    cats = [c for c in cats if c]
    user_top_cat = Counter(cats).most_common(1)[0][0] if cats else None

    # Adjust scores: boost matching category, penalize already-interacted items
    adjusted = []
    for c in candidates:
        score = c['base_score']
        if user_top_cat is not None:
            item_cat = item_categories.get(c['item_id'])
            if item_cat == user_top_cat:
                score += 0.2  # modest boost for category match
        if c['item_id'] in interacted:
            score -= 0.1  # penalize items the user already saw/bought
        adjusted.append((c['item_id'], score))

    # If no model candidates, seed with popular products
    if not adjusted:
        adjusted = [(pid, 0.0) for pid in popular_products]

    # Sort by adjusted score desc, then by item_id asc for determinism
    adjusted_sorted = sorted(adjusted, key=lambda x: (-x[1], x[0]))

    result_ids = []
    for pid, _ in adjusted_sorted:
        if pid not in result_ids:
            result_ids.append(pid)
        if len(result_ids) >= top_n:
            break

    # If still not enough, append remaining catalog items
    if len(result_ids) < top_n:
        for pid in product_catalog.keys():
            if pid not in result_ids:
                result_ids.append(pid)
            if len(result_ids) >= top_n:
                break

    source = "model" if model_entries else "fallback"
    return result_ids[:top_n], source


@asynccontextmanager
async def lifespan(app: FastAPI):
    print("\n" + "="*60)
    print("Loading recommendation data and product catalog...")
    print("="*60)
    
    # First, inspect the actual schema
    debug_print_products_schema()
    
    load_recommendations()
    load_product_catalog()

    # Load local item properties and user interactions to support light re-ranking
    # Try MinIO first, then fallback to local files
    client = None
    try:
        client = Minio(
            MINIO_ENDPOINT,
            access_key=MINIO_ACCESS_KEY,
            secret_key=MINIO_SECRET_KEY,
            secure=False,
        )
    except Exception as e:
        print(f"Could not initialize MinIO client for re-ranking data: {e}")
    
    try:
        # Try to load item_properties from MinIO
        if client:
            try:
                response = client.get_object(MINIO_BUCKET, "item_properties.csv")
                csv_data = io.BytesIO(response.read())
                ip = pd.read_csv(csv_data)
                print(f"  ✓ Loaded item_properties.csv from MinIO ({len(ip)} items)")
            except Exception as e:
                print(f"  Could not load item_properties from MinIO: {e}, trying local...")
                items_path = Path(__file__).resolve().parent.parent / "data" / "item_properties.csv"
                if items_path.exists():
                    ip = pd.read_csv(str(items_path))
                    print(f"  ✓ Loaded item_properties.csv from local file ({len(ip)} items)")
                else:
                    ip = None
        else:
            items_path = Path(__file__).resolve().parent.parent / "data" / "item_properties.csv"
            if items_path.exists():
                ip = pd.read_csv(str(items_path))
                print(f"  ✓ Loaded item_properties.csv from local file ({len(ip)} items)")
            else:
                ip = None
        
        if ip is not None:
            item_categories.clear()
            for _, row in ip.iterrows():
                try:
                    item_categories[int(row['item_id'])] = row.get('category', '')
                except Exception:
                    continue
            print(f"  Loaded {len(item_categories)} item categories")
    except Exception as e:
        print(f"Could not load item properties: {e}")

    try:
        # Try to load events from MinIO
        if client:
            try:
                response = client.get_object(MINIO_BUCKET, "events.csv")
                csv_data = io.BytesIO(response.read())
                ev = pd.read_csv(csv_data)
                print(f"  ✓ Loaded events.csv from MinIO ({len(ev)} events)")
            except Exception as e:
                print(f"  Could not load events from MinIO: {e}, trying local...")
                events_path = Path(__file__).resolve().parent.parent / "data" / "events.csv"
                if events_path.exists():
                    ev = pd.read_csv(str(events_path))
                    print(f"  ✓ Loaded events.csv from local file ({len(ev)} events)")
                else:
                    ev = None
        else:
            events_path = Path(__file__).resolve().parent.parent / "data" / "events.csv"
            if events_path.exists():
                ev = pd.read_csv(str(events_path))
                print(f"  ✓ Loaded events.csv from local file ({len(ev)} events)")
            else:
                ev = None
        
        if ev is not None:
            user_interactions.clear()
            if 'user_id' in ev.columns and 'item_id' in ev.columns:
                for _, r in ev.iterrows():
                    try:
                        uid = int(r['user_id'])
                        iid = int(r['item_id'])
                    except Exception:
                        continue
                    user_interactions.setdefault(uid, set()).add(iid)
            # convert sets to lists for JSON serializability
            for k in list(user_interactions.keys()):
                user_interactions[k] = list(user_interactions[k])
            print(f"  Loaded interactions for {len(user_interactions)} users")
    except Exception as e:
        print(f"Could not load events: {e}")

    # Self-test: verify both model and fallback paths work
    print("\n" + "="*60)
    print("Self-test: validating recommendation logic...")
    print("="*60)
    
    if user_recommendations:
        test_user = sorted(user_recommendations.keys())[0]
        item_ids, source = _get_recommendations_internal(test_user, top_n=5)
        print(f"  Test 1 (existing user {test_user}): source={source}, got {len(item_ids)} items")
    else:
        print("  Test 1 (existing user): SKIPPED (no users with recommendations)")
    
    if popular_products:
        nonexistent_user = 999999
        item_ids, source = _get_recommendations_internal(nonexistent_user, top_n=5)
        print(f"  Test 2 (fallback user {nonexistent_user}): source={source}, got {len(item_ids)} items")
    else:
        print("  Test 2 (fallback user): SKIPPED (no popular products)")
    
    print("\n" + "="*60)
    print("Startup complete. API is ready.")
    print("="*60 + "\n")
    yield
    print("Shutting down API.")


app = FastAPI(
    title="Glow-E Recommendation API",
    description="Personalized cosmetics recommendations powered by ALS (PySpark MLlib)",
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "users_with_recs": len(user_recommendations),
        "products_in_catalog": len(product_catalog),
    }


@app.get("/users")
def list_users_with_recs():
    return {
        "users_with_recommendations": sorted(list(user_recommendations.keys())),
        "count": len(user_recommendations),
    }


@app.get("/debug/state")
def debug_state():
    return {
        "users_with_recs": sorted(list(user_recommendations.keys())),
        "num_users_with_recs": len(user_recommendations),
        "num_products": len(product_catalog),
        "popular_products": popular_products,
    }


@app.get("/debug/recommend/{user_id}")
def debug_recommend(user_id: int, top_n: int = 10):
    """
    Debug endpoint: returns detailed recommendation metadata including provenance,
    model scores, category matches, and interaction history.
    """
    print(f"\n[/debug/recommend] user_id={user_id}, top_n={top_n}")
    
    model_entries = user_recommendations.get(user_id, [])
    interacted = set(user_interactions.get(user_id, []))
    
    # Determine user's top category
    from collections import Counter
    cats = [item_categories.get(i) for i in interacted if i in item_categories]
    cats = [c for c in cats if c]
    user_top_cat = Counter(cats).most_common(1)[0][0] if cats else None
    
    # Build detailed scores
    detail = []
    for e in model_entries[:top_n * 2]:  # Show more for debug
        if isinstance(e, dict):
            pid = int(e.get('item_id'))
            base = float(e.get('pred_rating', 0.0))
        else:
            pid = int(e)
            base = 0.0
        
        p = product_catalog.get(pid, {})
        cat = item_categories.get(pid, '')
        already_interacted = pid in interacted
        cat_match = (cat == user_top_cat) if user_top_cat else False
        
        detail.append({
            'item_id': pid,
            'name': p.get('nom', ''),
            'base_score': base,
            'category': cat,
            'category_match': cat_match,
            'already_interacted': already_interacted,
            'user_top_category': user_top_cat,
            'price': p.get('prix', 0.0),
        })
    
    return {
        'user_id': user_id,
        'past_interactions_count': len(interacted),
        'user_top_category': user_top_cat,
        'model_has_recs': user_id in user_recommendations,
        'detailed_scores': detail,
    }


@app.get("/recommend/{user_id}", response_model=RecommendationResponse)
def get_recommendations(user_id: int, top_n: int = 10):
    print(f"\n[/recommend] Request: user_id={user_id}, top_n={top_n}")
    
    item_ids, source = _get_recommendations_internal(user_id, top_n)
    print(f"[/recommend] Source: {source}, item_ids: {item_ids}")

    recommendations = []
    for item_id in item_ids:
        p = product_catalog.get(item_id)
        if not p:
            print(f"[/recommend] WARNING: item_id={item_id} not found in product_catalog")
            continue
        recommendations.append(ProductRecommendation(
            id=p["id"],
            nom=p["nom"],
            prix=p["prix"],
            categorie=p["categorie"],
            sous_categorie=p["sous_categorie"],
            marque=p["marque"],
            image_url=p["image_url"],
        ))
    
    print(f"[/recommend] Built {len(recommendations)} ProductRecommendation objects")

    if not recommendations:
        print(f"[/recommend] No recommendations after building list.")
        if len(product_catalog) == 0 and len(popular_products) == 0:
            print(f"[/recommend] Both product_catalog and popular_products are empty. Returning 404.")
            raise HTTPException(
                status_code=404,
                detail=f"No recommendations available for user_id={user_id}",
            )
        else:
            print(f"[/recommend] Product catalog or popular products available, returning empty list.")

    return RecommendationResponse(
        user_id=user_id,
        recommendations=recommendations,
        source=source,
    )


if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=API_PORT,
        reload=False,
    )

 .\minio.exe server C:\minio-data --license C:\minio-data\minio.license --console-address ":9001"

 http://127.0.0.1:9001/object-store/details



to go with minio open it 

(venv) PS C:\xampp\htdocs\Glow-E.web .1.0.1> cd C:\minio-data                                                           
(venv) PS C:\minio-data> .\minio.exe --version        
minio.exe version RELEASE.2026-05-04T23-02-27Z (commit-id=f1b82d385e5e1d055903eb12956046c61cd38aab)
Runtime: go1.26.2 windows/amd64
License: MinIO AIStor License
Copyright: 2015-2026 MinIO, Inc.
(venv) PS C:\minio-data> 
 to activate it 

 .\minio.exe server C:\minio-data --license C:\minio-data\minio.license --console-address ":9001"

 minioadmin     login in minio


 to run export for phase 1   
 python recommender/export_to_minio.py


 training pahse 3 



 .\venv\Scripts\activate  


(venv) PS C:\xampp\htdocs\Glow-E.web .1.0.1> python recommender/train_als.py

PAHSE4 fastAPI

.\venv\Scripts\activate
(venv) PS C:\xampp\htdocs\Glow-E.web .1.0.1> python recommender\api\main.py

AND GO TO 
http://localhost:8000/health
http://localhost:8000/users




============================================================
Loading recommendation data and product catalog...
============================================================

============================================================
Actual schema for 'products' table in database 'projects':
============================================================
  id                             int(11)
  name                           varchar(255)
  description                    text
  price                          decimal(10,2)
  category                       varchar(50)
  subcategory                    varchar(50)
  brand                          varchar(100)
  image_url                      varchar(255)
============================================================

  DataFrame shape: (160, 3) rows (columns: ['user_id', 'item_id', 'pred_rating'])
  Distinct user_ids in DataFrame: 16
Loaded 16 users with recommendations.
  User IDs: [2, 3, 11, 12, 13, 14, 15, 16, 17, 18] ...
  Data types: user_id=<class 'int'>
Detected 8 columns in products table.
Column names (case-insensitive mapping): ['id', 'name', 'description', 'price', 'category', 'subcategory', 'brand', 'image_url']
Column mapping: {'id': 'id', 'nom': 'name', 'description': 'description', 'prix': 'price', 'categorie': 'category', 'sous_categorie': 'subcategory', 'marque': 'brand', 'image_url': 'image_url'}

Executing query: SELECT id AS id, name AS nom, description AS description, price AS prix, category AS categorie, subcategory AS sous_categorie, brand AS marque, image_url AS image_url FROM products

Product catalog size: 54
Sample product IDs: [3, 4, 5, 6, 7, 8, 9, 10, 11, 12] ...
Data types: product_id=<class 'int'>
Popular products fallback list: [14, 76, 17, 15, 65, 50, 18, 48, 49, 6]

============================================================
Self-test: validating recommendation logic...
============================================================
  Test 1 (existing user 2): source=model, got 5 items
  Test 2 (fallback user 999999): source=fallback, got 5 items

============================================================
Startup complete. API is ready.
============================================================

INFO:     Application startup complete.
INFO:     Uvicorn running on http://0.0.0.0:8000 (Press CTRL+C to quit)

[/recommend] Request: user_id=2, top_n=10
[/recommend] Source: model, item_ids: [76, 75, 74, 73, 70, 69, 68, 67, 66, 65]
[/recommend] Built 10 ProductRecommendation objects
INFO:     127.0.0.1:10215 - "GET /recommend/2 HTTP/1.1" 200 OK




===================================================================================================================================================================================================================================================================================================================================================================

How you can test this now (commands)
# in your venv
python recommender/api/main.py

Restart the FastAPI server so the API code changes take effect:
Run the Streamlit dashboard:
# in streamlit
streamlit run recommender/dashboard_app.py

.\venv\Scripts\activate 
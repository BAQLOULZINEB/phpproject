# Current Architecture

## Overview
Glow-E is a split architecture with a PHP storefront, a MySQL database, and a separate Python recommendation service. The recommendation UI is embedded in the PHP homepage, while model training and diagnostics are handled in the `recommender/` directory.

## PHP storefront
- `index.php`: main homepage and product display page
- `filtrage.php`: product filtering and search page
- `product.php`: product detail rendering for quick view / AJAX display
- `ajouter_panier.php`: add/update/delete cart items via POST
- `effectuer_commande.php`: checkout and order placement
- `confirmation.php`: order confirmation page
- `historique_commandes.php`: user order history
- `connexion.php` and `inscription.php`: login and registration
- `interface_admin.php`: admin dashboard for product and client management
- `connexionbd.php`: shared MySQL connection helper

## MySQL database
- Database name: `projects`
- Key tables used by the storefront:
  - `users` for authentication and roles
  - `products` for the product catalog
  - `commande` and `ligne_commande` for orders
- Recommendation API also reads `products` from MySQL to enrich item metadata

## Recommendation API
- `recommender/api/main.py` is the FastAPI backend
- It exposes:
  - `GET /health`
  - `GET /users`
  - `GET /debug/state`
  - `GET /debug/recommend/{user_id}`
  - `GET /recommend/{user_id}`
- At startup, the API loads:
  - `recommender/data/user_recs.parquet`
  - `products` metadata from MySQL
  - optional local item/category metadata from `recommender/data/item_properties.csv`
  - optional local interaction history from `recommender/data/events.csv`

## Homepage recommendation widget
- `recommender_widget.php` renders the personalized recommendation section
- It sends an HTTP GET to the FastAPI endpoint with the logged-in user ID
- The response includes product fields such as `id`, `nom`, `prix`, `categorie`, `sous_categorie`, `marque`, and `image_url`
- The widget then renders product cards matching the existing trending product style and includes add-to-cart forms
- If the user is not logged in, the widget returns nothing and the page continues normally

## Recommendation pipeline pieces
- `recommender/train_implicit_als.py`: current implicit ALS training implementation using NumPy/SciPy
- `recommender/preprocessing.py`: existing data preprocessing script for Spark workflows
- `recommender/generate_user_recs_simple.py`: fallback recommendation generator
- `recommender/config.py`: database and service configuration
- `recommender/requirements.txt`: Python dependencies for the recommender system

## Dashboard / visualization
- `recommender/dashboard_app.py` is a Streamlit app
- It is separate from the PHP storefront and is used for data/model diagnostics
- It probes the FastAPI `/health` endpoint and displays dataset and model statistics

## How the pieces connect
1. User opens `index.php` in the browser
2. `index.php` includes `recommender_widget.php`
3. If `$_SESSION['user_id']` exists, `recommender_widget.php` calls FastAPI at `http://localhost:8000/recommend/{user_id}`
4. FastAPI uses `user_recs.parquet` and MySQL `products` to build recommendations
5. The widget displays the returned recommended products on the homepage
6. `Add to Cart` on recommended cards posts to `ajouter_panier.php` like the rest of the storefront

## Important implementation detail
- The repo currently contains MinIO connection configuration, but the recommendation API code does not actually retrieve recommendation data from MinIO. It operates on local files and MySQL instead.

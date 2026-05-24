# Glow-E Project Overview

## What this project is
Glow-E is a PHP + Bootstrap e-commerce storefront for beauty and cosmetics products. It combines a traditional shopping experience with a personalized recommendation layer powered by a Python FastAPI backend and local recommendation data.

## Main technologies used
- PHP 8.x with session-based authentication and cart logic
- Bootstrap 5 and custom CSS for layout and styling
- JavaScript / jQuery for frontend interactions, AJAX, and modals
- MySQL (`projects` database) for products, users, orders, and cart state
- Python 3.x for recommendation pipeline and API
- FastAPI for the recommendation REST service
- PySpark / Pandas for recommendation data preparation
- Streamlit for a separate recommender diagnostics dashboard
- MinIO configuration exists, but the current API code does not actively load recommendations from MinIO

## Main user-facing features
- Browse product listings on the homepage and filter page
- Search and filter products by category, brand, price, and keywords
- Quick-view product modal or detail page for product information
- Add items to cart via `ajouter_panier.php`
- Session-backed cart with quantity updates and removal
- Checkout flow through `effectuer_commande.php`
- Order confirmation and user order history pages
- Login and registration forms for customer access
- Recommended products section on the homepage for logged-in users

## Current admin features
- Admin dashboard in `interface_admin.php`
- Admin user access controlled by `$_SESSION['role'] === 'admin'`
- Product CRUD: add, edit, delete products
- Client management: view, edit, delete non-admin users
- Admin interface uses the same Bootstrap/template styling as the storefront

## Current recommendation integration state
- Homepage includes `recommender_widget.php` after the trending products section
- The widget only renders for logged-in users
- It calls the FastAPI backend at `http://localhost:8000/recommend/{user_id}` via PHP cURL
- FastAPI loads recommendation data from `recommender/data/user_recs.parquet`
- FastAPI also loads product metadata from MySQL `products` table
- The recommender widget maps returned products into homepage-style cards and preserves add-to-cart behavior
- A separate Streamlit dashboard exists in `recommender/dashboard_app.py` for model and data diagnostics
- `recommender/config.py` contains MinIO settings, but the current `recommender/api/main.py` implementation does not use MinIO for serving recommendations

## Important notes
- The recommendation layer is currently integrated at the homepage level only.
- If the API call fails, the widget fails silently and does not break the page.
- The actual product images are resolved from `images/prod_images/` by filename matching.
- The current codebase does not require or use Redis for storefront functionality.

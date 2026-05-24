# Change Status

## What is already implemented
- Traditional PHP storefront with product browsing, cart, checkout, login, and order history
- Admin interface for product and user management in `interface_admin.php`
- Homepage recommendation widget in `recommender_widget.php`
- FastAPI recommendation service in `recommender/api/main.py`
- Recommendation data loading from `recommender/data/user_recs.parquet`
- Product metadata enrichment from MySQL `products` table
- Separate Streamlit dashboard in `recommender/dashboard_app.py`
- Python recommender training script `recommender/train_implicit_als.py`
- Local recommender configuration in `recommender/config.py`

## What is partially implemented
- MinIO configuration exists, but the current FastAPI API does not actively fetch data from MinIO
- Homepage recommendations are present but only visible when a user is logged in
- PHP/product image lookup is fragile because it relies on exact filename matching in `images/prod_images/`
- Documentation exists in multiple overlapping files and is not synchronized with the current code state
- The recommendation widget styling is implemented but can be improved to match trending products more consistently

## What is planned next
- Consolidate the documentation package around the current code state
- Provide Kimi with a clean UI handoff package that focuses on the homepage, recommendation widget, search/filter results, and product cards
- Preserve backend PHP and Bootstrap logic while modernizing visuals
- Clarify the current recommendation architecture in documentation and call out the local parquet-based data path
- Capture screenshots of actual homepage and recommendation states for final handoff

## Documentation update status
- This new documentation package reflects the current recommendation integration and homepage state
- It acknowledges the existing FastAPI integration, the local recommendation data dependency, and the Streamlit diagnostics app
- It marks legacy planning documents and duplicate summaries as non-core for the handoff

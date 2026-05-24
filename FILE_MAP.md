# File Map

## Key files for Kimi to review
- `index.php`
  - Homepage and main storefront layout
  - Includes the recommendation widget after trending products
  - Contains product grids, cart offcanvas, and homepage sections

- `recommender_widget.php`
  - Homepage recommendation section
  - Calls FastAPI `/recommend/{user_id}` and renders recommended product cards
  - Should be the primary focus for recommendation UI alignment

- `style.css`
  - Main custom CSS theme for the storefront
  - Defines card appearance, modal overlay, badge styles, and spacing
  - Important for visual consistency and redesign

- `filtrage.php`
  - Product filter and search page
  - Primary user-facing category/filter experience
  - Contains card layouts used elsewhere

- `product.php`
  - Product detail/quick-view HTML template
  - Used by AJAX product preview and product links

- `js/script.js`
  - Frontend interaction logic for modals, sliders, and cart actions
  - Contains product preview and quantity control behavior

- `ajouter_panier.php`
  - Cart add/update/remove endpoint
  - Keeps backend cart logic and should remain intact during UI changes

- `effectuer_commande.php`
  - Checkout page and order creation logic
  - Must keep backend form fields and post actions unchanged

- `connexion.php` and `inscription.php`
  - Login and registration pages
  - UI improvements may be applied, but backend form flow should remain

- `interface_admin.php`
  - Admin product/client CRUD dashboard
  - Only change for visual consistency if admin UI is required

## Recommendation / backend files for context
- `recommender/api/main.py`
  - FastAPI recommendation service implementation
  - Not a primary UI file; do not change for front-end redesign

- `recommender/train_implicit_als.py`
  - Current recommendation training script
  - Not part of the UI handoff

- `recommender/dashboard_app.py`
  - Streamlit dashboard for recommender analytics
  - Separate from the storefront and not required for visual redesign

- `recommender/config.py`
  - Backend service configuration
  - Do not modify for UI work

- `recommender/requirements.txt`
  - Python dependency list for recommender backend
  - Useful for environment context only

## Files to avoid changing for UI-only handoff
- `connexionbd.php`
  - Shared database connection helper
  - Backend infrastructure; avoid edits unless necessary

- `recommender/api/main.py`
  - Recommendation API service; backend logic only

- `recommender/preprocessing.py`
  - Data preparation script

- `recommender/debug_db.py`
  - Debugging utility

- `recommender/minio_client.py`
  - MinIO integration helper

- `recommender/train_als.py`
  - Spark training script

- `project-full-source.md`, `rapport.md`, `presentation.md`, `phase4.md`, `commandes.md`
  - Legacy documentation/notes and design artifacts; not part of the live UI code

## Notes on file relationships
- `index.php` renders the homepage and inserts `recommender_widget.php`
- `recommender_widget.php` uses `ajouter_panier.php` for add-to-cart actions
- `product.php` is used to show product details for quick view interactions
- `filtrage.php` and `index.php` share similar product card styling and should be visually aligned
- `style.css`, `css/vendor.css`, and `css/normalize.css` define the frontend appearance; `style.css` is the key file for CSS updates

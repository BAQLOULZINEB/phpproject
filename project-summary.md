# Glow-E.web Project Summary

## Overview
This project is a PHP-based e-commerce web application built on a Bootstrap/HTML template from TemplatesJungle. It provides:
- user registration and login
- product browsing/filtering
- shopping cart management
- order placement and confirmation
- order history for logged-in users
- an admin interface for products and clients

The project uses PHP sessions for cart state and user authentication. It connects to a local MySQL/MariaDB database named `projects`.

## Directory Structure

- `ajouter_panier.php` - shopping cart add/update/delete logic
- `confirmation.php` - order confirmation page and status update
- `connexion.php` - login form and authentication
- `connexionbd.php` - database connection helper
- `effectuer_commande.php` - order processing and checkout logic
- `filtrage.php` - product search/filter page and product listing
- `historique_commandes.php` - user order history display
- `index.php` - home page with cart offcanvas, search, content sections
- `inscription.php` - user registration page
- `interface_admin.php` - admin dashboard for product and client management
- `projects.sql` - SQL dump defining database tables and sample data
- `readme.txt` - original template license and usage info
- `style.css` - custom theme CSS
- `css/normalize.css` - CSS reset/normalization
- `css/vendor.css` - vendor stylesheet bundle
- `js/jquery-1.11.0.min.js` - jQuery library
- `js/modernizr.js` - Modernizr feature detection
- `js/plugins.js` - plugin initializations likely supporting template components
- `js/script.js` - custom frontend JavaScript for preloader, sliders, and quantity controls
- `images/` - image assets, including product thumbnails and template graphics
- `images/prod_images/` - product image files used by the product catalog

## File Summaries

### `ajouter_panier.php`
- Starts the PHP session.
- Ensures `$_SESSION['panier']` exists.
- Handles three POST actions:
  - `update`: change quantity for an item in the cart.
  - `delete`: remove an item from the cart.
  - `add`: add a product to the cart or increase quantity if already present.
- Uses `product_id`, `product_name`, `product_price`, and `quantity` from POST.
- Redirects back to `index.php` after every action.

### `effectuer_commande.php`
- Requires `connexionbd.php` and session start.
- Verifies the user is logged in and cart is not empty.
- On POST, creates a new row in table `commande` with current user ID and timestamp.
- Inserts each cart item into `ligne_commande` with quantity and unit price.
- Clears the cart session after order placement.
- Stores `id_commande` and `mode_paiement` in the session.
- Redirects to `confirmation.php`.
- Includes a Bootstrap checkout page with payment mode selection.

### `confirmation.php`
- Starts session and requires `connexionbd.php`.
- Requires `mode_paiement` and `id_commande` in session.
- Sets order status based on payment choice:
  - `Carte bancaire` → `Payee`
  - `Paiement à la livraison` → `En attente`
- Updates `commande.statut_commande` and displays a success or error message.
- Includes a simple Bootstrap confirmation page.

### `connexion.php`
- Starts session and requires `connexionbd.php`.
- Handles login POST via `success` button.
- Reads `email` and `password` from POST and queries `users` table.
- On success, stores `user_id`, `user_email`, `user_name`, and `role` in session.
- Redirects admin users to `interface_admin.php`; others to `index.php`.
- Contains a styled login form with inline CSS.
- Notes:
  - Passwords are checked in plain text.
  - No password hashing or prepared statements.

### `connexionbd.php`
- Defines `connectMaBasi()`.
- Connects to MySQL at `localhost`, user `root`, no password, database `projects`.
- Returns the mysqli connection or dies on failure.

### `inscription.php`
- Includes `connexionbd.php`.
- Handles registration POST via `inscription` button.
- Validates fields, password length, password confirmation, and role.
- Checks for existing email.
- Inserts new user into `users` with `NOM`, `PRENOM`, `EMAIL`, `PASSWORD`, and `role`.
- Displays feedback messages.
- Contains a styled registration form with inline CSS.
- Notes:
  - Passwords are stored in plain text.
  - The role field is hidden and set to `client`.

### `historique_commandes.php`
- Starts session and requires `connexionbd.php`.
- Redirects to login if no user session.
- Queries `commande` rows for current user and computes total from `ligne_commande`.
- Displays a Bootstrap table of orders with date, status, and total.
- Uses status badges for `Payée`, `En attente`, and other statuses.

### `interface_admin.php`
- Starts session and requires `connexionbd.php`.
- Restricts access to logged-in admin users only.
- Supports admin actions:
  - add product
  - delete product
  - edit product
  - update product
  - delete client
  - edit client
  - update client
- Retrieves all products and non-admin users.
- Displays forms and tables for product / client CRUD.
- Uses Bootstrap markup and inline admin controls.

### `filtrage.php`
- Connects to the `projects` database directly with mysqli.
- Defines `findProductImage()` to locate product image files by name.
- Reads filter inputs from GET: `category`, `subcategory`, `brand`, `min_price`, `max_price`, `sort_price`, and `search`.
- Builds a SQL query dynamically for product filtering and search.
- Executes the query and displays the product listing.
- Includes a large HTML template with search, filters, and product cards.
- Notes: query building uses string concatenation, which is vulnerable to SQL injection.

### `index.php`
- Starts session.
- Loads Bootstrap, Swiper, vendor CSS, and `style.css`.
- Contains the main homepage and a cart offcanvas.
- Shows the current cart contents using `$_SESSION['panier']`.
- Allows quantity updates and item deletion through `ajouter_panier.php`.
- Displays total cart value and buttons to checkout or continue shopping.
- Includes a search offcanvas and likely a full homepage layout from the template.

### `style.css`
- Main theme CSS for the template.
- Defines colors, typography, button styles, layout structure, and responsive rules.
- Includes theme variables and extended styling for site sections.
- Appears to be the original template stylesheet customized for this project.

### `readme.txt`
- Describes the template source: TemplatesJungle.
- Includes usage rights, license conditions, and credits.
- Mentions Bootstrap, Google Fonts, Swiper, Chocolat.js, and Magnific Lightbox.

### `projects.sql`
- SQL dump for database `projects`.
- Creates tables including `brand`, `category`, `commande`, `ligne_commande`, and `products`.
- Inserts sample brands, categories, orders, shipment lines, and many product rows.
- Contains product catalog data for makeup, hair, skin care, and accessories.
- Note: the excerpt confirms sample order history data and product catalog setup.

### `js/script.js`
- Custom JS wrapped in an IIFE with jQuery.
- Initializes:
  - preloader animation
  - Chocolat image lightbox
  - Swiper sliders for main carousel, category carousel, brand carousel, and products carousel
  - product quantity increment/decrement buttons
  - Jarallax parallax effects
- Runs initialization on document ready.

## Important Notes for the Next Assistant

- Security issues:
  - `connexion.php` and `inscription.php` accept plain-text passwords and query the database directly.
  - `filtrage.php` uses dynamic SQL concatenation for filters and search.
  - `interface_admin.php` and other PHP files use raw POST values in SQL queries.
- Database assumptions:
  - The MySQL database is named `projects`.
  - `users` table is used for authentication and contains `ID`, `EMAIL`, `PASSWORD`, `NOM`, `PRENOM`, and `role`.
  - `commande`, `ligne_commande`, and `products` tables support order and product workflows.
- Frontend assets:
  - `css/vendor.css` and `css/normalize.css` are third-party styles.
  - `js/jquery-1.11.0.min.js`, `js/modernizr.js`, and `js/plugins.js` are template/vendor JS support files.
  - `images/prod_images/` contains product thumbnail images referenced in the catalog.

## Summary
This repository is a full-stack PHP e-commerce storefront built on a third-party Bootstrap template. The core features are session-based cart operations, user auth, checkout, order history, and a simple admin panel. It requires a `projects` MySQL database and currently has several security and validation weaknesses that should be fixed before production use.

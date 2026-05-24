# Glow-E Project Summary

## Project Overview

**Project Name**: Glow-E (E-Commerce Platform v1.0.1)

**Purpose**: Full-stack e-commerce platform for beauty and cosmetics with intelligent personalized product recommendations using Apache Spark-based Alternating Least Squares (ALS) machine learning algorithm.

**Core Value Proposition**: 
- Browse and purchase cosmetics/beauty products
- Real-time product recommendations based on user behavior
- Fast checkout and cart management
- Admin interface for product/order management
- Smart recommendation engine analyzing user-product interactions

**Technology Stack**: PHP 8.5+, JavaScript/jQuery, Bootstrap 5, Python 3.8+, Apache Spark 3.0+, PySpark, PostgreSQL/MySQL, FastAPI, MinIO (S3-compatible storage), Redis (optional caching)

---

## Architecture Overview

### High-Level System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    USER BROWSER (Client)                    │
│  JavaScript/jQuery Interface | Bootstrap UI Components     │
└────────────────┬──────────────────────────────────────────┘
                 │ HTTP/AJAX Requests
                 ▼
┌─────────────────────────────────────────────────────────────┐
│           PHP Web Application Layer (localhost)             │
│  - index.php (Homepage)                                     │
│  - product.php (Product Details)                            │
│  - connexion.php (User Login)                               │
│  - inscription.php (User Registration)                      │
│  - ajouter_panier.php (Add to Cart - AJAX)                 │
│  - effectuer_commande.php (Checkout)                        │
│  - commandes.php (Order Management)                         │
│  - interface_admin.php (Admin Dashboard)                    │
│  - recommender_widget.php (Recommendations Display)         │
│  - connexionbd.php (DB Connection Handler)                  │
└────────────────┬──────────────────────────────────────────┘
                 │
                 ├─────────────────────┬─────────────────────┐
                 │                     │                     │
                 ▼                     ▼                     ▼
        ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐
        │  MySQL/PostgreSQL │  │  FastAPI Backend │  │  MinIO       │
        │  (User Data,      │  │  (localhost:8000)│  │  (Object     │
        │   Orders,         │  │                   │  │   Storage)   │
        │   Products)       │  │  GET /recommend/ │  │              │
        │                  │  │  <user_id>      │  │  - Spark Jobs│
        │                  │  │                   │  │  - Models    │
        └──────────────────┘  └──────────────────┘  └──────────────┘
                 │                     ▲
                 └─────────────┬───────┘
                               │
                    ┌──────────▼──────────┐
                    │  Python ML Pipeline  │
                    │  (PySpark Batch)     │
                    │                      │
                    │  - Data Ingestion    │
                    │  - Preprocessing     │
                    │  - ALS Training      │
                    │  - Model Export      │
                    │  - Evaluation Metrics│
                    │                      │
                    │  Scripts:            │
                    │  • train_als.py      │
                    │  • generate_user_rec │
                    │  • preprocessing.py  │
                    └─────────────────────┘
```

### Data Flow

**User Journey**:
1. User browses homepage (index.php)
2. User clicks product card → Opens quick-view modal (js/script.js AJAX load from product.php)
3. User adds product to cart (ajouter_panier.php AJAX endpoint) → Updates cart badge
4. User clicks recommender widget products → Same modal flow
5. User proceeds to checkout (effectuer_commande.php)
6. System logs interaction (for ML pipeline)

**Recommendation Pipeline**:
1. User actions logged in database
2. Batch job (train_als.py) preprocesses interactions
3. ALS algorithm trains on interactions, computes user-product vectors
4. Model exported to MinIO object storage
5. FastAPI loads model from MinIO, serves predictions
6. PHP recommender_widget.php calls `/recommend/<user_id>` endpoint
7. Personalized products displayed on homepage

---

## Core Components

### 1. Frontend (User Interface)

#### Purpose
Render e-commerce interface, handle user interactions, communicate with backend via AJAX, display personalized recommendations.

#### Key Technologies
- **JavaScript/jQuery**: DOM manipulation, AJAX requests, event handling
- **Bootstrap 5**: Responsive grid, components, utility classes, CSS variables
- **Custom CSS (style.css)**: Product cards, modals, badges, animations, hover effects

#### Key Features
- **Quick-View Modal System**: Click product → modal overlay appears over existing page (no full-page navigation)
- **Product Cards**: Display image, name, price, category, "Recommended" badge
- **Shopping Cart**: Add/remove items via AJAX, cart count badge in header
- **Carousels**: Swiper-based product category sliders
- **Badge Styling**: Green success badges for recommended items (position: absolute, top-left corner)
- **Responsive Design**: Mobile, tablet, desktop compatible

#### Key Functions (js/script.js)
- `handleOpenProductPreview(e)`: Load product details via AJAX from product.php
- `buildStaticPreviewHtml(details)`: HTML template builder for modal content (fallback)
- `openProductPreviewModal(html)`: Display modal overlay, manage lifecycle
- `handleAjaxCartSubmit(event)`: Submit add-to-cart form via AJAX
- `initProductPreviewBindings()`: Attach click handlers to all product cards
- `escapeHtml(text)`: XSS prevention via HTML entity encoding
- Modal close handlers: Escape key, outside click, close button

#### Key Styling (style.css)
- `.product-preview-backdrop`: Fixed fullscreen dark overlay (z-index: 1040)
- `.product-preview-modal`: Centered floating panel with shadow (z-index: 1050)
- `.badge-recommended`: Absolute positioned green badge (`bg-success`)
- Product card hover effects and shadows
- Modal close button styling

### 2. Backend (PHP Web Application)

#### Purpose
Handle HTTP requests, render HTML pages, process user actions, manage sessions, interface with database and API.

#### Environment
- **Server**: Apache (XAMPP)
- **PHP Version**: 8.5+
- **Session Management**: PHP $_SESSION for user persistence
- **Database Driver**: MySQLi (or PDO)

#### Core Files Overview

**index.php**
- Purpose: Homepage with product grid, trending section, carousel
- Displays: Best-selling products, popular products, just-arrived products
- Includes: recommender_widget.php for personalized recommendations
- Features: Bootstrap tabs, Swiper carousels, product grids
- Dependencies: connexionbd.php (DB connection), recommender_widget.php

**product.php**
- Purpose: Render single product details for AJAX modal loading
- Content: Product image, name, description, price, category, add-to-cart form
- Output: Minimal HTML (no page wrapper) for modal display
- Used by: AJAX requests from js/script.js handleOpenProductPreview()
- Return format: HTML snippet for modal body

**connexion.php**
- Purpose: User login page and form handler
- Features: Email/password validation, session creation, redirect to homepage
- Includes: connexionbd.php for DB query
- Validates: User credentials against database
- Sets: $_SESSION['user_id'], $_SESSION['user_email']

**inscription.php**
- Purpose: User registration page and form handler
- Features: Email validation, password hashing (PHP password_hash), duplicate user check
- Includes: connexionbd.php for DB insert
- Creates: New user account in database
- Redirects: To connexion.php after successful registration

**ajouter_panier.php**
- Purpose: AJAX endpoint for adding products to cart
- Method: POST via AJAX from js/script.js
- Input: product_id, quantity
- Action: Adds item to $_SESSION['cart'] array or increments quantity
- Output: JSON response with status, new cart count
- Used by: Modal product forms, cart management

**effectuer_commande.php**
- Purpose: Checkout process and order creation
- Displays: Cart items, total price, shipping address form
- Features: Order validation, inventory check, order creation in database
- Creates: Order record with user_id, items, timestamp
- Sends: Confirmation email (optional)
- Redirects: To confirmation.php on success

**confirmation.php**
- Purpose: Order confirmation page
- Displays: Order number, items, total, estimated delivery
- Retrieves: Order details from database via order_id
- Provides: Print-friendly receipt, tracking information (if available)

**historique_commandes.php**
- Purpose: User order history page
- Displays: List of past orders for logged-in user
- Features: Order filtering by date, status; detail view per order
- Queries: Orders table filtered by $_SESSION['user_id']

**interface_admin.php**
- Purpose: Admin dashboard for product/order management
- Features: 
  - View all products, add/edit/delete products
  - View all orders, update order status
  - View user list
  - Product inventory management
- Access: Restricted to admin users (checked via session)
- Includes: connexionbd.php for database operations

**recommender_widget.php**
- Purpose: Display personalized product recommendations
- Data Source: FastAPI backend at localhost:8000/recommend/<user_id>
- Features:
  - cURL HTTP request to recommendation API
  - Error handling with JSON response
  - Displays 6-8 recommended products in grid
  - Products have "Recommended" green badge
  - AJAX add-to-cart forms
  - Dynamic image finding (image_id lookup with extension fallback)
- Output: HTML grid of product cards with recommendation badges
- Only displays: If user is logged in ($_SESSION['user_id'] exists)
- PHP 8.5 Compatibility: cURL close guarded with type check (is_resource || instanceof CurlHandle)

**connexionbd.php**
- Purpose: Centralized database connection handler
- Creates: MySQLi or PDO connection object
- Credentials: Stored in file (not hardcoded in other files)
- Used by: All other PHP files for database queries
- Error Handling: Connection failure alerts

**test.php**
- Purpose: Development/debugging test script
- Content: Ad-hoc PHP code for testing features, DB connections, API calls
- Not used: In production

**tmp_db_test.php**
- Purpose: Temporary database testing
- Content: DB connection tests, sample queries
- Not used: In production

### 3. Database Layer

#### Purpose
Persist data: users, products, orders, user interactions, cart data.

#### Database Type
MySQL or PostgreSQL (XAMPP default is MySQL)

#### Core Tables

**users**
- Columns: user_id (PK), email (unique), password (hashed), first_name, last_name, created_at
- Used by: connexion.php, inscription.php, order tracking

**products**
- Columns: product_id (PK), name, description, price, category, image_id, image_path, stock, created_at
- Used by: index.php (product display), product.php (details), recommender_widget.php (product lookup)

**orders**
- Columns: order_id (PK), user_id (FK), total_price, status (pending/shipped/delivered), created_at, updated_at
- Used by: effectuer_commande.php (create), historique_commandes.php (retrieve), interface_admin.php (manage)

**order_items**
- Columns: order_item_id (PK), order_id (FK), product_id (FK), quantity, price_at_purchase
- Used by: Stores individual items within an order

**user_interactions** (for ML pipeline)
- Columns: interaction_id (PK), user_id (FK), product_id (FK), interaction_type (view/click/cart/purchase), timestamp
- Used by: train_als.py (ML algorithm input), generate_user_recs_simple.py (context)
- Purpose: Raw data for ALS model training

**cart** (session-based, or persisted in DB)
- Columns: cart_id (PK), user_id (FK), product_id (FK), quantity, added_at
- Used by: ajouter_panier.php, effectuer_commande.php, cart display

#### Database Connection
- Handler: connexionbd.php
- Driver: MySQLi or PDO
- Host: localhost (XAMPP)
- Port: 3306 (default MySQL)
- Database: Glow-E (or similar)

### 4. FastAPI Backend (Recommendation Engine)

#### Purpose
Serve personalized product recommendations to PHP frontend via REST API.

#### Location
`recommender/api/main.py`

#### Endpoints

**GET /recommend/<user_id>**
- Purpose: Get recommended products for specific user
- Input: user_id (path parameter)
- Process:
  1. Load ALS model from MinIO object storage
  2. Retrieve user vector from model
  3. Compute similarity with all product vectors
  4. Rank products by similarity score (highest first)
  5. Filter: Remove already-purchased items, apply business rules
  6. Return: Top 6-8 product IDs
- Output: JSON array of product_id, rank, score
- Example Response:
  ```json
  [
    {"product_id": 45, "score": 0.92, "rank": 1},
    {"product_id": 78, "score": 0.88, "rank": 2}
  ]
  ```
- Called by: recommender_widget.php via cURL

**GET /health**
- Purpose: Health check endpoint
- Output: `{"status": "healthy"}`
- Used by: Deployment monitoring

#### Dependencies
- **FastAPI**: REST framework
- **PySpark**: Model loading
- **MinIO Client**: Object storage access
- **NumPy/Pandas**: Numerical operations
- **Pydantic**: Request/response validation

#### Configuration
- Host: 0.0.0.0 (accessible from PHP application)
- Port: 8000
- Model Path: MinIO bucket (location specified in config)

### 5. Python ML Pipeline (Spark-Based Recommendation Engine)

#### Purpose
Train ALS model on user-product interactions, generate personalized recommendations, export model to production.

#### Core Scripts

**train_als.py**
- Purpose: Train ALS model on user-product interaction data
- Input:
  - User interactions CSV (user_id, product_id, interaction_type, timestamp)
  - Spark session initialized via spark_session.py
- Process:
  1. Load interactions from CSV using PySpark
  2. Filter interactions (only purchases, high-value clicks)
  3. Assign implicit ratings (purchase=5, cart=3, click=1)
  4. Create ALS model instance with hyperparameters
  5. Train on interactions (rank=10, iterations=10, regParam=0.01)
  6. Evaluate using RMSE, Precision@K, Recall@K metrics
  7. Save model to MinIO for FastAPI to load
- Output:
  - Trained model artifact (.parquet)
  - Evaluation metrics printed to stdout
- Hyperparameters:
  - rank (latent factors): 10
  - iterations: 10
  - regParam (regularization): 0.01
  - coldStartStrategy: "drop"

**train_implicit_als.py**
- Purpose: Alternative ALS training using implicit feedback algorithm
- Difference: Uses implicit feedback (clicks, views) vs explicit ratings
- Better for: E-commerce where explicit ratings are rare
- Input: Same interaction CSV
- Output: Model optimized for implicit feedback

**generate_user_recs_simple.py**
- Purpose: Generate recommendation batch for all users
- Process:
  1. Load trained model
  2. For each user: Get top-N product recommendations
  3. Store recommendations in database or CSV
  4. Filter out already-purchased items
- Used by: Batch job scheduler (cron/airflow)
- Output: Recommendations CSV or database insert

**preprocessing.py**
- Purpose: Clean and prepare raw interaction data
- Operations:
  - Remove duplicates
  - Filter invalid user_id/product_id
  - Handle missing values
  - Normalize timestamps
  - Remove old interactions (retention window)
- Input: Raw events.csv
- Output: Cleaned events.csv

**debug_db.py**
- Purpose: Development utility to inspect database state
- Functions: Query user interactions, verify data quality, test DB connection
- Used by: Development/debugging

**export_to_minio.py**
- Purpose: Export trained model to MinIO object storage
- Process:
  1. Train model (or load existing)
  2. Serialize model to .parquet format
  3. Upload to MinIO bucket using minio_client.py
  4. Store metadata (model version, training timestamp)
- Output: Model accessible via FastAPI

**minio_client.py**
- Purpose: MinIO (S3-compatible) client wrapper
- Functions:
  - Upload file to bucket
  - Download file from bucket
  - List objects in bucket
  - Delete objects
- Used by: export_to_minio.py, FastAPI backend

**spark_session.py**
- Purpose: Spark session factory/configuration
- Creates: Shared Spark session with optimization settings
- Configuration:
  - master: "local[*]" (use all cores)
  - appName: "GlowE-Recommender"
  - Memory: 2G executor, 1G driver (tunable)
  - Shuffle partitions: 4
- Used by: All ML scripts (train_als.py, preprocessing.py, etc.)

**dashboard_app.py**
- Purpose: Flask-based dashboard for model monitoring
- Features:
  - Real-time training metrics
  - Model performance visualization
  - User coverage stats
  - Recommendation quality metrics
- Port: 5000 (separate from FastAPI)
- Used by: Development team for monitoring

**requirements.txt**
- Purpose: Python dependency list
- Dependencies:
  - pyspark>=3.0.0
  - fastapi>=0.70.0
  - uvicorn>=0.15.0
  - pydantic>=1.8.0
  - minio>=7.0.0
  - pandas>=1.3.0
  - numpy>=1.21.0
  - flask>=2.0.0 (for dashboard)

#### Data Directory (`recommender/data/`)

**events.csv**
- Purpose: Raw user-product interactions
- Columns: user_id, product_id, event_type (click/view/cart/purchase), timestamp
- Size: ~2.7M rows (Retailrocket dataset)
- Format: CSV with header

**item_properties.csv**
- Purpose: Product metadata
- Columns: product_id, category, brand, price, color, size
- Used by: Preprocessing for feature engineering (optional)

**als_model/** (directory)
- Purpose: Trained ALS model artifact storage
- Contents:
  - metadata/ (model metadata, training date)
  - parquet files (model vectors, parameters)
- Format: Spark MLlib format
- Location: Also backed up to MinIO

#### Execution Environment
- Python Version: 3.8+
- Environment: Virtual environment (venv) in project root
- Activation: `venv\Scripts\activate.ps1` (Windows)
- Location: `recommender/` directory
- Scheduler: Manual execution or cron/Airflow (in production)

---

## File-by-File Breakdown

### Root Directory (c:\xampp\htdocs\Glow-E.web .1.0.1\)

#### PHP Application Files

| File | Purpose | Key Functions | Dependencies |
|------|---------|---------------|--------------|
| **index.php** | Homepage with product grid, trending section, personalized recommendations | Fetch products from DB, render carousels, include recommender widget | connexionbd.php, style.css, js/script.js, recommender_widget.php |
| **product.php** | Single product details for AJAX modal loading | Query product by ID, render HTML snippet | connexionbd.php |
| **connexion.php** | User login page and handler | Validate credentials, create session | connexionbd.php, style.css |
| **inscription.php** | User registration page and handler | Validate email, hash password, create user account | connexionbd.php, style.css |
| **ajouter_panier.php** | AJAX endpoint for cart operations | Add item to cart, update quantity, return JSON | Session ($_SESSION['cart']) |
| **effectuer_commande.php** | Checkout and order creation | Validate cart, create order in DB, send confirmation | connexionbd.php, ajouter_panier.php |
| **confirmation.php** | Order confirmation display | Fetch order details by order_id, render receipt | connexionbd.php, style.css |
| **historique_commandes.php** | User order history page | Query orders for user_id, display with details | connexionbd.php, style.css |
| **interface_admin.php** | Admin dashboard for product/order management | CRUD operations on products/orders/users, restrict to admin | connexionbd.php, style.css |
| **connexionbd.php** | Database connection handler | Initialize MySQLi/PDO connection, error handling | (database: MySQL/PostgreSQL) |
| **recommender_widget.php** | Display personalized recommendations | Call FastAPI endpoint, fetch product details, render HTML grid | connexionbd.php, FastAPI backend (localhost:8000), style.css, js/script.js |
| **test.php** | Development test script | Ad-hoc testing (not in production) | Variable |
| **tmp_db_test.php** | Temporary DB testing | Connection/query tests (not in production) | connexionbd.php |

#### Documentation Files

| File | Purpose | Content |
|------|---------|---------|
| **rapport.md** | Comprehensive technical report for PFA project | 11 sections: Context, objectives, architecture, stack, ML pipeline, implementation plan (8 weeks), evaluation metrics, deployment, conclusion |
| **presentation.md** | Presentation guide for PFA defense | 16 slides with speaker notes, timing, visual asset checklist, Q&A handling |
| **PROJECT_SUMMARY.md** | Project overview for AI assistants | Architecture, component descriptions, file breakdown, technology stack, data flow (this file) |
| **readme.txt** | Project readme | General project information |
| **commandes.md** | Commands reference (possibly deployment/setup instructions) | CLI commands, database setup |
| **phase4.md** | Phase 4 implementation notes | Development phase documentation |
| **RECOMMENDER_QUICKSTART.md** | Recommender system quick start guide | Getting started with ML pipeline |
| **RECOMMENDER_UPGRADE_SUMMARY.md** | Upgrade summary for recommender system | Version upgrade notes, changes |
| **project-full-source.md** | Full project source code archive | Complete codebase export |
| **project-summary.md** | Previous project summary (possibly outdated) | Legacy project overview |
| **projects.csv** | Project list or product list | CSV data (content unclear) |
| **projects.sql** | SQL database schema/dump | Database structure and/or sample data |

#### Configuration Files

| File | Purpose | Content |
|------|---------|---------|
| **style.css** | Global CSS stylesheet | Product cards, modals, badges, responsive design, animations |
| **index_out.html** | Static HTML export of homepage | (possibly for development/testing) |

### CSS Directory (css/)

| File | Purpose |
|------|---------|
| **normalize.css** | CSS normalization for cross-browser consistency |
| **vendor.css** | Vendor-prefixed CSS, Bootstrap or third-party styles |

### JavaScript Directory (js/)

| File | Purpose | Key Functions |
|------|---------|---------------|
| **script.js** | Main interaction script for frontend | handleOpenProductPreview(), openProductPreviewModal(), handleAjaxCartSubmit(), initProductPreviewBindings(), escapeHtml(), modal lifecycle management |
| **jquery-1.11.0.min.js** | jQuery library (minified) | DOM manipulation, AJAX |
| **modernizr.js** | Feature detection library | HTML5/CSS3 feature detection |
| **plugins.js** | jQuery plugins bundle | Third-party jQuery extensions |

### Images Directory (images/)

| File/Folder | Purpose |
|-------------|---------|
| **product-thumb-13.avif** | Sample product thumbnail image |
| **chocolat/** | Product category folder (chocolat = chocolate) |
| **prod_images/** | Product images folder (Cosmetics: mascara, foundation, mask, etc.) |

### Recommender System Directory (recommender/)

#### Root Files

| File | Purpose |
|------|---------|
| **config.py** | Configuration settings (API host, port, MinIO credentials, database connection) |
| **requirements.txt** | Python dependencies (PySpark, FastAPI, MinIO, Pandas, etc.) |

#### Training/Batch Scripts

| File | Purpose |
|------|---------|
| **train_als.py** | Train ALS model on user-product interactions |
| **train_implicit_als.py** | Alternative ALS training with implicit feedback |
| **generate_user_recs_simple.py** | Batch generate recommendations for all users |
| **preprocessing.py** | Clean and prepare interaction data |
| **generate_random_orders.py** | Generate synthetic orders for testing |
| **debug_db.py** | Database inspection and debugging utility |
| **spark_session.py** | Spark session factory and configuration |
| **minio_client.py** | MinIO client wrapper for object storage |
| **export_to_minio.py** | Export trained model to MinIO |
| **dashboard_app.py** | Flask dashboard for model monitoring |

#### API Subdirectory (recommender/api/)

| File | Purpose |
|------|---------|
| **main.py** | FastAPI application with /recommend/<user_id> endpoint |

#### Data Subdirectory (recommender/data/)

| File | Purpose |
|------|---------|
| **events.csv** | Raw user-product interactions (2.7M rows) |
| **item_properties.csv** | Product metadata (category, brand, price, etc.) |
| **als_model/** | Trained model directory (Spark MLlib format) |

#### Hadoop Directory (org/apache/hadoop/fs/s3a/)

| Purpose | Content |
|---------|---------|
| S3A filesystem integration | Hadoop S3A connector classes (for MinIO/S3 compatibility) |

---

## Technology Stack Reference

### Frontend

| Technology | Version | Purpose | Files |
|------------|---------|---------|-------|
| HTML5 | Latest | Page structure | *.php |
| CSS3 | Latest | Styling, layout | style.css, css/*.css |
| JavaScript (ES5) | Latest | Interactivity | js/script.js |
| jQuery | 1.11.0 | DOM/AJAX | js/jquery-1.11.0.min.js |
| Bootstrap | 5.x | Component framework | CSS classes in HTML |
| Swiper | Latest | Carousel library | Included via CDN (likely) |

### Backend

| Technology | Version | Purpose | Files |
|------------|---------|---------|-------|
| PHP | 8.5+ | Web application | *.php files |
| Apache | 2.4.x | Web server | (XAMPP) |
| MySQL/PostgreSQL | 5.7+/11+ | Database | connexionbd.php → DB |
| MySQLi | Latest | PHP-DB driver | connexionbd.php |

### ML/Data Pipeline

| Technology | Version | Purpose | Files |
|------------|---------|---------|-------|
| Python | 3.8+ | Language | recommender/*.py |
| Apache Spark | 3.0+ | Distributed processing | train_als.py, preprocessing.py |
| PySpark | 3.0+ | Python Spark API | train_als.py, spark_session.py |
| Spark MLlib | 3.0+ | ML algorithms (ALS) | train_als.py |
| NumPy | 1.21+ | Numerical operations | (requirements.txt) |
| Pandas | 1.3+ | Data manipulation | preprocessing.py, generate_random_orders.py |
| MinIO | 7.0+ | Object storage (S3 API) | minio_client.py, export_to_minio.py |

### API/Services

| Technology | Version | Purpose | Files |
|------------|---------|---------|-------|
| FastAPI | 0.70+ | REST API framework | recommender/api/main.py |
| Uvicorn | 0.15+ | ASGI server | (requirements.txt) |
| Pydantic | 1.8+ | Data validation | recommender/api/main.py |
| Flask | 2.0+ | Dashboard web framework | dashboard_app.py |
| cURL | System | HTTP client (PHP) | recommender_widget.php |

---

## Component Interaction Flow

### User Registration & Login Flow

```
User → inscription.php (form) → connexionbd.php (DB insert) → MySQL
User → connexion.php (form) → connexionbd.php (DB query) → $_SESSION created
```

### Product Browsing Flow

```
User Opens Browser → index.php (rendered)
  ├─ Fetches products via connexionbd.php → MySQL
  ├─ Renders product grid (HTML/CSS)
  ├─ Loads recommender_widget.php
  │   └─ Calls FastAPI: GET /recommend/<user_id>
  │   └─ Displays recommended products (same card style)
  └─ Initializes js/script.js event handlers
```

### Product Click → Quick-View Modal Flow

```
User Clicks Product Card
  ↓ (js/script.js: handleOpenProductPreview trigger)
  ↓ AJAX GET /product.php?id=123
  ↓ connexionbd.php queries MySQL for details
  ↓ product.php returns HTML snippet
  ↓ js/script.js builds modal HTML via buildStaticPreviewHtml()
  ↓ openProductPreviewModal() displays overlay
  ↓ Modal appears over homepage (homepage remains visible)
  ↓ User can see add-to-cart form, close via Escape/click outside
```

### Add to Cart Flow

```
User Clicks "Add to Cart"
  ↓ (js/script.js: handleAjaxCartSubmit trigger)
  ↓ AJAX POST /ajouter_panier.php
  ↓ $_SESSION['cart'] array updated
  ↓ JSON response: {status: "success", cart_count: 5}
  ↓ js/script.js updates cart badge (#cart-count-badge)
  ↓ Visual feedback: Cart icon badge shows new count
```

### Checkout & Order Creation Flow

```
User → effectuer_commande.php (cart review)
  ↓ Validates items in session
  ↓ Form POST to effectuer_commande.php (again)
  ↓ connexionbd.php: inserts order into MySQL orders table
  ↓ connexionbd.php: inserts items into order_items table
  ↓ Clears $_SESSION['cart']
  ↓ Redirects to confirmation.php
  ↓ Logs interaction to user_interactions table (ML pipeline input)
```

### Recommendation Generation Flow (Batch)

```
Scheduled Job (nightly cron)
  ↓ Runs: python recommender/train_als.py
  ↓ Loads events.csv (user interactions)
  ↓ preprocessing.py cleans data
  ↓ Initializes Spark session via spark_session.py
  ↓ Trains ALS model (rank=10, iterations=10)
  ↓ Evaluates metrics (RMSE, Precision@K)
  ↓ Exports model via export_to_minio.py
  ↓ Model uploaded to MinIO object storage
```

### Real-Time Recommendation Serving Flow

```
User Visits Homepage (recommender_widget.php renders)
  ↓ cURL HTTP GET → FastAPI /recommend/123
  ↓ FastAPI main.py loads model from MinIO
  ↓ Computes user-product similarities
  ↓ Returns top-6 product_ids + scores
  ↓ recommender_widget.php queries MySQL for product details
  ↓ Renders product grid (same as product.php)
  ↓ Badge styling: `bg-success` (green) badge
```

### Admin Management Flow

```
Admin User → interface_admin.php (auth check: admin role)
  ├─ CRUD Products (ajouter_panier.php-like forms)
  │  └─ connexionbd.php: INSERT/UPDATE/DELETE products
  ├─ CRUD Orders (view status, update status)
  │  └─ connexionbd.php: queries orders table
  └─ User Management (view users, reset passwords)
     └─ connexionbd.php: queries users table
```

---

## Development Workflow

### Setting Up Development Environment

1. **PHP Application** (XAMPP)
   ```
   Start Apache & MySQL in XAMPP Control Panel
   Navigate to http://localhost/Glow-E.web%20.1.0.1
   Database: Create via projects.sql
   ```

2. **Python ML Pipeline**
   ```
   cd c:\xampp\htdocs\Glow-E.web .1.0.1
   python -m venv venv
   venv\Scripts\activate.ps1
   pip install -r recommender/requirements.txt
   ```

3. **FastAPI Backend**
   ```
   (venv activated)
   uvicorn recommender.api.main:app --reload --host 0.0.0.0 --port 8000
   ```

4. **Flask Dashboard** (optional)
   ```
   (venv activated)
   python recommender/dashboard_app.py
   Navigate to http://localhost:5000
   ```

### Common Development Tasks

| Task | Command/File |
|------|--------------|
| Run ML training | `python recommender/train_als.py` |
| Generate recommendations | `python recommender/generate_user_recs_simple.py` |
| Clean data | `python recommender/preprocessing.py` |
| Test API locally | `curl http://localhost:8000/recommend/1` |
| Check database | `mysql -u root -p < projects.sql` |
| Debug interactions | `python recommender/debug_db.py` |

---

## Deployment & Production Environment

### Hosting Requirements

- **Web Server**: Apache 2.4+ with PHP 8.5+ module
- **Database**: MySQL 5.7+ or PostgreSQL 11+
- **Python Runtime**: Python 3.8+ with Spark 3.0+
- **Object Storage**: MinIO or AWS S3
- **API Server**: FastAPI with Uvicorn (separate process)
- **Scheduler**: Cron job or Airflow for batch jobs

### Deployment Architecture

```
Load Balancer
  ↓
Apache + PHP (multiple instances)
  ├─ index.php, product.php, etc.
  ├─ Session storage: Redis or Memcached
  └─ Database: MySQL (read replicas)

FastAPI Service (separate)
  ├─ /recommend/<user_id>
  └─ Model cache: Redis (hot cache)

Batch Jobs (scheduled)
  └─ train_als.py (nightly)
  └─ generate_user_recs_simple.py

Object Storage
  └─ MinIO (or S3) for ML models
```

---

## Key Metrics & Monitoring

### ML Model Metrics (from rapport.md)

- **RMSE**: Root Mean Squared Error (target: 0.75)
- **Precision@K**: Top-K recommendation accuracy (target: 0.65)
- **Recall@K**: Coverage of relevant items (target: 0.55)
- **NDCG**: Ranking quality metric (target: 0.72)

### System Metrics

- Page load time: < 2s
- Add-to-cart response time: < 500ms
- Recommendation API latency: < 200ms
- Database query time: < 100ms
- ML training time: < 30 minutes (nightly)

---

## Security Considerations

### Frontend Security

- **XSS Prevention**: escapeHtml() in js/script.js encodes HTML entities
- **CSRF Tokens**: Implement in forms (not currently visible, recommended)
- **Input Validation**: Client-side in JS, server-side in PHP

### Backend Security

- **SQL Injection**: Use prepared statements (MySQLi parameterized queries)
- **Session Security**: Use secure cookies, httponly flag, secure flag (HTTPS)
- **Password Storage**: PHP password_hash() with bcrypt
- **API Security**: FastAPI input validation via Pydantic

### Data Security

- **PII Protection**: User emails, passwords stored securely
- **Order Data**: Encrypted transmission (HTTPS)
- **Model Security**: Models stored in private MinIO bucket
- **Logs**: No sensitive data in logs

---

## Future Enhancements & Roadmap

### Phase 1 (Months 1-3)
- Real-time recommendation updates
- A/B testing framework
- Recommendation diversity metrics

### Phase 2 (Months 3-6)
- Mobile app (React Native) for recommendations
- Advanced segmentation (behavioral, demographic)
- Collaborative filtering + content-based hybrid

### Phase 3 (Months 6+)
- GPU-accelerated training (RAPIDS)
- Real-time streaming (Kafka + Spark Streaming)
- Multi-model ensemble (XGBoost, LightGBM)

---

## Summary

**Glow-E** is a full-stack e-commerce platform combining traditional PHP/MySQL e-commerce functionality with a modern distributed machine learning recommendation engine. The architecture separates concerns: frontend (user experience), backend (business logic), database (persistence), and ML pipeline (personalization). All components communicate via HTTP APIs and persistent storage, enabling scalability and maintainability. The system uses industry-standard technologies (Apache Spark, FastAPI, MinIO) to deliver production-grade recommendation features while maintaining simplicity for development and deployment.

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projects"; 
//FONCTION pour donner a chaque produit son image 
function findProductImage($productName) {
    $imageDir = 'images/prod_images/';
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    
    foreach ($allowedExtensions as $ext) {
        $imagePath = $imageDir . $productName . '.' . $ext;
        if (file_exists($imagePath)) {
            return $imagePath;
        }
    }
    
    // Return default image if no product image found
    return 'images/no-image.png';
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get unique categories, subcategories, brands
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$subcategories = $conn->query("SELECT DISTINCT subcategory FROM products ORDER BY subcategory ASC");
$brands = $conn->query("SELECT DISTINCT brand FROM products ORDER BY brand ASC");

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;
$sort_price = isset($_GET['sort_price']) ? $_GET['sort_price'] : 'none';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Base query
$sql = "SELECT * FROM products WHERE 1=1";

//  category filter 
if (!empty($category) && $category !== 'all') {
    $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
}
// subcategory filter 
if (!empty($subcategory) && $subcategory !== 'all') {
    $sql .= " AND subcategory = '" . $conn->real_escape_string($subcategory) . "'";
}
// brand filter 
if (!empty($brand) && $brand !== 'all') {
    $sql .= " AND brand = '" . $conn->real_escape_string($brand) . "'";
}
//  range filter
$sql .= " AND price BETWEEN " . $min_price . " AND " . $max_price;


if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Add sorting by price if specified
if ($sort_price === 'asc') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort_price === 'desc') {
    $sql .= " ORDER BY price DESC";
}

//  query
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Glow - ECommerce Beauty Store </title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
    .product-item {
      position: relative;
      padding: 16px;
      background: #FFFFFF;
      border: 1px solid #FBFBFB;
      box-shadow: 0px 5px 22px rgba(0, 0, 0, 0.04);
      border-radius: 16px;
      margin-bottom: 30px;
      transition: box-shadow 0.3s ease-out;
    }
    .product-item:hover {
      box-shadow: 0px 21px 44px rgba(0, 0, 0, 0.08);
    }
    .product-item h3 {
      display: block;
      width: 100%;
      font-weight: 600;
      font-size: 18px;
      line-height: 25px;
      text-transform: capitalize;
      color: #333333;
      margin: 0;
    }
    .product-item figure {
      background: #F9F9F9;
      border-radius: 12px;
      text-align: center;
    }
    .product-item figure img {
      max-height: 210px;
      height: auto;
    }
    .product-item .btn-wishlist {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 50px;
      height: 50px;
      border-radius: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      border: 1px solid #d8d8d8;
      transition: all 0.3s ease-out;
    }
    .product-item .btn-wishlist:hover {
      background: rgb(240, 56, 56);
      color: #fff;
    }
    .product-item .qty {
      font-weight: 400;
      font-size: 13px;
      line-height: 18px;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      color: #9D9D9D;
    }
    .product-item .rating {
      font-weight: 600;
      font-size: 13px;
      line-height: 18px;
      text-transform: capitalize;
      color: #222222;
    }
    .product-item .rating iconify-icon {
      color: #d9912b;
    }
    .product-item .price {
      display: block;
      width: 100%;
      font-weight: 600;
      font-size: 22px;
      line-height: 30px;
      text-transform: capitalize;
      color: #222222;
    }
    .product-item .product-qty {
      width: 85px;
    }
    .product-item .btn-link {
      text-decoration: none;
    }
    .product-item #quantity {
      height: auto;
      width: 28px;
      text-align: center;
      border: none;
      margin: 0;
      padding: 0;
    }
    .product-item .btn-number {
      width: 26px;
      height: 26px;
      line-height: 1;
      text-align: center;
      background: #FFFFFF;
      border: 1px solid #E2E2E2;
      border-radius: 6px;
      color: #222;
      padding: 0;
    }
    .sidebar-filter {
      min-width: 180px;
      max-width: 220px;
    }
    </style>
</head>
<body>
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <defs>
       <symbol xmlns="http://www.w3.org/2000/svg" id="link" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 19a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0-4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm-5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm7-12h-1V2a1 1 0 0 0-2 0v1H8V2a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V6a1 1 0 0 1 1-1h1v1a1 1 0 0 0 2 0V5h8v1a1 1 0 0 0 2 0V5h1a1 1 0 0 1 1 1ZM7 15a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="arrow-right" viewBox="0 0 24 24">
          <path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="category" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="calendar" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16Zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="heart" viewBox="0 0 24 24">
          <path fill="currentColor" d="M20.16 4.61A6.27 6.27 0 0 0 12 4a6.27 6.27 0 0 0-8.16 9.48l7.45 7.45a1 1 0 0 0 1.42 0l7.45-7.45a6.27 6.27 0 0 0 0-8.87Zm-1.41 7.46L12 18.81l-6.75-6.74a4.28 4.28 0 0 1 3-7.3a4.25 4.25 0 0 1 3 1.25a1 1 0 0 0 1.42 0a4.27 4.27 0 0 1 6 6.05Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="plus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="minus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
          <path fill="currentColor" d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="check" viewBox="0 0 24 24">
          <path fill="currentColor" d="M18.71 7.21a1 1 0 0 0-1.42 0l-7.45 7.46l-3.13-3.14A1 1 0 1 0 5.29 13l3.84 3.84a1 1 0 0 0 1.42 0l8.16-8.16a1 1 0 0 0 0-1.47Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="trash" viewBox="0 0 24 24">
          <path fill="currentColor" d="M10 18a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1ZM20 6h-4V5a3 3 0 0 0-3-3h-2a3 3 0 0 0-3 3v1H4a1 1 0 0 0 0 2h1v11a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8h1a1 1 0 0 0 0-2ZM10 5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h-4Zm7 14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8h10Zm-3-1a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-outline" viewBox="0 0 15 15">
          <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.804L5.337 11l.413-2.533L4 6.674l2.418-.37L7.5 4l1.082 2.304l2.418.37l-1.75 1.793L9.663 11L7.5 9.804Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-solid" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="search" viewBox="0 0 24 24">
          <path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
          <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="close" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
    </defs>
  </svg>
    <header>
      <div class="container-fluid">
        <div class="row py-3 border-bottom">
          <!-- Logo Section -->
          <div class="col-lg-3 col-md-4 col-sm-4">
            <div class="main-logo">
              <a href="index.php">
                <img src="images/logo.png" alt="logo" class="img-fluid">
              </a>
            </div>
          </div>
          
          <!-- Search Bar Section -->
          <div class="col-lg-5 col-md-6 col-sm-8">
            <div class="search-bar bg-light p-2 my-2 rounded-4">
              <form id="search-form" action="filtrage.php" method="GET" class="d-flex align-items-center">
                <div class="col-md-4 d-none d-md-block">
                  <select class="form-select border-0 bg-transparent">
                    <option>All Categories</option>
                  </select>
                </div>
                <div class="col-11 col-md-7">
                  <input type="text" name="search" class="form-control border-0 bg-transparent" placeholder="Search for more than 20,000 products" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                </div>
                <div class="col-1">
                  <button type="submit" class="btn btn-link p-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z"/></svg>
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Support Section -->
          <div class="col-lg-4 col-md-2 col-sm-12">
            <div class="support-box text-end d-flex justify-content-end align-items-center h-100">
              <div class="d-none d-xl-block">
                <span class="fs-6 text-muted">For Support?</span>
                <h5 class="mb-0">+33-34984089</h5>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Section -->
      <div class="container-fluid">
        <div class="row py-3">
          <div class="col-12">
            <nav class="main-menu navbar navbar-expand-lg">
              <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
              </button>

              <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                  <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body">
                  <ul class="navbar-nav justify-content-center menu-list list-unstyled d-flex gap-md-3 mb-0">
                    <li class="nav-item" id="home">
                      <a href="index.php" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item active">
                      <a href="filtrage.php?category=Makeup" class="nav-link">Makeup</a>
                    </li>
                    <li class="nav-item">
                      <a href="filtrage.php?category=Skin Care" class="nav-link">Skin Care</a>
                    </li>
                    <li class="nav-item">
                      <a href="filtrage.php?category=Hair" class="nav-link">Hair</a>
                    </li>
                    <li class="nav-item">
                      <a href="filtrage.php?category=accessories" class="nav-link">Accessories</a>
                    </li>
                  </ul>
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </header>

    <section class="py-5">
      <div class="container-fluid">
        <div class="row">
          <!-- Sidebar Filter -->
          <aside class="sidebar-filter col-md-2">
            <div class="card mb-4">
              <div class="card-body">
                <h5 class="card-title">Filter Products</h5>
                <form action="filtrage.php" method="GET" id="filter-form">
                  <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                      <option value="all">All Categories</option>
                      <?php if ($categories) while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php if($category == $cat['category']) echo 'selected'; ?>>
                          <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Subcategory</label>
                    <select name="subcategory" class="form-select">
                      <option value="all">All Subcategories</option>
                      <?php if ($subcategories) while($sub = $subcategories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($sub['subcategory']); ?>" <?php if($subcategory == $sub['subcategory']) echo 'selected'; ?>>
                          <?php echo htmlspecialchars($sub['subcategory']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Brand</label>
                    <select name="brand" class="form-select">
                      <option value="all">All Brands</option>
                      <?php if ($brands) while($b = $brands->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($b['brand']); ?>" <?php if($brand == $b['brand']) echo 'selected'; ?>>
                          <?php echo htmlspecialchars($b['brand']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Price Range</label>
                    <div id="price-slider"></div>
                    <div class="d-flex justify-content-between">
                      <span id="slider-min"></span>
                      <span id="slider-max"></span>
                    </div>
                    <input type="hidden" name="min_price" id="min_price" value="<?php echo $min_price; ?>">
                    <input type="hidden" name="max_price" id="max_price" value="<?php echo $max_price; ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Sort by Price</label>
                    <select name="sort_price" class="form-select">
                      <option value="none" <?php if ($sort_price === 'none') echo 'selected'; ?>>None</option>
                      <option value="asc" <?php if ($sort_price === 'asc') echo 'selected'; ?>>Price: Low to High</option>
                      <option value="desc" <?php if ($sort_price === 'desc') echo 'selected'; ?>>Price: High to Low</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>
              </div>
            </div>
          </aside>
          <!-- Product Grid -->
          <main class="col-md-10">
            <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
              <?php
              if ($result && $result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
              ?>
                <div class="col">
                  <div class="product-item">
                    <!-- Example badge, you can add logic for discounts if you add a badge field -->
                    <a href="#" class="btn-wishlist"><svg width="24" height="24"><use xlink:href="#heart"></use></svg></a>
                    <figure>
                      <img src="<?php echo findProductImage($row['name']); ?>" class="tab-image" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    </figure>
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <span class="qty"><?php echo htmlspecialchars($row['brand']); ?> | <?php echo htmlspecialchars($row['subcategory']); ?></span>
                    <span class="price">$<?php echo number_format($row['price'], 2); ?></span>
                    <form action="ajouter_panier.php" method="POST">
                    <div class="d-flex align-items-center justify-content-between">
                      <div class="input-group product-qty">
                          <span class="input-group-btn">
                            
                              <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                              <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                              <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                              <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                                <svg width="16" height="16"><use xlink:href="#minus"></use></svg>
                              </button>
                          </span>
                          <input type="text" id="quantity" name="quantity" class="form-control input-number" value="1">
                          <span class="input-group-btn">
                              <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                  <svg width="16" height="16"><use xlink:href="#plus"></use></svg>
                              </button>
                          </span>
                      </div>
                      <input type="submit" class="btn btn-primary" value="Add to Cart" name="add">
                    </div>
                  </div>
                  </form>
                </div>
              <?php
                  }
              } else {
                  echo '<div class="col-12"><p class="text-center">No products found matching your criteria.</p></div>';
              }
              ?>
            </div>
            <!-- / product-grid -->
          </main>
        </div>
      </div>
    </section>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
    <!-- noUiSlider CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css">
    <!-- noUiSlider JS -->
    <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var slider = document.getElementById('price-slider');
      if (slider) {
        noUiSlider.create(slider, {
          start: [<?php echo $min_price; ?>, <?php echo $max_price; ?>],
          connect: true,
          range: {
            'min': 0,
            'max': 200
          }
        });
        slider.noUiSlider.on('update', function(values, handle) {
          document.getElementById('min_price').value = Math.round(values[0]);
          document.getElementById('max_price').value = Math.round(values[1]);
          document.getElementById('slider-min').innerText = '$' + Math.round(values[0]);
          document.getElementById('slider-max').innerText = '$' + Math.round(values[1]);
        });
      }
    });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>

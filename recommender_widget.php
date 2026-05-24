<?php
/**
 * Glow-E Recommender Widget
 * 
 * Self-contained widget for displaying personalized product recommendations
 * from the FastAPI recommendation engine.
 * 
 * Behavior:
 * - Checks if user is logged in via $_SESSION['user_id']
 * - Silently returns if not logged in
 * - Fetches personalized recommendations via cURL
 * - Fails gracefully on any error (no exception thrown, no output)
 * - Renders cards matching the "Trending Products" section style
 * - Uses same image lookup and Add to Cart functionality as filtrage.php
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Exit silently if user is not logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

// Configuration
$api_base_url = 'http://localhost:8000';
$api_endpoint = '/recommend/' . intval($_SESSION['user_id']);
$api_timeout = 3;
$top_n = 10;

// Query parameter for number of recommendations
$api_url = $api_base_url . $api_endpoint . '?top_n=' . $top_n;

try {
    // Initialize cURL
    $ch = curl_init();
    
    if (!$ch) {
        throw new Exception('Failed to initialize cURL');
    }
    
    // Configure cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $api_timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $api_timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if (is_resource($ch) || (class_exists('CurlHandle') && $ch instanceof CurlHandle)) {
        curl_close($ch);
    }
    
    // Handle cURL errors
    if ($curl_error) {
        throw new Exception('cURL Error: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        throw new Exception('HTTP ' . $http_code . ' response');
    }
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    if (!is_array($data) || !isset($data['recommendations'])) {
        throw new Exception('Invalid or missing recommendations in response');
    }
    
    $recommendations = $data['recommendations'];
    
    // Exit silently if no recommendations
    if (empty($recommendations) || !is_array($recommendations)) {
        return;
    }
    
    // Function to find product image with any extension (matching filtrage.php)
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
    
    // HTML rendering - matching Trending Products section structure
    ?>
    <section class="py-5">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex justify-content-between align-items-center mb-4">
              <h3 class="mb-0">Produits recommandés pour vous</h3>
              <?php if (isset($data['source'])): ?>
                <small class="text-muted">
                  (Source: <?php echo htmlspecialchars($data['source'], ENT_QUOTES, 'UTF-8'); ?>)
                </small>
              <?php endif; ?>
            </div>

            <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
              
              <?php foreach ($recommendations as $product): ?>
                <?php
                  // Safely extract product fields with defaults
                  $product_id = isset($product['id']) ? intval($product['id']) : null;
                  $product_nom = isset($product['nom']) ? htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8') : 'Unknown Product';
                  $product_prix = isset($product['prix']) ? floatval($product['prix']) : 0;
                  $product_nom_plain = isset($product['nom']) ? $product['nom'] : ''; // For findProductImage
                  $product_image_url = findProductImage($product_nom_plain);
                  $product_categorie = isset($product['categorie']) ? htmlspecialchars($product['categorie'], ENT_QUOTES, 'UTF-8') : '';
                  $product_sous_categorie = isset($product['sous_categorie']) ? htmlspecialchars($product['sous_categorie'], ENT_QUOTES, 'UTF-8') : '';
                  $product_marque = isset($product['marque']) ? htmlspecialchars($product['marque'], ENT_QUOTES, 'UTF-8') : '';
                  
                  // Build category display
                  $category_label = $product_categorie;
                  if (!empty($product_sous_categorie)) {
                    $category_label .= ' / ' . $product_sous_categorie;
                  }
                ?>
                
                <div class="col">
                  <div class="product-item">
                    <span class="badge bg-success badge-recommended position-absolute m-3">Recommended</span>
                    <a href="#" class="btn-wishlist" aria-label="Add to wishlist">
                      <svg width="24" height="24"><use xlink:href="#heart"></use></svg>
                    </a>
                    
                    <figure>
                      <a href="product.php?id=<?php echo intval($product_id); ?>" class="open-product-preview" title="<?php echo $product_nom; ?>">
                        <img src="<?php echo $product_image_url; ?>" class="tab-image" alt="<?php echo $product_nom; ?>">
                      </a>
                    </figure>
                    
                    <h3><?php echo $product_nom; ?></h3>
                    
                    <?php if (!empty($product_marque)): ?>
                      <span class="qty"><?php echo $product_marque; ?> | <?php echo $category_label; ?></span>
                    <?php else: ?>
                      <span class="qty"><?php echo $category_label; ?></span>
                    <?php endif; ?>
                    
                    <span class="price">$<?php echo number_format($product_prix, 2); ?></span>
                    
                    <form action="ajouter_panier.php" method="POST" class="ajax-cart-action">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="input-group product-qty">
                          <span class="input-group-btn">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="product_name" value="<?php echo $product_nom; ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product_prix; ?>">
                            <input type="hidden" name="add" value="1">
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
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
              
            </div>
            <!-- / product-grid -->
            
          </div>
        </div>
      </div>
    </section>
    <?php

} catch (Exception $e) {
    // Silently fail - log error if needed but never break the page
    // In production, you could log this to a file:
    // error_log('Recommender widget error: ' . $e->getMessage());
    return;
}

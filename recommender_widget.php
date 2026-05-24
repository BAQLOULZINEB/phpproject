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

// Debug logging helper
$debug_enabled = isset($_GET['debug_recommender']) && $_GET['debug_recommender'] === '1';
function debug_log($msg) {
    global $debug_enabled;
    if ($debug_enabled) {
        error_log("[Recommender Widget] " . $msg);
    }
}

// Define fallback rendering function early so it can be called
function renderRecommenderFallback($message, $source = null) {
    global $debug_enabled;
    debug_log("Rendering fallback: {$message}");
    ?>
    <section class="py-5">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="section-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
              <div>
                <h3 class="mb-0">Produits recommandés pour vous</h3>

              </div>
              <?php if ($source !== null): ?>
                <small class="text-muted mt-2 mt-md-0">(Source: <?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>)</small>
              <?php endif; ?>
            </div>
            <div class="alert alert-warning" role="alert">
              <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
}

debug_log("Session Status: " . (isset($_SESSION['user_id']) ? "User ID: " . $_SESSION['user_id'] : "Not logged in"));

// Exit gracefully if user is not logged in
if (!isset($_SESSION['user_id'])) {
    debug_log("User not logged in - rendering fallback");
    renderRecommenderFallback('Connectez-vous pour voir vos recommandations personnalisées.', null);
    return;
}

// Configuration
$user_id = intval($_SESSION['user_id']);
$api_base_url = 'http://localhost:8000';
$api_endpoint = '/recommend/' . $user_id;
$api_timeout = 8;  // Increased from 3 to 8 seconds for better reliability
$top_n = 10;

// Query parameter for number of recommendations
$api_url = $api_base_url . $api_endpoint . '?top_n=' . $top_n;

try {
    // Initialize cURL
    $ch = curl_init();
    
    if (!$ch) {
        throw new Exception('Failed to initialize cURL');
    }
    
    debug_log("cURL initialized - requesting: {$api_url}");
    
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
    $curl_errno = curl_errno($ch);
    
    debug_log("cURL Response: HTTP {$http_code}, errno={$curl_errno}");
    
    if (is_resource($ch) || (class_exists('CurlHandle') && $ch instanceof CurlHandle)) {
        curl_close($ch);
    }
    
    // Handle cURL errors
    if ($curl_error) {
        debug_log("cURL Error: {$curl_error}");
        throw new Exception('cURL Error: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        debug_log("HTTP Error {$http_code}: {$response}");
        throw new Exception('HTTP ' . $http_code . ' response');
    }
    
    if (empty($response)) {
        debug_log("Empty response from API");
        throw new Exception('Empty response from API');
    }
    
    debug_log("Response length: " . strlen($response));
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    if ($data === null) {
        debug_log("JSON decode failed: " . json_last_error_msg());
        throw new Exception('Invalid JSON response: ' . json_last_error_msg());
    }
    
    if (!is_array($data) || !isset($data['recommendations'])) {
        debug_log("Invalid response structure: " . print_r($data, true));
        throw new Exception('Invalid or missing recommendations in response');
    }
    
    $recommendations = $data['recommendations'];
    debug_log("Got " . count($recommendations) . " recommendations");
    
    // Exit gracefully if no recommendations
    if (empty($recommendations) || !is_array($recommendations)) {
        debug_log("No recommendations available");
        renderRecommenderFallback('Aucune recommandation disponible pour le moment.', isset($data['source']) ? $data['source'] : null);
        return;
    }
    
    // Function to find product image with any extension (matching filtrage.php)
    if (!function_exists('findProductImage')) {
        function findProductImage($productName, $imageUrl = '') {
            $imageDir = 'images/prod_images/';
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
            
            if (!empty($imageUrl)) {
                if (file_exists($imageUrl)) {
                    return $imageUrl;
                }
                if (file_exists($imageDir . $imageUrl)) {
                    return $imageDir . $imageUrl;
                }
            }

            foreach ($allowedExtensions as $ext) {
                $imagePath = $imageDir . $productName . '.' . $ext;
                if (file_exists($imagePath)) {
                    return $imagePath;
                }
            }
            
            // Return default image if no product image found
            return 'images/no-image.png';
        }
    }
    
    // HTML rendering - matching Trending Products section structure
    ?>
    <section class="py-5">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <div class="section-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
              <div>
                <h3 class="mb-0">Produits recommandés pour vous</h3>
                <div class="text-muted small">Suggestions personnalisées basées sur votre historique</div>
              </div>
              <?php if (isset($data['source'])): ?>
                <small class="text-muted mt-2 mt-md-0">
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
                  $product_image_url = findProductImage($product_nom_plain, isset($product['image_url']) ? $product['image_url'] : '');
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
    debug_log("Exception caught: " . $e->getMessage());
    // Render a fallback section instead of failing silently.
    // This ensures the user sees the recommendation area even when the API is unavailable.
    $error_msg = 'Impossible de charger les recommandations pour le moment. Réessayez plus tard.';
    if ($debug_enabled) {
        $error_msg .= ' [Debug: ' . $e->getMessage() . ']';
    }
    renderRecommenderFallback($error_msg, null);
}

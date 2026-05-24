<?php
session_start();
require_once 'connexionbd.php';

$conn = connectMaBasi();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit();
}

function findProductImage($productName) {
    $imageDir = 'images/prod_images/';
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    foreach ($allowedExtensions as $ext) {
        $imagePath = $imageDir . $productName . '.' . $ext;
        if (file_exists($imagePath)) {
            return $imagePath;
        }
    }
    return 'images/no-image.png';
}

$image = findProductImage($product['name']);
?>
<?php

// If request is AJAX, return only the modal inner HTML for insertion
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ?>
    <div class="product-preview-modal" role="dialog" aria-modal="true">
      <button class="product-preview-close" aria-label="Close">&times;</button>
      <div class="media-image">
        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
      </div>
      <div class="meta">
        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
        <div class="category"><?php echo htmlspecialchars($product['brand']); ?> · <?php echo htmlspecialchars($product['category']); ?></div>
        <div class="price">$<?php echo number_format($product['price'],2); ?></div>
        <div class="rating">★★★★☆ <span class="text-muted">(4.5)</span></div>
        <div class="availability text-success" style="margin-top:8px;">In stock</div>
        <div class="desc"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>

        <form action="ajouter_panier.php" method="POST" class="ajax-cart-action">
          <input type="hidden" name="product_id" value="<?php echo intval($product['id']); ?>">
          <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>">
          <input type="hidden" name="product_price" value="<?php echo floatval($product['price']); ?>">
          <div class="actions">
            <input type="number" name="quantity" value="1" min="1" class="form-control w-25">
            <button type="submit" name="add" class="btn btn-primary">Add to Cart</button>
            <a href="effectuer_commande.php?buy=1&product_id=<?php echo intval($product['id']); ?>" class="btn btn-outline-primary">Buy Now</a>
          </div>
        </form>
      </div>
    </div>
    <?php
    exit();
}

// Non-AJAX fallback: render a centered modal-like page so direct visits still look premium
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($product['name']); ?> - Glow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* Ensure page shows modal centered when opened directly */
    body { background: rgba(10,10,10,0.45); height:100vh; }
    .page-modal-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem; }
  </style>
</head>
<body>
  <div class="page-modal-wrap">
    <div class="product-preview-modal">
      <button class="product-preview-close" onclick="window.location.href='index.php'">&times;</button>
      <div class="media-image">
        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
      </div>
      <div class="meta">
        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
        <div class="category"><?php echo htmlspecialchars($product['brand']); ?> · <?php echo htmlspecialchars($product['category']); ?></div>
        <div class="price">$<?php echo number_format($product['price'],2); ?></div>
        <div class="rating">★★★★☆ <span class="text-muted">(4.5)</span></div>
        <div class="availability text-success" style="margin-top:8px;">In stock</div>
        <div class="desc"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>

        <form action="ajouter_panier.php" method="POST" class="ajax-cart-action">
          <input type="hidden" name="product_id" value="<?php echo intval($product['id']); ?>">
          <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>">
          <input type="hidden" name="product_price" value="<?php echo floatval($product['price']); ?>">
          <div class="actions">
            <input type="number" name="quantity" value="1" min="1" class="form-control w-25">
            <button type="submit" name="add" class="btn btn-primary">Add to Cart</button>
            <a href="effectuer_commande.php?buy=1&product_id=<?php echo intval($product['id']); ?>" class="btn btn-outline-primary">Buy Now</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/script.js"></script>
</body>
</html>

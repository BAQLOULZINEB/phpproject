<?php
/**
 * Products Management Component
 * Included in admin_dashboard_hub.php
 */

if (!function_exists('ensureProductsCreatedAt')) {
    function ensureProductsCreatedAt($conn) {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'created_at'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE products ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    function sanitizeFileName($fileName) {
        $fileName = preg_replace('/[^\pL\pN\s\-_\.]/u', '', $fileName);
        $fileName = preg_replace('/[\s]+/u', ' ', $fileName);
        $fileName = trim($fileName, ' _-.');
        return $fileName;
    }

    function fileExistsRelative($relativePath) {
        return file_exists(__DIR__ . '/../' . $relativePath);
    }

    function tryImageCandidates($productName, $directories, $extensions) {
        foreach ($directories as $directory) {
            foreach ($extensions as $ext) {
                $candidate = $directory . $productName . '.' . $ext;
                if (fileExistsRelative($candidate)) {
                    return $candidate;
                }
            }
        }
        return '';
    }

    function getProductImageSrc($product) {
        if (!empty($product['image_url']) && fileExistsRelative($product['image_url'])) {
            return $product['image_url'];
        }

        $rawName = $product['name'];
        $sanitizedName = sanitizeFileName($rawName);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $candidates = [
            'images/prod_images/',
            'images/',
        ];

        $rawImage = tryImageCandidates($rawName, $candidates, $allowedExtensions);
        if (!empty($rawImage)) {
            return $rawImage;
        }

        $sanitizedImage = tryImageCandidates($sanitizedName, $candidates, $allowedExtensions);
        if (!empty($sanitizedImage)) {
            return $sanitizedImage;
        }

        return 'images/product-thumb-1.png';
    }

    function handleProductImageUpload($file, $productName) {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return '';
        }

        $uploadDir = __DIR__ . '/../images/prod_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $baseName = sanitizeFileName($productName);
        if (empty($baseName)) {
            $baseName = 'product';
        }

        $fileName = $baseName . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        $count = 1;
        while (file_exists($targetPath)) {
            $fileName = $baseName . '-' . $count . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            $count++;
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'images/prod_images/' . $fileName;
        }

        return '';
    }
}

function runExportToMinio() {
    if (!function_exists('shell_exec')) {
        return;
    }

    $projectRoot = realpath(__DIR__ . '/..');
    $scriptPath = realpath($projectRoot . '/recommender/export_to_minio.py');
    if (!$scriptPath) {
        return;
    }

    $pythonCandidates = [
        $projectRoot . '/venv/Scripts/python.exe',
        $projectRoot . '/venv/Scripts/python',
        'py -3',
        'python',
    ];

    foreach ($pythonCandidates as $python) {
        $python = trim($python);
        if (empty($python)) {
            continue;
        }

        if (strpos($python, '/') !== false || strpos($python, '\\') !== false) {
            if (!file_exists($python)) {
                continue;
            }
        }

        $cmd = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
        $output = shell_exec($cmd);
        if ($output !== null) {
            file_put_contents($projectRoot . '/recommender/export_to_minio.log', date('c') . ' - ' . $cmd . '\n' . $output . '\n', FILE_APPEND);
            return;
        }
    }
}

// Ensure connection exists
if (!isset($basi)) {
    $basi = connectMaBasi();
}

ensureProductsCreatedAt($basi);

// Initialize variables
$edit_product = null;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add product
    if (isset($_POST['add_product'])) {
        $name = mysqli_real_escape_string($basi, $_POST['name']);
        $description = mysqli_real_escape_string($basi, $_POST['description']);
        $price = floatval($_POST['price']);
        $category = mysqli_real_escape_string($basi, $_POST['category']);
        $subcategory = mysqli_real_escape_string($basi, $_POST['subcategory']);
        $brand = mysqli_real_escape_string($basi, $_POST['brand']);
        $image_url = '';

        if (isset($_FILES['product_image'])) {
            $image_url = handleProductImageUpload($_FILES['product_image'], $name);
            $image_url = mysqli_real_escape_string($basi, $image_url);
        }

        $query = "INSERT INTO products (name, description, price, category, subcategory, brand, image_url) 
                  VALUES ('$name', '$description', $price, '$category', '$subcategory', '$brand', '$image_url')";
        mysqli_query($basi, $query);
        runExportToMinio();
        header("Location: admin_dashboard_hub.php?tab=products&action=added");
        exit();
    }

    // Delete product
    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        $query = "DELETE FROM products WHERE id = $product_id";
        mysqli_query($basi, $query);
        runExportToMinio();
        header("Location: admin_dashboard_hub.php?tab=products&action=deleted");
        exit();
    }

    // Edit product
    if (isset($_POST['edit_product'])) {
        $product_id = intval($_POST['product_id']);
        $query = "SELECT * FROM products WHERE id = $product_id";
        $result = mysqli_query($basi, $query);
        $edit_product = mysqli_fetch_assoc($result);
    }

    // Update product
    if (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $name = mysqli_real_escape_string($basi, $_POST['name']);
        $description = mysqli_real_escape_string($basi, $_POST['description']);
        $price = floatval($_POST['price']);
        $category = mysqli_real_escape_string($basi, $_POST['category']);
        $subcategory = mysqli_real_escape_string($basi, $_POST['subcategory']);
        $brand = mysqli_real_escape_string($basi, $_POST['brand']);
        $image_sql = '';

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = handleProductImageUpload($_FILES['product_image'], $name);
            if (!empty($uploadedImage)) {
                $uploadedImage = mysqli_real_escape_string($basi, $uploadedImage);
                $image_sql = ", image_url = '$uploadedImage'";
            }
        }

        $query = "UPDATE products SET 
                  name = '$name', description = '$description', price = $price, 
                  category = '$category', subcategory = '$subcategory', brand = '$brand' $image_sql 
                  WHERE id = $product_id";
        mysqli_query($basi, $query);
        runExportToMinio();
        header("Location: admin_dashboard_hub.php?tab=products&action=updated");
        exit();
    }
}

// Get categories, subcategories, brands for datalists
$categories = [];
$subcategories = [];
$brands = [];

$category_result = mysqli_query($basi, "SELECT DISTINCT category FROM products WHERE category != '' ORDER BY category ASC");
$subcategory_result = mysqli_query($basi, "SELECT DISTINCT subcategory FROM products WHERE subcategory != '' ORDER BY subcategory ASC");
$brand_result = mysqli_query($basi, "SELECT DISTINCT brand FROM products WHERE brand != '' ORDER BY brand ASC");

while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row['category'];
}
while ($row = mysqli_fetch_assoc($subcategory_result)) {
    $subcategories[] = $row['subcategory'];
}
while ($row = mysqli_fetch_assoc($brand_result)) {
    $brands[] = $row['brand'];
}

$selected_category = isset($_GET['filter_category']) ? mysqli_real_escape_string($basi, $_GET['filter_category']) : '';
$selected_brand = isset($_GET['filter_brand']) ? mysqli_real_escape_string($basi, $_GET['filter_brand']) : '';

$filter_sql = '';
if (!empty($selected_category) && $selected_category !== 'all') {
    $filter_sql .= " AND category = '$selected_category'";
}
if (!empty($selected_brand) && $selected_brand !== 'all') {
    $filter_sql .= " AND brand = '$selected_brand'";
}

// Get products
$query_products = "SELECT * FROM products WHERE 1=1" . $filter_sql . " ORDER BY created_at DESC, id DESC";
$result_products = mysqli_query($basi, $query_products);
?>

<!-- Success Messages -->
<?php if (isset($_GET['action'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php 
        if ($_GET['action'] === 'added') echo 'Product added successfully!';
        elseif ($_GET['action'] === 'updated') echo 'Product updated successfully!';
        elseif ($_GET['action'] === 'deleted') echo 'Product deleted successfully!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Add/Edit Product Form -->
<div class="card mb-4" style="border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <div class="card-header" style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
        <h5 class="mb-0">
            <?php echo $edit_product ? '<i class="fas fa-edit"></i> Edit Product' : '<i class="fas fa-plus"></i> Add New Product'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" 
                           placeholder="Enter product name" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description *</label>
                    <input type="text" name="description" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?>" 
                           placeholder="Enter description" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Price ($) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['price']) : ''; ?>" 
                           placeholder="0.00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category *</label>
                    <input list="category-options" type="text" name="category" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : ''; ?>" 
                           placeholder="Select category" required>
                    <datalist id="category-options">
                        <?php foreach ($categories as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subcategory *</label>
                    <input list="subcategory-options" type="text" name="subcategory" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['subcategory']) : ''; ?>" 
                           placeholder="Select subcategory" required>
                    <datalist id="subcategory-options">
                        <?php foreach ($subcategories as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Brand *</label>
                    <input list="brand-options" type="text" name="brand" class="form-control" 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['brand']) : ''; ?>" 
                           placeholder="Select brand" required>
                    <datalist id="brand-options">
                        <?php foreach ($brands as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="product_image" class="form-control" accept="image/*">
                </div>
                
                <?php if ($edit_product && (!empty($edit_product['image_url']) || !empty($edit_product['name']))): ?>
                    <div class="col-12">
                        <img src="<?php echo htmlspecialchars(getProductImageSrc($edit_product)); ?>" 
                             alt="Current image" class="img-thumbnail" style="max-height: 120px;">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3">
                <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" 
                        class="btn <?php echo $edit_product ? 'btn-warning' : 'btn-success'; ?>">
                    <i class="fas fa-<?php echo $edit_product ? 'save' : 'plus'; ?>"></i>
                    <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                </button>
                <?php if ($edit_product): ?>
                    <a href="admin_dashboard_hub.php?tab=products" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4" style="border: 1px solid #e5e7eb;">
    <div class="card-header" style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Products</h5>
    </div>
    <div class="card-body">
        <form action="admin_dashboard_hub.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="products">
            <div class="col-md-4">
                <label class="form-label">Filter by Category</label>
                <select name="filter_category" class="form-select">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" 
                                <?php echo ($selected_category === $option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Brand</label>
                <select name="filter_brand" class="form-select">
                    <option value="all">All Brands</option>
                    <?php foreach ($brands as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" 
                                <?php echo ($selected_brand === $option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Apply Filter
                </button>
                <a href="admin_dashboard_hub.php?tab=products" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_count = 0; while ($product = mysqli_fetch_assoc($result_products)): $row_count++; ?>
                <tr>
                    <td><small>#<?php echo $product['id']; ?></small></td>
                    <td>
                        <img src="<?php echo htmlspecialchars(getProductImageSrc($product)); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="img-thumbnail" style="max-height: 50px; width: auto;">
                    </td>
                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                    <td><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</td>
                    <td><strong>$<?php echo number_format($product['price'], 2); ?></strong></td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['brand']); ?></span></td>
                    <td><small><?php echo substr($product['created_at'] ?? '-', 0, 10); ?></small></td>
                    <td>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="edit_product" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="submit" name="delete_product" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete this product?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php if ($row_count === 0): ?>
        <div class="alert alert-info">No products found.</div>
    <?php endif; ?>
</div>

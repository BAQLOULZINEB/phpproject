<?php
session_start();
require "connexionbd.php";

// Vérifiez si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

$basi = connectMaBasi();
ensureProductsCreatedAt($basi);

// Initialisation des variables pour le formulaire de modification
$edit_product = null;
$edit_client = null;

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
    return file_exists(__DIR__ . '/' . $relativePath);
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

    // First try raw product name
    $rawImage = tryImageCandidates($rawName, $candidates, $allowedExtensions);
    if (!empty($rawImage)) {
        return $rawImage;
    }

    // Then try sanitized product name
    $sanitizedImage = tryImageCandidates($sanitizedName, $candidates, $allowedExtensions);
    if (!empty($sanitizedImage)) {
        return $sanitizedImage;
    }

    // Fallback placeholder that exists in the repo
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

    $uploadDir = __DIR__ . '/images/prod_images/';
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

// Gestion des produits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter un produit
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
        header("Location: interface_admin.php");
        exit();
    }

    // Supprimer un produit
    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        $query = "DELETE FROM products WHERE id = $product_id";
        mysqli_query($basi, $query);
        header("Location: interface_admin.php");
        exit();
    }

    // Préparer la modification d'un produit
    if (isset($_POST['edit_product'])) {
        $product_id = intval($_POST['product_id']);
        $query = "SELECT * FROM products WHERE id = $product_id";
        $result = mysqli_query($basi, $query);
        $edit_product = mysqli_fetch_assoc($result);
    }

    // Modifier un produit
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
        header("Location: interface_admin.php");
        exit();
    }

    // Supprimer un client
    if (isset($_POST['delete_client'])) {
        $client_id = intval($_POST['client_id']);
        $query = "DELETE FROM users WHERE ID = $client_id";
        mysqli_query($basi, $query);
        header("Location: interface_admin.php");
        exit();
    }

    // Préparer la modification d'un client
    if (isset($_POST['edit_client'])) {
        $client_id = intval($_POST['client_id']);
        $query = "SELECT * FROM users WHERE ID = $client_id";
        $result = mysqli_query($basi, $query);
        $edit_client = mysqli_fetch_assoc($result);
    }

    // Modifier un client
    if (isset($_POST['update_client'])) {
        $client_id = intval($_POST['client_id']);
        $name = mysqli_real_escape_string($basi, $_POST['name']);
        $prenom = mysqli_real_escape_string($basi, $_POST['prenom']);
        $email = mysqli_real_escape_string($basi, $_POST['email']);

        $query = "UPDATE users SET NOM = '$name', PRENOM = '$prenom', EMAIL = '$email' WHERE ID = $client_id";
        mysqli_query($basi, $query);
        header("Location: interface_admin.php");
        exit();
    }
}

// Récupérer les valeurs existantes pour les listes éditables
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

// Récupérer les produits, triés par date d'ajout la plus récente
$query_products = "SELECT * FROM products WHERE 1=1" . $filter_sql . " ORDER BY created_at DESC, id DESC";
$result_products = mysqli_query($basi, $query_products);

// Récupérer les clients
$query_clients = "SELECT ID, NOM, PRENOM, EMAIL FROM users WHERE role != 'admin'";
$result_clients = mysqli_query($basi, $query_clients);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Interface Administrateur</h1>

        <!-- Gestion des produits -->
        <h2>Gestion des Produits</h2>
        <?php if ($edit_product): ?>
            <!-- Formulaire de modification d'un produit -->
            <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($edit_product['description']); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($edit_product['price']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input list="category-options" type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit_product['category']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input list="subcategory-options" type="text" name="subcategory" class="form-control" value="<?php echo htmlspecialchars($edit_product['subcategory']); ?>" placeholder="Sous-catégorie" required>
                    </div>
                    <div class="col-md-3">
                        <input list="brand-options" type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($edit_product['brand']); ?>" placeholder="Marque" required>
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>
                    <?php if (!empty($edit_product['image_url']) || !empty($edit_product['name'])): ?>
                        <div class="col-12">
                            <img src="<?php echo htmlspecialchars(getProductImageSrc($edit_product)); ?>" alt="Image actuelle" class="img-fluid" style="max-height:120px;">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="update_product" class="btn btn-warning mt-3">Modifier le produit</button>
            </form>
        <?php else: ?>
            <!-- Formulaire d'ajout d'un produit -->
            <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Nom" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control" placeholder="Description" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Prix" required>
                    </div>
                    <div class="col-md-3">
                        <input list="category-options" type="text" name="category" class="form-control" placeholder="Catégorie" required>
                        <datalist id="category-options">
                            <?php foreach ($categories as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <input list="subcategory-options" type="text" name="subcategory" class="form-control" placeholder="Sous-catégorie" required>
                        <datalist id="subcategory-options">
                            <?php foreach ($subcategories as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <input list="brand-options" type="text" name="brand" class="form-control" placeholder="Marque" required>
                        <datalist id="brand-options">
                            <?php foreach ($brands as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-success mt-3">Ajouter le produit</button>
            </form>
        <?php endif; ?>

        <form action="" method="GET" class="row g-3 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label">Filtrer par catégorie</label>
                <select name="filter_category" class="form-select">
                    <option value="all">Toutes les catégories</option>
                    <?php foreach ($categories as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selected_category === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Filtrer par marque</label>
                <select name="filter_brand" class="form-select">
                    <option value="all">Toutes les marques</option>
                    <?php foreach ($brands as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($selected_brand === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Appliquer le filtre</button>
                <a href="interface_admin.php" class="btn btn-outline-secondary ms-2">Réinitialiser</a>
            </div>
        </form>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Catégorie</th>
                    <th>Dernièr arrivé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = mysqli_fetch_assoc($result_products)): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <img src="<?php echo htmlspecialchars(getProductImageSrc($product)); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height:50px;">
                        </td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['description']; ?></td>
                        <td><?php echo $product['price']; ?></td>
                        <td><?php echo $product['category']; ?></td>
                        <td><?php echo !empty($product['created_at']) ? $product['created_at'] : '-'; ?></td>
                        <td>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="edit_product" class="btn btn-warning btn-sm">Modifier</button>
                                <button type="submit" name="delete_product" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Gestion des clients -->
        <h2 class="mt-5">Gestion des Clients</h2>
        <?php if ($edit_client): ?>
            <!-- Formulaire de modification d'un client -->
            <form action="" method="POST" class="mb-4">
                <input type="hidden" name="client_id" value="<?php echo $edit_client['ID']; ?>">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" value="<?php echo $edit_client['NOM']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="prenom" class="form-control" value="<?php echo $edit_client['PRENOM']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" value="<?php echo $edit_client['EMAIL']; ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_client" class="btn btn-warning mt-3">Modifier le client</button>
            </form>
        <?php endif; ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result_clients)): ?>
                    <tr>
                        <td><?php echo $client['ID']; ?></td>
                        <td><?php echo $client['NOM']; ?></td>
                        <td><?php echo $client['PRENOM']; ?></td>
                        <td><?php echo $client['EMAIL']; ?></td>
                        <td>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="client_id" value="<?php echo $client['ID']; ?>">
                                <button type="submit" name="edit_client" class="btn btn-warning btn-sm">Modifier</button>
                                <button type="submit" name="delete_client" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
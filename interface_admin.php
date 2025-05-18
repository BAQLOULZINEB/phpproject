<?php
session_start();
require "connexionbd.php";

// Vérifiez si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

$basi = connectMaBasi();

// Initialisation des variables pour le formulaire de modification
$edit_product = null;
$edit_client = null;

// Gestion des produits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter un produit
    if (isset($_POST['add_product'])) {
        $name = mysqli_real_escape_string($basi, $_POST['name']);
        $description = mysqli_real_escape_string($basi, $_POST['description']);
        $price = floatval($_POST['price']);
        $category = mysqli_real_escape_string($basi, $_POST['category']);

        $query = "INSERT INTO products (name, description, price, category) 
                  VALUES ('$name', '$description', $price, '$category')";
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

        $query = "UPDATE products SET 
                  name = '$name', description = '$description', price = $price, 
                  category = '$category' 
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

// Récupérer les produits
$query_products = "SELECT * FROM products";
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
            <form action="" method="POST" class="mb-4">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" value="<?php echo $edit_product['name']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control" value="<?php echo $edit_product['description']; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $edit_product['price']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="category" class="form-control" value="<?php echo $edit_product['category']; ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_product" class="btn btn-warning mt-3">Modifier le produit</button>
            </form>
        <?php else: ?>
            <!-- Formulaire d'ajout d'un produit -->
            <form action="" method="POST" class="mb-4">
                <div class="row">
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
                        <input type="text" name="category" class="form-control" placeholder="Catégorie" required>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-success mt-3">Ajouter le produit</button>
            </form>
        <?php endif; ?>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Catégorie</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = mysqli_fetch_assoc($result_products)): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['description']; ?></td>
                        <td><?php echo $product['price']; ?></td>
                        <td><?php echo $product['category']; ?></td>
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
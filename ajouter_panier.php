<?php
session_start();

// Vérifiez si le panier existe, sinon créez-le
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Modifier la quantité d'un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id > 0 && $quantity > 0) {
        if (isset($_SESSION['panier'][$product_id])) {
            $_SESSION['panier'][$product_id]['quantity'] = $quantity;
        }
    }

    // Redirigez vers index.php
    header('Location: index.php');
    exit();
}

// Supprimer un produit du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id > 0) {
        unset($_SESSION['panier'][$product_id]);
    }

    // Redirigez vers index.php
    header('Location: index.php');
    exit();
}

// Ajouter un produit au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : '';
    $product_price = isset($_POST['product_price']) ? floatval($_POST['product_price']) : 0.0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id > 0 && $quantity > 0) {
        // Vérifiez si le produit est déjà dans le panier
        if (isset($_SESSION['panier'][$product_id])) {
            $_SESSION['panier'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['panier'][$product_id] = [
                'name' => $product_name,
                'price' => $product_price,
                'quantity' => $quantity
            ];
        }
    }

    // Redirigez vers index.php
    header('Location: index.php');
    exit();
}
?>
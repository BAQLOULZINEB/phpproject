<?php
session_start();

// Vérifiez si le panier existe, sinon créez-le
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
}

function getCartSummary() {
    $count = 0;
    $total = 0.0;
    foreach ($_SESSION['panier'] as $item) {
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
        $price = isset($item['price']) ? floatval($item['price']) : 0.0;
        $count += $qty;
        $total += $price * $qty;
    }
    return ['count' => $count, 'total' => $total];
}

function renderCartItemsHtml() {
    if (empty($_SESSION['panier'])) {
        return '<li class="list-group-item d-flex justify-content-between lh-sm"><div><h6 class="my-0">Votre panier est vide</h6></div></li>';
    }

    $html = '';
    foreach ($_SESSION['panier'] as $product_id => $product) {
        $name = htmlspecialchars($product['name']);
        $price = number_format($product['price'], 2);
        $quantity = intval($product['quantity']);

        $html .= '<li class="list-group-item d-flex justify-content-between lh-sm">';
        $html .= '<div>';
        $html .= '<h6 class="my-0">' . $name . '</h6>';
        $html .= '<small class="text-muted">Prix: $' . $price . ' x ' . $quantity . '</small>';
        $html .= '</div>';
        $html .= '<div class="text-end">';
        $html .= '<form action="ajouter_panier.php" method="POST" class="d-inline ajax-cart-action">';
        $html .= '<input type="hidden" name="product_id" value="' . intval($product_id) . '">';
        $html .= '<input type="number" name="quantity" value="' . $quantity . '" min="1" class="form-control d-inline w-50">';
        $html .= '<button type="submit" name="update" class="btn btn-sm btn-success">Modifier</button>';
        $html .= '</form> ';
        $html .= '<form action="ajouter_panier.php" method="POST" class="d-inline ajax-cart-action">';
        $html .= '<input type="hidden" name="product_id" value="' . intval($product_id) . '">';
        $html .= '<button type="submit" name="delete" class="btn btn-sm btn-danger">Supprimer</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</li>';
    }

    return $html;
}

function renderCartPanelHtml() {
    $summary = getCartSummary();
    $cartCount = $summary['count'];
    $cartTotal = number_format($summary['total'], 2);

    $html = '<div class="order-md-last">';
    $html .= '<h4 class="d-flex justify-content-between align-items-center mb-3">';
    $html .= '<span class="text-primary">Panier</span>';
    $html .= '<span id="cart-sidebar-count" class="badge bg-primary rounded-pill">' . $cartCount . '</span>';
    $html .= '</h4>';
    $html .= '<ul class="list-group mb-3" id="cart-items-list">';
    $html .= renderCartItemsHtml();
    $html .= '<li class="list-group-item d-flex justify-content-between">';
    $html .= '<span>Total (USD)</span>';
    $html .= '<strong id="cart-total-value">$' . $cartTotal . '</strong>';
    $html .= '</li>';
    $html .= '</ul>';
    $html .= '<div class="d-flex justify-content-between mt-4">';
    $html .= '<a href="effectuer_commande.php" class="btn btn-success">Effectuer commande</a>';
    $html .= '<a href="filtrage.php" class="btn btn-primary">Continuer les achats</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
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

    $summary = getCartSummary();
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour.',
            'cart_count' => $summary['count'],
            'cart_total' => $summary['total'],
            'cart_items_html' => renderCartItemsHtml(),
            'cart_panel_html' => renderCartPanelHtml()
        ]);
        exit();
    }

    header('Location: index.php');
    exit();
}

// Supprimer un produit du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id > 0) {
        unset($_SESSION['panier'][$product_id]);
    }

    $summary = getCartSummary();
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Article supprimé du panier.',
            'cart_count' => $summary['count'],
            'cart_total' => $summary['total'],
            'cart_items_html' => renderCartItemsHtml(),
            'cart_panel_html' => renderCartPanelHtml()
        ]);
        exit();
    }
    header('Location: index.php');
    exit();
}

// Ajouter un produit au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add']) || (isset($_POST['product_id']) && isset($_POST['product_name']) && isset($_POST['product_price'])))) {
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

    $summary = getCartSummary();
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Produit ajouté au panier.',
            'cart_count' => $summary['count'],
            'cart_total' => $summary['total'],
            'cart_items_html' => renderCartItemsHtml(),
            'cart_panel_html' => renderCartPanelHtml()
        ]);
        exit();
    }

    header('Location: index.php');
    exit();
}
?>
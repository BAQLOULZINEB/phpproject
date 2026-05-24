<?php
session_start();
require "connexionbd.php";

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

$basi = connectMaBasi();

// Get statistics
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($basi, "SELECT COUNT(*) AS count FROM users WHERE role != 'admin'"))['count'],
    'products' => mysqli_fetch_assoc(mysqli_query($basi, "SELECT COUNT(*) AS count FROM products"))['count'],
    'orders' => mysqli_fetch_assoc(mysqli_query($basi, "SELECT COUNT(*) AS count FROM commandes"))['count'],
    'revenue' => mysqli_fetch_assoc(mysqli_query($basi, "SELECT SUM(total) AS total FROM commandes"))['total'] ?? 0,
    'pending_orders' => mysqli_fetch_assoc(mysqli_query($basi, "SELECT COUNT(*) AS count FROM commandes WHERE statut = 'pending'"))['count'],
    'recent_orders' => [],
    'top_products' => [],
];

// Get recent orders
$recent = mysqli_query($basi, "SELECT c.*, u.EMAIL FROM commandes c LEFT JOIN users u ON c.user_id = u.ID ORDER BY c.date DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($recent)) {
    $stats['recent_orders'][] = $row;
}

// Get top products
$top = mysqli_query($basi, "SELECT p.*, SUM(lc.quantite) AS total_qty FROM products p JOIN ligne_commande lc ON p.id = lc.id_produit GROUP BY p.id ORDER BY total_qty DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($top)) {
    $stats['top_products'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Stats - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f8ef7;
            --secondary: #9b6bff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .stat-card.secondary { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .stat-card.secondary .stat-value { color: var(--success); }
        .stat-card.warning .stat-value { color: var(--warning); }
        .stat-card.danger .stat-value { color: var(--danger); }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .dashboard-link {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dashboard-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(79, 142, 247, 0.3);
            color: white;
            text-decoration: none;
        }

        .section {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section h3 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .table {
            margin-bottom: 0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-chart-bar"></i> Quick Stats
                    </h1>
                    <p class="mb-0">Real-time overview of your store</p>
                </div>
                <div>
                    <a href="admin_dashboard_hub.php?tab=dashboard" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-label"><i class="fas fa-users"></i> Total Users</div>
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card secondary">
                    <div class="stat-label"><i class="fas fa-box"></i> Total Products</div>
                    <div class="stat-value"><?php echo $stats['products']; ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-label"><i class="fas fa-shopping-cart"></i> Total Orders</div>
                    <div class="stat-value"><?php echo $stats['orders']; ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card danger">
                    <div class="stat-label"><i class="fas fa-dollar-sign"></i> Total Revenue</div>
                    <div class="stat-value">$<?php echo number_format($stats['revenue'], 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Dashboard CTA -->
        <div class="section text-center">
            <h3><i class="fas fa-rocket"></i> Access Full Analytics Dashboard</h3>
            <p class="text-muted mb-4">
                Get advanced insights with interactive charts, ML recommendations, and detailed analytics powered by Streamlit
            </p>
            <a href="http://localhost:8501" target="_blank" class="dashboard-link">
                <i class="fas fa-external-link-alt"></i>
                Open Streamlit Dashboard
            </a>
            <p class="text-muted mt-3"><small>Opens in a new tab • Requires Python backend running on port 8501</small></p>
        </div>

        <!-- Recent Orders -->
        <div class="section">
            <h3><i class="fas fa-history"></i> Recent Orders</h3>
            <?php if (!empty($stats['recent_orders'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_orders'] as $order): ?>
                                <tr>
                                    <td><small>#<?php echo $order['id']; ?></small></td>
                                    <td><?php echo htmlspecialchars($order['EMAIL'] ?? 'Unknown'); ?></td>
                                    <td><strong>$<?php echo number_format($order['total'], 2); ?></strong></td>
                                    <td><small><?php echo substr($order['date'], 0, 10); ?></small></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($order['statut'] === 'completed') ? 'success' : 
                                                 (($order['statut'] === 'pending') ? 'warning' : 'secondary');
                                        ?>">
                                            <?php echo ucfirst($order['statut']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p>No orders yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Products -->
        <div class="section">
            <h3><i class="fas fa-star"></i> Top Selling Products</h3>
            <?php if (!empty($stats['top_products'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_products'] as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><strong><?php echo $product['total_qty']; ?></strong></td>
                                    <td>$<?php echo number_format($product['price'] * $product['total_qty'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p>No sales data yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require "connexionbd.php";

// Vérifiez si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

$basi = connectMaBasi();
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Glow-E</title>
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

        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-tabs {
            border-bottom: 3px solid #e5e7eb;
            gap: 1rem;
            flex-wrap: nowrap;
        }

        .nav-link {
            color: #6b7280;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-link:hover {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: none;
        }

        .tab-pane {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 142, 247, 0.3);
            color: white;
            text-decoration: none;
        }

        .metrics-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metrics-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .metrics-card h5 {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .metrics-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .iframe-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: 800px;
            width: 100%;
            border: 1px solid #e5e7eb;
        }

        .iframe-container iframe {
            border: none;
            border-radius: 0.75rem;
        }

        .quick-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .quick-link-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-link-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(79, 142, 247, 0.05);
        }

        .product-section, .clients-section {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <h1>
                <i class="fas fa-crown"></i>
                Admin Control Center
            </h1>
            <small>Glow-E Management System</small>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" 
                   href="?tab=products" 
                   id="products-tab" 
                   role="tab">
                    <i class="fas fa-box"></i> Products Management
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>" 
                   href="?tab=dashboard" 
                   id="dashboard-tab" 
                   role="tab">
                    <i class="fas fa-chart-line"></i> Analytics Dashboard
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $current_tab === 'clients' ? 'active' : ''; ?>" 
                   href="?tab=clients" 
                   id="clients-tab" 
                   role="tab">
                    <i class="fas fa-users"></i> Clients Management
                </a>
            </li>
            <li class="ms-auto">
                <a href="index.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Store
                </a>
            </li>
            <li>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Products Tab -->
            <div class="tab-pane fade <?php echo $current_tab === 'products' ? 'show active' : ''; ?>" 
                 id="products-tab-content" role="tabpanel">
                <div class="product-section">
                    <h2><i class="fas fa-box"></i> Products Management</h2>
                    <?php include 'components/products_management.php'; ?>
                </div>
            </div>

            <!-- Dashboard Tab -->
            <div class="tab-pane fade <?php echo $current_tab === 'dashboard' ? 'show active' : ''; ?>" 
                 id="dashboard-tab-content" role="tabpanel">
                <div class="quick-links">
                    <a href="http://localhost:8501" target="_blank" class="quick-link-btn">
                        <i class="fas fa-external-link-alt"></i> Open Full Dashboard
                    </a>
                    <a href="admin_dashboard_stats.php" class="quick-link-btn">
                        <i class="fas fa-chart-bar"></i> Quick Stats
                    </a>
                </div>
                
                <h2><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
                
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <?php
                        $users_result = mysqli_query($basi, "SELECT COUNT(*) AS total FROM users WHERE role != 'admin'");
                        $users_data = mysqli_fetch_assoc($users_result);
                        
                        $products_result = mysqli_query($basi, "SELECT COUNT(*) AS total FROM products");
                        $products_data = mysqli_fetch_assoc($products_result);
                        
                        $orders_result = mysqli_query($basi, "SELECT COUNT(*) AS total FROM commandes");
                        $orders_data = mysqli_fetch_assoc($orders_result);
                        
                        $revenue_result = mysqli_query($basi, "SELECT SUM(total) AS total FROM commandes");
                        $revenue_data = mysqli_fetch_assoc($revenue_result);
                    ?>
                    <div class="col-md-3">
                        <div class="metrics-card">
                            <h5><i class="fas fa-users"></i> Total Users</h5>
                            <div class="value"><?php echo $users_data['total']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metrics-card" style="border-left-color: var(--success);">
                            <h5><i class="fas fa-box"></i> Total Products</h5>
                            <div class="value" style="color: var(--success);"><?php echo $products_data['total']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metrics-card" style="border-left-color: var(--warning);">
                            <h5><i class="fas fa-shopping-cart"></i> Total Orders</h5>
                            <div class="value" style="color: var(--warning);"><?php echo $orders_data['total']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metrics-card" style="border-left-color: #10b981;">
                            <h5><i class="fas fa-dollar-sign"></i> Total Revenue</h5>
                            <div class="value" style="color: #10b981;">$<?php echo number_format($revenue_data['total'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Streamlit Embedded Dashboard -->
                <div class="card" style="border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border: none;">
                        <h5 class="mb-0"><i class="fas fa-chart-area"></i> Advanced Analytics</h5>
                        <small>Powered by Streamlit ML Dashboard</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="alert alert-info mb-3 m-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Pro Tip:</strong> Click "Open Full Dashboard" to access the interactive Streamlit dashboard with detailed insights, visualizations, and ML-powered recommendations.
                        </div>
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">The dashboard provides:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><i class="fas fa-chart-pie text-primary"></i> User behavior analysis</p>
                                </div>
                                <div class="col-md-4">
                                    <p><i class="fas fa-brain text-success"></i> ML-powered recommendations</p>
                                </div>
                                <div class="col-md-4">
                                    <p><i class="fas fa-trending-up text-warning"></i> Sales trends & forecasting</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clients Tab -->
            <div class="tab-pane fade <?php echo $current_tab === 'clients' ? 'show active' : ''; ?>" 
                 id="clients-tab-content" role="tabpanel">
                <div class="product-section">
                    <h2><i class="fas fa-users"></i> Clients Management</h2>
                    <?php include 'components/clients_management.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

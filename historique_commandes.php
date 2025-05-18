<?php
session_start();
require "connexionbd.php";

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$id_client = $_SESSION['user_id'];
$basi = connectMaBasi();

// Récupérer les commandes de l'utilisateur avec le total calculé
$query = "
    SELECT c.id_commande, c.date_commande, c.statut_commande, 
           (SELECT SUM(lc.quantite * lc.prix_unitaire) 
            FROM ligne_commande lc 
            WHERE lc.id_commande = c.id_commande) AS total
    FROM commande c
    WHERE c.id_client = '$id_client'
    ORDER BY c.date_commande DESC";
$result = mysqli_query($basi, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fdfcfb, #f8f9fa);
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #FFC43F, #FFB800);
            color: white;
            font-size: 24px;
            font-weight: bold;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .card-body {
            background: rgba(255, 255, 255, 0.8);
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .table {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            overflow: hidden;
        }

        .table th {
            background: rgba(255, 196, 63, 0.8);
            color: white;
            text-align: center;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #FFC107;
            color: #000;
        }

        .badge-secondary {
            background-color: #6c757d;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFC43F, #FFB800);
            border: none;
            font-weight: bold;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #FFB800, #FFC43F);
            box-shadow: 0 4px 10px rgba(255, 196, 63, 0.5);
        }

        .alert-info {
            background: rgba(255, 196, 63, 0.2);
            color: #FFC43F;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header text-center">
                        <h3>Historique des commandes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($commande = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $commande['id_commande']; ?></td>
                                            <td><?php echo $commande['date_commande']; ?></td>
                                            <td>
                                                <?php if ($commande['statut_commande'] === 'Payée'): ?>
                                                    <span class="badge badge-success">Payée</span>
                                                <?php elseif ($commande['statut_commande'] === 'En attente'): ?>
                                                    <span class="badge badge-warning">En attente</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($commande['statut_commande']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($commande['total'], 2); ?> €</td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                Vous n'avez pas encore passé de commande.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="index.php" class="btn btn-primary">Retourner à la boutique</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
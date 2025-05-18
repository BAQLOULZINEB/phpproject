<?php
session_start();
require "connexionbd.php";

// Vérifiez si le mode de paiement est défini dans la session
if (!isset($_SESSION['mode_paiement'])) {
    echo "Mode de paiement non défini. Veuillez revenir à la page précédente.";
    exit();
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Vérifiez si une commande est en cours
if (!isset($_SESSION['id_commande'])) {
    header("Location: index.php");
    exit();
}

$id_commande = intval($_SESSION['id_commande']);
$mode_paiement = trim($_SESSION['mode_paiement']); // Récupérer le mode de paiement
$basi = connectMaBasi();

// Définir le statut en fonction du mode de paiement
if ($mode_paiement === 'Carte bancaire') {
    $statut_commande = 'Payée';
} elseif ($mode_paiement === 'Paiement à la livraison') {
    $statut_commande = 'En attente';
} else {
    $statut_commande = 'Inconnu'; // Par défaut, si le mode de paiement est invalide
}

// Mettre à jour le statut de la commande
$query = "UPDATE commande SET statut_commande = '$statut_commande' WHERE id_commande = $id_commande";
if (mysqli_query($basi, $query)) {
    $message = "Votre commande a été confirmée avec succès. Mode de paiement : $mode_paiement.";
} else {
    $message = "Une erreur s'est produite lors de la confirmation de votre commande : " . mysqli_error($basi);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white text-center">
                        <h3>Commande confirmée</h3>
                    </div>
                    <div class="card-body text-center">
                        <p><?php echo $message; ?></p>
                        <a href="index.php" class="btn btn-primary mt-3">Retourner à la boutique</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require "connexionbd.php";



// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Vérifiez si le panier existe et n'est pas vide
if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    header("Location: index.php");
    exit();
}

$message = "";

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = $_SESSION['user_id']; // ID de l'utilisateur connecté
    $mode_paiement = $_POST['mode_paiement']; // Récupérer le mode de paiement depuis le formulaire
    $_SESSION['mode_paiement'] = $mode_paiement; // Enregistrer le mode de paiement dans la session
    $statut_commande = "En attente"; // Statut initial de la commande
    $basi = connectMaBasi();

    // Insérer la commande dans la table `commande`
    $query_commande = "INSERT INTO commande (id_client, date_commande, statut_commande) VALUES ('$id_client', NOW(), '$statut_commande')";
    if (mysqli_query($basi, $query_commande)) {
        $id_commande = mysqli_insert_id($basi); // Récupérer l'ID de la commande insérée
        $_SESSION['id_commande'] = $id_commande; // Enregistrer l'ID de la commande dans la session

        // Insérer les lignes de commande dans la table `ligne_commande`
        foreach ($_SESSION['panier'] as $id_produit => $produit) {
            $quantite = $produit['quantity'];
            $prix_unitaire = $produit['price'];

            $query_ligne_commande = "INSERT INTO ligne_commande (id_commande, id_produit, quantite, prix_unitaire) 
                                     VALUES ('$id_commande', '$id_produit', '$quantite', '$prix_unitaire')";
            mysqli_query($basi, $query_ligne_commande);
        }

        // Vider le panier après la commande
        unset($_SESSION['panier']);
        header("Location: confirmation.php"); // Rediriger vers la page de confirmation
        exit();
    } else {
        $message = "Une erreur s'est produite lors de la finalisation de la commande.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Effectuer commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h3>Effectuer commande</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="effectuer_commande.php" method="POST">
                            <div class="mb-3">
                                <label for="mode_paiement" class="form-label">Choisissez un mode de paiement :</label>
                                <select name="mode_paiement" id="mode_paiement" class="form-select" required>
                                    <option value="Carte bancaire">Carte bancaire</option>
                                    <option value="Paiement à la livraison">Paiement à la livraison</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Confirmer la commande</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-link">Continuer les achats</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
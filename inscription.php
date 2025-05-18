<?php 
require "connexionbd.php"; // Assurez-vous que ce fichier contient la connexion à la BDD
$message = ""; // Variable pour stocker les messages d'erreur/succès

if (isset($_POST['inscription'])) {
    $basi = connectMaBasi();
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = htmlspecialchars($_POST['role']);

    // Validation des champs
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "⚠️ Tous les champs sont obligatoires !";
    } elseif (strlen($password) < 8) {
        $message = "⚠️ Le mot de passe doit contenir au moins 8 caractères !";
    } elseif (strlen($password) > 20 || strlen($nom) > 50) {
        $message = "⚠️ Le mot de passe ou le nom est trop long";
    } elseif ($password !== $confirm_password) {
        $message = "⚠️ Les mots de passe ne correspondent pas";
    } elseif (empty($role)) {
        $message = "⚠️ Vous devez sélectionner un rôle !";
    } else {
        // Vérification si l'email existe déjà
        $check = "SELECT email FROM users WHERE EMAIL='$email'";
        $result = mysqli_query($basi, $check) or die(mysqli_error($basi));
        if (mysqli_num_rows($result) > 0) {
            $message = "⚠️ Cet email est déjà utilisé !";
        } else {
            // Insertion dans la base de données
            $insert = "INSERT INTO users (NOM, PRENOM, EMAIL, PASSWORD, role) 
                       VALUES ('$nom', '$prenom', '$email', '$password', '$role')";
            $result = mysqli_query($basi, $insert);

            if ($result) {
                $message = "✅ Inscription réussie !";
            } else {
                $message = "⚠️ Une erreur s'est produite : " . mysqli_error($basi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa, #e0e0e0);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            overflow: auto; /* Allow scrolling if needed */
        }

        .register-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 100%;
            max-width: 600px; /* Medium size */
            padding: 50px; /* Reduced padding */
            text-align: center;
        }

        .register-header h2 {
            color: #333333;
            font-size: 32px; /* Medium font size */
            margin-bottom: 15px; /* Reduced margin */
            font-weight: 600;
        }

        .register-header p {
            color: #666666;
            font-size: 16px; /* Medium font size */
            margin-bottom: 30px; /* Reduced margin */
        }

        .form-group {
            margin-bottom: 25px; /* Reduced margin */
        }

        .input-group {
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: inset 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .input-group i {
            padding: 14px; /* Medium padding */
            background: rgba(255, 255, 255, 0.2);
            color: #555;
            min-width: 50px; /* Medium width */
            text-align: center;
        }

        .form-control,
        select {
            width: 100%;
            padding: 14px; /* Medium padding */
            border: none;
            outline: none;
            font-size: 16px; /* Medium font size */
            background-color: transparent;
            color: #333;
        }

        .form-control:focus,
        select:focus {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .btn {
            width: 100%;
            padding: 16px; /* Medium padding */
            background: linear-gradient(135deg, #FFC43F, #FFB800);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px; /* Medium font size */
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 196, 63, 0.4);
        }

        .btn:hover {
            background: linear-gradient(135deg, #FFB800, #FFC43F);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 196, 63, 0.6);
        }

        .message {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .register-footer {
            margin-top: 20px;
            color: #666666;
            font-size: 14px;
        }

        .register-footer a {
            color: #FFC43F;
            text-decoration: none;
            font-weight: bold;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Lien Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Lien Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Inscription</h2>
            <p>Créez un compte pour accéder à nos services</p>
        </div>
        
        <?php if(!empty($message)): ?>
            <div class="message error"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" placeholder="Nom" name="nom" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user-tag"></i>
                    <input type="text" class="form-control" placeholder="Prénom" name="prenom" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" class="form-control" placeholder="Adresse e-mail" name="email" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" placeholder="Mot de passe" name="password" required minlength="8" maxlength="20">
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" placeholder="Confirmez le mot de passe" name="confirm_password" required minlength="8" maxlength="20">
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user-cog"></i>
                    <select class="form-control" name="role" required>
                        <option value="">-- Sélectionnez un rôle --</option>
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn" name="inscription">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
        </form>
        
        <div class="register-footer">
            Déjà inscrit ? <a href="connexion.php">Connectez-vous</a>
        </div>
    </div>
</body>
</html>
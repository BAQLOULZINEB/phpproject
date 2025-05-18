<?php
session_start();
require "connexionbd.php";

$message = "";

if (isset($_POST['success'])) {
    // Vérification des champs remplis
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = htmlspecialchars($_POST['email']);
        $password = $_POST['password']; // Pas de hachage ici si les mots de passe sont stockés en clair

        $basi = connectMaBasi();
        $query = "SELECT * FROM users WHERE EMAIL='$email' AND PASSWORD='$password'";
        $result = mysqli_query($basi, $query);

        if (mysqli_num_rows($result) > 0) {
            // Connexion réussie
            $user = mysqli_fetch_assoc($result);

            // Stocker les informations utilisateur dans la session
            $_SESSION['user_id'] = $user['ID']; // ID utilisateur
            $_SESSION['user_email'] = $user['EMAIL'];
            $_SESSION['user_name'] = $user['NOM']; // Nom de l'utilisateur
            $_SESSION['role'] = $user['role']; // Rôle de l'utilisateur

            // Redirection en fonction du rôle
            if ($user['role'] === 'admin') {
                header("Location: interface_admin.php"); // Rediriger vers l'interface admin
            } else {
                header("Location: index.php"); // Rediriger vers la page d'accueil pour les utilisateurs normaux
            }
            exit();
        } else {
            $message = "Adresse e-mail ou mot de passe incorrect !";
        }
    } else {
        $message = "Veuillez remplir tous les champs !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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
            overflow: hidden;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 100%;
            max-width: 600px; /* Increased max-width */
            padding: 60px; /* Increased padding */
            text-align: center;
        }

        .login-header h2 {
            color: #333333;
            font-size: 36px; /* Increased font size */
            margin-bottom: 20px; /* Increased margin */
            font-weight: 600;
        }

        .login-header p {
            color: #666666;
            font-size: 18px; /* Increased font size */
            margin-bottom: 40px; /* Increased margin */
        }

        .form-group {
            margin-bottom: 30px; /* Increased margin */
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
            padding: 16px; /* Increased padding */
            background: rgba(255, 255, 255, 0.2);
            color: #555;
            min-width: 60px; /* Increased width */
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 16px; /* Increased padding */
            border: none;
            outline: none;
            font-size: 18px; /* Increased font size */
            background-color: transparent;
            color: #333;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .btn {
            width: 100%;
            padding: 18px; /* Increased padding */
            background: linear-gradient(135deg, #FFC43F, #FFB800);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px; /* Increased font size */
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
            margin: 20px 0; /* Increased margin */
            padding: 15px; /* Increased padding */
            border-radius: 8px;
            font-size: 16px; /* Increased font size */
        }

        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .login-footer {
            margin-top: 30px; /* Increased margin */
            color: #666666;
            font-size: 16px; /* Increased font size */
        }

        .login-footer a {
            color: #FFC43F;
            text-decoration: none;
            font-weight: bold;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Lien Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Lien Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Connexion</h2>
            <p>Connectez-vous pour accéder à votre compte</p>
        </div>
        
        <?php if(!empty($message)): ?>
            <div class="message error"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" class="form-control" placeholder="Adresse e-mail" name="email" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" placeholder="Mot de passe" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="btn" name="success">Se connecter</button>
            
            <div class="login-footer">
                Vous n'avez pas de compte ? <a href="inscription.php">Inscrivez-vous</a>
            </div>
        </form>
    </div>
</body>
</html>
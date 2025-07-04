<?php
// login.php - Pas besoin de session_handler ici, car la page est publique
session_start();
// Si l'utilisateur est déjà connecté, on le redirige vers son tableau de bord
if (isset($_SESSION['user_id'])) {
    // Adapter le chemin de redirection ici aussi si nécessaire
    header('Location: /GestionMySoutenance/dashboard_etudiant.php'); 
    exit();
}
// Pas de 'header.php' car la page de login a une structure différente
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Gestion MySoutenance</title>
    <link rel="stylesheet" href="/GestionMySoutenance/assets/style.css"> 
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Connexion</h2>
        <?php
            // Afficher les messages d'erreur ou de statut
            if (isset($_GET['error'])) {
                echo '<p class="error">'.htmlspecialchars($_GET['error']).'</p>';
            }
            if (isset($_GET['reason']) && $_GET['reason'] == 'inactive') {
                echo '<p class="info">Votre session a expiré pour inactivité.</p>';
            }
        ?>
        
        <form action="/GestionMySoutenance/traitement/login_traitement.php" method="POST">
            <div class="form-group">
                <label for="login">Identifiant</label>
                <input type="text" id="login" name="login" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <a href="/GestionMySoutenance/forgot_password.php">Mot de passe oublié ?</a>
    </div>
</body>
</html>
<?php
// forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié - Gestion MySoutenance</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Mot de passe oublié</h2>
        <p>Veuillez entrer votre adresse e-mail. Un lien de réinitialisation vous y sera envoyé.</p>
        <?php
            if (isset($_GET['success'])) {
                echo '<p class="success">'.htmlspecialchars($_GET['success']).'</p>';
            }
            if (isset($_GET['error'])) {
                echo '<p class="error">'.htmlspecialchars($_GET['error']).'</p>';
            }
        ?>
        <form action="/traitement/forgot_password_traitement.php" method="POST">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Envoyer le lien</button>
        </form>
        <a href="/login.php">Retour à la connexion</a>
    </div>
</body>
</html>
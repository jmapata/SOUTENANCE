<?php
// Démarrer la session pour pouvoir gérer les redirections si l'utilisateur est déjà connecté
session_start();

// Si l'utilisateur est déjà connecté, on le redirige vers son tableau de bord
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Note : Idéalement, on redirige vers le tableau de bord spécifique à son rôle
    header('location: dashboard_admin.php'); // Pour le moment, on assume que c'est l'admin
    exit;
}

// Gérer l'affichage des messages d'erreur
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_credentials') {
        $error_message = 'Identifiant ou mot de passe incorrect.';
    } elseif ($_GET['error'] === 'missing_fields') {
        $error_message = 'Veuillez remplir tous les champs.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GestionMySoutenance</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .login-container { padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; }
        input { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 0.7rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Connexion</h2>
        <p>Veuillez entrer vos identifiants.</p>
        
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="traitement/login_traitement.php" method="POST">
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
    </div>

</body>
</html>
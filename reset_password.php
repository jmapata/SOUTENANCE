<?php
// reset_password.php
session_start();
require_once 'config/db_connect.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die("Jeton manquant.");
}

// Vérifier si le jeton est valide et non expiré
$stmt = $p_pdo->prepare("SELECT numero_utilisateur FROM utilisateur WHERE token_reset_mdp = :token AND date_expiration_token_reset > NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    die("Jeton invalide ou expiré. Veuillez refaire une demande.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Choisir un nouveau mot de passe</h2>
        <form action="/traitement/reset_password_traitement.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit">Réinitialiser</button>
        </form>
    </div>
</body>
</html>
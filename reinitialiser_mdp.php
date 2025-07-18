<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le Mot de Passe - ValidMaster</title>
    <link rel="stylesheet" href="assets/css/login_style.css">
</head>
<body>
    <div class="form-container">
        <h2>Réinitialiser votre mot de passe</h2>
        <p>Veuillez choisir un nouveau mot de passe sécurisé.</p>
        <form action="traitement/reinitialiser_mdp_traitement.php" method="POST">
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="btn">Réinitialiser le mot de passe</button>
        </form>
    </div>
</body>
</html>
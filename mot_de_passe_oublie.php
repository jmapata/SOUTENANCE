<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de Passe Oublié - ValidMaster</title>
    <link rel="stylesheet" href="assets/css/login_style.css"> </head>
<body>
    <div class="form-container">
        <h2>Mot de passe oublié ?</h2>
        <p>Veuillez entrer votre adresse email. Si un compte étudiant est associé, nous y enverrons un code de vérification.</p>
        <form action="traitement/demande_reset_mdp.php" method="POST">
            <div class="form-group">
                <label for="email">Email de connexion</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit" class="btn">Envoyer le code</button>
            <a href="login.php" class="back-link">Retour à la connexion</a>
        </form>
    </div>
</body>
</html>

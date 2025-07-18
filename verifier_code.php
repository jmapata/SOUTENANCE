<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification du Code - ValidMaster</title>
    <link rel="stylesheet" href="assets/css/login_style.css">
</head>
<body>
    <div class="form-container">
        <h2>Vérification de sécurité</h2>
        <p>Un code à 6 chiffres a été envoyé à votre adresse email. Il expirera dans 1 minute.</p>
        <form action="traitement/verifier_code_traitement.php" method="POST">
            <div class="form-group">
                <label for="code">Code de vérification</label>
                <input type="text" name="code" id="code" maxlength="6" required>
            </div>
            <button type="submit" class="btn">Vérifier</button>
            <a href="mot_de_passe_oublie.php" class="back-link">Renvoyer le code</a>
        </form>
    </div>
</body>
</html>
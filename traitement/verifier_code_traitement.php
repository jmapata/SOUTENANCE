<?php
// traitement/verifier_code_traitement.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// LA CORRECTION EST ICI : On lit l'email depuis $_POST
$code = $_POST['code'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($code) || empty($email)) {
    $_SESSION['error_message'] = "Le code ou l'email est manquant.";
    // On doit renvoyer l'email dans l'URL pour que la page de vérification puisse l'afficher
    header('Location: ../verifier_code.php?email=' . urlencode($email));
    exit();
}

try {
    // 1. Retrouver l'utilisateur par son email
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email_principal = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Vérifier le code et sa date d'expiration
    if (!$user || is_null($user['token_reset_mdp']) || new DateTime() > new DateTime($user['date_expiration_token_reset'])) {
        throw new Exception("Code invalide ou expiré. Veuillez refaire une demande.");
    }

    if (password_verify($code, $user['token_reset_mdp'])) {
        // Le code est correct, on invalide le token
        $stmt_clear = $pdo->prepare("UPDATE utilisateur SET token_reset_mdp = NULL, date_expiration_token_reset = NULL WHERE numero_utilisateur = ?");
        $stmt_clear->execute([$user['numero_utilisateur']]);

        // On autorise l'étape suivante
        $_SESSION['user_id_pour_reset_mdp'] = $user['numero_utilisateur'];
        
        header('Location: ../reinitialiser_mdp.php');
        exit();
    } else {
        throw new Exception("Le code que vous avez entré est incorrect.");
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ../verifier_code.php?email=' . urlencode($email));
    exit();
}
?>
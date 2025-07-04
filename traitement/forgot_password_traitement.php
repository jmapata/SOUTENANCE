<?php
// /traitement/forgot_password_traitement.php
session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/forgot_password.php');
}

$email = $_POST['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/forgot_password.php?error=Adresse email invalide.');
}

$stmt = $p_pdo->prepare("SELECT numero_utilisateur FROM utilisateur WHERE email_principal = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if ($user) {
    // Générer un jeton sécurisé
    $token = bin2hex(random_bytes(32));
    $token_expiry = new DateTime('+1 hour');

    // Mettre à jour l'utilisateur avec le jeton
    $stmt = $p_pdo->prepare(
        "UPDATE utilisateur 
         SET token_reset_mdp = :token, date_expiration_token_reset = :expiry 
         WHERE numero_utilisateur = :id"
    );
    $stmt->execute([
        'token' => $token,
        'expiry' => $token_expiry->format('Y-m-d H:i:s'),
        'id' => $user['numero_utilisateur']
    ]);

    // Envoyer l'email (simulation)
    $reset_link = "http://localhost/reset_password.php?token=" . $token;
    $subject = "Réinitialisation de votre mot de passe";
    $message = "Bonjour,\n\nCliquez sur le lien suivant pour réinitialiser votre mot de passe : " . $reset_link . "\nCe lien expirera dans une heure.";
    
    // Pour un vrai projet, utilisez une bibliothèque comme PHPMailer
    // mail($email, $subject, $message); 
    
    // Pour le test, nous affichons le lien
    error_log("Lien de reset pour $email : $reset_link");
}

// Toujours afficher un message de succès pour ne pas révéler si un email existe ou non.
redirect('/forgot_password.php?success=Si votre email est dans notre système, un lien de réinitialisation a été envoyé.');
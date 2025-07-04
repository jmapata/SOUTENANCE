<?php
// /traitement/reset_password_traitement.php
session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// --- Validations ---
if (empty($token) || empty($password) || empty($password_confirm)) {
    redirect('/login.php?error=Données manquantes.');
}
if ($password !== $password_confirm) {
    redirect("/reset_password.php?token=$token&error=Les mots de passe ne correspondent pas.");
}
// Règle de gestion RG6 pour la complexité du mot de passe
if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
    redirect("/reset_password.php?token=$token&error=Le mot de passe doit faire 12 caractères et contenir majuscule, minuscule, chiffre et caractère spécial.");
}

// --- Mise à jour en base de données ---
$new_password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE utilisateur SET 
            mot_de_passe = :password,
            token_reset_mdp = NULL,
            date_expiration_token_reset = NULL
        WHERE token_reset_mdp = :token AND date_expiration_token_reset > NOW()";

$stmt = $p_pdo->prepare($sql);
$stmt->execute([
    'password' => $new_password_hash,
    'token' => $token
]);

if ($stmt->rowCount() > 0) {
    redirect('/login.php?success=Votre mot de passe a été réinitialisé avec succès.');
} else {
    redirect('/login.php?error=Le jeton est invalide ou a expiré.');
}
<?php
// traitement/reinitialiser_mdp_traitement.php
session_start();
require_once '../config/database.php';

// Sécurité : on vérifie que l'utilisateur a bien passé l'étape de vérification du code
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id_pour_reset_mdp'])) {
    header('Location: ../login.php');
    exit();
}

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_id_to_reset = $_SESSION['user_id_pour_reset_mdp'];

// Validation
if (empty($new_password) || $new_password !== $confirm_password) {
    $_SESSION['error_message'] = "Les mots de passe ne correspondent pas ou sont vides.";
    header('Location: ../reinitialiser_mdp.php');
    exit();
}
if (strlen($new_password) < 8) {
    $_SESSION['error_message'] = "Le mot de passe doit contenir au moins 8 caractères.";
    header('Location: ../reinitialiser_mdp.php');
    exit();
}

try {
    // Hacher le nouveau mot de passe
    $password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe dans la base de données
    $stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE numero_utilisateur = ?");
    $stmt->execute([$password_hashed, $user_id_to_reset]);

    // Nettoyer la session
    unset($_SESSION['user_id_pour_reset_mdp']);

    $_SESSION['success_message'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
    header('Location: ../login.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour.";
    header('Location: ../reinitialiser_mdp.php');
    exit();
}
?>
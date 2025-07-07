<?php
session_start();
require_once '../config/database.php';

// Sécurité : On vérifie que l'utilisateur est bien un Gestionnaire Scolarité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_SCOLARITE') {
    exit('Accès refusé. Seuls les gestionnaires de scolarité peuvent effectuer cette action.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['numero_utilisateur'])) {
    
    $numero_utilisateur = $_POST['numero_utilisateur'];

    try {
        // On met à jour le statut du compte pour le passer à 'actif'
        $stmt = $pdo->prepare("UPDATE utilisateur SET statut_compte = 'actif' WHERE numero_utilisateur = ?");
        $stmt->execute([$numero_utilisateur]);

        // C'est ici que l'on déclenchera l'envoi de l'email à l'étudiant
        $_SESSION['success_message'] = "Le compte a été activé avec succès. Un email avec les identifiants peut être envoyé.";

    } catch (PDOException $e) {
        // En cas d'erreur, on stocke le message pour l'afficher
        $_SESSION['error_message'] = "Une erreur est survenue lors de l'activation du compte : " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Aucun utilisateur sélectionné pour l'activation.";
}

// Après l'opération, on redirige vers la page de gestion des activations
header('Location: ../dashboard_gestion_scolarite.php?page=activer_comptes');
exit();
?>
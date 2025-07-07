<?php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php'; // On inclut le fichier pour pouvoir envoyer des emails

// Sécurité : On vérifie que l'utilisateur est bien un Gestionnaire Scolarité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_SCOLARITE') {
    $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les droits nécessaires.";
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['numero_utilisateur'])) {
    
    $numero_utilisateur = $_POST['numero_utilisateur'];

    try {
        // --- Étape 1 : On active le compte dans la base de données ---
        
        // On met à jour le statut du compte pour le passer à 'actif'
        $stmt_activate = $pdo->prepare("UPDATE utilisateur SET statut_compte = 'actif' WHERE numero_utilisateur = ?");
        $stmt_activate->execute([$numero_utilisateur]);

        // --- Étape 2 : On tente d'envoyer l'email de notification ---

        // On récupère les informations de l'étudiant pour personnaliser l'email
        $stmt_info = $pdo->prepare(
            "SELECT e.nom, e.prenom, u.email_principal 
             FROM utilisateur u 
             JOIN etudiant e ON u.numero_utilisateur = e.numero_utilisateur 
             WHERE u.numero_utilisateur = ?"
        );
        $stmt_info->execute([$numero_utilisateur]);
        $etudiant = $stmt_info->fetch();

        // On vérifie qu'on a bien trouvé l'étudiant et son email
        if ($etudiant && !empty($etudiant['email_principal'])) {
            
            $sujet = "Votre compte ValidMaster est maintenant actif ";
            $contenu_html = "<h1>Votre compte est activé !</h1>
                             <p>Bonjour " . htmlspecialchars($etudiant['prenom']) . ",</p>
                             <p>Bonne nouvelle ! Le service de scolarité a validé votre dossier et votre compte sur la plateforme est maintenant actif.</p>
                             <p>Vous pouvez désormais vous connecter en utilisant votre email et le mot de passe qui vous a été communiqué précédemment.</p>
                             <p>Cordialement,<br>L'équipe ValidMaster</p>";
            
            // On tente d'envoyer l'email
            if (envoyerEmail($etudiant['email_principal'], $etudiant['prenom'] . ' ' . $etudiant['nom'], $sujet, $contenu_html)) {
                $_SESSION['success_message'] = "Le compte a été activé et l'étudiant a été notifié par email.";
            } else {
                // Si l'envoi échoue, on informe le GS, mais le compte reste activé
                $_SESSION['error_message'] = "Le compte a bien été activé, mais l'envoi de l'email de notification a échoué. Veuillez vérifier la configuration du mailer.";
            }
        } else {
            $_SESSION['error_message'] = "Le compte a été activé, mais l'email de notification n'a pas pu être envoyé (informations de l'étudiant introuvables).";
        }

    } catch (PDOException $e) {
        // En cas d'erreur de base de données, on stocke le message pour l'afficher
        $_SESSION['error_message'] = "Une erreur de base de données est survenue lors de l'activation du compte : " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Aucun utilisateur sélectionné pour l'activation.";
}

// Après l'opération, on redirige vers la page de gestion des activations
header('Location: ../dashboard_gestion_scolarite.php?page=activer_comptes');
exit();
?>
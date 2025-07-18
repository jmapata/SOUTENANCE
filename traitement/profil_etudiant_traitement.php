<?php
// traitement/profil_etudiant_traitement.php (Version finale et robuste)
session_start();
require_once '../config/database.php';

// Sécurité de base
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$action = $_POST['action'] ?? '';
$numero_utilisateur = $_SESSION['numero_utilisateur'];

try {
    switch ($action) {
        // ===================================
        // MISE À JOUR DES INFORMATIONS DE CONTACT
        // ===================================
        case 'update_contact':
            $telephone = $_POST['telephone'] ?? '';
            $email_secondaire = $_POST['email_secondaire'] ?? '';

            $stmt = $pdo->prepare("UPDATE etudiant SET telephone = ?, email_contact_secondaire = ? WHERE numero_utilisateur = ?");
            $stmt->execute([$telephone, $email_secondaire, $numero_utilisateur]);
            
            $_SESSION['success_message'] = "Vos informations de contact ont été mises à jour.";
            break;

        // ===================================
        // CHANGEMENT DU MOT DE PASSE
        // ===================================
         case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("Tous les champs de mot de passe sont obligatoires.");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("Le nouveau mot de passe et sa confirmation ne correspondent pas.");
            }
            if (strlen($new_password) < 8) {
                throw new Exception("Le nouveau mot de passe doit contenir au moins 8 caractères.");
            }

            // Récupérer le mot de passe haché actuel de l'utilisateur
            $stmt_pass = $pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE numero_utilisateur = ?");
            $stmt_pass->execute([$numero_utilisateur]);
            $user = $stmt_pass->fetch();

            // Vérification cruciale : le mot de passe actuel est-il correct ?
            if (!$user || !password_verify($current_password, $user['mot_de_passe'])) {
                throw new Exception("Le mot de passe actuel que vous avez saisi est incorrect.");
            }

            // Hacher le nouveau mot de passe
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Mettre à jour dans la base de données
            $stmt_update_pass = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE numero_utilisateur = ?");
            $stmt_update_pass->execute([$new_password_hashed, $numero_utilisateur]);
            
            $_SESSION['success_message'] = "Votre mot de passe a été changé avec succès.";
            break;
        // ===================================
        // MISE À JOUR DE LA PHOTO DE PROFIL
        // ===================================
        case 'upload_photo':
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo'];
                $upload_dir = '../uploads/photos_profil/';
                
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }

                $allowed_types = ['image/jpeg', 'image/png'];
                if (!in_array($file['type'], $allowed_types)) {
                    throw new Exception("Format de fichier non autorisé (uniquement JPG, PNG).");
                }
                if ($file['size'] > 2097152) { // Limite de 2MB
                    throw new Exception("Le fichier est trop volumineux (max 2MB).");
                }

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'profil_' . $numero_utilisateur . '_' . time() . '.' . $extension;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $db_path = 'uploads/photos_profil/' . $new_filename;
                    $stmt_photo = $pdo->prepare("UPDATE utilisateur SET photo_profil = ? WHERE numero_utilisateur = ?");
                    $stmt_photo->execute([$db_path, $numero_utilisateur]);
                    $_SESSION['success_message'] = "Votre photo de profil a été mise à jour.";
                } else {
                    throw new Exception("Erreur lors du déplacement du fichier téléversé.");
                }
            } else {
                throw new Exception("Aucun fichier valide n'a été téléversé.");
            }
            break;
            
        default:
            throw new Exception("Action non reconnue.");
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
}

// Rediriger vers la page de profil pour voir le message
header('Location: ../dashboard_etudiant.php?page=profil');
exit();
?>
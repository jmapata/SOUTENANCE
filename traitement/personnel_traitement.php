<?php
session_start();
require_once '../config/database.php';

// Sécurité : Vérifier que l'utilisateur est un administrateur connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    // Si ce n'est pas le cas, on arrête tout.
    exit('Accès refusé.');
}

// Fonction pour générer un mot de passe aléatoire sécurisé
function genererMotDePasse($longueur = 12) {
    // Génère un mot de passe plus simple à lire
    $caracteres = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    return substr(str_shuffle(str_repeat($caracteres, $longueur)), 0, $longueur);
}

// On vérifie qu'une action a bien été postée via un formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // On utilise une transaction pour garantir que toutes les opérations d'une action réussissent ou échouent ensemble
    $pdo->beginTransaction();
    try {
        switch ($action) {

            // ======================= AJOUTER =======================
            case 'ajouter':
                $mot_de_passe_clair = genererMotDePasse();
                $mot_de_passe_hache = password_hash($mot_de_passe_clair, PASSWORD_DEFAULT);
                $numero_utilisateur = "USER_" . time(); // ID unique temporaire
                $numero_personnel = "PERS_" . time(); // ID unique temporaire
                
                // Insertion dans la table utilisateur
                $stmt_user = $pdo->prepare(
                    "INSERT INTO utilisateur (numero_utilisateur, login_utilisateur, email_principal, mot_de_passe, statut_compte, id_niveau_acces_donne, id_groupe_utilisateur, id_type_utilisateur) 
                     VALUES (?, ?, ?, ?, 'actif', ?, ?, ?)"
                );
                $stmt_user->execute([$numero_utilisateur, $_POST['login'], $_POST['email_pro'], $mot_de_passe_hache, 'ACCES_DEPARTEMENT', $_POST['id_groupe'], $_POST['id_type']]);
                
                // Insertion dans la table personnel_administratif avec la liaison
                $stmt_perso = $pdo->prepare(
                    "INSERT INTO personnel_administratif (numero_personnel_administratif, nom, prenom, email_professionnel, numero_utilisateur) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt_perso->execute([$numero_personnel, $_POST['nom'], $_POST['prenom'], $_POST['email_pro'], $numero_utilisateur]);

                // Message de succès avec le mot de passe généré
                $_SESSION['success_message'] = "Membre ajouté avec succès. Mot de passe initial : <strong>" . htmlspecialchars($mot_de_passe_clair) . "</strong>";
                break;

            // ======================= MODIFIER =======================
            case 'modifier':
                $id_personnel = $_POST['id_personnel'];
                $nom = $_POST['nom'];
                $prenom = $_POST['prenom'];
                $email = $_POST['email_pro'];

                $stmt_find_user = $pdo->prepare("SELECT numero_utilisateur FROM personnel_administratif WHERE numero_personnel_administratif = ?");
                $stmt_find_user->execute([$id_personnel]);
                $numero_utilisateur = $stmt_find_user->fetchColumn();

                $stmt_perso = $pdo->prepare("UPDATE personnel_administratif SET nom = ?, prenom = ?, email_professionnel = ? WHERE numero_personnel_administratif = ?");
                $stmt_perso->execute([$nom, $prenom, $email, $id_personnel]);

                if ($numero_utilisateur) {
                    $stmt_user = $pdo->prepare("UPDATE utilisateur SET email_principal = ? WHERE numero_utilisateur = ?");
                    $stmt_user->execute([$email, $numero_utilisateur]);
                }
                
                $_SESSION['success_message'] = "Les informations ont été mises à jour avec succès.";
                break;

            // ======================= ACTIVER/DÉSACTIVER =======================
            case 'toggle_status':
                $numero_utilisateur = $_POST['numero_utilisateur'];
                $current_status = $_POST['current_status'];
                $new_status = ($current_status === 'actif') ? 'inactif' : 'actif';

                $stmt = $pdo->prepare("UPDATE utilisateur SET statut_compte = ? WHERE numero_utilisateur = ?");
                $stmt->execute([$new_status, $numero_utilisateur]);
                
                $_SESSION['success_message'] = "Le statut du compte a été mis à jour.";
                break;
                
            // ======================= SUPPRIMER =======================
            case 'supprimer':
                $id_personnel = $_POST['id_personnel'];
                
                $stmt_find_user = $pdo->prepare("SELECT numero_utilisateur FROM personnel_administratif WHERE numero_personnel_administratif = ?");
                $stmt_find_user->execute([$id_personnel]);
                $numero_utilisateur = $stmt_find_user->fetchColumn();

                $stmt_perso = $pdo->prepare("DELETE FROM personnel_administratif WHERE numero_personnel_administratif = ?");
                $stmt_perso->execute([$id_personnel]);
                
                if ($numero_utilisateur) {
                    $stmt_user = $pdo->prepare("DELETE FROM utilisateur WHERE numero_utilisateur = ?");
                    $stmt_user->execute([$numero_utilisateur]);
                }
                
                $_SESSION['success_message'] = "Le membre du personnel et son compte ont été supprimés.";
                break;
        }

        // Si tout s'est bien passé, on valide les changements dans la base de données
        $pdo->commit();

    } catch (PDOException $e) {
        // En cas d'erreur, on annule tous les changements effectués
        $pdo->rollBack();
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Après chaque opération, on redirige l'utilisateur vers la page de gestion
header('Location: ../dashboard_admin.php?page=gestion_personnel');
exit();
?>
<?php
session_start();
require_once '../config/database.php';

// Sécurité : Vérifier que l'utilisateur est un administrateur connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    exit('Accès refusé.');
}

// Fonction pour générer un mot de passe aléatoire
function genererMotDePasse($longueur = 12) {
    return substr(bin2hex(random_bytes($longueur)), 0, $longueur);
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
                
                // Logique pour générer des ID uniques (sera à affiner avec votre service de séquences)
                $numero_utilisateur = "USER_" . time();
                $numero_enseignant = "ENS_" . time();
                
                // 1. Insérer dans la table utilisateur
                $stmt_user = $pdo->prepare(
                    "INSERT INTO utilisateur (numero_utilisateur, login_utilisateur, email_principal, mot_de_passe, statut_compte, id_niveau_acces_donne, id_groupe_utilisateur, id_type_utilisateur) 
                     VALUES (?, ?, ?, ?, 'actif', ?, ?, 'TYPE_ENS')"
                );
                $stmt_user->execute([$numero_utilisateur, $_POST['login'], $_POST['email_pro'], $mot_de_passe_hache, 'ACCES_DEPARTEMENT', $_POST['id_groupe']]);
                
                // 2. Insérer dans la table enseignant avec la liaison
                $stmt_ens = $pdo->prepare(
                    "INSERT INTO enseignant (numero_enseignant, nom, prenom, email_professionnel, numero_utilisateur) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt_ens->execute([$numero_enseignant, $_POST['nom'], $_POST['prenom'], $_POST['email_pro'], $numero_utilisateur]);

                $_SESSION['success_message'] = "Enseignant ajouté. Mot de passe initial : <strong>" . htmlspecialchars($mot_de_passe_clair) . "</strong>";
                break;

            // ======================= MODIFIER =======================
            case 'modifier':
                $id_enseignant = $_POST['id_enseignant'];
                $nom = $_POST['nom'];
                $prenom = $_POST['prenom'];
                $email = $_POST['email_pro'];
                $login = $_POST['login'];
                $id_groupe = $_POST['id_groupe'];

                // On récupère le numero_utilisateur pour mettre à jour la bonne ligne dans les deux tables
                $stmt_find_user = $pdo->prepare("SELECT numero_utilisateur FROM enseignant WHERE numero_enseignant = ?");
                $stmt_find_user->execute([$id_enseignant]);
                $numero_utilisateur = $stmt_find_user->fetchColumn();

                // 1. Mettre à jour la table enseignant
                $stmt_ens = $pdo->prepare("UPDATE enseignant SET nom = ?, prenom = ?, email_professionnel = ? WHERE numero_enseignant = ?");
                $stmt_ens->execute([$nom, $prenom, $email, $id_enseignant]);

                // 2. Mettre à jour la table utilisateur si un compte est lié
                if ($numero_utilisateur) {
                    $stmt_user = $pdo->prepare("UPDATE utilisateur SET login_utilisateur = ?, email_principal = ?, id_groupe_utilisateur = ? WHERE numero_utilisateur = ?");
                    $stmt_user->execute([$login, $email, $id_groupe, $numero_utilisateur]);
                }
                
                $_SESSION['success_message'] = "Les informations de l'enseignant ont été mises à jour.";
                break;

            // ======================= ACTIVER/DÉSACTIVER =======================
            case 'toggle_status':
                $numero_utilisateur = $_POST['numero_utilisateur'];
                $current_status = $_POST['current_status'];
                $new_status = ($current_status === 'actif') ? 'inactif' : 'actif';

                $stmt = $pdo->prepare("UPDATE utilisateur SET statut_compte = ? WHERE numero_utilisateur = ?");
                $stmt->execute([$new_status, $numero_utilisateur]);
                
                $_SESSION['success_message'] = "Le statut du compte de l'enseignant a été mis à jour.";
                break;

            // ======================= SUPPRIMER =======================
            case 'supprimer':
                $id_enseignant = $_POST['id_enseignant'];
                
                // On récupère le numero_utilisateur associé avant toute suppression
                $stmt_find_user = $pdo->prepare("SELECT numero_utilisateur FROM enseignant WHERE numero_enseignant = ?");
                $stmt_find_user->execute([$id_enseignant]);
                $numero_utilisateur = $stmt_find_user->fetchColumn();

                // 1. On supprime la fiche de l'enseignant
                $stmt_ens = $pdo->prepare("DELETE FROM enseignant WHERE numero_enseignant = ?");
                $stmt_ens->execute([$id_enseignant]);
                
                // 2. On supprime le compte utilisateur lié, s'il existe
                if ($numero_utilisateur) {
                    $stmt_user = $pdo->prepare("DELETE FROM utilisateur WHERE numero_utilisateur = ?");
                    $stmt_user->execute([$numero_utilisateur]);
                }
                
                $_SESSION['success_message'] = "L'enseignant et son compte ont été supprimés.";
                break;
        }

        // Si aucune erreur n'est survenue, on valide tous les changements dans la base de données
        $pdo->commit();

    } catch (PDOException $e) {
        // En cas d'erreur, on annule tous les changements
        $pdo->rollBack();
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Après chaque opération, on redirige l'utilisateur vers la page de gestion des enseignants
header('Location: ../dashboard_admin.php?page=gestion_enseignants');
exit();
?>
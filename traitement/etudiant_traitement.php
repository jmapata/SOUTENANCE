<?php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php';

// Sécurité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    exit('Accès refusé.');
}

function genererMotDePasse($longueur = 12) {
    return substr(bin2hex(random_bytes($longueur)), 0, $longueur);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'creer_compte_etudiant') {
        
        $numero_carte_etudiant = $_POST['numero_carte_etudiant'];
        $etudiant = null;
        $mot_de_passe_clair = '';
        $login = '';

        // --- Étape 1 : Création du compte dans la base de données ---
        $pdo->beginTransaction();
        try {
            $stmt_info = $pdo->prepare("SELECT nom, prenom, email_contact_secondaire FROM etudiant WHERE numero_carte_etudiant = ?");
            $stmt_info->execute([$numero_carte_etudiant]);
            $etudiant = $stmt_info->fetch();

            if ($etudiant && !empty($etudiant['email_contact_secondaire'])) {
                $login = $etudiant['email_contact_secondaire'];
                $mot_de_passe_clair = genererMotDePasse();
                $mot_de_passe_hache = password_hash($mot_de_passe_clair, PASSWORD_DEFAULT);
                $numero_utilisateur = "ETU_USER_" . time();

                $stmt_user = $pdo->prepare("INSERT INTO utilisateur (numero_utilisateur, login_utilisateur, email_principal, mot_de_passe, statut_compte, id_groupe_utilisateur, id_type_utilisateur, id_niveau_acces_donne) VALUES (?, ?, ?, ?, 'inactif', ?, ?, ?)");
                $stmt_user->execute([$numero_utilisateur, $login, $etudiant['email_contact_secondaire'], $mot_de_passe_hache, 'GRP_ETUDIANT', 'TYPE_ETUD', 'ACCES_PERSONNEL']);

                $stmt_link = $pdo->prepare("UPDATE etudiant SET numero_utilisateur = ? WHERE numero_carte_etudiant = ?");
                $stmt_link->execute([$numero_utilisateur, $numero_carte_etudiant]);
                
                $pdo->commit();
                // Le compte est créé, même si l'email échoue.
            } else {
                $_SESSION['error_message'] = "Impossible de créer le compte : email de l'étudiant manquant.";
                header('Location: ../dashboard_admin.php?page=gestion_etudiants');
                exit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
            header('Location: ../dashboard_admin.php?page=gestion_etudiants');
            exit();
        }

        // --- Étape 2 : Tentative d'envoi de l'email (en dehors de la transaction) ---
        if ($etudiant) {
            $sujet = "Vos identifiants pour la plateforme ValidMaster";
            $contenu_html = "<h1>Bienvenue sur ValidMaster</h1>
                             <p>Bonjour " . htmlspecialchars($etudiant['prenom']) . ",</p>
                             <p>Votre compte a été créé. <strong>Il n'est pas encore actif.</strong></p>
                             <p>Voici vos identifiants :</p>
                             <ul>
                                 <li><strong>Login (votre email) :</strong> " . htmlspecialchars($login) . "</li>
                                 <li><strong>Mot de passe :</strong> " . htmlspecialchars($mot_de_passe_clair) . "</li>
                             </ul>
                             <p>Vous serez notifié(e) par la scolarité dès que votre compte sera activé.</p>";

            if (envoyerEmail($etudiant['email_contact_secondaire'], $etudiant['prenom'] . ' ' . $etudiant['nom'], $sujet, $contenu_html)) {
                $_SESSION['success_message'] = "Compte créé et email envoyé avec succès.";
            } else {
                $_SESSION['error_message'] = "Le compte a été créé, mais l'envoi de l'email a échoué. Vérifiez la configuration de 'config/mailer.php'.";
            }
        }
    }
}

header('Location: ../dashboard_admin.php?page=gestion_etudiants');
exit();
?>
<?php
session_start();
require_once '../config/database.php';

// Sécurité : Idéalement, vérifier le rôle du Gestionnaire Scolarité.
// Pour l'instant, on vérifie juste si l'utilisateur est connecté.
if (!isset($_SESSION['loggedin'])) {
    $_SESSION['error_message'] = "Accès refusé. Vous devez être connecté.";
    header('Location: ../dashboard_gestion_scolarite.php?page=creer_etudiant');
    exit();
}

// On vérifie que le formulaire a été soumis avec la bonne action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    if ($action === 'creer_fiche_etudiant') {
        
        // Validation de la date de naissance
        $date_naissance = $_POST['date_naissance'] ?? '';
        if (!empty($date_naissance)) {
            $age = date_diff(date_create($date_naissance), date_create('today'))->y;
            if ($age < 18) {
                $_SESSION['error_message'] = "L'étudiant doit avoir au moins 18 ans.";
                header('Location: ../dashboard_gestion_scolarite.php?page=creer_etudiant');
                exit();
            }
        }

        // On utilise une transaction pour s'assurer que toutes les opérations réussissent ou échouent ensemble.
        $pdo->beginTransaction();

        try {
            // --- Étape 1 : Création de la fiche étudiant ---

            // Logique pour générer un ID unique (à améliorer plus tard avec votre service de séquences)
            $numero_carte_etudiant = "ETU-" . time();
            
            // REQUÊTE CORRIGÉE : On insère l'étudiant avec 7 valeurs, y compris l'email
            $stmt_etudiant = $pdo->prepare(
                "INSERT INTO etudiant (numero_carte_etudiant, nom, prenom, date_naissance, nationalite, telephone, email_contact_secondaire, numero_utilisateur) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL)"
            );
            $stmt_etudiant->execute([
                $numero_carte_etudiant,
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['date_naissance'],
                $_POST['nationalite'],
                $_POST['telephone'],
                $_POST['email'] // La valeur pour email_contact_secondaire
            ]);
            
            // --- Étape 2 : Enregistrement du premier versement ---
            
            // REQUÊTE CORRIGÉE : On ne mentionne plus "id_inscription"
            $stmt_inscrire = $pdo->prepare(
                "INSERT INTO inscrire (numero_carte_etudiant, id_niveau_etude, id_annee_academique, montant_inscription, date_inscription, id_statut_paiement, numero_recu_paiement) 
                 VALUES (?, ?, ?, ?, NOW(), ?, ?)"
            );
            $stmt_inscrire->execute([
                $numero_carte_etudiant,
                'M2_MIAGE',
                'ANNEE-2025-2026',
                $_POST['montant'],
                'PAY_PARTIEL',
                $_POST['numero_recu']
            ]);
             // ## AUDIT DE L'ACTION ##
        $audit_id = 'AUDIT-' . strtoupper(uniqid());
        $stmt_audit = $pdo->prepare(
            "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
             VALUES (?, ?, 'SCOLARITE_CREATION_FICHE', NOW(), ?, 'etudiant')"
        );
        // L'acteur est l'agent de scolarité connecté, l'entité concernée est le nouvel étudiant
        $stmt_audit->execute([$audit_id, $_SESSION['numero_utilisateur'], $numero_carte_etudiant]);

            // Si tout s'est bien passé, on valide les changements dans la base de données
            $pdo->commit();

            // On prépare un message de succès pour l'afficher sur la page de retour
            $_SESSION['success_message'] = "La fiche de l'étudiant " . htmlspecialchars($_POST['prenom'] . ' ' . $_POST['nom']) . " a été créée avec succès.";

        } catch (PDOException $e) {
            // En cas d'erreur, on annule tous les changements
            $pdo->rollBack();
            // On prépare un message d'erreur détaillé pour le débogage
            $_SESSION['error_message'] = "Erreur lors de la création de la fiche : " . $e->getMessage();
        }
    }
}

// Après chaque opération, on redirige l'utilisateur vers la page de gestion
header('Location: ../dashboard_gestion_scolarite.php?page=creer_etudiant');
exit();
?>
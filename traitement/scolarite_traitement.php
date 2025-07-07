<?php
session_start();
require_once '../config/database.php';

// Sécurité : Idéalement, vérifier le rôle du Gestionnaire Scolarité.
if (!isset($_SESSION['loggedin'])) { // Pour l'instant, on vérifie juste la connexion
    exit('Accès refusé. Vous devez être connecté.');
}

// On vérifie que le formulaire a été soumis avec la bonne action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    if ($action === 'creer_fiche_etudiant') {
        
        $pdo->beginTransaction();

        try {
            // Logique pour générer un ID unique
            $numero_carte_etudiant = "ETU-" . time();
            
            // On insère l'étudiant, en laissant numero_utilisateur à NULL
            $stmt_etudiant = $pdo->prepare(
                "INSERT INTO etudiant (numero_carte_etudiant, nom, prenom, date_naissance, nationalite, telephone, numero_utilisateur) 
                 VALUES (?, ?, ?, ?, ?, ?, NULL)"
            );
            $stmt_etudiant->execute([
                $numero_carte_etudiant,
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['date_naissance'],
                $_POST['nationalite'],
                $_POST['telephone']
            ]);
            
            // On enregistre le premier versement dans la table 'inscrire'
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
            
            $pdo->commit();
            $_SESSION['success_message'] = "La fiche de l'étudiant " . htmlspecialchars($_POST['prenom'] . ' ' . $_POST['nom']) . " a été créée avec succès.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }
}

// On redirige vers la page de gestion pour voir la liste à jour
header('Location: ../dashboard_gestion_scolarite.php?page=creer_etudiant');
exit();
?>
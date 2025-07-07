<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['loggedin'])) { exit('Accès refusé'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $pdo->beginTransaction();

    try {
        switch ($action) {
            case 'ajouter_versement':
                $num_etudiant = $_POST['numero_carte_etudiant'];
                $montant_ajout = $_POST['montant'];

                // On trouve l'inscription de l'étudiant pour l'année en cours
                $stmt_find = $pdo->prepare("SELECT montant_inscription FROM inscrire WHERE numero_carte_etudiant = ? AND id_annee_academique = ?");
                $stmt_find->execute([$num_etudiant, 'ANNEE-2025-2026']); // Année à rendre dynamique
                $montant_actuel = $stmt_find->fetchColumn();

                if ($montant_actuel !== false) {
                    $nouveau_montant = $montant_actuel + $montant_ajout;
                    
                    $stmt_update = $pdo->prepare("UPDATE inscrire SET montant_inscription = ? WHERE numero_carte_etudiant = ? AND id_annee_academique = ?");
                    $stmt_update->execute([$nouveau_montant, $num_etudiant, 'ANNEE-2025-2026']);

                    $_SESSION['success_message'] = "Versement ajouté avec succès.";
                } else {
                    $_SESSION['error_message'] = "Aucune inscription trouvée pour cet étudiant pour l'année en cours.";
                }
                break;

            case 'activer_compte':
                $numero_utilisateur = $_POST['numero_utilisateur'];
                if ($numero_utilisateur) {
                    $stmt_activate = $pdo->prepare("UPDATE utilisateur SET statut_compte = 'actif' WHERE numero_utilisateur = ?");
                    $stmt_activate->execute([$numero_utilisateur]);
                    $_SESSION['success_message'] = "Le compte a été activé.";
                } else {
                     $_SESSION['error_message'] = "Impossible d'activer le compte : utilisateur non trouvé.";
                }
                break;
            
            // Note : le cas 'creer_fiche_etudiant' est géré par scolarite_traitement.php
            // On peut le laisser ici si on veut tout centraliser, ou le garder séparé.
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    }
}

header('Location: ../dashboard_gestion_scolarite.php?page=gestion_inscriptions');
exit();
?>
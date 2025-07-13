<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') { exit('Accès refusé'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $ref_name = $_POST['ref_name']; // Le nom de la table

    // Sécurité: liste blanche de toutes les tables modifiables
    $allowed_refs = ['grade', 'fonction', 'specialite', 'niveau_etude', 'annee_academique', 'action', 'statut_rapport_ref', 'traitement', 'ecue', 'entreprise', 'groupe_utilisateur', 'niv_acces_donnees', 'statut_jury', 'type_utilisateur', 'ue', 'decision_passage_ref', 'decision_validation_pv_ref', 'decision_vote_ref', 'statut_conformite_ref', 'statut_paiement_ref', 'statut_penalite_ref', 'statut_pv_ref', 'statut_reclamation_ref', 'type_document_ref'];
    if (!in_array($ref_name, $allowed_refs)) { exit('Action sur référentiel non valide.'); }

    // Détermination dynamique des noms de colonnes
    $id_column = 'id_' . $ref_name;
    $libelle_column = 'libelle_' . $ref_name;
    $exceptions = [ /* ... (votre tableau d'exceptions pour les noms de colonnes) ... */ ];
    if (isset($exceptions[$ref_name])) { $libelle_column = $exceptions[$ref_name]; }
    
    $pdo->beginTransaction();
    try {
        if ($action === 'ajouter') {
            $stmt = $pdo->prepare("INSERT INTO `$ref_name` ($id_column, $libelle_column) VALUES (?, ?)");
            $stmt->execute([$_POST['id_value'], $_POST['libelle_value']]);
            $_SESSION['success_message'] = "L'entrée a été ajoutée avec succès.";
        } elseif ($action === 'supprimer') {
            $stmt = $pdo->prepare("DELETE FROM `$ref_name` WHERE $id_column = ?");
            $stmt->execute([$_POST['id_value']]);
            $_SESSION['success_message'] = "L'entrée a été supprimée.";
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    }
}
header('Location: ../dashboard_admin.php?page=parametres');
exit();
?>
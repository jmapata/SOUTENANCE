<?php
// traitement/resoumettre_rapport.php
session_start();
require_once '../config/database.php';

// Sécurité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// Récupération des données
$rapport_id = $_POST['rapport_id'] ?? null;
$contenu_corrige = $_POST['contenu_corrige'] ?? '';

if (!$rapport_id || empty($contenu_corrige)) {
    // Gérer l'erreur, par exemple en redirigeant avec un message
    $_SESSION['error_message'] = "Erreur : le contenu corrigé est vide.";
    header('Location: ../dashboard_etudiant.php?page=rapport_modification');
    exit();
}

try {
    $pdo->beginTransaction();

    // On met à jour le rapport.
    // La stratégie la plus simple est de remplacer l'ancien contenu par le nouveau.
    // D'abord, on supprime les anciennes sections du rapport.
    $stmt_delete = $pdo->prepare("DELETE FROM section_rapport WHERE id_rapport_etudiant = ?");
    $stmt_delete->execute([$rapport_id]);

    // Ensuite, on insère le nouveau contenu corrigé comme une seule section.
    $stmt_insert = $pdo->prepare("INSERT INTO section_rapport (id_rapport_etudiant, titre_section, contenu_section, ordre) VALUES (?, ?, ?, 1)");
    $stmt_insert->execute([$rapport_id, 'Corps du rapport corrigé', $contenu_corrige]);
    
    // Finalement, on met à jour le statut du rapport pour le soumettre à nouveau
    $stmt_update = $pdo->prepare("
        UPDATE rapport_etudiant 
        SET 
            id_statut_rapport = 'RAP_SOUMIS', 
            date_derniere_modif = NOW(),
            date_soumission = NOW()
        WHERE id_rapport_etudiant = ?
    ");
    $stmt_update->execute([$rapport_id]);

    
    // ## AUDIT ##
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare("INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) VALUES (?, ?, 'ETUDIANT_RESUMISSION_RAPPORT', NOW(), ?, 'rapport_etudiant')");
    $stmt_audit->execute([$audit_id, $_SESSION['numero_utilisateur'], $rapport_id]);


    $pdo->commit();

    $_SESSION['success_message'] = "Votre rapport a été corrigé et soumis à nouveau avec succès.";
    // On redirige vers l'historique pour voir le nouveau statut
    header('Location: ../dashboard_etudiant.php?page=rapport_historique');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Une erreur est survenue lors de la re-soumission : " . $e->getMessage();
    header('Location: ../dashboard_etudiant.php?page=rapport_modification');
    exit();
}
?>
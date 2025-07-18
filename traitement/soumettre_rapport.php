<?php
// traitement/soumettre_rapport.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    header('Location: ../login.php');
    exit();
}

$rapport_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("UPDATE rapport_etudiant SET id_statut_rapport = 'RAP_SOUMIS', date_soumission = NOW() WHERE id_rapport_etudiant = ?");
    $stmt->execute([$rapport_id]);

    // ## AUDIT ##
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare("INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) VALUES (?, ?, 'ETUDIANT_SOUMISSION_RAPPORT', NOW(), ?, 'rapport_etudiant')");
    $stmt_audit->execute([$audit_id, $_SESSION['numero_utilisateur'], $rapport_id]);


    $_SESSION['success_message'] = "Votre rapport a été soumis avec succès.";
    header('Location: ../dashboard_etudiant.php?page=rapport_suivi');
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la soumission : " . $e->getMessage();
    header('Location: ../dashboard_etudiant.php?page=rapport_suivi');
    exit();
}
?>
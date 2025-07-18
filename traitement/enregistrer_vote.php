<?php
// traitement/enregistrer_vote.php (Version finale, sécurisée et avec audit)
session_start();
require_once(__DIR__ . '/../config/database.php');

// --- Sécurité et Validation ---
if (!isset($_SESSION['numero_utilisateur'])) {
    header('Location: ../login.php');
    exit();
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];

// Récupération et validation des données du formulaire
$id_rapport = $_POST['id_rapport_etudiant'] ?? null;
$id_decision = $_POST['id_decision_vote'] ?? null;
$commentaire = trim($_POST['commentaire_vote'] ?? '');

if (!$id_rapport || !$id_decision) {
    $_SESSION['error_message'] = "La décision de vote est obligatoire.";
    header('Location: ../dashboard_commission.php?page=rapports_a_traiter');
    exit();
}

try {
    $pdo->beginTransaction();

    // ==========================================================
    // ## LOGIQUE CORRIGÉE : VÉRIFIER PUIS AGIR ##
    // ==========================================================

    // 1. Vérifier si un vote de cet utilisateur pour ce rapport existe déjà
    $stmt_check = $pdo->prepare("SELECT id_vote FROM vote_commission WHERE id_rapport_etudiant = ? AND numero_utilisateur = ?");
    $stmt_check->execute([$id_rapport, $numero_utilisateur]);
    $vote_existant = $stmt_check->fetch();

    if ($vote_existant) {
        // 2a. Si le vote existe, on le MET À JOUR
        $stmt_update = $pdo->prepare(
            "UPDATE vote_commission SET id_decision_vote = ?, commentaire_vote = ?, date_vote = NOW() WHERE id_vote = ?"
        );
        $stmt_update->execute([$id_decision, $commentaire, $vote_existant['id_vote']]);
    } else {
        // 2b. Si le vote n'existe pas, on l'INSÈRE
        $id_vote = 'VOTE-' . strtoupper(uniqid()); // Générer un ID unique pour le vote
        $stmt_insert = $pdo->prepare(
            "INSERT INTO vote_commission (id_vote, id_rapport_etudiant, numero_utilisateur, id_decision_vote, commentaire_vote, date_vote, tour_vote) 
             VALUES (?, ?, ?, ?, ?, NOW(), 1)"
        );
        $stmt_insert->execute([$id_vote, $id_rapport, $numero_utilisateur, $id_decision, $commentaire]);
    }

    // --- 3. Enregistrement de l'action dans le journal d'audit ---
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
         VALUES (?, ?, 'COMMISSION_VOTE_ENREGISTRE', NOW(), ?, 'rapport_etudiant')"
    );
    $stmt_audit->execute([$audit_id, $numero_utilisateur, $id_rapport]);
    
    // Si tout s'est bien passé, on valide la transaction
    $pdo->commit();
    $_SESSION['success_message'] = "Votre vote a été enregistré avec succès.";

} catch (Exception $e) {
    // En cas d'erreur, on annule toutes les opérations
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
}

// Redirection vers la page des rapports à traiter pour voir le résultat
header("Location: ../dashboard_commission.php?page=rapports_a_traiter");
exit();
?>
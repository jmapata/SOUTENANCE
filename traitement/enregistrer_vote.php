<?php
// traitement/enregistrer_vote.php (Version finale, sécurisée et avec audit)
session_start();
require_once(__DIR__ . '/../config/database.php');

// --- Sécurité et Validation ---
if (!isset($_SESSION['numero_utilisateur'])) {
    // Rediriger vers la page de connexion si non connecté
    header('Location: ../login.php');
    exit();
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];

// Récupération et validation des données du formulaire
$id_rapport = $_POST['id_rapport_etudiant'] ?? null;
$id_decision = $_POST['id_decision_vote'] ?? null;
$commentaire = trim($_POST['commentaire_vote'] ?? '');

if (!$id_rapport || !$id_decision) {
    $_SESSION['error_message'] = "Tous les champs sont requis pour le vote.";
    header('Location: ../dashboard_commission.php?page=rapports_a_traiter');
    exit();
}

try {
    // On commence une transaction pour garantir que toutes les opérations réussissent ou échouent ensemble.
    $pdo->beginTransaction();

    // On utilise la même requête pour insérer ou mettre à jour le vote.
    // Pour cela, la clé primaire de votre table `vote_commission` doit être sur (id_rapport_etudiant, numero_utilisateur).
    $sql_vote = "
        INSERT INTO vote_commission (id_rapport_etudiant, numero_utilisateur, id_decision_vote, commentaire_vote, date_vote, tour_vote)
        VALUES (?, ?, ?, ?, NOW(), 1)
        ON DUPLICATE KEY UPDATE
            id_decision_vote = VALUES(id_decision_vote),
            commentaire_vote = VALUES(commentaire_vote),
            date_vote = NOW()
    ";
    $stmt_vote = $pdo->prepare($sql_vote);
    $stmt_vote->execute([$id_rapport, $numero_utilisateur, $id_decision, $commentaire]);

    // ==========================================================
    // ## NOUVELLE PARTIE : ENREGISTREMENT DE L'ACTION DANS LE JOURNAL D'AUDIT ##
    // ==========================================================
    // On vérifie d'abord que l'action 'VOTE_ENREGISTRE' existe dans la table de référence
    $stmt_action_check = $pdo->prepare("SELECT id_action FROM action WHERE id_action = 'VOTE_ENREGISTRE'");
    $stmt_action_check->execute();
    if ($stmt_action_check->fetch()) {
        $audit_id = 'AUDIT-' . strtoupper(uniqid());
        $stmt_audit = $pdo->prepare(
            "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
             VALUES (?, ?, 'VOTE_ENREGISTRE', NOW(), ?, 'rapport_etudiant')"
        );
        $stmt_audit->execute([$audit_id, $numero_utilisateur, $id_rapport]);
    }
    // ==========================================================

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
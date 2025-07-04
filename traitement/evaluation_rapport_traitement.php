<?php
// /traitement/evaluation_rapport_traitement.php
require_once '../includes/session_handler.php';
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// if (!checkPermission('TRAIT_COMMISSION_VOTER')) { die("Accès non autorisé."); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard_commission.php');
}

// --- Récupération des données ---
$id_rapport = $_POST['id_rapport'] ?? '';
$decision_vote = $_POST['decision_vote'] ?? '';
$commentaire_vote = $_POST['commentaire_vote'] ?? '';
$id_votant = $_SESSION['user_id'];

// --- Validation ---
if (empty($id_rapport) || empty($decision_vote)) {
    redirect("/evaluation_rapport.php?id=$id_rapport&error=La décision est requise.");
}
if ($decision_vote !== 'APPROUVE' && empty(trim($commentaire_vote))) {
    redirect("/evaluation_rapport.php?id=$id_rapport&error=Un commentaire est obligatoire si la décision n'est pas 'Approuvé'.");
}

try {
    $p_pdo->beginTransaction();

    // 1. Enregistrer le vote individuel dans la table `vote_commission`
    $stmt_vote = $p_pdo->prepare(
        "INSERT INTO vote_commission (id_vote, id_rapport_etudiant, numero_enseignant, id_decision_vote, commentaire_vote, date_vote)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt_vote->execute([
        generateUniqueID('VOT'),
        $id_rapport,
        $id_votant,
        $decision_vote,
        $commentaire_vote
    ]);

    // 2. Vérifier si un statut final peut être déterminé (logique de décision)
    // Règle de gestion RG21 : Au moins 2 membres doivent valider un rapport pour qu'il soit ACCEPTÉ.
    $nombre_votes_approbation_requis = 2; 

    // Compter les votes "Approuvé" ou "Approuvé avec corrections"
    $stmt_count = $p_pdo->prepare(
        "SELECT COUNT(*) FROM vote_commission 
         WHERE id_rapport_etudiant = ? AND (id_decision_vote = 'APPROUVE' OR id_decision_vote = 'APPROUVE_CORRECTIONS')"
    );
    $stmt_count->execute([$id_rapport]);
    $nombre_approbations = $stmt_count->fetchColumn();

    $statut_final = null;
    if ($nombre_approbations >= $nombre_votes_approbation_requis) {
        $statut_final = 'ACCEPTÉ';
    }
    
    // (Optionnel) Ajouter une logique pour le refus, par exemple si 2 membres refusent.
    $stmt_count_refus = $p_pdo->prepare("SELECT COUNT(*) FROM vote_commission WHERE id_rapport_etudiant = ? AND id_decision_vote = 'REFUSE'");
    $stmt_count_refus->execute([$id_rapport]);
    $nombre_refus = $stmt_count_refus->fetchColumn();
    if ($nombre_refus >= 2) { // Hypothèse de règle de refus
        $statut_final = 'REFUSÉ';
    }


    // 3. Si un statut final est déterminé, mettre à jour le rapport
    if ($statut_final) {
        $stmt_update = $p_pdo->prepare("UPDATE rapport_etudiant SET id_statut_rapport = ?, date_derniere_modif = NOW() WHERE id_rapport_etudiant = ?");
        $stmt_update->execute([$statut_final, $id_rapport]);

        // Envoyer une notification finale à l'étudiant
        // ... (logique d'emailing)
    }

    $p_pdo->commit();
    logAction($id_votant, 'COMMISSION_VOTE', ['rapport_id' => $id_rapport, 'decision' => $decision_vote]);
    redirect('/dashboard_commission.php?success=Votre évaluation a été enregistrée avec succès.');

} catch (Exception $e) {
    $p_pdo->rollBack();
    error_log("Erreur d'évaluation : " . $e->getMessage());
    redirect("/evaluation_rapport.php?id=$id_rapport&error=Une erreur technique est survenue.");
}
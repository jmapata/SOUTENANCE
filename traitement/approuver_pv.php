<?php
// traitement/approuver_pv.php (Version finale avec la logique corrigée)
session_start();
require_once '../config/database.php';

function send_json_response(array $data) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// --- Sécurité et Récupération des données ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

$pv_id = $_POST['pv_id'] ?? null;
$decision = $_POST['decision_validation'] ?? null;
$commentaire = trim($_POST['commentaire_validation'] ?? '');
$user_id_session = $_SESSION['numero_utilisateur'];

if (!$pv_id || !$decision) {
    send_json_response(['success' => false, 'message' => 'Décision manquante.']);
}

try {
    $pdo->beginTransaction();

    // 1. Récupérer le numero_enseignant du membre qui vote
    $stmt_ens = $pdo->prepare("SELECT numero_enseignant FROM enseignant WHERE numero_utilisateur = ?");
    $stmt_ens->execute([$user_id_session]);
    $enseignant = $stmt_ens->fetch();
    if (!$enseignant) { throw new Exception("Profil enseignant non trouvé."); }
    $numero_enseignant_votant = $enseignant['numero_enseignant'];

    // ==========================================================
    // ## CORRECTION APPLIQUÉE ICI ##
    // ÉTAPE 2 : On enregistre D'ABORD le vote de l'utilisateur
    // ==========================================================
    $stmt_insert_vote = $pdo->prepare(
        "INSERT INTO validation_pv (id_compte_rendu, numero_enseignant, id_decision_validation_pv, commentaire_validation_pv, date_validation) 
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE 
            id_decision_validation_pv = VALUES(id_decision_validation_pv),
            commentaire_validation_pv = VALUES(commentaire_validation_pv),
            date_validation = NOW()"
    );
    $stmt_insert_vote->execute([$pv_id, $numero_enseignant_votant, $decision, $commentaire]);
    
    // --- ÉTAPE 3 : On gère la logique APRÈS l'enregistrement ---
    if ($decision === 'APPROB_PV_NON') {
        // Si un membre demande une modification, on remet le PV en brouillon
        $stmt_revert = $pdo->prepare("UPDATE compte_rendu SET id_statut_pv = 'PV_BROUILLON' WHERE id_compte_rendu = ?");
        $stmt_revert->execute([$pv_id]);
        $_SESSION['success_message'] = "Une demande de modification a été enregistrée. Le PV a été retourné au rédacteur.";
    } else {
        // Sinon (si c'est une approbation), on compte le total des approbations pour vérifier le consensus
        $nombre_membres_commission = 4;

        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM validation_pv WHERE id_compte_rendu = ? AND id_decision_validation_pv = 'APPROB_PV_OUI'");
        $stmt_count->execute([$pv_id]);
        $nombre_approbations = $stmt_count->fetchColumn();

        if ($nombre_approbations >= $nombre_membres_commission) {
            // Le consensus est atteint, on finalise le PV
            $stmt_finalize = $pdo->prepare("UPDATE compte_rendu SET id_statut_pv = 'PV_VALID' WHERE id_compte_rendu = ?");
            $stmt_finalize->execute([$pv_id]);
            $_SESSION['success_message'] = "Approbation enregistrée. Consensus atteint, le PV est maintenant validé !";
        } else {
            $_SESSION['success_message'] = "Votre approbation a été enregistrée. En attente de(s) " . ($nombre_membres_commission - $nombre_approbations) . " autre(s) membre(s).";
        }
    }
     // ## AUDIT DE L'ACTION ##
    $id_action_audit = ($decision === 'APPROB_PV_OUI') ? 'COMMISSION_APPROBATION_PV' : 'COMMISSION_REJET_PV';
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
         VALUES (?, ?, ?, NOW(), ?, 'compte_rendu')"
    );
    $stmt_audit->execute([$audit_id, $user_id_session, $id_action_audit, $pv_id]);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
}

// Redirection vers la page de gestion des PV
header('Location: ../dashboard_commission.php?page=gestion_pv');
exit();
?>
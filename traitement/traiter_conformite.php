<?php
// traitement/traiter_conformite.php (Version finale avec UPSERT)
session_start();
require_once '../config/database.php';

function send_json_response(array $data) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// Sécurité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_CONFORMITE' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

$rapport_id = $_POST['rapport_id'] ?? null;
$decision = $_POST['decision'] ?? null;
$commentaire = trim($_POST['commentaire'] ?? '');
$user_id_session = $_SESSION['numero_utilisateur'];

if (!$rapport_id || !$decision) {
    send_json_response(['success' => false, 'message' => 'Données manquantes.']);
}
if ($decision === 'non_conforme' && empty($commentaire)) {
    send_json_response(['success' => false, 'message' => 'Un commentaire est obligatoire en cas de non-conformité.']);
}

try {
    $pdo->beginTransaction();

    // Récupérer le bon ID du personnel
    $stmt_pers = $pdo->prepare("SELECT numero_personnel_administratif FROM personnel_administratif WHERE numero_utilisateur = ?");
    $stmt_pers->execute([$user_id_session]);
    $personnel = $stmt_pers->fetch();
    if (!$personnel) {
        throw new Exception("Profil du personnel administratif non trouvé.");
    }
    $personnel_id = $personnel['numero_personnel_administratif'];

    // Définir les statuts
    $nouveau_statut_rapport = ($decision === 'conforme') ? 'RAP_EN_COMMISSION' : 'RAP_NON_CONF';
    $statut_conformite_log = ($decision === 'conforme') ? 'CONF_CONFORME' : 'CONF_NON_CONFORME';

    // Mettre à jour le statut du rapport
    $stmt_update = $pdo->prepare("UPDATE rapport_etudiant SET id_statut_rapport = ? WHERE id_rapport_etudiant = ?");
    $stmt_update->execute([$nouveau_statut_rapport, $rapport_id]);

    // ==========================================================
    // ## CORRECTION APPLIQUÉE ICI ##
    // On utilise INSERT ... ON DUPLICATE KEY UPDATE pour éviter l'erreur de doublon
    // ==========================================================
    $sql_log = "
        INSERT INTO approuver 
            (numero_personnel_administratif, id_rapport_etudiant, id_statut_conformite, commentaire_conformite, date_verification_conformite) 
        VALUES 
            (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            id_statut_conformite = VALUES(id_statut_conformite),
            commentaire_conformite = VALUES(commentaire_conformite),
            date_verification_conformite = NOW()
    ";
    
    $stmt_log = $pdo->prepare($sql_log);
    $stmt_log->execute([$personnel_id, $rapport_id, $statut_conformite_log, $commentaire]);

    $pdo->commit();
    
    send_json_response(['success' => true, 'message' => "Décision enregistrée avec succès. Le rapport a été traité."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json_response(['success' => false, 'message' => "Erreur lors de l'enregistrement : " . $e->getMessage()]);
}
?>
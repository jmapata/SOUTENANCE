<?php
// traitement/get_stage_details.php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (ob_get_level()) ob_end_clean();

function send_json_response(array $data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

require_once '../config/database.php';

// ==========================================================
// SÉCURITÉ : VÉRIFICATION DE L'AUTORISATION
// ==========================================================
// Seuls les gestionnaires de scolarité ou administrateurs peuvent voir ces détails
if (!isset($_SESSION['loggedin']) || 
    !in_array($_SESSION['user_group'], ['GRP_SCOLARITE', 'GRP_ADMIN_SYS'])) {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

// ==========================================================
// VÉRIFICATION DES PARAMÈTRES
// ==========================================================
$etudiant_id = $_GET['etudiant_id'] ?? null;

if (empty($etudiant_id)) {
    send_json_response(['success' => false, 'message' => 'ID étudiant manquant.']);
}

try {
    // --- Récupérer les informations du stage de l'étudiant ---
    $stage_info = null;
    $stmt_stage = $pdo->prepare("
        SELECT fs.*, e.libelle_entreprise
        FROM faire_stage fs
        LEFT JOIN entreprise e ON fs.id_entreprise = e.id_entreprise
        WHERE fs.numero_carte_etudiant = ?
        ORDER BY fs.date_debut_stage DESC
        LIMIT 1
    ");
    $stmt_stage->execute([$etudiant_id]);
    $stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

    // --- Récupérer le document de preuve principal pour ce stage ---
    $proof_document = null;
    if ($stage_info) {
        $stmt_proof_doc = $pdo->prepare("
            SELECT dg.*, tdr.libelle_type_document
            FROM document_genere dg
            JOIN type_document_ref tdr ON dg.id_type_document = tdr.id_type_document
            WHERE dg.id_entite_concernee = ? 
              AND dg.type_entite_concernee = 'etudiant_stage'
              AND dg.id_type_document = 'DOC_STAGE_PREUVE'
            ORDER BY dg.date_generation DESC
            LIMIT 1
        ");
        $stmt_proof_doc->execute([$etudiant_id]);
        $proof_document = $stmt_proof_doc->fetch(PDO::FETCH_ASSOC);
    }

    send_json_response([
        'success' => true,
        'stage_info' => $stage_info,
        'proof_document' => $proof_document
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO dans get_stage_details.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Erreur de base de données lors de la récupération des détails du stage.']);
} catch (Exception $e) {
    error_log("Erreur inattendue dans get_stage_details.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Une erreur inattendue est survenue.']);
}
// Pas de balise de fermeture PHP `?>`
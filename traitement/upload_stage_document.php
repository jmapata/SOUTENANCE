<?php
// traitement/upload_stage_document.php
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
require_once '../services/IdentifiantGenerator.php';

if (!class_exists('IdentifiantGenerator')) {
    send_json_response(['success' => false, 'message' => 'Erreur interne: Le service de génération d\'ID est manquant ou contient une erreur.']);
}

// ==========================================================
// SÉCURITÉ : VÉRIFICATION DE L'AUTORISATION
// ==========================================================
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

// ==========================================================
// VÉRIFICATION ET RÉCUPÉRATION DES DONNÉES
// ==========================================================
$numero_carte_etudiant = $_POST['numero_carte_etudiant'] ?? null;
$numero_utilisateur_session = $_SESSION['numero_utilisateur'] ?? null;
$document_type = $_POST['document_type'] ?? null; // Pour les nouveaux uploads
$document_id_to_replace = $_POST['document_id_to_replace'] ?? null; // Pour les remplacements
$document_type_to_replace = $_POST['document_type_to_replace'] ?? null; // Type original du document à remplacer

// Déterminer si c'est un nouvel upload ou un remplacement
$is_replacement = !empty($document_id_to_replace);
$final_document_type = $is_replacement ? $document_type_to_replace : $document_type;

// Vérifier que l'étudiant qui téléverse est bien celui dont le numéro de carte est fourni
$stmt_check_etudiant = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
$stmt_check_etudiant->execute([$numero_utilisateur_session]);
$etudiant_recherche = $stmt_check_etudiant->fetchColumn();

if (!$numero_carte_etudiant || $numero_carte_etudiant !== $etudiant_recherche) {
    send_json_response(['success' => false, 'message' => 'Erreur de sécurité : L\'ID étudiant ne correspond pas à l\'utilisateur connecté.']);
}

if (empty($final_document_type)) {
    send_json_response(['success' => false, 'message' => 'Type de document manquant.']);
}

// Vérification du fichier téléversé
$file_input_name = $is_replacement ? 'new_document_file' : 'document_file';
if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
    send_json_response(['success' => false, 'message' => 'Erreur lors du téléversement du fichier. Code: ' . ($_FILES[$file_input_name]['error'] ?? 'N/A')]);
}

$file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
$file_name = $_FILES[$file_input_name]['name'];
$file_size = $_FILES[$file_input_name]['size'];
$file_type = $_FILES[$file_input_name]['type'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// ==========================================================
// VALIDATION DU FICHIER
// ==========================================================
$allowed_extensions = ['pdf', 'doc', 'docx'];
$max_file_size = 5 * 1024 * 1024; // 5 Mo

if (!in_array($file_ext, $allowed_extensions)) {
    send_json_response(['success' => false, 'message' => 'Format de fichier non autorisé. Seuls PDF, DOC, DOCX sont acceptés.']);
}

if ($file_size > $max_file_size) {
    send_json_response(['success' => false, 'message' => 'Le fichier est trop volumineux. La taille maximale est de 5 Mo.']);
}

// ==========================================================
// TRAITEMENT DU FICHIER ET ENREGISTREMENT EN BASE DE DONNÉES
// ==========================================================
try {
    $pdo->beginTransaction();

    // Définir le dossier de destination pour les documents de stage
    $upload_dir = '../uploads/stage_documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Crée le dossier si inexistant
    }

    $document_id = $document_id_to_replace; // Si remplacement, on garde l'ID existant
    $action_audit_type = 'ETUDIANT_UPLOAD_DOC_STAGE'; // Par défaut pour un nouvel upload
    $message_response = 'Document téléversé avec succès !';

    if ($is_replacement) {
        // Récupérer l'ancien chemin du fichier pour le supprimer
        $stmt_old_path = $pdo->prepare("SELECT chemin_fichier FROM document_genere WHERE id_document_genere = ? AND id_entite_concernee = ?");
        $stmt_old_path->execute([$document_id_to_replace, $numero_carte_etudiant]);
        $old_file_path = $stmt_old_path->fetchColumn();

        if ($old_file_path && file_exists($old_file_path)) {
            unlink($old_file_path); // Supprimer l'ancien fichier
        }

        // Générer un nouveau nom de fichier pour le remplacement (pour éviter les problèmes de cache navigateur)
        $new_file_name = $document_id_to_replace . '_' . uniqid() . '.' . $file_ext; // Ajout d'un uniqid pour garantir un nom unique
        $destination_path = $upload_dir . $new_file_name;

        // Mettre à jour l'entrée existante dans la base de données
        $stmt_update_doc = $pdo->prepare("
            UPDATE document_genere SET 
                chemin_fichier = ?, 
                date_generation = NOW(), 
                version = version + 1,
                mime_type = ? -- Assurez-vous que votre table document_genere a une colonne mime_type
            WHERE id_document_genere = ? AND id_entite_concernee = ?
        ");
        $stmt_update_doc->execute([
            $destination_path,
            $_FILES[$file_input_name]['type'], // Utiliser le mime type réel du fichier
            $document_id_to_replace,
            $numero_carte_etudiant
        ]);
        $action_audit_type = 'ETUDIANT_REPLACE_DOC_STAGE'; // Nouvelle action d'audit
        $message_response = 'Document remplacé avec succès !';

    } else {
        // C'est un nouvel upload
        $document_id = IdentifiantGenerator::generateId('DOC', date('Y'));
        $new_file_name = $document_id . '.' . $file_ext;
        $destination_path = $upload_dir . $new_file_name;

        // Insérer une nouvelle entrée dans la base de données
        $stmt_insert_doc = $pdo->prepare("
            INSERT INTO document_genere 
                (id_document_genere, id_type_document, chemin_fichier, date_generation, version, id_entite_concernee, type_entite_concernee, numero_utilisateur_concerne, mime_type)
            VALUES 
                (?, ?, ?, NOW(), 1, ?, 'etudiant_stage', ?, ?)
        ");
        $stmt_insert_doc->execute([
            $document_id,
            $final_document_type,
            $destination_path,
            $numero_carte_etudiant,
            $numero_utilisateur_session,
            $_FILES[$file_input_name]['type'] // Enregistrer le mime type
        ]);
    }

    // Déplacer le fichier téléversé
    if (!move_uploaded_file($file_tmp_name, $destination_path)) {
        throw new Exception("Erreur lors du déplacement du fichier téléversé vers " . $destination_path);
    }

    // Enregistrer l'action d'audit
    $audit_id = IdentifiantGenerator::generateId('AUDIT', date('Y'));
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee, details_action) 
         VALUES (?, ?, ?, NOW(), ?, 'document_stage', ?)"
    );
    $details_action = json_encode([
        'document_id' => $document_id,
        'type_document' => $final_document_type,
        'nom_original' => $file_name,
        'taille' => $file_size,
        'action' => ($is_replacement ? 'replace' : 'upload')
    ]);
    $stmt_audit->execute([
        $audit_id,
        $numero_utilisateur_session,
        $action_audit_type,
        $document_id, // L'entité concernée est le document lui-même
        $details_action
    ]);

    $pdo->commit();
    send_json_response(['success' => true, 'message' => $message_response]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur PDO dans upload_stage_document.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Erreur de base de données lors du traitement du document.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur inattendue dans upload_stage_document.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}
// Pas de balise de fermeture PHP `?>`
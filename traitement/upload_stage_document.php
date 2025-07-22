<?php
// traitement/upload_stage_document.php
session_start();

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Nettoyage de tout buffer de sortie avant d'envoyer la réponse JSON
if (ob_get_level()) ob_end_clean();

/**
 * Fonction pour envoyer une réponse JSON et arrêter l'exécution.
 * @param array $data Les données à encoder en JSON.
 */
function send_json_response(array $data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// --- Inclusions nécessaires ---
require_once '../config/database.php';
// L'inclusion de IdentifiantGenerator.php est nécessaire pour générer les IDs DOC si vous n'utilisez pas uniqid()
// et pour l'audit si vous le réactivez.
require_once '../services/IdentifiantGenerator.php'; // Garder cette inclusion si vous voulez des IDs DOC-ANNEE-SEQUENCE

// Vérifier si la classe IdentifiantGenerator existe (si vous l'incluez)
if (!class_exists('IdentifiantGenerator')) {
    send_json_response(['success' => false, 'message' => 'Erreur interne: Le service de génération d\'ID est manquant ou contient une erreur.']);
}


// ==========================================================
// SÉCURITÉ : VÉRIFICATION DE L'AUTORISATION
// ==========================================================
// Seuls les étudiants connectés peuvent téléverser leurs documents de stage.
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

// ==========================================================
// VÉRIFICATION ET RÉCUPÉRATION DES DONNÉES
// ==========================================================
$numero_carte_etudiant = $_POST['numero_carte_etudiant'] ?? null;
$numero_utilisateur_session = $_SESSION['numero_utilisateur'] ?? null; // Utilisé pour fk dans document_genere
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

    // --- DÉFINITION DES CHEMINS PHYSIQUES ET WEB ---
    // $_SERVER['DOCUMENT_ROOT'] est la racine du serveur web (ex: C:/wamp64/www)
    // Assurez-vous que '/SOUTENANCE/' est le chemin de votre projet depuis la racine du serveur web.
    // Si votre projet est directement à la racine (ex: http://127.0.0.1/mon_projet.php), utilisez '/'.
    $project_web_root = '/SOUTENANCE/'; // <--- TRÈS IMPORTANT : AJUSTER CE CHEMIN SELON VOTRE CONFIGURATION APACHE/WAMP

    // Chemin physique complet sur le système de fichiers du serveur
    $file_system_base_dir = $_SERVER['DOCUMENT_ROOT'] . $project_web_root . 'uploads/stage_documents/';
    // Chemin accessible via le navigateur web (stocké en base de données)
    $web_accessible_base_url = $project_web_root . 'uploads/stage_documents/';

    if (!is_dir($file_system_base_dir)) {
        mkdir($file_system_base_dir, 0777, true); // Crée le dossier si inexistant
    }

    $document_id = $document_id_to_replace; // Si remplacement, on garde l'ID existant
    $message_response = 'Document téléversé avec succès !';

    if ($is_replacement) {
        // Récupérer l'ancien chemin web stocké pour le convertir en chemin physique et le supprimer
        $stmt_old_path = $pdo->prepare("SELECT chemin_fichier FROM document_genere WHERE id_document_genere = ? AND id_entite_concernee = ?");
        $stmt_old_path->execute([$document_id_to_replace, $numero_carte_etudiant]);
        $old_stored_web_path = $stmt_old_path->fetchColumn();

        if ($old_stored_web_path) {
            // Convertir l'URL web stockée en chemin physique pour la suppression
            // On doit s'assurer que $old_stored_web_path commence bien par $project_web_root
            $old_file_system_path = $_SERVER['DOCUMENT_ROOT'] . $old_stored_web_path;
            
            if (file_exists($old_file_system_path)) {
                unlink($old_file_system_path); // Supprimer l'ancien fichier
            }
        }

        // Générer un nouveau nom de fichier pour le remplacement (pour éviter les problèmes de cache navigateur)
        $new_file_name = $document_id_to_replace . '_' . uniqid() . '.' . $file_ext; 
        $file_system_destination_path = $file_system_base_dir . $new_file_name;
        $web_accessible_destination_path = $web_accessible_base_url . $new_file_name; // Chemin à stocker en BD

        // Mettre à jour l'entrée existante dans la base de données
        $stmt_update_doc = $pdo->prepare("
            UPDATE document_genere SET 
                chemin_fichier = ?, 
                date_generation = NOW(), 
                version = version + 1,
                mime_type = ? 
            WHERE id_document_genere = ? AND id_entite_concernee = ?
        ");
        $stmt_update_doc->execute([
            $web_accessible_destination_path, // Stocker le chemin web
            $_FILES[$file_input_name]['type'], 
            $document_id_to_replace,
            $numero_carte_etudiant
        ]);
        $message_response = 'Document remplacé avec succès !';

    } else {
        // C'est un nouvel upload
        // Utilisation de IdentifiantGenerator pour un ID lisible et structuré
        $document_id = IdentifiantGenerator::generateId('DOC', date('Y')); 
        $new_file_name = $document_id . '.' . $file_ext;
        $file_system_destination_path = $file_system_base_dir . $new_file_name;
        $web_accessible_destination_path = $web_accessible_base_url . $new_file_name; // Chemin à stocker en BD

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
            $web_accessible_destination_path, // Stocker le chemin web
            $numero_carte_etudiant,
            $numero_utilisateur_session,
            $_FILES[$file_input_name]['type'] 
        ]);
    }

    // Déplacer le fichier téléversé (utilise le chemin physique)
    if (!move_uploaded_file($file_tmp_name, $file_system_destination_path)) {
        throw new Exception("Erreur lors du déplacement du fichier téléversé vers " . $file_system_destination_path . ". Vérifiez les permissions du dossier: " . $file_system_base_dir);
    }

    $pdo->commit();
    send_json_response(['success' => true, 'message' => $message_response]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur PDO dans upload_stage_document.php: " . $e->getMessage() . " Code: " . $e->getCode());
    send_json_response(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur inattendue dans upload_stage_document.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}
// Pas de balise de fermeture PHP `?>`

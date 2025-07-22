<?php
// traitement/delete_document.php
session_start();

// --- Configuration du débogage (à désactiver en production) ---
error_reporting(E_ALL); // Rapporte TOUTES les erreurs PHP
ini_set('display_errors', 1); // Affiche les erreurs directement dans la sortie

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
require_once '../services/IdentifiantGenerator.php'; // Nécessaire pour l'audit

// Vérifier si la classe IdentifiantGenerator existe après l'inclusion.
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
$document_id = $_POST['document_id'] ?? null;
$numero_carte_etudiant_from_client = $_POST['numero_carte_etudiant'] ?? null; // Reçu du client pour vérification
$numero_utilisateur_session = $_SESSION['numero_utilisateur'];

if (empty($document_id)) {
    send_json_response(['success' => false, 'message' => 'ID du document manquant.']);
}

try {
    $pdo->beginTransaction();

    // Récupérer les informations du document pour vérification et suppression du fichier
    $stmt_doc = $pdo->prepare("SELECT chemin_fichier, id_entite_concernee FROM document_genere WHERE id_document_genere = ?");
    $stmt_doc->execute([$document_id]);
    $doc_info = $stmt_doc->fetch(PDO::FETCH_ASSOC);

    if (!$doc_info) {
        throw new Exception("Document non trouvé ou déjà supprimé.");
    }

    // Vérifier que l'étudiant connecté est bien le propriétaire du document
    // (id_entite_concernee devrait être le numero_carte_etudiant)
    $stmt_check_etudiant = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
    $stmt_check_etudiant->execute([$numero_utilisateur_session]);
    $etudiant_recherche = $stmt_check_etudiant->fetchColumn();

    if ($doc_info['id_entite_concernee'] !== $etudiant_recherche || $numero_carte_etudiant_from_client !== $etudiant_recherche) {
        throw new Exception("Erreur de sécurité : Vous n'êtes pas autorisé à supprimer ce document.");
    }

    // =========================================================================================
    // MODIFICATION : Convertir l'URL web stockée en chemin physique pour la suppression du fichier
    // =========================================================================================
    // $_SERVER['DOCUMENT_ROOT'] est la racine du serveur web (ex: C:/wamp64/www)
    // Assurez-vous que '/SOUTENANCE/' est le chemin de votre projet depuis la racine du serveur web.
    // Si votre projet est directement à la racine (ex: http://127.0.0.1/mon_projet.php), utilisez '/'.
    $project_web_root = '/SOUTENANCE/'; // <--- TRÈS IMPORTANT : AJUSTER CE CHEMIN SELON VOTRE CONFIGURATION APACHE/WAMP

    $file_system_path_to_delete = $_SERVER['DOCUMENT_ROOT'] . $doc_info['chemin_fichier'];
    
    // Supprimer le fichier du système de fichiers
    if (file_exists($file_system_path_to_delete)) {
        if (!unlink($file_system_path_to_delete)) {
            throw new Exception("Impossible de supprimer le fichier physique du document à l'emplacement: " . $file_system_path_to_delete . ". Vérifiez les permissions.");
        }
    } else {
        // Optionnel: Logguer si le fichier n'existe pas physiquement mais est en BD
        error_log("Tentative de suppression d'un document inexistant physiquement: " . $file_system_path_to_delete);
    }

    // Supprimer l'entrée de la base de données
    $stmt_delete = $pdo->prepare("DELETE FROM document_genere WHERE id_document_genere = ?");
    $stmt_delete->execute([$document_id]);

    // AUDIT : Enregistrer l'action de suppression
    // Cette partie est incluse car l'audit est une fonctionnalité clé de votre projet.
    $audit_id = IdentifiantGenerator::generateId('AUDIT', date('Y'));
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee, details_action) 
         VALUES (?, ?, ?, NOW(), ?, 'document_genere', ?)"
    );
    $details_action = json_encode([
        'document_id' => $document_id,
        'action_type' => 'delete_document',
        'chemin_fichier_supprime' => $doc_info['chemin_fichier']
    ]);
    $stmt_audit->execute([
        $audit_id,
        $numero_utilisateur_session,
        'ETUDIANT_DEL_DOC_STAGE', // Assurez-vous que cette action existe dans votre table 'action'
        $document_id,
        $details_action
    ]);

    $pdo->commit();
    send_json_response(['success' => true, 'message' => 'Document supprimé avec succès.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur PDO dans delete_document.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Erreur de base de données lors de la suppression du document: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur inattendue dans delete_document.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}
// Pas de balise de fermeture PHP `?>`

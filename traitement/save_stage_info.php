<?php
// traitement/save_stage_info.php
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
require_once '../services/IdentifiantGenerator.php'; // Nécessaire pour générer l'ID de l'entreprise et l'audit

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
$libelle_entreprise = trim($_POST['libelle_entreprise'] ?? ''); // Nouveau : nom de l'entreprise
$date_debut_stage = $_POST['date_debut_stage'] ?? null;
$date_fin_stage = $_POST['date_fin_stage'] ?? null; // Peut être NULL
$nom_tuteur_entreprise = trim($_POST['nom_tuteur_entreprise'] ?? '');
$sujet_stage = trim($_POST['sujet_stage'] ?? '');
$numero_utilisateur_session = $_SESSION['numero_utilisateur'];

// Vérifier que l'étudiant qui soumet est bien celui dont le numéro de carte est fourni
$stmt_check_etudiant = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
$stmt_check_etudiant->execute([$numero_utilisateur_session]);
$etudiant_recherche = $stmt_check_etudiant->fetchColumn();

if (!$numero_carte_etudiant || $numero_carte_etudiant !== $etudiant_recherche) {
    send_json_response(['success' => false, 'message' => 'Erreur de sécurité : L\'ID étudiant ne correspond pas à l\'utilisateur connecté.']);
}

if (empty($libelle_entreprise) || empty($date_debut_stage) || empty($nom_tuteur_entreprise) || empty($sujet_stage)) {
    send_json_response(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires (Entreprise, Date de Début, Tuteur, Sujet).']);
}

// Validation des dates
if (!empty($date_fin_stage) && (new DateTime($date_debut_stage) > new DateTime($date_fin_stage))) {
    send_json_response(['success' => false, 'message' => 'La date de fin de stage ne peut pas être antérieure à la date de début.']);
}

try {
    $pdo->beginTransaction();

    // ==========================================================
    // GESTION DE L'ENTREPRISE : RÉCUPÉRER OU CRÉER L'ID
    // ==========================================================
    $id_entreprise = null;
    $stmt_find_entreprise = $pdo->prepare("SELECT id_entreprise FROM entreprise WHERE libelle_entreprise = ?");
    $stmt_find_entreprise->execute([$libelle_entreprise]);
    $entreprise_existante = $stmt_find_entreprise->fetchColumn();

    if ($entreprise_existante) {
        $id_entreprise = $entreprise_existante;
    } else {
        // L'entreprise n'existe pas, la créer
        $id_entreprise = IdentifiantGenerator::generateId('ENT', date('Y')); // Génère un ID pour la nouvelle entreprise
        $stmt_insert_entreprise = $pdo->prepare("
            INSERT INTO entreprise (id_entreprise, libelle_entreprise) VALUES (?, ?)
        ");
        $stmt_insert_entreprise->execute([$id_entreprise, $libelle_entreprise]);
    }

    // Vérifier si un enregistrement de stage existe déjà pour cet étudiant
    $stmt_check_stage = $pdo->prepare("SELECT COUNT(*) FROM faire_stage WHERE numero_carte_etudiant = ?");
    $stmt_check_stage->execute([$numero_carte_etudiant]);
    $stage_exists = $stmt_check_stage->fetchColumn();

    if ($stage_exists) {
        // Mettre à jour le stage existant
        $stmt_update_stage = $pdo->prepare("
            UPDATE faire_stage SET 
                id_entreprise = ?, 
                date_debut_stage = ?, 
                date_fin_stage = ?, 
                sujet_stage = ?, 
                nom_tuteur_entreprise = ?
            WHERE numero_carte_etudiant = ?
        ");
        $stmt_update_stage->execute([
            $id_entreprise,
            $date_debut_stage,
            $date_fin_stage ?: null, // Assure que la date de fin est NULL si vide
            $sujet_stage,
            $nom_tuteur_entreprise,
            $numero_carte_etudiant
        ]);
        $message = 'Informations de stage mises à jour avec succès !';
        $action_audit = 'ETUDIANT_MAJ_STAGE';
    } else {
        // Insérer un nouveau stage
        $stmt_insert_stage = $pdo->prepare("
            INSERT INTO faire_stage 
                (id_entreprise, numero_carte_etudiant, date_debut_stage, date_fin_stage, sujet_stage, nom_tuteur_entreprise)
            VALUES 
                (?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert_stage->execute([
            $id_entreprise,
            $numero_carte_etudiant,
            $date_debut_stage,
            $date_fin_stage ?: null,
            $sujet_stage,
            $nom_tuteur_entreprise
        ]);
        $message = 'Informations de stage enregistrées avec succès !';
        $action_audit = 'ETUDIANT_ADD_STAGE';
    }

    // AUDIT : Enregistrer l'action
    $audit_id = IdentifiantGenerator::generateId('AUDIT', date('Y'));
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee, details_action) 
         VALUES (?, ?, ?, NOW(), ?, 'stage', ?)"
    );
    $details_action = json_encode([
        'id_entreprise' => $id_entreprise,
        'libelle_entreprise' => $libelle_entreprise,
        'sujet_stage' => $sujet_stage,
        'action_type' => ($stage_exists ? 'update' : 'insert')
    ]);
    $stmt_audit->execute([
        $audit_id,
        $numero_utilisateur_session,
        $action_audit,
        $numero_carte_etudiant, // L'entité concernée est l'étudiant via sa carte
        $details_action
    ]);

    $pdo->commit();
    send_json_response(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur PDO dans save_stage_info.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => 'Erreur de base de données lors de l\'enregistrement des informations de stage.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur inattendue dans save_stage_info.php: " . $e->getMessage());
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}
// Pas de balise de fermeture PHP `?>`
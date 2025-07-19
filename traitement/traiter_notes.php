<?php
// traitement/traiter_notes.php
session_start();

// Nettoyage de tout buffer de sortie.
// C'est la première instruction exécutable. Si de la sortie est générée avant, elle sera effacée.
if (ob_get_level()) ob_end_clean();

/**
 * Fonction pour envoyer une réponse JSON et arrêter l'exécution.
 * @param array $data Les données à encoder en JSON.
 */
function send_json_response(array $data) {
    // Il est généralement suffisant d'appeler ob_end_clean() une fois au début du script.
    // Mais le garder ici est une sécurité si d'autres parties du code ouvrent des buffers.
    // if (ob_get_level()) ob_end_clean(); // Déjà fait au début du fichier
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// --- Inclusion de la base de données ---
// Vérifiez encore et encore ce fichier : pas d'espaces/sauts de ligne/BOM avant <?php,
// ni après ?> si présent. Idéalement, supprimez ?>.
require_once '../config/database.php'; 

// --- Inclusion du service IdentifiantGenerator ---
// =========================================================================================
// MODIFICATION CRITIQUE ICI : VÉRIFIER CE FICHIER IdentifiantGenerator.php
// =========================================================================================
// Ce fichier est un NOUVEAU point de pollution potentiel.
// Assurez-vous que '../services/IdentifiantGenerator.php' :
// 1. Existe bien et le chemin est correct.
// 2. Ne contient AUCUN espace, saut de ligne, caractère invisible (comme un BOM)
//    avant la balise '<?php' au début du fichier.
// 3. Ne contient AUCUN espace, saut de ligne, ou caractère APRÈS la balise de fermeture '?>'
//    (La meilleure pratique est de SUPPRIMER la balise de fermeture '?>' si le fichier ne contient que du code PHP).
require_once '../services/IdentifiantGenerator.php'; 

// Vérifier si la classe IdentifiantGenerator existe
if (!class_exists('IdentifiantGenerator')) {
    send_json_response(['success' => false, 'message' => 'Erreur: La classe IdentifiantGenerator est introuvable. Vérifiez le fichier de service.']);
}


// ==========================================================
// SÉCURITÉ : VÉRIFICATION DE L'AUTORISATION ET DE LA MÉTHODE
// ==========================================================
if (!isset($_SESSION['loggedin']) || 
    !in_array($_SESSION['user_group'], ['GRP_SCOLARITE', 'GRP_ADMIN_SYS']) ||
    $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

$etudiant_id = $_POST['etudiant_id'] ?? null;
$notes_saisies = $_POST['notes'] ?? []; // Tableau associatif id_ecue => note
$user_id_session = $_SESSION['numero_utilisateur'];

if (!$etudiant_id || empty($notes_saisies)) {
    send_json_response(['success' => false, 'message' => 'Données de notes manquantes.']);
}

// Récupérer l'ID de l'année académique active (TRÈS IMPORTANT pour la table evaluer)
$stmt_annee_active = $pdo->query("SELECT id_annee_academique FROM annee_academique WHERE est_active = 1");
$annee_active_id = $stmt_annee_active->fetchColumn();

if (!$annee_active_id) {
    send_json_response(['success' => false, 'message' => 'Impossible de déterminer l\'année académique active. Vérifiez la configuration de l\'année académique.']);
}

try {
    $pdo->beginTransaction();

    // Boucler sur chaque note saisie
    foreach ($notes_saisies as $ecue_id => $note_value) {
        // Valider la note : doit être un nombre, entre 0 et 20
        $note_value = trim($note_value);
        if ($note_value === '') { 
            continue; 
        }

        $note_float = floatval(str_replace(',', '.', $note_value));

        if (!is_numeric($note_float) || $note_float < 0 || $note_float > 20) {
            // =========================================================================================
            // MODIFICATION : Message d'erreur plus précis pour la note invalide
            // =========================================================================================
            throw new Exception("La note pour l'ECUE '$ecue_id' (valeur: '$note_value') est invalide (doit être un nombre entre 0 et 20).");
        }

        // Vérifier si la note existe déjà pour cet étudiant/ECUE/année
        $stmt_check_note = $pdo->prepare("
            SELECT COUNT(*) FROM evaluer 
            WHERE numero_carte_etudiant = ? AND id_ecue = ? AND id_annee_academique = ?
        ");
        $stmt_check_note->execute([$etudiant_id, $ecue_id, $annee_active_id]);
        $note_exists = $stmt_check_note->fetchColumn();

        if ($note_exists) {
            // Mettre à jour la note existante
            $stmt_update_note = $pdo->prepare("
                UPDATE evaluer SET note = ?, date_evaluation = NOW()
                WHERE numero_carte_etudiant = ? AND id_ecue = ? AND id_annee_academique = ?
            ");
            $stmt_update_note->execute([$note_float, $etudiant_id, $ecue_id, $annee_active_id]);
        } else {
            // Insérer une nouvelle note
            $stmt_insert_note = $pdo->prepare("
                INSERT INTO evaluer (numero_carte_etudiant, id_ecue, id_annee_academique, date_evaluation, note)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt_insert_note->execute([$etudiant_id, $ecue_id, $annee_active_id, $note_float]);
        }
    }

    // AUDIT : Enregistrer l'action de saisie/modification de notes
    // Vérifier que IdentifiantGenerator::generateId est appelée sur une classe existante.
    $audit_id = IdentifiantGenerator::generateId('AUDIT', date('Y')); 
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee, details_action) 
         VALUES (?, ?, ?, NOW(), ?, 'etudiant', ?)"
    );
    $details_action = json_encode(['notes_saisies' => $notes_saisies, 'annee_academique' => $annee_active_id]);
    $stmt_audit->execute([$audit_id, $user_id_session, 'SCOLARITE_ENREGISTREMENT_NOTE', $etudiant_id, $details_action]);


    $pdo->commit();
    send_json_response(['success' => true, 'message' => 'Notes enregistrées avec succès.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur PDO dans traiter_notes.php: " . $e->getMessage() . " (Étudiant ID: " . $etudiant_id . ", User: " . $user_id_session . ")");
    send_json_response(['success' => false, 'message' => 'Erreur de base de données lors de l\'enregistrement des notes. Veuillez contacter l\'administrateur système.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur inattendue dans traiter_notes.php: " . $e->getMessage() . " (Étudiant ID: " . $etudiant_id . ", User: " . $user_id_session . ")");
    send_json_response(['success' => false, 'message' => $e->getMessage()]); // Affiche le message d'exception personnalisé
}

// NE PAS INCLURE LA BALISE DE FERMETURE PHP `?>` SI LE FICHIER NE CONTIENT QUE DU CODE PHP.
// C'est une source fréquente de pollution de la sortie.
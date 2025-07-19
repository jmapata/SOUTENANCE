<?php
// traitement/get_notes_etudiant.php
session_start();
require_once '../config/database.php'; // Assurez-vous que ce chemin est correct

function send_json_response(array $data) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// Vérifier si l'utilisateur est autorisé (par exemple, un gestionnaire scolarité ou admin)
// Adaptez les groupes selon votre configuration RBAC
if (!isset($_SESSION['loggedin']) || 
    !in_array($_SESSION['user_group'], ['GRP_SCOLARITE', 'GRP_ADMIN_SYS'])) {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

$etudiant_id = $_GET['etudiant_id'] ?? null;

if (!$etudiant_id) {
    send_json_response(['success' => false, 'message' => 'ID étudiant manquant.']);
}

try {
    // Récupérer les notes de l'étudiant pour l'année académique active (si applicable)
    // Pour cet exemple, nous récupérons toutes les notes pour cet étudiant,
    // mais vous pouvez ajouter un filtre par année académique si votre table 'evaluer' le supporte
    // ou si vous avez un paramètre pour l'année active.

    // Récupérer l'ID de l'année académique active si nécessaire
    // $stmt_annee_active = $pdo->query("SELECT id_annee_academique FROM annee_academique WHERE est_active = 1");
    // $annee_active_id = $stmt_annee_active->fetchColumn();
    // if (!$annee_active_id) {
    //     send_json_response(['success' => false, 'message' => 'Aucune année académique active trouvée.']);
    // }

    $stmt = $pdo->prepare("
        SELECT id_ecue, note
        FROM evaluer
        WHERE numero_carte_etudiant = ?
        -- AND id_annee_academique = ? -- Décommentez et ajoutez $annee_active_id si vous gérez par année
    ");
    // $stmt->execute([$etudiant_id, $annee_active_id]); // Si filtre par année
    $stmt->execute([$etudiant_id]); // Sans filtre par année
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json_response(['success' => true, 'notes' => $notes]);

} catch (PDOException $e) {
    send_json_response(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()]);
}
?>
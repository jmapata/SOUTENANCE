<?php
// traitement/traiter_conformite.php (Version finale avec UPSERT)
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php';

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

    // Envoi d'un email à l'étudiant si le rapport est non conforme
    if ($decision === 'non_conforme') {
        // Récupérer l'email et le nom de l'étudiant concerné
        $stmt_etud = $pdo->prepare("SELECT u.email_principal, e.prenom, e.numero_carte_etudiant FROM rapport_etudiant r JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur WHERE r.id_rapport_etudiant = ? LIMIT 1");
        $stmt_etud->execute([$rapport_id]);
        $etud = $stmt_etud->fetch();
        if ($etud && !empty($etud['email_principal'])) {
            $dest_email = $etud['email_principal'];
            $dest_nom = trim(($etud['prenom'] ?? '') . ' ' . ($etud['numero_carte_etudiant'] ?? ''));
            $sujet = 'Votre rapport n\'est pas conforme - ValidMaster';
            $contenu = "<p>Bonjour <b>" . htmlspecialchars($dest_nom) . ",<br>Votre rapport a été jugé <b>non conforme</b> par le contrôle de conformité.<br><br><i>" . "</i><br><br>Veuillez vous connecter à la plateforme ValidMaster pour corriger ou soumettre un nouveau rapport.<br><br>Cordialement,<br>L'équipe ValidMaster.</p>";
            envoyerEmail($dest_email, $dest_nom, $sujet, $contenu);
        }
    }
    // ## AUDIT DE L'ACTION ##
    $id_action_audit = ($decision === 'conforme') ? 'CONFORMITE_RAPPORT_APPROUVE' : 'CONFORMITE_RAPPORT_REJETE';
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
         VALUES (?, ?, ?, NOW(), ?, 'rapport_etudiant')"
    );
    $stmt_audit->execute([$audit_id, $user_id_session, $id_action_audit, $rapport_id]);


    $pdo->commit();
    
    send_json_response(['success' => true, 'message' => "Décision enregistrée avec succès. Le rapport a été traité."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json_response(['success' => false, 'message' => "Erreur lors de l'enregistrement : " . $e->getMessage()]);
}
?>
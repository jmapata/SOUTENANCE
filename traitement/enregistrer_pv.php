<?php
// traitement/enregistrer_pv.php (Version finale et corrigée)
session_start();
require_once '../config/database.php';

// --- Sécurité de base ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    header('Location: ../login.php');
    exit();
}

// --- Récupération des données du formulaire ---
$pv_id = $_POST['pv_id'] ?? null;
$contenu_pv = $_POST['contenu_pv'] ?? '';
$action = $_POST['action'] ?? '';
$user_id_session = $_SESSION['numero_utilisateur'];

// --- Validation des données ---
if (!$pv_id || !$action || ($action !== 'sauvegarder_brouillon' && $action !== 'soumettre_validation')) {
    $_SESSION['error_message'] = "Action non valide ou données manquantes.";
    header('Location: ../dashboard_commission.php?page=gestion_pv');
    exit();
}

try {
    // ==========================================================
    // ## CORRECTION APPLIQUÉE ICI ##
    // La requête vérifie maintenant directement si l'ID de l'utilisateur en session
    // correspond à l'ID du rédacteur enregistré pour ce PV.
    // ==========================================================
    $stmt_check = $pdo->prepare("SELECT id_redacteur FROM compte_rendu WHERE id_compte_rendu = ? AND id_redacteur = ?");
    $stmt_check->execute([$pv_id, $user_id_session]);
    
    if ($stmt_check->fetch() === false) {
        // Si la requête ne retourne rien, cela signifie que l'utilisateur connecté n'est pas le rédacteur.
        throw new Exception("Action non autorisée. Vous n'êtes pas le rédacteur de ce PV.");
    }
    
    // Si la vérification passe, on continue le script normalement.
    $pdo->beginTransaction();

    // Détermination du nouveau statut
    $nouveau_statut = ($action === 'soumettre_validation') ? 'PV_SOUMIS_VALID' : 'PV_BROUILLON';

    // Mise à jour du PV
    $stmt_update = $pdo->prepare("UPDATE compte_rendu SET libelle_compte_rendu = ?, id_statut_pv = ? WHERE id_compte_rendu = ?");
    $stmt_update->execute([$contenu_pv, $nouveau_statut, $pv_id]);
      // ## AUDIT DE L'ACTION (uniquement si le PV est soumis) ##
    if ($action === 'soumettre_validation') {
        $audit_id = 'AUDIT-' . strtoupper(uniqid());
        $stmt_audit = $pdo->prepare(
            "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
             VALUES (?, ?, 'COMMISSION_SOUMISSION_PV', NOW(), ?, 'compte_rendu')"
        );
        $stmt_audit->execute([$audit_id, $user_id_session, $pv_id]);
    }

    $pdo->commit();

    // Message de succès
    if ($action === 'soumettre_validation') {
        $_SESSION['success_message'] = "Le PV a été soumis à la validation de la commission.";
    } else {
        $_SESSION['success_message'] = "Le brouillon du PV a été sauvegardé avec succès.";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Erreur lors de l'enregistrement du PV : " . $e->getMessage();
}

// Redirection vers la liste des PV
header('Location: ../dashboard_commission.php?page=gestion_pv');
exit();
?>
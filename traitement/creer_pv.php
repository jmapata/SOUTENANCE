<?php
// traitement/creer_pv.php (Version finale et corrigée)
session_start();
require_once '../config/database.php';

// --- Sécurité et Validation des entrées ---
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION' || !isset($_GET['rapport_id'])) {
    header('Location: ../login.php');
    exit();
}

$rapport_id = $_GET['rapport_id'];
$user_id_session = $_SESSION['numero_utilisateur']; // L'ID de l'utilisateur connecté

try {
    // --- 1. Vérifier que l'utilisateur est bien un enseignant (règle métier) ---
    // Cette étape est juste une vérification, elle est importante pour la logique des droits.
    $stmt_ens = $pdo->prepare("SELECT numero_enseignant FROM enseignant WHERE numero_utilisateur = ?");
    $stmt_ens->execute([$user_id_session]);
    if (!$stmt_ens->fetch()) {
        throw new Exception("Seul un profil enseignant peut rédiger un PV.");
    }

    // --- 2. Vérifier si un PV existe déjà pour ce rapport ---
    $stmt_check = $pdo->prepare("SELECT id_compte_rendu FROM compte_rendu WHERE id_rapport_etudiant = ?");
    $stmt_check->execute([$rapport_id]);
    $existing_pv = $stmt_check->fetch();

    if ($existing_pv) {
        // Si un PV existe déjà, on redirige simplement vers la page d'édition.
        header('Location: ../dashboard_commission.php?page=editer_pv&id=' . $existing_pv['id_compte_rendu']);
        exit();
    }
    
    // --- 3. Si aucun PV n'existe, on le crée ---
    $pdo->beginTransaction();

    // Récupérer le titre du rapport pour le titre du PV
    $stmt_rapport_info = $pdo->prepare("SELECT libelle_rapport_etudiant FROM rapport_etudiant WHERE id_rapport_etudiant = ?");
    $stmt_rapport_info->execute([$rapport_id]);
    $rapport_info = $stmt_rapport_info->fetch();
    $pv_title = "Procès-Verbal pour le rapport : " . ($rapport_info['libelle_rapport_etudiant'] ?? 'Sans Titre');

    $pv_id = 'PV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
    
    // ==========================================================
    // ## CORRECTION APPLIQUÉE ICI ##
    // On insère l'ID de l'utilisateur depuis la session ($user_id_session) comme rédacteur,
    // car la table `compte_rendu` attend un `numero_utilisateur`.
    // ==========================================================
    $stmt_create = $pdo->prepare(
        "INSERT INTO compte_rendu 
            (id_compte_rendu, id_rapport_etudiant, type_pv, libelle_compte_rendu, id_statut_pv, id_redacteur) 
         VALUES 
            (?, ?, 'Individuel', ?, 'PV_BROUILLON', ?)"
    );
    // On laisse le contenu (`libelle_compte_rendu`) vide, il sera pré-rempli sur la page d'édition.
    $stmt_create->execute([$pv_id, $rapport_id, $pv_title, $user_id_session]);

    $pdo->commit();

    $_SESSION['success_message'] = "Le brouillon du PV a été créé. Vous pouvez commencer la rédaction.";
    header('Location: ../dashboard_commission.php?page=editer_pv&id=' . $pv_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    header('Location: ../dashboard_commission.php?page=gestion_pv');
    exit();
}
?>
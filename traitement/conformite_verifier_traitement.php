<?php
// /traitement/conformite_verifier_traitement.php
require_once '../includes/session_handler.php';
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// if (!checkPermission('TRAIT_CONFORMITE_VERIFIER')) { die("Accès non autorisé."); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/conformite_liste.php');
}

// --- Récupération des données ---
$id_rapport = $_POST['id_rapport'] ?? '';
$decision = $_POST['decision'] ?? '';
$commentaire = $_POST['commentaire'] ?? '';
$criteres_post = $_POST['critere'] ?? [];
$email_etudiant = $_POST['email_etudiant'];
$admin_id = $_SESSION['user_id'];

// --- Validation ---
if (empty($id_rapport) || empty($decision) || empty($criteres_post)) {
    redirect("/conformite_verifier.php?id=$id_rapport&error=Données manquantes.");
}
// Un commentaire est obligatoire si le rapport est jugé incomplet (RG12)
if ($decision === 'INCOMPLET' && empty(trim($commentaire))) {
    redirect("/conformite_verifier.php?id=$id_rapport&error=Un commentaire est obligatoire pour un rapport non conforme.");
}


try {
    $p_pdo->beginTransaction();

    // 1. Enregistrer le détail de chaque critère vérifié
    $stmt_critere = $p_pdo->prepare(
        "INSERT INTO conformite_rapport_details (id_conformite_detail, id_rapport_etudiant, id_critere, statut_validation, date_verification) 
         VALUES (:id_detail, :id_rapport, :id_critere, :statut, NOW())"
    );
    foreach ($criteres_post as $id_critere => $statut) {
        $stmt_critere->execute([
            'id_detail' => generateUniqueID('CRD'),
            'id_rapport' => $id_rapport,
            'id_critere' => $id_critere,
            'statut' => $statut
        ]);
    }

    // 2. Mettre à jour le statut global du rapport
    $stmt_update = $p_pdo->prepare("UPDATE rapport_etudiant SET id_statut_rapport = ?, date_derniere_modif = NOW() WHERE id_rapport_etudiant = ?");
    $stmt_update->execute([$decision, $id_rapport]);

    // 3. Enregistrer l'action d'approbation dans la table `approuver`
    $stmt_approuver = $p_pdo->prepare(
        "INSERT INTO approuver (numero_personnel_administratif, id_rapport_etudiant, id_statut_conformite, commentaire_conformite, date_verification_conformite)
         VALUES (?, ?, ?, ?, NOW())"
    );
    // Note: 'id_statut_conformite' doit correspondre à une table de référence, ici on utilise la décision.
    $stmt_approuver->execute([$admin_id, $id_rapport, $decision, $commentaire]);
    
    $p_pdo->commit();
    
    // 4. Envoyer une notification à l'étudiant (RG17)
    $sujet = "Mise à jour du statut de votre rapport";
    $message_notif = "Bonjour,\n\nLe statut de votre rapport a été mis à jour : " . $decision . ".\n";
    if (!empty($commentaire)) {
        $message_notif .= "\nCommentaires de l'administration :\n" . $commentaire;
    }
    $message_notif .= "\n\nCordialement,\nLe service de la scolarité.";
    
    // Simulation d'envoi d'email
    error_log("Email envoyé à $email_etudiant : $sujet - $message_notif");
    // mail($email_etudiant, $sujet, $message_notif);

    logAction($admin_id, 'VERIFICATION_CONFORMITE', ['rapport_id' => $id_rapport, 'decision' => $decision]);

    redirect('/conformite_liste.php?success=La vérification du rapport a bien été enregistrée.');

} catch (Exception $e) {
    $p_pdo->rollBack();
    error_log("Erreur de vérification : " . $e->getMessage());
    redirect("/conformite_verifier.php?id=$id_rapport&error=Une erreur technique est survenue.");
}
<?php
session_start();
require_once(__DIR__ . '/../config/database.php');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['numero_utilisateur'])) {
    die("Erreur : utilisateur non connecté.");
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];

// Vérifie que l'utilisateur est bien membre de la commission
$stmt = $pdo->prepare("
    SELECT g.libelle_groupe_utilisateur
    FROM utilisateur u
    JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
    WHERE u.numero_utilisateur = ?
");
$stmt->execute([$numero_utilisateur]);
$groupe = $stmt->fetch();

if (!$groupe || $groupe['libelle_groupe_utilisateur'] !== 'Membre de Commission') {
    die("Erreur : vous n'avez pas les droits pour voter.");
}

// Récupération des données du formulaire
$id_rapport = $_POST['id_rapport_etudiant'] ?? null;
$id_decision = $_POST['id_decision_vote'] ?? null;
$commentaire = trim($_POST['commentaire_vote'] ?? '');

if (!$id_rapport || !$id_decision) {
    die("Tous les champs sont requis.");
}

// Vérifie si un vote existe déjà
$stmt_check = $pdo->prepare("
    SELECT * FROM vote_commission
    WHERE id_rapport_etudiant = ? AND numero_utilisateur = ?
");
$stmt_check->execute([$id_rapport, $numero_utilisateur]);
$vote_existant = $stmt_check->fetch();

if ($vote_existant) {
    // 🔁 Mise à jour du vote existant
    $stmt_update = $pdo->prepare("
        UPDATE vote_commission
        SET id_decision_vote = ?, commentaire_vote = ?, date_vote = NOW()
        WHERE id_rapport_etudiant = ? AND numero_utilisateur = ?
    ");
    $stmt_update->execute([
        $id_decision,
        $commentaire,
        $id_rapport,
        $numero_utilisateur
    ]);
} else {
    // 🆕 Nouveau vote
    $id_vote = uniqid('VOTE_');
    $stmt_insert = $pdo->prepare("
        INSERT INTO vote_commission (
            id_vote, id_rapport_etudiant, numero_utilisateur,
            id_decision_vote, commentaire_vote, date_vote, tour_vote
        ) VALUES (?, ?, ?, ?, ?, NOW(), 1)
    ");
    $stmt_insert->execute([
        $id_vote,
        $id_rapport,
        $numero_utilisateur,
        $id_decision,
        $commentaire
    ]);
}

// ✅ Redirection vers communication
header("Location: ../dashboard_commission.php?page=communication&topic=" . urlencode($id_rapport));
exit;

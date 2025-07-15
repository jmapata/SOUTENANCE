<?php
session_start();
require_once(__DIR__ . '/../config/database.php');

if (!isset($_SESSION['numero_utilisateur'])) {
    die("Erreur : utilisateur non connecté.");
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];
$id_rapport = $_POST['id_rapport_etudiant'] ?? null;
$decision = $_POST['decision_correction'] ?? null;
$commentaire = trim($_POST['commentaire_commission'] ?? '');

if (!$id_rapport || !$decision) {
    die("Tous les champs sont obligatoires.");
}

// Déterminer le nouveau statut
switch ($decision) {
    case 'accepte':
        $nouveau_statut = 'RAP_VALIDE';
        break;
    case 'refuse':
        $nouveau_statut = 'RAP_REFUSE_DEFINITIF';
        break;
    case 'discussion':
        $nouveau_statut = 'RAP_EN_DISCUSSION';
        break;
    default:
        die("Décision invalide.");
}

// Mettre à jour le statut dans la base
$stmt = $pdo->prepare("
    UPDATE rapport_etudiant
    SET id_statut_rapport = ?, date_statut = NOW()
    WHERE id_rapport_etudiant = ?
");
$stmt->execute([$nouveau_statut, $id_rapport]);

header("Location: ../dashboard_commission.php?correction=ok");
exit;

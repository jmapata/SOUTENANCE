<?php
require_once(__DIR__ . '/../config/database.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['numero_utilisateur'])) {
    die("Erreur : utilisateur non connecté.");
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];
$id_rapport = $_POST['id_rapport'] ?? null;
$contenu = trim($_POST['message'] ?? '');

if (!$id_rapport || $contenu === '') {
    die("Données incomplètes.");
}

// Vérifie si la conversation existe
$stmt_conv = $pdo->prepare("SELECT id_conversation FROM conversation WHERE nom_conversation = ?");
$stmt_conv->execute([$id_rapport]);
$conversation = $stmt_conv->fetch();

if (!$conversation) {
    // Crée la conversation
    $id_conversation = uniqid('CONV-');
    $stmt_create = $pdo->prepare("
        INSERT INTO conversation (id_conversation, nom_conversation, type_conversation)
        VALUES (?, ?, 'Groupe')
    ");
    $stmt_create->execute([$id_conversation, $id_rapport]);

    // Ajouter tous les membres de la commission
    $stmt_membres = $pdo->prepare("
        SELECT u.numero_utilisateur
        FROM utilisateur u
        JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
        WHERE g.libelle_groupe_utilisateur = 'Membre de Commission'
    ");
    $stmt_membres->execute();
    foreach ($stmt_membres->fetchAll() as $membre) {
        $stmt_add = $pdo->prepare("
            INSERT INTO participant_conversation (id_conversation, numero_utilisateur)
            VALUES (?, ?)
        ");
        $stmt_add->execute([$id_conversation, $membre['numero_utilisateur']]);
    }

} else {
    $id_conversation = $conversation['id_conversation'];
}

// Enregistre le message
$id_message = uniqid('MSG-');
$stmt_msg = $pdo->prepare("
    INSERT INTO message_chat (id_message_chat, id_conversation, numero_utilisateur_expediteur, contenu_message)
    VALUES (?, ?, ?, ?)
");
$stmt_msg->execute([$id_message, $id_conversation, $numero_utilisateur, $contenu]);

// Redirection propre
header("Location: ../dashboard_commission.php?page=communication&topic=" . urlencode($id_rapport));
exit;

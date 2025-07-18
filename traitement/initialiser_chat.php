<?php
// traitement/initialiser_chat.php
session_start();
require_once(__DIR__ . '/../config/database.php');

if (!isset($_SESSION['numero_utilisateur']) || !isset($_GET['topic'])) {
    header('Location: ../login.php');
    exit();
}

$id_rapport = $_GET['topic'];

try {
    // Vérifier si la conversation existe déjà
    $stmt_check = $pdo->prepare("SELECT id_conversation FROM conversation WHERE nom_conversation = ?");
    $stmt_check->execute([$id_rapport]);
    $conversation = $stmt_check->fetch();

    if (!$conversation) {
        // Si elle n'existe pas, on la crée et on ajoute les participants
        $id_conversation = 'CONV-' . strtoupper(uniqid());
        $pdo->beginTransaction();

        $stmt_create_conv = $pdo->prepare("INSERT INTO conversation (id_conversation, nom_conversation, type_conversation) VALUES (?, ?, 'Groupe')");
        $stmt_create_conv->execute([$id_conversation, $id_rapport]);

        $stmt_membres = $pdo->query("SELECT numero_utilisateur FROM utilisateur WHERE id_groupe_utilisateur = 'GRP_COMMISSION'");
        $membres = $stmt_membres->fetchAll(PDO::FETCH_COLUMN);

        $stmt_add_participant = $pdo->prepare("INSERT INTO participant_conversation (id_conversation, numero_utilisateur) VALUES (?, ?)");
        foreach ($membres as $membre_id) {
            $stmt_add_participant->execute([$id_conversation, $membre_id]);
        }
        
        $pdo->commit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error_message'] = "Erreur lors de l'initialisation du chat : " . $e->getMessage();
    header('Location: ../dashboard_commission.php?page=rapports_a_traiter');
    exit();
}

// Dans tous les cas, on redirige vers la page de communication
header("Location: ../dashboard_commission.php?page=communication&topic=" . urlencode($id_rapport));
exit();
?>
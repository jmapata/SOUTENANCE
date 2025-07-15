<?php
require_once(__DIR__ . '/../config/database.php');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['numero_utilisateur'])) {
    echo "Erreur : utilisateur non connecté.";
    exit;
}

$numero_utilisateur = $_SESSION['numero_utilisateur'];
$login_utilisateur = $_SESSION['login_utilisateur'] ?? '';
$id_rapport = $_GET['topic'] ?? null;

if (!$id_rapport) {
    echo "Aucun rapport précisé.";
    exit;
}

// Vérifier l'existence du rapport
$stmt_verif = $pdo->prepare("SELECT * FROM rapport_etudiant WHERE id_rapport_etudiant = ?");
$stmt_verif->execute([$id_rapport]);
$rapport = $stmt_verif->fetch();

if (!$rapport) {
    echo "Rapport introuvable.";
    exit;
}

// Vérifier ou récupérer la conversation
$stmt_conv = $pdo->prepare("SELECT id_conversation FROM conversation WHERE nom_conversation = ?");
$stmt_conv->execute([$id_rapport]);
$conversation = $stmt_conv->fetch();

if (!$conversation) {
    echo "Conversation non initialisée.";
    exit;
}
$id_conversation = $conversation['id_conversation'];

// Récupérer les messages
$stmt_messages = $pdo->prepare("
    SELECT m.contenu_message, m.date_envoi, u.login_utilisateur
    FROM message_chat m
    JOIN utilisateur u ON m.numero_utilisateur_expediteur = u.numero_utilisateur
    WHERE m.id_conversation = ?
    ORDER BY m.date_envoi ASC
");
$stmt_messages->execute([$id_conversation]);
$messages = $stmt_messages->fetchAll();
?>


<div class="page-header">
    <h2><i class="fa-solid fa-comments"></i> Discussion sur le rapport : <?= htmlspecialchars($rapport['libelle_rapport_etudiant']) ?></h2>
</div>

<div class="chat-container">
    <div class="chat-messages" id="chat-messages">
        <?php if (empty($messages)): ?>
            <p class="empty">Aucun message pour le moment.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                    $isOwn = $msg['login_utilisateur'] === $login_utilisateur;
                    $class = $isOwn ? 'own' : 'other';
                ?>
                <div class="bubble <?= $class ?>">
                    <div class="sender"><?= htmlspecialchars($msg['login_utilisateur']) ?></div>
                    <div class="content"><?= nl2br(htmlspecialchars($msg['contenu_message'])) ?></div>
                    <div class="time"><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="traitement/envoyer_message.php" class="chat-form">
    <input type="hidden" name="id_rapport" value="<?= htmlspecialchars($id_rapport) ?>">
    <textarea name="message" placeholder="Votre message ici..." required></textarea>
    <button type="submit">Envoyer</button>
</form>

</div>

<style>
.chat-container {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    max-width: 800px;
    margin: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.chat-messages {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}
.bubble {
    max-width: 70%;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 15px;
    position: relative;
    background-color: #f1f1f1;
}
.bubble.own {
    background-color: #d1ecf1;
    align-self: flex-end;
    text-align: right;
}
.bubble.other {
    background-color: #f9f9f9;
    align-self: flex-start;
}
.sender {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 0.9rem;
}
.content {
    font-size: 1rem;
    line-height: 1.4;
}
.time {
    font-size: 0.75rem;
    color: #777;
    margin-top: 5px;
}
.chat-form textarea {
    width: 100%;
    height: 80px;
    resize: none;
    margin-bottom: 10px;
}
.chat-form button {
    background: #007bff;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}
.chat-form button:hover {
    background: #0056b3;
}
.empty {
    text-align: center;
    color: #777;
}
</style>

<script>
// Scroll automatique en bas
const chatBox = document.getElementById('chat-messages');
chatBox.scrollTop = chatBox.scrollHeight;
</script>

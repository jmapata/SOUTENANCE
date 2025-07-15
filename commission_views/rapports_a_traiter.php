<?php
require_once(__DIR__ . '/../config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est membre de la commission
$stmt = $pdo->prepare("
    SELECT u.numero_utilisateur, g.libelle_groupe_utilisateur
    FROM utilisateur u
    JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
    WHERE u.numero_utilisateur = ?
");
$stmt->execute([$_SESSION['numero_utilisateur']]);
$utilisateur = $stmt->fetch();

if (!$utilisateur || $utilisateur['libelle_groupe_utilisateur'] !== 'Membre de Commission') {
    die("<div class='alert alert-danger'>Accès refusé : vous n'êtes pas membre de la commission.</div>");
}
$numero_utilisateur = $utilisateur['numero_utilisateur'];

// Rapports transmis par la conformité
$stmt_rapports = $pdo->prepare("
    SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.chemin_pdf, r.date_soumission,
           e.nom, e.prenom, r.theme
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION'
    ORDER BY r.date_soumission ASC
");
$stmt_rapports->execute();
$rapports = $stmt_rapports->fetchAll();

// Décisions disponibles
$decisions_vote = $pdo->query("SELECT * FROM decision_vote_ref")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fa-solid fa-folder-open"></i> Rapports à Traiter par la Commission</h2>
</div>

<?php if (empty($rapports)): ?>
    <div class="alert alert-info">Aucun rapport transmis par la conformité.</div>
<?php else: ?>
    <?php foreach ($rapports as $rapport): ?>
        <?php
        // Récupérer les votes du rapport
        $stmt_votes = $pdo->prepare("
            SELECT v.commentaire_vote, d.libelle_decision_vote, u.login_utilisateur, v.numero_utilisateur
            FROM vote_commission v
            JOIN decision_vote_ref d ON v.id_decision_vote = d.id_decision_vote
            JOIN utilisateur u ON v.numero_utilisateur = u.numero_utilisateur
            WHERE v.id_rapport_etudiant = ?
        ");
        $stmt_votes->execute([$rapport['id_rapport_etudiant']]);
        $votes = $stmt_votes->fetchAll();

        $vote_existant = null;
        foreach ($votes as $vote) {
            if ($vote['numero_utilisateur'] == $numero_utilisateur) {
                $vote_existant = $vote;
                break;
            }
        }
        ?>
        <div class="report-card">
            <div class="report-card-header">
                <h4><?= htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></h4>
                <span>Par : <?= htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></span>
            </div>

            <div class="report-card-body">
                <div class="vote-info">
                    <h5>📄 Rapport</h5>
                    <a href="<?= htmlspecialchars($rapport['chemin_pdf']); ?>" class="btn btn-sm btn-secondary" target="_blank">Consulter le document</a>
                    <p><strong>Thème :</strong> <?= htmlspecialchars($rapport['theme']); ?></p>
                    <hr>
                    <h5>🗳️ Votes enregistrés (<?= count($votes); ?>/4)</h5>
                    <?php if (empty($votes)): ?>
                        <p>Aucun vote encore exprimé.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($votes as $v): ?>
                                <li><strong><?= htmlspecialchars($v['login_utilisateur']); ?></strong> : <?= htmlspecialchars($v['libelle_decision_vote']); ?><br>
                                    <em><?= nl2br(htmlspecialchars($v['commentaire_vote'])) ?></em></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="vote-form-container">
                    <h5><?= $vote_existant ? "📝 Modifier mon vote" : "🗳️ Soumettre mon vote" ?></h5>
                    <form action="traitement/enregistrer_vote.php" method="POST">
                        <input type="hidden" name="id_rapport_etudiant" value="<?= $rapport['id_rapport_etudiant']; ?>">

                        <div class="form-group">
                            <label>Décision :</label>
                            <select name="id_decision_vote" class="form-control" required>
                                <option value="">-- Choisir une option --</option>
                                <?php foreach ($decisions_vote as $decision): ?>
                                    <option value="<?= $decision['id_decision_vote']; ?>" <?= ($vote_existant && $vote_existant['libelle_decision_vote'] == $decision['libelle_decision_vote']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($decision['libelle_decision_vote']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-2">
                            <label>Commentaire :</label>
                            <textarea name="commentaire_vote" class="form-control" rows="4"><?= $vote_existant['commentaire_vote'] ?? '' ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary mt-2"><?= $vote_existant ? "Mettre à jour" : "Soumettre" ?> le vote</button>
                    </form>
                </div>
            </div>

            <div class="report-card-footer">
                <a href="dashboard_commission.php?page=communication&topic=<?= $rapport['id_rapport_etudiant']; ?>">
                    💬 Discuter de ce rapport
                </a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
.report-card {
    background: #fff;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.report-card-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.report-card-body {
    display: flex;
    gap: 20px;
    padding: 20px;
}
.vote-info {
    flex: 2;
}
.vote-form-container {
    flex: 3;
}
.report-card-footer {
    padding: 10px 20px;
    background: #f8f9fa;
    text-align: right;
}
</style>

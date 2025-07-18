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
               <a href="traitement/initialiser_chat.php?topic=<?php echo $rapport['id_rapport_etudiant']; ?>">
                    💬 Discuter de ce rapport
                </a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
/* Variables CSS pour une cohérence visuelle */
:root {
    --primary-color: #3b82f6;
    --primary-dark: #2563eb;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #06b6d4;
    
    --bg-primary: #f8fafc;
    --bg-secondary: #ffffff;
    --bg-accent: #f1f5f9;
    
    --text-primary: #1e293b;
    --text-secondary: #475569;
    --text-muted: #64748b;
    
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
}

/* Styles généraux */
body {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--text-primary);
    line-height: 1.6;
    min-height: 100vh;
}

/* Header de la page */
.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.1;
}

.page-header h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-header h2 i {
    margin-right: 1rem;
    font-size: 2rem;
}

/* Alertes */
.alert {
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: var(--radius-md);
    border: none;
    font-weight: 500;
    box-shadow: var(--shadow-sm);
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border-left: 4px solid var(--info-color);
}

.alert-danger {
    background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
    color: #dc2626;
    border-left: 4px solid var(--danger-color);
}

/* Cartes de rapport */
.report-card {
    background: var(--bg-secondary);
    border-radius: var(--radius-xl);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-xl);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid var(--border-light);
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

/* Header de la carte */
.report-card-header {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, var(--bg-accent) 0%, #e2e8f0 100%);
    border-bottom: 2px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.report-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--info-color), var(--success-color));
}

.report-card-header h4 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.4rem;
    font-weight: 600;
}

.report-card-header span {
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
    background: var(--bg-secondary);
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-sm);
}

/* Corps de la carte */
.report-card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 2rem;
}

@media (max-width: 768px) {
    .report-card-body {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

/* Section d'information de vote */
.vote-info {
    background: var(--bg-accent);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
}

.vote-info h5 {
    color: var(--text-primary);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vote-info p {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.vote-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vote-info li {
    background: var(--bg-secondary);
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: var(--radius-md);
    border-left: 4px solid var(--primary-color);
    box-shadow: var(--shadow-sm);
}

.vote-info li:last-child {
    margin-bottom: 0;
}

.vote-info li strong {
    color: var(--primary-color);
}

.vote-info li em {
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* Conteneur du formulaire de vote */
.vote-form-container {
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 2px solid var(--border-color);
    box-shadow: var(--shadow-sm);
}

.vote-form-container h5 {
    color: var(--text-primary);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Formulaires */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.form-control:hover {
    border-color: var(--primary-color);
}

/* Boutons */
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #1d4ed8 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #475569 100%);
    color: white;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Footer de la carte */
.report-card-footer {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, var(--bg-accent) 0%, #e2e8f0 100%);
    border-top: 1px solid var(--border-color);
    text-align: right;
}

.report-card-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
    transition: all 0.3s ease;
}

.report-card-footer a:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

/* Séparateur */
hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--border-color), transparent);
    margin: 1.5rem 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .page-header h2 {
        font-size: 2rem;
    }
    
    .report-card-body {
        padding: 1.5rem;
    }
}

@media (max-width: 768px) {
    .page-header {
        margin-bottom: 1rem;
        padding: 1.5rem 0;
    }
    
    .page-header h2 {
        font-size: 1.8rem;
    }
    
    .report-card-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .report-card-body {
        padding: 1rem;
    }
    
    .vote-info,
    .vote-form-container {
        padding: 1rem;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.report-card {
    animation: fadeIn 0.5s ease forwards;
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--bg-accent);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: var(--radius-sm);
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Focus visible pour l'accessibilité */
.btn:focus-visible,
.form-control:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}
</style>

<?php
require_once(__DIR__ . '/../config/database.php');
$total_en_cours = $pdo->query("SELECT COUNT(*) FROM rapport_etudiant WHERE id_statut_rapport = 'RAP_EN_COMMISSION'")->fetchColumn();

// Nombre de rapports ayant atteint le consensus d'approbation (4 votes ou plus)
$stmt_approuves = $pdo->prepare("
    SELECT id_rapport_etudiant 
    FROM vote_commission
    WHERE id_decision_vote = 'VOTE_APPROUVE'
    GROUP BY id_rapport_etudiant
    HAVING COUNT(DISTINCT numero_utilisateur) >= 4
");
$stmt_approuves->execute();
$total_approuves = $stmt_approuves->rowCount();

// --- 2. Récupération de la liste complète des rapports soumis à la commission ---
$stmt_rapports = $pdo->prepare("
    SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, e.nom, e.prenom, r.date_soumission
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION'
    ORDER BY r.date_soumission DESC
");
$stmt_rapports->execute();
$rapports_en_commission = $stmt_rapports->fetchAll();
?>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>Gestion des Procès-Verbaux</h1>
        <p>Suivez les votes et gérez la création des PV.</p>
    </div>
</header>

<div class="dashboard-content">
    <div class="main-panel" style="grid-column: 1 / -1;"> <div class="stats-container" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
            <div class="stats-card" style="flex: 1; text-align: center;">
                <h4>Rapports en Cours d'Évaluation</h4>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $total_en_cours; ?></p>
            </div>
            <div class="stats-card" style="flex: 1; text-align: center;">
                <h4>Rapports Approuvés (Prêts pour PV)</h4>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $total_approuves; ?></p>
            </div>
        </div>

        <div class="table-container">
            <h3>Liste des Rapports Soumis à la Commission</h3>
            <table>
                <thead>
                    <tr>
                        <th>Titre du Rapport</th>
                        <th>Étudiant</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rapports_en_commission)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 20px;">Aucun rapport en cours d'évaluation pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rapports_en_commission as $rapport): ?>
                            <tr class="report-row">
                                <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
                                <td class="actions-cell">
                                    <button class="btn btn-secondary btn-sm toggle-details"><i class="fa-solid fa-eye"></i> Voir les détails</button>
                                </td>
                            </tr>
                            <tr class="details-row" style="display: none;">
                                <td colspan="3">
                                    <div class="details-content">
                                        <?php
                                        // Récupérer les votes et le PV pour ce rapport
                                        $stmt_votes = $pdo->prepare("SELECT d.libelle_decision_vote, u.login_utilisateur as membre_nom FROM vote_commission v JOIN decision_vote_ref d ON v.id_decision_vote = d.id_decision_vote JOIN utilisateur u ON v.numero_utilisateur = u.numero_utilisateur WHERE v.id_rapport_etudiant = ?");
                                        $stmt_votes->execute([$rapport['id_rapport_etudiant']]);
                                        $votes = $stmt_votes->fetchAll();
                                        
                                        $stmt_pv = $pdo->prepare("SELECT id_compte_rendu FROM compte_rendu WHERE id_rapport_etudiant = ? LIMIT 1");
                                        $stmt_pv->execute([$rapport['id_rapport_etudiant']]);
                                        $pv_existant = $stmt_pv->fetch();
                                        ?>
                                        <strong>Détails des votes (<?php echo count($votes); ?>) :</strong>
                                        <ul>
                                            <?php foreach ($votes as $vote): ?>
                                                <li><strong><?php echo htmlspecialchars($vote['membre_nom']); ?> :</strong> <?php echo htmlspecialchars($vote['libelle_decision_vote']); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <div class="pv-action">
                                        <a href="traitement/creer_pv.php?rapport_id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-primary btn-sm">Rédiger / Voir PV</a>
                                            <?php if ($pv_existant): ?>
                                                <div class="send-pv-container">
                                                    <button class="btn btn-success btn-sm"><i class="fa-solid fa-paper-plane"></i> Envoyer le PV</button>
                                                    <div class="send-pv-panel-hover">
                                                        <form action="traitement/envoyer_pv.php" method="POST">
                                                            <input type="hidden" name="pv_id" value="<?php echo $pv_existant['id_compte_rendu']; ?>">
                                                            <p><strong>Envoyer à :</strong></p>
                                                            <div class="form-group"><label><input type="checkbox" name="destinataires[]" value="etudiant" checked> Étudiant</label></div>
                                                            <div class="form-group"><label><input type="checkbox" name="destinataires[]" value="conformite"> Agent de Conformité</label></div>
                                                            <button type="submit" class="btn btn-primary btn-sm btn-block">Confirmer</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .stats-container { display: flex; gap: 1.5rem; }
    .table-container { margin-top: 2rem; }
    .details-row > td { padding: 0 !important; }
    .details-content { background: #fdfdff; padding: 1.5rem; border: 1px solid #e9ecef; }
    .details-content ul { list-style: none; padding-left: 0; margin-top: 0.5rem; }
    .pv-action { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ced4da; display: flex; align-items: center; gap: 10px; }
    .send-pv-container { position: relative; }
    .send-pv-panel-hover { display: none; position: absolute; bottom: 100%; left: 0; width: 280px; background-color: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); padding: 15px; z-index: 10; margin-bottom: 5px; }
    .send-pv-container:hover .send-pv-panel-hover { display: block; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', function() {
            const detailRow = this.closest('tr').nextElementSibling;
            const isVisible = detailRow.style.display === 'table-row';
            detailRow.style.display = isVisible ? 'none' : 'table-row';
            this.innerHTML = isVisible ? '<i class="fa-solid fa-eye"></i> Voir les détails' : '<i class="fa-solid fa-eye-slash"></i> Cacher les détails';
        });
    });
});
</script>
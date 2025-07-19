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
   /* Reset et base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #ffffff;
    color: #333;
    line-height: 1.6;
}

/* Header */
.header {
    background: #0d47a1;
    color: white;
    padding: 1.5rem 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.menu-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.menu-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.header p {
    opacity: 0.9;
    font-size: 0.95rem;
}

/* Dashboard Content */
.dashboard-content {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.main-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    border: 1px solid #e5e5e5;
}

/* Stats Container */
.stats-container {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stats-card {
    flex: 1;
    background: #fafafa;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #2962ff;
    border-radius: 12px 12px 0 0;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #d0d0d0;
}

.stats-card h4 {
    color: #333;
    font-size: 1rem;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.stats-card p {
    font-size: 2.2rem !important;
    font-weight: bold !important;
    color: #2962ff !important;
    margin: 0;
}

/* Table Container */
.table-container {
    margin-top: 2rem;
}

.table-container h3 {
    color: #333;
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    font-weight: 600;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e5e5;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e5e5;
}

table thead {
    background: #0d47a1;
}

table th {
    color: white;
    padding: 1.2rem;
    font-weight: 600;
    text-align: left;
    font-size: 0.95rem;
    letter-spacing: 0.5px;
}

table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

table tbody tr:hover {
    background-color: #fafafa;
    transform: translateX(2px);
}

table td {
    padding: 1.2rem;
    vertical-align: middle;
    color: #555;
}

.actions-cell {
    text-align: right;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    line-height: 1;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn-primary {
    background: #0d47a1;
    color: white;
    border: 1px solid transparent;
}

.btn-primary:hover {
    background: #0a3d91;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 71, 161, 0.3);
}

.btn-secondary {
    background: #f5f5f5;
    color: #555;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e8e8e8;
    border-color: #bbb;
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: 1px solid transparent;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-block {
    width: 100%;
    justify-content: center;
}

/* Details Row */
.details-row > td {
    padding: 0 !important;
}

.details-content {
    background: #fafafa;
    padding: 2rem;
    border-top: 3px solid #e5e5e5;
    border-left: 4px solid #2962ff;
    margin: 0.5rem;
    border-radius: 0 8px 8px 8px;
}

.details-content strong {
    color: #333;
    font-weight: 600;
}

.details-content ul {
    list-style: none;
    padding-left: 0;
    margin-top: 1rem;
}

.details-content li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e8e8e8;
    color: #555;
}

.details-content li:last-child {
    border-bottom: none;
}

.details-content li strong {
    color: #333;
    margin-right: 0.5rem;
}

/* PV Action */
.pv-action {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px dashed #ddd;
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Send PV Container */
.send-pv-container {
    position: relative;
}

.send-pv-panel-hover {
    display: none;
    position: absolute;
    bottom: 100%;
    left: 0;
    width: 300px;
    background-color: white;
    border: 2px solid #ddd;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    padding: 1.5rem;
    z-index: 10;
    margin-bottom: 8px;
}

.send-pv-panel-hover::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 20px;
    border: 8px solid transparent;
    border-top-color: white;
}

.send-pv-panel-hover::before {
    content: '';
    position: absolute;
    top: 100%;
    left: 18px;
    border: 10px solid transparent;
    border-top-color: #ddd;
}

.send-pv-container:hover .send-pv-panel-hover {
    display: block;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.send-pv-panel-hover p {
    color: #333;
    font-weight: 600;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 0.8rem;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #555;
    font-size: 0.9rem;
}

.form-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #2962ff;
}

/* Empty State */
td[colspan="3"] {
    text-align: center;
    padding: 3rem !important;
    color: #888;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-content {
        padding: 1rem;
    }
    
    .stats-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header {
        padding: 1rem;
    }
    
    .header h1 {
        font-size: 1.4rem;
    }
    
    .main-panel {
        padding: 1rem;
    }
    
    table {
        font-size: 0.9rem;
    }
    
    table th,
    table td {
        padding: 0.8rem;
    }
    
    .send-pv-panel-hover {
        width: 250px;
        right: 0;
        left: auto;
    }
}
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
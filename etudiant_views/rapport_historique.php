<?php
// etudiant_views/rapport_historique.php

// La variable $pdo est disponible car elle est incluse dans dashboard_etudiant.php

$stmt_etudiant = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
$stmt_etudiant->execute([$_SESSION['numero_utilisateur']]);
$etudiant = $stmt_etudiant->fetch();
$numero_carte_etudiant = $etudiant['numero_carte_etudiant'] ?? null;

$rapports = [];
if ($numero_carte_etudiant) {
    $stmt_rapports = $pdo->prepare("
        SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.chemin_pdf, r.date_derniere_modif, r.id_statut_rapport, s.libelle_statut_rapport 
        FROM rapport_etudiant r
        JOIN statut_rapport_ref s ON r.id_statut_rapport = s.id_statut_rapport
        WHERE r.numero_carte_etudiant = ?
        ORDER BY r.date_derniere_modif DESC
    ");
    $stmt_rapports->execute([$numero_carte_etudiant]);
    $rapports = $stmt_rapports->fetchAll();
}
?>

<div class="page-header">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Historique de vos Rapports</h2>
    <p>Retrouvez ici l'ensemble de vos travaux soumis et en cours de rédaction.</p>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre du Rapport</th>
                <th>Statut</th>
                <th>Dernière modification</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rapports)): ?>
                <tr><td colspan="4" style="text-align:center;">Vous n'avez encore aucun rapport.</td></tr>
            <?php else: ?>
                <?php foreach ($rapports as $rapport): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace('RAP_', '', $rapport['id_statut_rapport'])); ?>">
                                <?php echo htmlspecialchars($rapport['libelle_statut_rapport']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($rapport['date_derniere_modif'])); ?></td>
                        <td class="actions-cell">
                            <?php if (!empty($rapport['chemin_pdf'])): ?>
                                <a href="<?php echo htmlspecialchars($rapport['chemin_pdf']); ?>" target="_blank" class="btn btn-info"><i class="fa-solid fa-eye"></i> Voir le PDF</a>
                            <?php endif; ?>
                            
                            <?php if ($rapport['id_statut_rapport'] === 'RAP_BROUILLON'): ?>
                                <a href="traitement/soumettre_rapport.php?id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Soumettre</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .table-container { overflow-x: auto; background: #fff; padding:20px; border-radius:10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background-color: #f8f9fa; }
    .actions-cell { display: flex; gap: 10px; }
    .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
    .status-brouillon { background-color: #6c757d; }
    .status-soumis { background-color: #007bff; }
    .status-valide { background-color: #28a745; }
    .status-refuse { background-color: #dc3545; }
    .status-en_correction, .status-non_conf { background-color: #ffc107; color: #000; }
    .status-en_commission { background-color: #17a2b8; }
</style>
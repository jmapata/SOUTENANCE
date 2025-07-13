<?php
// conformite_views/rapports_a_verifier.php

// On récupère tous les rapports avec le statut 'Soumis'
$stmt = $pdo->prepare("
    SELECT 
        r.id_rapport_etudiant, 
        r.libelle_rapport_etudiant, 
        r.date_soumission, 
        e.nom, 
        e.prenom
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_SOUMIS'
    ORDER BY r.date_soumission ASC
");
$stmt->execute();
$rapports_a_verifier = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fa-solid fa-file-circle-question"></i> Rapports en attente de vérification</h2>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre du Rapport</th>
                <th>Étudiant</th>
                <th>Date de soumission</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rapports_a_verifier)): ?>
                <tr><td colspan="4" style="text-align:center;">Aucun rapport à vérifier pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($rapports_a_verifier as $rapport): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
                        <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($rapport['date_soumission'])); ?></td>
                        <td>
                            <a href="dashboard_conformite.php?page=verifier_un_rapport&id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-primary">
                                <i class="fa-solid fa-search"></i> Vérifier
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .table-container { overflow-x: auto; background:#fff; padding:20px; border-radius:10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background-color: #f8f9fa; }
</style>
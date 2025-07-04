<?php
// /dashboard_commission.php
require_once __DIR__ . '/includes/header.php';

// Vous devriez créer un traitement/permission comme 'TRAIT_COMMISSION_EVALUER'
// if (!checkPermission('TRAIT_COMMISSION_EVALUER')) { die("Accès non autorisé."); }

// Récupérer les rapports avec le statut "CONFORME"
$stmt = $p_pdo->query(
    "SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.date_soumission, e.nom, e.prenom
     FROM rapport_etudiant r
     JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
     WHERE r.id_statut_rapport = 'CONFORME'
     ORDER BY r.date_soumission ASC"
);
$rapports_a_evaluer = $stmt->fetchAll();
?>

<h2>Rapports en attente d'évaluation par la Commission</h2>
<p>Liste des mémoires validés administrativement et prêts pour une évaluation académique.</p>

<table>
    <thead>
        <tr>
            <th>Date de soumission</th>
            <th>Étudiant</th>
            <th>Titre du rapport</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rapports_a_evaluer)): ?>
            <tr>
                <td colspan="4">Aucun rapport en attente d'évaluation.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($rapports_a_evaluer as $rapport): ?>
        <tr>
            <td><?php echo (new DateTime($rapport['date_soumission']))->format('d/m/Y H:i'); ?></td>
            <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
            <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
            <td>
                <a href="evaluation_rapport.php?id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn">Évaluer ce rapport</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
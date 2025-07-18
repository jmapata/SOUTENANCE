<?php
// personnel_views/rapports_traites.php
// La variable $pdo est disponible depuis le dashboard principal.

// --- 1. Récupérer l'ID du personnel connecté ---
$stmt_pers = $pdo->prepare("SELECT numero_personnel_administratif FROM personnel_administratif WHERE numero_utilisateur = ?");
$stmt_pers->execute([$_SESSION['numero_utilisateur']]);
$personnel_id = $stmt_pers->fetchColumn();

// --- 2. Gestion des Filtres ---
$filters = [':personnel_id' => $personnel_id];
$where_clauses = ["a.numero_personnel_administratif = :personnel_id"];

// Filtre par nom ou titre
if (!empty($_GET['search'])) {
    $where_clauses[] = "(r.libelle_rapport_etudiant LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
    $filters[':search'] = '%' . $_GET['search'] . '%';
}
// Filtre par décision
if (!empty($_GET['decision'])) {
    $where_clauses[] = "a.id_statut_conformite = :decision";
    $filters[':decision'] = $_GET['decision'];
}

$sql_where = 'WHERE ' . implode(' AND ', $where_clauses);

// --- 3. Récupération de l'historique des rapports traités ---
$stmt_rapports = $pdo->prepare("
    SELECT 
        r.id_rapport_etudiant,
        r.libelle_rapport_etudiant,
        e.nom, e.prenom,
        a.date_verification_conformite,
        s.libelle_statut_conformite
    FROM approuver a
    JOIN rapport_etudiant r ON a.id_rapport_etudiant = r.id_rapport_etudiant
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    JOIN statut_conformite_ref s ON a.id_statut_conformite = s.id_statut_conformite
    $sql_where
    ORDER BY a.date_verification_conformite DESC
");
$stmt_rapports->execute($filters);
$rapports_traites = $stmt_rapports->fetchAll();

// Récupérer les statuts pour le filtre
$statuts_conformite = $pdo->query("SELECT * FROM statut_conformite_ref")->fetchAll();
?>

<style>
    .filter-form .form-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
    .filter-form select, .filter-form input, .filter-form button { padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; }
    .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
    .status-conforme { background-color: #28a745; }
    .status-non-conforme { background-color: #dc3545; }

    @media print {
        body > .sidebar, body > .main-container > .header, .page-header, .card.no-print { display: none !important; }
        body > .main-container, .content-area { margin: 0 !important; padding: 0 !important; }
        #printable-area { display: block !important; box-shadow: none; border: none; }
    }
</style>

<div class="page-header no-print">
    <h1><i class="fa-solid fa-history"></i> Historique des Rapports Traités</h1>
    <p>Consultez la liste de tous les rapports que vous avez vérifiés.</p>
</div>

<div class="card no-print">
    <div class="card-header"><h2 class="card-title">Rechercher et Filtrer</h2></div>
    <div class="card-content">
        <form method="GET" action="dashboard_conformite.php" class="filter-form">
            <input type="hidden" name="page" value="rapports_traites">
            <div class="form-row">
                <input type="text" name="search" placeholder="Rechercher par titre, étudiant..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <select name="decision">
                    <option value="">Toutes les décisions</option>
                    <?php foreach ($statuts_conformite as $statut): ?>
                        <option value="<?php echo $statut['id_statut_conformite']; ?>" <?php if(($_GET['decision'] ?? '') == $statut['id_statut_conformite']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($statut['libelle_statut_conformite']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <button type="button" onclick="window.print();" class="btn btn-secondary">
                    <i class="fa-solid fa-print"></i> Imprimer
                </button>
            </div>
        </form>
    </div>
</div>

<div id="printable-area" class="card table-container">
    <div class="card-content">
        <table class="table-spaced">
            <thead>
                <tr>
                    <th>Titre du Rapport</th>
                    <th>Étudiant</th>
                    <th>Date de Traitement</th>
                    <th>Décision</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rapports_traites)): ?>
                    <tr><td colspan="5" style="text-align: center;">Aucun rapport traité ne correspond à vos critères.</td></tr>
                <?php else: ?>
                    <?php foreach ($rapports_traites as $rapport): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
                            <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($rapport['date_verification_conformite'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rapport['libelle_statut_conformite'])); ?>">
                                    <?php echo htmlspecialchars($rapport['libelle_statut_conformite']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="dashboard_conformite.php?page=verifier_un_rapport&id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-info btn-sm">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
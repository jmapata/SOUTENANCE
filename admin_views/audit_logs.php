<?php
require_once 'config/database.php';

// --- 1. Gestion des Filtres ---
$filters = [];
$where_clauses = [];

// Filtre par nom/login
if (!empty($_GET['nom'])) {
    $where_clauses[] = "u.login_utilisateur LIKE :nom";
    $filters[':nom'] = '%' . $_GET['nom'] . '%';
}
// Filtre par groupe
if (!empty($_GET['groupe'])) {
    $where_clauses[] = "u.id_groupe_utilisateur = :groupe";
    $filters[':groupe'] = $_GET['groupe'];
}
// Filtre par type
if (!empty($_GET['type'])) {
    $where_clauses[] = "u.id_type_utilisateur = :type";
    $filters[':type'] = $_GET['type'];
}
// Filtre par date
if (!empty($_GET['date_debut'])) {
    $where_clauses[] = "e.date_action >= :date_debut";
    $filters[':date_debut'] = $_GET['date_debut'];
}
if (!empty($_GET['date_fin'])) {
    $where_clauses[] = "e.date_action <= :date_fin";
    $filters[':date_fin'] = $_GET['date_fin'] . ' 23:59:59';
}

$sql_where = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// --- 2. Récupération des Logs avec les Filtres ---
$stmt_logs = $pdo->prepare("
    SELECT 
        e.date_action, 
        e.id_entite_concernee, 
        u.login_utilisateur, 
        a.libelle_action,
        g.libelle_groupe_utilisateur
    FROM enregistrer e
    JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur
    JOIN action a ON e.id_action = a.id_action
    JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
    $sql_where
    ORDER BY e.date_action DESC
    LIMIT 100
");
$stmt_logs->execute($filters);
$logs = $stmt_logs->fetchAll();

// --- 3. Récupération des données pour les filtres ---
$groupes = $pdo->query("SELECT id_groupe_utilisateur, libelle_groupe_utilisateur FROM groupe_utilisateur ORDER BY libelle_groupe_utilisateur")->fetchAll();
$types = $pdo->query("SELECT id_type_utilisateur, libelle_type_utilisateur FROM type_utilisateur ORDER BY libelle_type_utilisateur")->fetchAll();
?>

<div class="page-header">
    <h1><i class="fa-solid fa-file-shield"></i> Journaux d'Audit</h1>
    <p>Tracez toutes les actions critiques effectuées sur la plateforme.</p>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Filtrer les activités</h2></div>
    <div class="card-content">
        <form method="GET" action="dashboard_admin.php" class="filter-form">
            <input type="hidden" name="page" value="audit_logs">
            <div class="form-row">
                <input type="text" name="nom" placeholder="Nom ou login de l'acteur..." value="<?php echo htmlspecialchars($_GET['nom'] ?? ''); ?>">
                <select name="groupe">
                    <option value="">Tous les groupes</option>
                    <?php foreach ($groupes as $groupe): ?>
                        <option value="<?php echo $groupe['id_groupe_utilisateur']; ?>" <?php if(($_GET['groupe'] ?? '') == $groupe['id_groupe_utilisateur']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($groupe['libelle_groupe_utilisateur']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="type">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $type): ?>
                         <option value="<?php echo $type['id_type_utilisateur']; ?>" <?php if(($_GET['type'] ?? '') == $type['id_type_utilisateur']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($type['libelle_type_utilisateur']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_debut" value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>" title="Date de début">
                <input type="date" name="date_fin" value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>" title="Date de fin">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                
            </div>
        </form>
    </div>
</div>

<div class="card table-container">
    <div class="card-content">
        <table class="table-spaced">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Acteur</th>
                    <th>Rôle</th>
                    <th>Action Effectuée</th>
                    <th>Entité Concernée</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" style="text-align: center;">Aucun log trouvé pour les critères sélectionnés.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['date_action'])); ?></td>
                            <td><?php echo htmlspecialchars($log['login_utilisateur']); ?></td>
                            <td><?php echo htmlspecialchars($log['libelle_groupe_utilisateur']); ?></td>
                            <td><?php echo htmlspecialchars($log['libelle_action']); ?></td>
                            <td><?php echo htmlspecialchars($log['id_entite_concernee'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .filter-form .form-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
    .filter-form select, .filter-form input { padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; }
</style>
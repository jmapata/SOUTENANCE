<?php
require_once 'config/database.php';

// --- Logique de Recherche ---
$search_term = $_GET['search'] ?? '';
$sql_where = '';
$params = [];
if (!empty($search_term)) {
    $sql_where = "AND (e.nom LIKE ? OR e.prenom LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term];
}

// --- Récupération des étudiants ayant soldé leur scolarité ---
$stmt = $pdo->prepare(
    "SELECT e.nom, e.prenom, e.numero_utilisateur, u.statut_compte
     FROM etudiant e
     JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
     LEFT JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur
     WHERE i.montant_inscription >= 1300000
     $sql_where
     ORDER BY e.nom, e.prenom"
);
$stmt->execute($params);
$etudiants_a_activer = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Activation des Comptes Étudiants</h1>
</div>

<div class="card search-card">
    <form action="dashboard_gestion_scolarite.php" method="GET">
        <input type="hidden" name="page" value="activer_comptes">
        <div class="search-box">
            <input type="text" name="search" placeholder="Rechercher un étudiant..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Étudiants avec Scolarité Soldée</h2></div>
    <div class="card-content">
        <table class="table-spaced">
            <thead><tr><th>Nom</th><th>Prénom</th><th>Statut du Compte</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (empty($etudiants_a_activer)): ?>
                    <tr><td colspan="4" style="text-align: center;">Aucun étudiant ayant soldé n'a été trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($etudiants_a_activer as $etudiant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($etudiant['nom']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['prenom']); ?></td>
                        <td>
                            <span class="status-badge <?php echo htmlspecialchars(strtolower($etudiant['statut_compte'] ?? 'non-cree')); ?>">
                                <?php echo htmlspecialchars($etudiant['statut_compte'] ?? 'Non créé'); ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <?php if ($etudiant['numero_utilisateur'] && $etudiant['statut_compte'] === 'inactif'): ?>
                                <form action="traitement/activer_compte_traitement.php" method="POST">
                                    <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($etudiant['numero_utilisateur']); ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Activer le Compte</button>
                                </form>
                            <?php elseif ($etudiant['numero_utilisateur'] && $etudiant['statut_compte'] === 'actif'): ?>
                                <button class="btn btn-sm" disabled>Déjà Actif</button>
                            <?php else: ?>
                                <button class="btn btn-sm" disabled title="L'administrateur n'a pas encore généré le compte utilisateur.">Compte non disponible</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
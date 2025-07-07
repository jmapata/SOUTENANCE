<?php
require_once 'config/database.php';

// --- RÉCUPÉRATION DES DONNÉES ET STATISTIQUES ---

// 1. Nombre total d'étudiants inscrits (pour la statistique)
$total_etudiants = $pdo->query("SELECT COUNT(DISTINCT numero_carte_etudiant) FROM inscrire")->fetchColumn();

// 2. Nombre d'étudiants ayant soldé (pour la statistique)
$total_scolarite_soldee = $pdo->query("SELECT COUNT(*) FROM inscrire WHERE montant_inscription >= 1300000")->fetchColumn();

// 3. Logique de Recherche (s'applique maintenant uniquement aux étudiants ayant soldé)
$search_term = $_GET['search'] ?? '';
$sql_where_search = '';
$params = [];
if (!empty($search_term)) {
    $sql_where_search = "AND (e.nom LIKE ? OR e.prenom LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term];
}

// 4. On récupère UNIQUEMENT les étudiants qui ont soldé leur scolarité
$stmt_etudiants = $pdo->prepare(
    "SELECT e.numero_carte_etudiant, e.nom, e.prenom, e.numero_utilisateur, u.statut_compte
     FROM etudiant e
     JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
     LEFT JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur
     WHERE i.montant_inscription >= 1300000
     $sql_where_search
     ORDER BY e.nom, e.prenom"
);
$stmt_etudiants->execute($params);
$etudiants_a_gerer = $stmt_etudiants->fetchAll();
?>

<div class="page-header">
    <h1>Gestion des Comptes Étudiants</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon-container"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="info">
            <h3><?php echo $total_etudiants; ?></h3>
            <p>Étudiants Inscrits (Total)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon-container" style="background-color: #dcfce7; color: #166534;">
            <i class="fa-solid fa-check-double"></i>
        </div>
        <div class="info">
            <h3><?php echo $total_scolarite_soldee; ?></h3>
            <p>Ont Soldé la Scolarité</p>
        </div>
    </div>
</div>

<div class="card search-card">
    <form action="dashboard_admin.php" method="GET">
        <input type="hidden" name="page" value="gestion_etudiants">
        <div class="search-box">
            <input type="text" name="search" placeholder="Rechercher un étudiant ayant soldé..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Étudiants Prêts pour la Création de Compte</h2></div>
    <div class="card-content">
        <table class="table-spaced">
            <thead><tr><th>Nom</th><th>Prénom</th><th>Statut du Compte</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($etudiants_a_gerer)): ?>
                    <tr><td colspan="4" style="text-align: center;">Aucun étudiant avec scolarité soldée trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($etudiants_a_gerer as $etudiant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($etudiant['nom']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['prenom']); ?></td>
                        <td>
                            <span class="status-badge <?php echo htmlspecialchars(strtolower($etudiant['statut_compte'] ?? 'non-cree')); ?>">
                                <?php echo htmlspecialchars($etudiant['statut_compte'] ?? 'Non créé'); ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <?php if (is_null($etudiant['numero_utilisateur'])): ?>
                                <form action="traitement/etudiant_traitement.php" method="POST">
                                    <input type="hidden" name="action" value="creer_compte_etudiant">
                                    <input type="hidden" name="numero_carte_etudiant" value="<?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Générer Compte</button>
                                </form>
                            <?php else: ?>
                                <a href="#" class="btn btn-sm btn-secondary" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                <form action="traitement/etudiant_traitement.php" method="POST" class="form-inline-action">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($etudiant['numero_utilisateur']); ?>">
                                    <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($etudiant['statut_compte']); ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $etudiant['statut_compte'] === 'actif' ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo $etudiant['statut_compte'] === 'actif' ? 'Désactiver' : 'Activer'; ?>"><i class="fa-solid <?php echo $etudiant['statut_compte'] === 'actif' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i></button>
                                </form>
                                <form action="traitement/etudiant_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer ?');" class="form-inline-action">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id_etudiant" value="<?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
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
<style>
  /* Page générale */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background-color: #ffffff;
    color: #333333;
    margin: 0;
    padding: 20px;
    line-height: 1.6;
}

/* En-tête de page */
.page-header {
    margin-bottom: 30px;
    text-align: center;
    padding: 20px 0;
    border-bottom: 2px solid #f5f5f5;
}

.page-header h1 {
    color: #0d47a1;
    font-size: 2.5rem;
    font-weight: 300;
    margin: 0;
    letter-spacing: -0.5px;
}

/* Cartes */
.card {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(13, 71, 161, 0.08);
    margin-bottom: 25px;
    overflow: hidden;
    border: 1px solid #e8eaf6;
}

.search-card {
    padding: 20px;
}

.card-header {
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
    color: white;
    padding: 20px;
    border-bottom: none;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 500;
}

.card-content {
    padding: 0;
}

/* Boîte de recherche */
.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 400px;
    margin: 0 auto;
}

.search-box input[type="text"] {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    outline: none;
}

.search-box input[type="text"]:focus {
    border-color: #1565c0;
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

/* Boutons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    background: #0d47a1;
    color: white;
}

.btn:hover {
    background: #1565c0;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 71, 161, 0.3);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.btn-success {
    background: #2e7d32;
    color: white;
}

.btn-success:hover {
    background: #388e3c;
}

.btn:disabled {
    background: #bdbdbd;
    color: #757575;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn:disabled:hover {
    background: #bdbdbd;
    transform: none;
    box-shadow: none;
}

/* Tableau */
.table-spaced {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.table-spaced th {
    background: #f8f9fa;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #0d47a1;
    border-bottom: 2px solid #e0e0e0;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-spaced td {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.table-spaced tr:hover {
    background: #f8f9ff;
}

.table-spaced tr:last-child td {
    border-bottom: none;
}

.table-actions {
    text-align: center;
    width: 200px;
}

.table-actions form {
    margin: 0;
}

/* Badges de statut */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-badge.actif {
    background: #e8f5e8;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.status-badge.inactif {
    background: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffcc02;
}

.status-badge.non-cree {
    background: #f5f5f5;
    color: #757575;
    border: 1px solid #e0e0e0;
}

/* Message vide */
.table-spaced td[colspan] {
    color: #757575;
    font-style: italic;
    padding: 40px 16px;
    background: #fafafa;
}

/* Responsive */
@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .search-box {
        max-width: 100%;
    }
    
    .table-spaced {
        font-size: 12px;
    }
    
    .table-spaced th,
    .table-spaced td {
        padding: 12px 8px;
    }
    
    .table-actions {
        width: auto;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 11px;
    }
}  
</style>
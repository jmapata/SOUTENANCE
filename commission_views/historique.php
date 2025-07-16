<?php

// ## LA CORRECTION EST ICI : ON CHARGE LA CONNEXION À LA BDD AU TOUT DÉBUT ##
require_once 'config/database.php';

// Sécurité : vérifier que l'utilisateur est connecté et est bien un membre de la commission
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    header('Location: login.php?error=unauthorized');
    exit();
}
// --- 1. Récupérer l'historique d'activité du membre connecté ---
$stmt_activites = $pdo->prepare("
    SELECT 
        e.date_action, 
        a.libelle_action, 
        e.id_entite_concernee
    FROM enregistrer e
    JOIN action a ON e.id_action = a.id_action
    WHERE e.numero_utilisateur = ?
    ORDER BY e.date_action DESC
    LIMIT 15
");
$stmt_activites->execute([$_SESSION['numero_utilisateur']]);
$activites = $stmt_activites->fetchAll();


// --- 2. Récupérer les PV archivés (ceux qui sont validés) ---
// On ajoute une logique de recherche simple
$search_term = $_GET['search'] ?? '';
$sql_pv = "
    SELECT 
        cr.id_compte_rendu, 
        cr.libelle_compte_rendu, 
        cr.date_creation_pv,
        r.chemin_pdf,
        e.nom as nom_etudiant,
        e.prenom as prenom_etudiant
    FROM compte_rendu cr
    LEFT JOIN rapport_etudiant r ON cr.id_rapport_etudiant = r.id_rapport_etudiant
    LEFT JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE cr.id_statut_pv = 'PV_VALID'
";
if (!empty($search_term)) {
    $sql_pv .= " AND (cr.libelle_compte_rendu LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
}
$sql_pv .= " ORDER BY cr.date_creation_pv DESC";

$stmt_pv_archives = $pdo->prepare($sql_pv);
if (!empty($search_term)) {
    $stmt_pv_archives->bindValue(':search', '%' . $search_term . '%');
}
$stmt_pv_archives->execute();
$pv_archives = $stmt_pv_archives->fetchAll();

?>

<div class="page-header">
</div>

<div class="historique-container">

    <div class="activites-panel">
        <h3>Mes Activités Récentes</h3>
        <div class="activity-list">
            <?php if (empty($activites)): ?>
                <p>Aucune activité récente enregistrée.</p>
            <?php else: ?>
                <?php foreach ($activites as $activite): ?>
                    <div class="activity-item">
                        <div class="activity-icon"><i class="fas fa-history"></i></div>
                        <div class="activity-content">
                            <div class="activity-title"><?php echo htmlspecialchars($activite['libelle_action']); ?></div>
                            <div class="activity-description">Sur l'entité : <?php echo htmlspecialchars($activite['id_entite_concernee']); ?></div>
                        </div>
                        <div class="activity-meta">
                            <div class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activite['date_action'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="archives-panel">
        <h3>Archives des Procès-Verbaux</h3>
        
        <form method="GET" action="" class="search-form">
            <input type="hidden" name="page" value="historique">
            <input type="text" name="search" placeholder="Rechercher par titre, étudiant..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit"><i class="fa-solid fa-search"></i></button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Titre du PV</th>
                        <th>Étudiant Concerné</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pv_archives)): ?>
                        <tr><td colspan="4" style="text-align:center;">Aucun PV archivé trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pv_archives as $pv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pv['libelle_compte_rendu']); ?></td>
                                <td><?php echo htmlspecialchars($pv['prenom_etudiant'] . ' ' . $pv['nom_etudiant']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pv['date_creation_pv'])); ?></td>
                                <td>
                                    <?php if (!empty($pv['chemin_pdf'])): ?>
                                        <a href="<?php echo htmlspecialchars($pv['chemin_pdf']); ?>" target="_blank" class="btn btn-info btn-sm">Voir PDF</a>
                                    <?php endif; ?>
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
    .historique-container {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }
    .activites-panel, .archives-panel {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
    }
    .activites-panel h3, .archives-panel h3 {
        margin-top: 0;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }
    .activity-list { max-height: 60vh; overflow-y: auto; }
    .activity-item { display: flex; align-items: center; padding: 15px 5px; border-bottom: 1px solid #f0f0f0; }
    .activity-icon { font-size: 1rem; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e9ecef; color: #495057; margin-right: 15px; }
    .search-form { display: flex; margin-bottom: 20px; }
    .search-form input { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px 0 0 5px; }
    .search-form button { padding: 10px 15px; border: none; background: #007bff; color: white; border-radius: 0 5px 5px 0; cursor: pointer; }
</style>
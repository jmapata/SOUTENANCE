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
   /* Reset et base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #343a40;
    line-height: 1.6;
    min-height: 100vh;
}

/* Header de page */
.page-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 2rem;
}

/* Conteneur principal */
.historique-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Panneaux */
.activites-panel, 
.archives-panel {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
    height: fit-content;
}

/* Titres des panneaux */
.activites-panel h3, 
.archives-panel h3 {
    margin: 0 0 1.5rem 0;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
    font-size: 1.4rem;
    color: #495057;
    font-weight: 600;
    position: relative;
}

.activites-panel h3::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 60px;
    height: 2px;
    background: linear-gradient(135deg, #6c757d, #495057);
    border-radius: 1px;
}

.archives-panel h3::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 80px;
    height: 2px;
    background: linear-gradient(135deg, #6c757d, #495057);
    border-radius: 1px;
}

/* Liste d'activités */
.activity-list {
    max-height: 65vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #ced4da #f8f9fa;
}

.activity-list::-webkit-scrollbar {
    width: 6px;
}

.activity-list::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 3px;
}

.activity-list::-webkit-scrollbar-thumb {
    background: #ced4da;
    border-radius: 3px;
}

.activity-list::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Items d'activité */
.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem 0.5rem;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.activity-item:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    color: #6c757d;
    margin-right: 1rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.activity-content {
    flex-grow: 1;
    min-width: 0;
}

.activity-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.activity-description {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
}

.activity-meta {
    margin-left: 1rem;
    flex-shrink: 0;
}

.activity-time {
    font-size: 0.8rem;
    color: #adb5bd;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

/* Formulaire de recherche */
.search-form {
    display: flex;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-form input {
    flex-grow: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #ced4da;
    border-right: none;
    background: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.search-form input:focus {
    outline: none;
    border-color: #6c757d;
    box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.1);
}

.search-form input::placeholder {
    color: #adb5bd;
    font-style: italic;
}

.search-form button {
    padding: 0.75rem 1.25rem;
    border: none;
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.search-form button:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
    transform: translateY(-1px);
}

/* Conteneur du tableau */
.table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Tableau */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

thead th {
    padding: 1rem 0.75rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f3f4;
}

tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

tbody tr:last-child {
    border-bottom: none;
}

tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    color: #495057;
}

tbody td:first-child {
    font-weight: 500;
}

/* Boutons */
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-block;
    cursor: pointer;
}

.btn-info {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    box-shadow: 0 2px 6px rgba(108, 117, 125, 0.2);
}

.btn-info:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

/* Messages d'état */
.activites-panel p,
tbody td[colspan] {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 1rem 0;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .historique-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .activites-panel {
        order: 2;
    }
    
    .archives-panel {
        order: 1;
    }
    
    .activity-list {
        max-height: 50vh;
    }
}

@media (max-width: 768px) {
    .historique-container {
        padding: 0 0.5rem;
        gap: 1rem;
    }
    
    .activites-panel,
    .archives-panel {
        padding: 1.5rem;
        border-radius: 8px;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .activity-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .activity-meta {
        margin-left: 0;
        margin-top: 0.5rem;
        align-self: flex-end;
    }
    
    .search-form {
        flex-direction: column;
        border-radius: 8px;
    }
    
    .search-form input,
    .search-form button {
        border-radius: 6px;
    }
    
    .search-form input {
        margin-bottom: 0.5rem;
        border: 1px solid #ced4da;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 600px;
    }
    
    thead th,
    tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .page-header {
        padding: 1rem;
    }
    
    .activites-panel h3,
    .archives-panel h3 {
        font-size: 1.2rem;
    }
    
    .activity-title {
        font-size: 0.9rem;
    }
    
    .activity-description {
        font-size: 0.8rem;
    }
    
    .activity-time {
        font-size: 0.75rem;
    }
}

/* Animation pour les éléments qui apparaissent */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.activity-item,
.archives-panel,
.activites-panel {
    animation: fadeInUp 0.6s ease-out;
}

.activity-item:nth-child(2) { animation-delay: 0.1s; }
.activity-item:nth-child(3) { animation-delay: 0.2s; }
.activity-item:nth-child(4) { animation-delay: 0.3s; }
</style>
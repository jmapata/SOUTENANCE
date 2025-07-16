<?php
// commission_views/accueil.php
// La variable $pdo et les variables de session sont déjà disponibles car elles viennent du fichier dashboard principal.

// --- 1. RÉCUPÉRATION DES DONNÉES DYNAMIQUES POUR LE DASHBOARD ---

// a. Statistiques générales pour les cartes du haut
$rapports_en_attente = $pdo->query("SELECT COUNT(*) FROM rapport_etudiant WHERE id_statut_rapport = 'RAP_EN_COMMISSION'")->fetchColumn();
$pv_a_valider = $pdo->query("SELECT COUNT(*) FROM compte_rendu WHERE id_statut_pv = 'PV_SOUMIS_VALID'")->fetchColumn();
$rapports_valides_total = $pdo->query("SELECT COUNT(DISTINCT id_rapport_etudiant) FROM vote_commission WHERE id_decision_vote = 'VOTE_APPROUVE' GROUP BY id_rapport_etudiant HAVING COUNT(DISTINCT numero_utilisateur) >= 4")->rowCount();

// b. Tâches spécifiques à l'utilisateur connecté
$stmt_mes_votes = $pdo->prepare("
    SELECT COUNT(r.id_rapport_etudiant) 
    FROM rapport_etudiant r
    WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION' AND NOT EXISTS 
    (SELECT 1 FROM vote_commission v WHERE v.id_rapport_etudiant = r.id_rapport_etudiant AND v.numero_utilisateur = ?)
");
$stmt_mes_votes->execute([$_SESSION['numero_utilisateur']]);
$mes_votes_en_attente = $stmt_mes_votes->fetchColumn();

// c. Activités récentes
$recent_activities = $pdo->query("
    SELECT a.libelle_action, e.date_action, u.login_utilisateur 
    FROM enregistrer e 
    JOIN action a ON e.id_action = a.id_action 
    JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur 
    WHERE a.categorie_action = 'Commission'
    ORDER BY e.date_action DESC LIMIT 5
")->fetchAll();

// d. Rapports clés (en attente de votre vote)
$stmt_rapports_cles = $pdo->prepare("
    SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant 
    FROM rapport_etudiant r 
    WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION' AND NOT EXISTS 
    (SELECT 1 FROM vote_commission v WHERE v.id_rapport_etudiant = r.id_rapport_etudiant AND v.numero_utilisateur = ?)
    LIMIT 3
");
$stmt_rapports_cles->execute([$_SESSION['numero_utilisateur']]);
$rapports_cles = $stmt_rapports_cles->fetchAll();
?>

<style>
    /* Variables CSS pour la cohérence visuelle */
/* Variables CSS pour la cohérence visuelle */
:root {
    --primary-color: #0d47a1;
    --primary-light: #2962ff;
    --primary-ultra-light: #e3f2fd;
    --primary-hover: #1565c0;
    
    --text-dark: #1a1a1a;
    --text-secondary: #555555;
    --text-light: #777777;
    --text-muted: #999999;
    
    --bg-white: #ffffff;
    --bg-light: #fafafa;
    --bg-card: #ffffff;
    
    --border-color: #e0e0e0;
    --border-light: #f0f0f0;
    
    --shadow-light: 0 2px 4px rgba(13, 71, 161, 0.08);
    --shadow-medium: 0 4px 12px rgba(13, 71, 161, 0.12);
    --shadow-strong: 0 8px 24px rgba(13, 71, 161, 0.15);
    
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    
    --transition: all 0.3s ease;
}

/* Styles généraux */
body {
    background: var(--bg-white);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: var(--text-dark);
    line-height: 1.6;
    margin: 0;
    padding: 0;
}

.page-content {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    padding: 1rem 2rem;
    margin-bottom: 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-medium);
    position: relative;
    overflow: hidden;
}

.header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.1;
}

.header-left {
    position: relative;
    z-index: 1;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
    text-align: center;
}

/* Layout du dashboard */
.dashboard-layout {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.dashboard-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-light);
    border: 1px solid var(--border-light);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.dashboard-card.col-span-3 {
    grid-column: 1 / -1;
}

.dashboard-card.col-span-2 {
    grid-column: span 2;
}

/* Headers des cartes */
.card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.card-header span {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-dark);
}

.card-header .icon {
    font-size: 1.1rem;
    color: var(--primary-color);
    background: var(--primary-ultra-light);
    padding: 0.5rem;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Grille de statistiques */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem 1rem;
    background: var(--bg-light);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-light);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    transform: scaleX(0);
    transition: var(--transition);
}

.stat-item:hover::before {
    transform: scaleX(1);
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
    background: var(--bg-white);
}

.stat-item .value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-item .label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Grille d'accès rapide */
.quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.access-item {
    display: block;
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    background: var(--bg-light);
    text-decoration: none;
    color: var(--text-dark);
    transition: var(--transition);
    border: 1px solid var(--border-light);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.access-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
    border-color: var(--primary-color);
    background: var(--bg-white);
}

.access-item .count {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--primary-color);
    display: block;
    margin-bottom: 0.5rem;
}

.access-item .label {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.access-item .description {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

/* Liste d'activités */
.activity-list {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.activity-list::-webkit-scrollbar {
    width: 6px;
}

.activity-list::-webkit-scrollbar-track {
    background: var(--bg-light);
    border-radius: var(--radius-sm);
}

.activity-list::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: var(--radius-sm);
}

.activity-list::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover);
}

.activity {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-light);
    transition: var(--transition);
}

.activity:last-child {
    border-bottom: none;
}

.activity:hover {
    background: var(--bg-light);
    padding-left: 1rem;
    padding-right: 1rem;
    margin-left: -1rem;
    margin-right: -1rem;
    border-radius: var(--radius-md);
}

.activity .info {
    flex-grow: 1;
}

.activity .info .description {
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.activity .info .author {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.activity .time {
    font-size: 0.85rem;
    color: var(--text-light);
    white-space: nowrap;
    background: var(--bg-light);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
}

/* Boutons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-medium);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-hover) 0%, var(--primary-color) 100%);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
}

/* Messages d'état */
.activity-list p {
    text-align: center;
    color: var(--text-secondary);
    font-style: italic;
    padding: 2rem;
    background: var(--bg-light);
    border-radius: var(--radius-md);
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-layout {
        grid-template-columns: 1fr 1fr;
    }
    
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-access-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-card.col-span-3,
    .dashboard-card.col-span-2 {
        grid-column: 1 / -1;
    }
}

@media (max-width: 768px) {
    .page-content {
        padding: 1rem;
    }
    
    .dashboard-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .quick-access-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .dashboard-card {
        padding: 1.5rem;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stat-item .value {
        font-size: 2rem;
    }
    
    .activity {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .activity .time {
        align-self: flex-end;
    }
}

@media (max-width: 480px) {
    .stat-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .stat-item .value {
        font-size: 1.8rem;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
}

/* Animations */
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

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.dashboard-card {
    animation: fadeInUp 0.5s ease forwards;
}

.dashboard-card:nth-child(1) { animation-delay: 0.1s; }
.dashboard-card:nth-child(2) { animation-delay: 0.2s; }
.dashboard-card:nth-child(3) { animation-delay: 0.3s; }
.dashboard-card:nth-child(4) { animation-delay: 0.4s; }

.activity {
    animation: slideIn 0.3s ease forwards;
}

.activity:nth-child(1) { animation-delay: 0.1s; }
.activity:nth-child(2) { animation-delay: 0.2s; }
.activity:nth-child(3) { animation-delay: 0.3s; }
.activity:nth-child(4) { animation-delay: 0.4s; }
.activity:nth-child(5) { animation-delay: 0.5s; }

/* États de focus pour l'accessibilité */
.access-item:focus,
.btn:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Effet subtil sur les cartes */
.dashboard-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(13, 71, 161, 0.02), transparent);
    transition: all 0.6s ease;
}

.dashboard-card:hover::after {
    left: 100%;
}
</style>


<div class="dashboard-layout">
    <div class="dashboard-card col-span-3">
        <div class="stat-grid">
            <div class="stat-item">
                <div class="value"><?php echo $rapports_en_attente; ?></div>
                <div class="label">Rapports en Attente</div>
            </div>
            <div class="stat-item">
                <div class="value"><?php echo $pv_a_valider; ?></div>
                <div class="label">PV à Valider</div>
            </div>
            <div class="stat-item">
                <div class="value"><?php echo $rapports_valides_total; ?></div>
                <div class="label">Rapports Approuvés</div>
            </div>
            <div class="stat-item">
                <div class="value"><?php echo $mes_votes_en_attente; ?></div>
                <div class="label">Mes Votes Requis</div>
            </div>
        </div>
    </div>

    <div class="dashboard-card col-span-2">
        <div class="card-header"><i class="icon fa-solid fa-rocket"></i><span>Accès Rapide</span></div>
        <div class="quick-access-grid">
            <a href="dashboard_commission.php?page=rapports_a_traiter" class="access-item">
                <span class="count"><?php echo $mes_votes_en_attente; ?></span>
                <span class="label">Traiter les Rapports</span>
                <span class="description">Rapports en attente de votre décision.</span>
            </a>
            <a href="dashboard_commission.php?page=gestion_pv" class="access-item">
                <span class="count"><?php echo $pv_a_valider; ?></span>
                <span class="label">Gérer les PV</span>
                <span class="description">Brouillons à rédiger et PV à valider.</span>
            </a>
            <a href="dashboard_commission.php?page=historique" class="access-item">
                <span class="count"><i class="icon fa-solid fa-box-archive"></i></span>
                <span class="label">Voir l'Historique</span>
                <span class="description">Consulter les archives finalisées.</span>
            </a>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header"><i class="icon fa-solid fa-list-ul"></i><span>Activités Récentes</span></div>
        <div class="activity-list">
             <?php if (empty($recent_activities)): ?>
                <p>Aucune activité récente à afficher.</p>
            <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity">
                    <div class="info">
                        <div class="description"><?php echo htmlspecialchars($activity['libelle_action']); ?></div>
                        <div class="author">Par : <?php echo htmlspecialchars($activity['login_utilisateur']); ?></div>
                    </div>
                    <div class="time"><?php echo date('d/m H:i', strtotime($activity['date_action'])); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-card col-span-3">
        <div class="card-header"><i class="icon fa-solid fa-inbox"></i><span>Rapports en Attente de Votre Vote</span></div>
        <div class="activity-list">
            <?php if (empty($rapports_cles)): ?>
                <p>Excellent ! Vous êtes à jour dans vos évaluations.</p>
            <?php else: ?>
                <?php foreach ($rapports_cles as $rapport): ?>
                <div class="activity">
                    <div class="info">
                        <div class="description"><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></div>
                    </div>
                    <a href="dashboard_commission.php?page=rapports_a_traiter" class="btn btn-primary btn-sm">Voter</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
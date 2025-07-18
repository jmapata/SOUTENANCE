<?php
// personnel_views/accueil.php
// La variable $pdo est disponible depuis le dashboard principal.

// --- 1. RÉCUPÉRATION DES STATISTIQUES POUR L'AGENT DE CONFORMITÉ ---

// a. Nombre de rapports en attente de vérification (statut 'Soumis')
$rapports_a_verifier_count = $pdo->query("SELECT COUNT(*) FROM rapport_etudiant WHERE id_statut_rapport = 'RAP_SOUMIS'")->fetchColumn();

// b. Nombre de rapports que cet agent a personnellement traités
$stmt_traites = $pdo->prepare("
    SELECT COUNT(DISTINCT id_rapport_etudiant) 
    FROM approuver 
    WHERE numero_personnel_administratif = (
        SELECT numero_personnel_administratif 
        FROM personnel_administratif 
        WHERE numero_utilisateur = ?
    )
");
$stmt_traites->execute([$_SESSION['numero_utilisateur']]);
$rapports_traites_count = $stmt_traites->fetchColumn();

// --- 2. RÉCUPÉRATION DE LA LISTE DES 5 DERNIERS RAPPORTS À VÉRIFIER ---
$stmt_rapports = $pdo->prepare("
    SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.date_soumission, e.nom, e.prenom
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_SOUMIS'
    ORDER BY r.date_soumission ASC
    LIMIT 5
");
$stmt_rapports->execute();
$liste_rapports_a_verifier = $stmt_rapports->fetchAll();
?>

<style>
    :root {
        --primary-color: #0d6efd; /* Un bleu plus vif */
        --success-color: #198754;
        --card-bg: #ffffff;
        --text-dark: #212529;
        --text-light: #6c757d;
        --border-color: #dee2e6;
        --shadow-soft: 0 .5rem 1rem rgba(0,0,0,.05);
    }
    .page-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--text-dark); }
    .page-header p { color: var(--text-light); }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-top: 30px;
    }
    .card {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
    }
    .card-full-width { grid-column: 1 / -1; }
    .card-col-2 { grid-column: span 2; }
    
    .card-title { font-size: 1.2rem; font-weight: 600; color: var(--text-dark); margin-bottom: 20px; }

    /* Cartes de statistiques */
    .stat-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .stat-item {
        background: #f8f9fa; padding: 20px; border-radius: 10px;
        border-left: 5px solid var(--primary-color);
    }
    .stat-item .value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); }
    .stat-item .label { font-size: 1rem; color: var(--text-light); margin-top: 5px; }
    .stat-item.success-border { border-left-color: var(--success-color); }

    /* Cartes d'accès rapide */
    .access-item {
        display: block; padding: 20px; border-radius: 10px;
        background-color: #f8f9fa; text-decoration: none; color: var(--text-dark);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .access-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-soft); }
    .access-item .icon { font-size: 1.8rem; margin-bottom: 10px; color: var(--primary-color); }
    .access-item .label { font-weight: 600; }

    /* Liste des tâches */
    .task-list .task { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); }
    .task-list .task:last-child { border-bottom: none; }
    .task .info { flex-grow: 1; }
    .task .info .title { font-weight: 500; }
    .task .info .author { font-size: 0.85rem; color: var(--text-light); }
    .task .btn-action { margin-left: auto; }
</style>

<div class="dashboard-grid">
    <div class="card col-span-2">
        <h3 class="card-title">Aperçu de l'Activité</h3>
        <div class="stat-card-grid">
            <div class="stat-item">
                <div class="value"><?php echo $rapports_a_verifier_count; ?></div>
                <div class="label">Rapports à Vérifier</div>
            </div>
            <div class="stat-item success-border">
                <div class="value"><?php echo $rapports_traites_count; ?></div>
                <div class="label">Rapports Traités</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3 class="card-title">Accès Rapides</h3>
        <div style="display:flex; flex-direction:column; gap:15px;">
            <a href="dashboard_conformite.php?page=rapports_a_verifier" class="access-item">
                <div class="icon"><i class="fas fa-list-check"></i></div>
                <span class="label">Voir tous les rapports</span>
            </a>
             <a href="dashboard_conformite.php?page=rapports_traites" class="access-item">
                <div class="icon"><i class="fas fa-history"></i></div>
                <span class="label">Consulter l'historique</span>
            </a>
        </div>
    </div>

    <div class="card col-span-3">
        <h3 class="card-title">File d'Attente - Derniers Rapports Reçus</h3>
        <div class="task-list">
             <?php if (empty($liste_rapports_a_verifier)): ?>
                <p>Félicitations, vous êtes à jour ! Aucun rapport n'est en attente de vérification.</p>
            <?php else: ?>
                <?php foreach ($liste_rapports_a_verifier as $rapport): ?>
                <div class="task">
                    <div class="info">
                        <div class="title"><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></div>
                        <div class="author">Soumis par <?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?> le <?php echo date('d/m/Y', strtotime($rapport['date_soumission'])); ?></div>
                    </div>
                    <div class="btn-action">
                        <a href="dashboard_conformite.php?page=verifier_un_rapport&id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-primary btn-sm">Vérifier</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
    <style>
    :root {
        --primary-color: #7c3aed;
        --primary-light: #a855f7;
        --primary-dark: #5b21b6;
        --success-color: #10b981;
        --card-bg: #ffffff;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --text-muted: #9ca3af;
        --border-color: #e5e7eb;
        --background: #f9fafb;
        --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
        background: var(--background);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--text-dark);
        line-height: 1.6;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        padding: 2rem 2rem 3rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(50%, -50%);
    }

    .page-header h1 {
        font-size: 2.25rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        position: relative;
        z-index: 1;
    }

    .page-header p {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-top: 0;
    }

    .card {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 16px 16px 0 0;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .card-full-width {
        grid-column: 1 / -1;
    }

    .col-span-2 {
        grid-column: span 2;
    }

    .col-span-3 {
        grid-column: span 3;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-title::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        border-radius: 2px;
    }

    /* Cartes de statistiques */
    .stat-card-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .stat-item {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.05) 0%, rgba(168, 85, 247, 0.02) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .stat-item:hover::before {
        opacity: 1;
    }

    .stat-item:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-soft);
    }

    .stat-item .value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        margin: 0;
        line-height: 1;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-item .label {
        font-size: 0.875rem;
        color: var(--text-light);
        margin-top: 0.5rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-item.success-border .value {
        background: linear-gradient(135deg, var(--success-color), #34d399);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Cartes d'accès rapide */
    .access-item {
        display: block;
        padding: 1.5rem;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        text-decoration: none;
        color: var(--text-dark);
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }

    .access-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .access-item:hover::before {
        opacity: 1;
    }

    .access-item:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
        text-decoration: none;
        color: var(--text-dark);
    }

    .access-item .icon {
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
        color: var(--primary-color);
        position: relative;
        z-index: 1;
    }

    .access-item .label {
        font-weight: 600;
        font-size: 0.95rem;
        position: relative;
        z-index: 1;
    }

    /* Liste des tâches */
    .task-list {
        background: var(--card-bg);
        border-radius: 12px;
        overflow: hidden;
    }

    .task-list .task {
        display: flex;
        align-items: center;
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s ease;
    }

    .task-list .task:last-child {
        border-bottom: none;
    }

    .task-list .task:hover {
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.02) 0%, rgba(168, 85, 247, 0.01) 100%);
    }

    .task .info {
        flex-grow: 1;
    }

    .task .info .title {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }

    .task .info .author {
        font-size: 0.875rem;
        color: var(--text-muted);
        font-weight: 400;
    }

    .task .btn-action {
        margin-left: auto;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(124, 58, 237, 0.3);
        text-decoration: none;
        color: white;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    /* Message d'état vide */
    .task-list p {
        text-align: center;
        color: var(--text-muted);
        font-style: italic;
        padding: 2rem;
        margin: 0;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.02) 0%, rgba(168, 85, 247, 0.01) 100%);
        border-radius: 12px;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .col-span-2, .col-span-3 {
            grid-column: span 2;
        }
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .col-span-2, .col-span-3 {
            grid-column: span 1;
        }
        
        .stat-card-grid {
            grid-template-columns: 1fr;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .page-header h1 {
            font-size: 1.875rem;
        }
        
        .card {
            padding: 1.5rem;
        }
    }
</style>
</style>
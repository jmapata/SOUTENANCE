<?php
require_once 'config/database.php';
$stmt_rapport = $pdo->prepare("
    SELECT r.*, s.libelle_statut_rapport, s.etape_workflow
    FROM rapport_etudiant r
    JOIN statut_rapport_ref s ON r.id_statut_rapport = s.id_statut_rapport
    WHERE r.numero_carte_etudiant = (SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?)
    ORDER BY r.date_derniere_modif DESC
    LIMIT 1
");
$stmt_rapport->execute([$_SESSION['numero_utilisateur']]);
$rapport_actuel = $stmt_rapport->fetch();

// 2. On récupère toutes les étapes du workflow pour le "stepper"
$etapes_workflow = $pdo->query("SELECT * FROM statut_rapport_ref WHERE etape_workflow IS NOT NULL AND etape_workflow > 1 ORDER BY etape_workflow")->fetchAll();

// 3. On vérifie si une action de correction est requise
$action_requise = ($rapport_actuel && $rapport_actuel['id_statut_rapport'] === 'RAP_NON_CONF');
?>

<style>
    :root {
        --primary-color: #5e72e4;
        --success-color: #2dce89;
        --warning-color: #fb6340;
        --card-bg: #ffffff;
        --text-dark: #32325d;
        --text-light: #8898aa;
        --border-color: #e9ecef;
        --shadow-soft: 0 7px 14px 0 rgba(60, 66, 87, 0.08), 0 3px 6px 0 rgba(0, 0, 0, 0.08);
    }
    .accueil-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    .main-content, .sidebar-content {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    .card {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
    }
    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title i { color: var(--primary-color); }

    /* Carte de statut principale */
    .main-status-card {
        background: linear-gradient(135deg, #717ff5, #5e72e4);
        color: white;
        text-align: center;
        padding: 30px 25px;
    }
    .main-status-card .label { font-size: 1rem; opacity: 0.8; }
    .main-status-card .status { font-size: 2rem; font-weight: 700; margin: 10px 0; text-transform: uppercase; letter-spacing: 1px; }
    .main-status-card .date { font-size: 0.9rem; opacity: 0.9; }

    /* Cartes d'accès rapide */
    .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    .access-item {
        display: block; padding: 25px 20px; border-radius: 10px;
        background-color: #f8f9fe; text-decoration: none; color: var(--text-dark);
        transition: transform 0.2s, box-shadow 0.2s; border: 1px solid var(--border-color);
        text-align: center;
    }
    .access-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-soft); border-color: var(--primary-color); }
    .access-item .icon { font-size: 2rem; margin-bottom: 15px; color: var(--primary-color); }
    .access-item .label { font-weight: 600; font-size: 1rem; }
    .access-item.alert { border-color: var(--warning-color); background-color: #fff9f6; }
    .access-item.alert .icon { color: var(--warning-color); animation: pulse-red 1.5s infinite; }

    /* Stepper de suivi */
    .workflow-stepper { list-style: none; padding: 0; position: relative; }
    .workflow-stepper::before { content: ''; position: absolute; top: 12px; left: 15px; width: 4px; height: calc(100% - 30px); background-color: var(--border-color); z-index: 1; }
    .step { display: flex; align-items: center; margin-bottom: 20px; position: relative; z-index: 2; }
    .step-icon { width: 30px; height: 30px; border-radius: 50%; background-color: #cbd5e1; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-right: 15px; border: 3px solid var(--card-bg);}
    .step-label { font-weight: 500; }
    .step.completed .step-icon { background-color: var(--success-color); }
    .step.active .step-icon { background-color: var(--primary-color); animation: pulse-blue 1.5s infinite; }
    .step.active .step-label { font-weight: 700; color: var(--text-dark); }
    
    @keyframes pulse-blue { 0% { box-shadow: 0 0 0 0 rgba(94, 114, 228, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(94, 114, 228, 0); } 100% { box-shadow: 0 0 0 0 rgba(94, 114, 228, 0); } }
    @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(251, 99, 64, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(251, 99, 64, 0); } 100% { box-shadow: 0 0 0 0 rgba(251, 99, 64, 0); } }

</style>

<div class="accueil-grid">
    <div class="main-content">
        <div class="card main-status-card">
            <?php if ($rapport_actuel): ?>
                <div class="label">Statut actuel de votre rapport :</div>
                <div class="status"><?php echo htmlspecialchars($rapport_actuel['libelle_statut_rapport']); ?></div>
                <div class="date">Dernière mise à jour le <?php echo date('d/m/Y à H:i', strtotime($rapport_actuel['date_derniere_modif'])); ?></div>
            <?php else: ?>
                <div class="status">Aucun Rapport Commencé</div>
                <div class="label">Utilisez les accès rapides pour démarrer la rédaction.</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 class="card-title"><i class="fas fa-rocket"></i> Mon Espace de Travail</h3>
            <div class="quick-access-grid">
                <a href="dashboard_etudiant.php?page=rapport_soumission" class="access-item">
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <span class="label">Nouveau Rapport</span>
                </a>
                <a href="dashboard_etudiant.php?page=rapport_modification" class="access-item <?php if($action_requise) echo 'alert'; ?>">
                    <div class="icon"><i class="fas fa-edit"></i></div>
                    <span class="label">Corriger Rapport</span>
                </a>
                <a href="dashboard_etudiant.php?page=rapport_historique" class="access-item">
                    <div class="icon"><i class="fas fa-history"></i></div>
                    <span class="label">Mon Historique</span>
                </a>
            </div>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="card">
            <h3 class="card-title"><i class="fas fa-route"></i> Suivi du Processus</h3>
            <?php if ($rapport_actuel && $rapport_actuel['id_statut_rapport'] !== 'RAP_BROUILLON'): ?>
                <ul class="workflow-stepper">
                    <?php foreach ($etapes_workflow as $etape): ?>
                        <?php
                            $status_class = '';
                            if ($rapport_actuel['etape_workflow'] > $etape['etape_workflow']) $status_class = 'completed';
                            elseif ($rapport_actuel['etape_workflow'] == $etape['etape_workflow']) $status_class = 'active';
                        ?>
                        <li class="step <?php echo $status_class; ?>">
                            <div class="step-icon"><i class="fas fa-check"></i></div>
                            <div class="step-label"><?php echo htmlspecialchars($etape['libelle_statut_rapport']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Soumettez votre rapport pour démarrer le suivi du processus de validation.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require_once 'config/database.php';

// --- RÉCUPÉRATION DES STATISTIQUES CLÉS ---
$total_users = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE statut_compte = 'actif'")->fetchColumn();
$total_rapports = $pdo->query("SELECT COUNT(*) FROM rapport_etudiant WHERE id_statut_rapport != 'RAP_VALID'")->fetchColumn();
$total_etudiants = $pdo->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
?>

<div class="page-header">
    <h1>Tableau de Bord Principal</h1>
</div>

<div class="dashboard-grid stats-grid">
    <div class="stat-card">
        <div class="icon-container" style="background-color: #e0e7ff; color: #4338ca;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="info">
            <h3><?php echo $total_users; ?></h3>
            <p>Utilisateurs Actifs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon-container" style="background-color: #dcfce7; color: #166534;">
            <i class="fa-solid fa-file-alt"></i>
        </div>
        <div class="info">
            <h3><?php echo $total_rapports; ?></h3>
            <p>Rapports en Cours</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon-container" style="background-color: #ffedd5; color: #9a3412;">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="info">
            <h3><?php echo $total_etudiants; ?></h3>
            <p>Étudiants Inscrits</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Accès Rapides</h2>
    </div>
    <div class="card-content">
        <div class="dashboard-grid shortcuts-grid">
            <a href="dashboard_admin.php?page=gestion_etudiants" class="shortcut-card">
                <i class="fa-solid fa-user-graduate shortcut-icon"></i>
                <span>Gestion des Étudiants</span>
            </a>
            <a href="dashboard_admin.php?page=gestion_enseignants" class="shortcut-card">
                <i class="fa-solid fa-chalkboard-user shortcut-icon"></i>
                <span>Gestion des Enseignants</span>
            </a>
            <a href="dashboard_admin.php?page=gestion_roles" class="shortcut-card">
                <i class="fa-solid fa-key shortcut-icon"></i>
                <span>Gestion des Habilitations</span>
            </a>
            <a href="dashboard_admin.php?page=referentiels" class="shortcut-card">
                <i class="fa-solid fa-list-check shortcut-icon"></i>
                <span>Gestion des Référentiels</span>
            </a>
        </div>
    </div>
</div>
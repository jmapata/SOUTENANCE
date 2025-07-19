<?php
session_start();
// Sécurité : vérifier que l'utilisateur est connecté et est bien un gestionnaire de scolarité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_SCOLARITE') {
    header('Location: login.php?error=unauthorized');
    exit();
}
$fullName = $_SESSION['user_full_name'] ?? 'Gestionnaire';

// 2. On calcule les initiales de manière sécurisée
$name_parts = explode(' ', htmlspecialchars($fullName));
$initials = '';
if (isset($name_parts[0]) && !empty($name_parts[0])) {
    $initials .= strtoupper(substr($name_parts[0], 0, 1));
}
if (isset($name_parts[1]) && !empty($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
}
// Si aucune initiale n'est trouvée, on met 'GS' par défaut
$initials = !empty($initials) ? $initials : 'GS';

$page = $_GET['page'] ?? 'accueil';
$page_title = '';
$titles = [
    'creer_etudiant' => '',
    'gestion_inscriptions' => '',
    'activer_comptes' => '',
    'gestion_notes' => '',
    'gestion_stages' => '',
    'generer_documents' => '',
    'traiter_reclamations' => ''
];
if (array_key_exists($page, $titles)) {
    $page_title = $titles[$page];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Gestion Scolarité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/personnel_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-school logo-icon"></i>
            <span class="logo-text">Gestion Scolarité</span>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <h3><?php echo htmlspecialchars($fullName); ?></h3>
            <p>Gestionnaire Scolarité</p>
        </div>
        
        <nav class="nav-menu">
           <ul>
                <li class="nav-item <?php echo ($page === 'accueil') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a>
                </li>
                
                <li class="nav-section-title">Gestion des Étudiants</li>
                <li class="nav-item <?php echo ($page === 'creer_etudiant') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=creer_etudiant"><i class="fa-solid fa-user-plus icon"></i> Créer Fiche Étudiant</a>
                </li>
                <li class="nav-item <?php echo ($page === 'gestion_inscriptions') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=gestion_inscriptions"><i class="fa-solid fa-file-invoice-dollar icon"></i> Gérer Inscriptions</a>
                </li>
                <li class="nav-item <?php echo ($page === 'activer_comptes') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=activer_comptes"><i class="fa-solid fa-user-check icon"></i> Activer les Comptes</a>
                </li>

                <li class="nav-section-title">Gestion Académique</li>
                <li class="nav-item <?php echo ($page === 'gestion_notes') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=gestion_notes"><i class="fa-solid fa-marker icon"></i> Gérer les Notes</a>
                </li>
                <li class="nav-item <?php echo ($page === 'gestion_stages') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=gestion_stages"><i class="fa-solid fa-building icon"></i> Suivi des Stages</a>
                </li>

                <li class="nav-section-title">Documents & Support</li>
                <li class="nav-item <?php echo ($page === 'generer_documents') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=generer_documents"><i class="fa-solid fa-file-pdf icon"></i> Générer Documents</a>
                </li>
                <li class="nav-item <?php echo ($page === 'traiter_reclamations') ? 'active' : ''; ?>">
                    <a href="dashboard_gestion_scolarite.php?page=traiter_reclamations"><i class="fa-solid fa-headset icon"></i> Traiter Réclamations</a>
                </li>
                
                <li class="nav-item" style="margin-top: auto;">
                    <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i> Déconnexion</a>
                </li>
            </ul>
        </nav>
    </aside>

    <div class="main-container">
        <header class="header">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <h1 class="header-title"><?php echo $page_title; ?></h1>

            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                <div class="user-avatar"><?php echo $initials; ?></div>
            </div>
        </header>

        <main class="content-area">
            <?php
            $allowed_pages = [
                'creer_etudiant', 'gestion_inscriptions', 'activer_comptes', 'gestion_notes',
                'gestion_stages', 'generer_documents','accueil', 'traiter_reclamations'
            ];
            
            if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
                include 'personnel_views/' . $_GET['page'] . '.php';
            } else {
                include 'personnel_views/accueil.php';
            
            }
            ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggleIcon = document.querySelector('.menu-toggle-icon');
            const sidebar = document.querySelector('.sidebar');
            menuToggleIcon.addEventListener('click', () => sidebar.classList.toggle('visible'));
        });
    </script>
</body>
</html>
<style>
    /* ===== RESET ET BASE ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    color: #333;
    line-height: 1.6;
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
    z-index: 1000;
    transition: transform 0.3s ease;
    box-shadow: 4px 0 20px rgba(13, 71, 161, 0.1);
    overflow-y: auto;
}

.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
}

.logo-icon {
    font-size: 28px;
    color: #fff;
    margin-right: 12px;
}

.logo-text {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.user-profile {
    padding: 24px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.02);
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.user-profile h3 {
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
}

.user-profile p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
}

/* ===== NAVIGATION ===== */
.nav-menu {
    padding: 20px 0;
}

.nav-menu ul {
    list-style: none;
}

.nav-section-title {
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 16px 20px 8px;
    margin-top: 16px;
}

.nav-item {
    margin: 2px 12px;
    border-radius: 8px;
    overflow: hidden;
}

.nav-item a {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
    position: relative;
}

.nav-item a:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    transform: translateX(4px);
}

.nav-item.active a {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    font-weight: 600;
}

.nav-item.active a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #fff;
}

.nav-item .icon {
    margin-right: 12px;
    font-size: 16px;
    width: 20px;
    text-align: center;
}

/* ===== MAIN CONTAINER ===== */
.main-container {
    margin-left: 280px;
    min-height: 100vh;
    background: #f8f9fa;
}

/* ===== HEADER ===== */
.header {
    background: #fff;
    padding: 20px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 100;
}

.menu-toggle-icon {
    display: none;
    font-size: 20px;
    color: #0d47a1;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.menu-toggle-icon:hover {
    background: rgba(13, 71, 161, 0.1);
}

.header-title {
    font-size: 28px;
    font-weight: 600;
    color: #0d47a1;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-name {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.user-info .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    border: 2px solid rgba(13, 71, 161, 0.1);
}

/* ===== CONTENT AREA ===== */
.content-area {
    padding: 32px;
    min-height: calc(100vh - 90px);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.visible {
        transform: translateX(0);
    }
    
    .main-container {
        margin-left: 0;
    }
    
    .menu-toggle-icon {
        display: block;
    }
    
    .header {
        padding: 16px 20px;
    }
    
    .header-title {
        font-size: 24px;
    }
    
    .content-area {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 100%;
    }
    
    .header-title {
        font-size: 20px;
    }
    
    .user-name {
        display: none;
    }
    
    .content-area {
        padding: 16px;
    }
}

/* ===== CARDS ET COMPOSANTS GÉNÉRAUX ===== */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid #e9ecef;
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #fff;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1565c0, #0d47a1);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(13, 71, 161, 0.3);
}

.btn-secondary {
    background: #f8f9fa;
    color: #0d47a1;
    border: 1px solid #e9ecef;
}

.btn-secondary:hover {
    background: #e9ecef;
    border-color: #0d47a1;
}

/* ===== FORMS ===== */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #1565c0;
    box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
}

/* ===== TABLES ===== */
.table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.table th,
.table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #0d47a1;
    font-size: 14px;
}

.table tr:hover {
    background: #f8f9fa;
}

/* ===== BADGES ===== */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: rgba(13, 71, 161, 0.1);
    color: #0d47a1;
}

/* ===== ALERTS ===== */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid transparent;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.alert-info {
    background: rgba(13, 71, 161, 0.1);
    color: #0d47a1;
    border-color: rgba(13, 71, 161, 0.2);
}
</style>
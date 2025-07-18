<?php
// On démarre le buffer de sortie tout au début. PHP va "garder en mémoire" tout ce qui est affiché.
ob_start();

session_start();
require_once 'config/database.php';

// Sécurité
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_CONFORMITE') {
    header('Location: login.php?error=unauthorized');
    exit();
}

// Préparation des variables
$fullName = $_SESSION['user_full_name'] ?? 'Agent de Conformité';
$name_parts = explode(' ', htmlspecialchars($fullName));
$initials = '';
if (isset($name_parts[0]) && !empty($name_parts[0])) $initials .= strtoupper(substr($name_parts[0], 0, 1));
if (isset($name_parts[1]) && !empty($name_parts[1])) $initials .= strtoupper(substr($name_parts[1], 0, 1));
$initials = !empty($initials) ? $initials : 'AC';

// Logique pour les titres
$page = $_GET['page'] ?? 'accueil';
$page_title = '';
$titles = [
    'rapports_a_verifier' => '',
    'rapports_traites' => '',
    'chat' => '',
    'verifier_un_rapport' => ''
];
if (array_key_exists($page, $titles)) {
    $page_title = $titles[$page];
}

// Juste avant d'envoyer le HTML, on nettoie tout ce qui aurait pu être affiché par erreur.
ob_end_clean(); 

// Le DOCTYPE est maintenant garanti d'être la première chose envoyée au navigateur.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Contrôle de Conformité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/conformite_style.css">
  <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-clipboard-check logo-icon"></i>
            <span class="logo-text">Conformité</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item <?php echo ($page === 'accueil') ? 'active' : ''; ?>"><a href="dashboard_conformite.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a></li>
                <li class="nav-section-title">Tâches Principales</li>
                <li class="nav-item <?php echo ($page === 'rapports_a_verifier') ? 'active' : ''; ?>"><a href="dashboard_conformite.php?page=rapports_a_verifier"><i class="fa-solid fa-file-circle-question icon"></i> Rapports à Vérifier</a></li>
                <li class="nav-item <?php echo ($page === 'rapports_traites') ? 'active' : ''; ?>"><a href="dashboard_conformite.php?page=rapports_traites"><i class="fa-solid fa-clock-rotate-left icon"></i> Rapports Traités</a></li>
                <li class="nav-section-title">Communication</li>
                <li class="nav-item <?php echo ($page === 'chat') ? 'active' : ''; ?>"><a href="dashboard_conformite.php?page=chat"><i class="fa-solid fa-comments icon"></i> Chat</a></li>
                <li class="nav-item" style="margin-top: auto; padding-top: 20px;"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i> Déconnexion</a></li>
            </ul>
        </nav>
    </aside>

    <div class="main-container">
        <header class="header">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <h1 class="header-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                <div class="user-avatar"><?php echo $initials; ?></div>
            </div>
        </header>

        <main class="content-area">
            <?php
            $allowed_pages = ['rapports_a_verifier', 'rapports_traites', 'chat', 'verifier_un_rapport'];
            if (in_array($page, $allowed_pages)) {
                include 'conformite_views/' . $page . '.php';
            } else {
                include 'conformite_views/accueil.php';
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
  /* Variables CSS */
:root {
    --primary-color: #6b46c1;
    --primary-hover: #7c3aed;
    --white: #ffffff;
    --light-gray: #f8fafc;
    --medium-gray: #e2e8f0;
    --dark-gray: #64748b;
    --text-color: #334155;
    --shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
    --shadow-hover: 0 8px 25px rgba(107, 70, 193, 0.15);
    --border-radius: 20px;
    --transition: all 0.3s ease;
}

/* Reset et base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: var(--text-color);
    line-height: 1.6;
    min-height: 100vh;
}

/* Layout principal */
.main-container {
    display: flex;
    min-height: 100vh;
    padding: 20px;
    gap: 20px;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    position: fixed;
    height: calc(100vh - 40px);
    left: 20px;
    top: 20px;
    transition: var(--transition);
    z-index: 1000;
    overflow: hidden;
}

.sidebar-header {
    padding: 30px 25px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-icon {
    font-size: 28px;
    color: var(--white);
}

.logo-text {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.nav-menu {
    padding: 25px 0;
    height: calc(100% - 120px);
    overflow-y: auto;
}

.nav-menu ul {
    list-style: none;
    padding: 0 20px;
}

.nav-section-title {
    padding: 20px 5px 10px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--dark-gray);
    margin-top: 15px;
}

.nav-item {
    margin: 8px 0;
    position: relative;
}

.nav-item a {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    color: var(--text-color);
    text-decoration: none;
    border-radius: 15px;
    transition: var(--transition);
    font-weight: 500;
    position: relative;
}

.nav-item a:hover {
    background: rgba(107, 70, 193, 0.08);
    color: var(--primary-color);
    transform: translateX(5px);
}

.nav-item.active a {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
    box-shadow: 0 4px 15px rgba(107, 70, 193, 0.3);
}

.nav-item.active a::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 20px;
    background: var(--primary-color);
    border-radius: 2px;
}

.icon {
    font-size: 18px;
    min-width: 20px;
}

/* Responsive sidebar */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.visible {
        transform: translateX(0);
    }
}

/* Contenu principal */
.main-container {
    margin-left: 320px;
    flex: 1;
    padding: 20px 20px 20px 0;
}

@media (max-width: 768px) {
    .main-container {
        margin-left: 0;
        padding: 20px;
    }
}

/* Header */
.header {
    background: var(--white);
    padding: 25px 35px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    position: sticky;
    top: 20px;
    z-index: 100;
}

.menu-toggle-icon {
    display: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--primary-color);
    padding: 12px;
    border-radius: 12px;
    transition: var(--transition);
}

.menu-toggle-icon:hover {
    background: rgba(107, 70, 193, 0.1);
}

@media (max-width: 768px) {
    .menu-toggle-icon {
        display: block;
    }
}

.header-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    background: var(--light-gray);
    padding: 12px 20px;
    border-radius: 50px;
    transition: var(--transition);
}

.user-info:hover {
    background: rgba(107, 70, 193, 0.1);
}

.user-name {
    font-weight: 600;
    color: var(--text-color);
}

.user-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-hover);
}

/* Zone de contenu */
.content-area {
    padding: 0;
    min-height: calc(100vh - 140px);
}

/* Cards */
.card {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 30px;
    margin-bottom: 25px;
    transition: var(--transition);
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--light-gray);
}

.card-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-title i {
    color: var(--primary-color);
    font-size: 28px;
}

/* Boutons */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: var(--transition);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-hover) 0%, var(--primary-color) 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: var(--white);
}

.btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: var(--white);
}

.btn-warning:hover {
    background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: var(--white);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
}

/* Tableaux */
.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
    border-radius: var(--border-radius);
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--white);
}

thead {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
}

th {
    padding: 18px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 18px 20px;
    border-bottom: 1px solid var(--light-gray);
    transition: var(--transition);
}

tbody tr:hover {
    background: rgba(107, 70, 193, 0.05);
}

tbody tr:last-child td {
    border-bottom: none;
}

/* Badges */
.badge {
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.badge-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: var(--white);
}

.badge-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: var(--white);
}

.badge-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: var(--white);
}

.badge-info {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: var(--white);
}

/* Formulaires */
.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-color);
}

.form-control {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid var(--medium-gray);
    border-radius: 15px;
    font-size: 14px;
    transition: var(--transition);
    background: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

/* Grilles */
.grid {
    display: grid;
    gap: 25px;
}

.grid-2 {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.grid-3 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.grid-4 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

/* Statistiques */
.stat-card {
    text-align: center;
    padding: 35px 25px;
    border-radius: var(--border-radius);
    background: var(--white);
    box-shadow: var(--shadow);
    transition: var(--transition);
    border: 3px solid var(--light-gray);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary-color);
}

.stat-number {
    font-size: 42px;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.stat-label {
    font-size: 14px;
    color: var(--dark-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* Alertes */
.alert {
    padding: 20px 25px;
    border-radius: var(--border-radius);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
    color: #059669;
    border: 2px solid rgba(16, 185, 129, 0.2);
}

.alert-warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%);
    color: #d97706;
    border: 2px solid rgba(245, 158, 11, 0.2);
}

.alert-danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
    color: #dc2626;
    border: 2px solid rgba(239, 68, 68, 0.2);
}

.alert-info {
    background: linear-gradient(135deg, rgba(107, 70, 193, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
    color: var(--primary-color);
    border: 2px solid rgba(107, 70, 193, 0.2);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: var(--light-gray);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover);
}

/* Responsive */
@media (max-width: 768px) {
    .main-container {
        padding: 15px;
        gap: 15px;
    }
    
    .content-area {
        padding: 0;
    }
    
    .card {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .header {
        padding: 20px;
    }
    
    .header-title {
        font-size: 24px;
    }
    
    .user-name {
        display: none;
    }
    
    .grid-2,
    .grid-3,
    .grid-4 {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        left: 15px;
        top: 15px;
        height: calc(100vh - 30px);
    }
}  /* Ajoutez ici vos styles CSS personnels */
</style>
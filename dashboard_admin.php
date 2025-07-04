<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    header('Location: login.php');
    exit();
}

// Logique pour déterminer le titre de la page en fonction du menu
$page = $_GET['page'] ?? 'accueil';
$page_title = '';
switch ($page) {
    case 'gestion_etudiants': $page_title = ''; break;
    case 'gestion_enseignants': $page_title = ''; break;
    case 'gestion_personnel': $page_title = ''; break;
    case 'gestion_roles': $page_title = ''; break;
    case 'referentiels': $page_title = ''; break;
    case 'parametres': $page_title = ''; break;
    case 'audit_logs': $page_title = ''; break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/admin_style.css">
    <link rel="stylesheet" href="assets/js/admin_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-shield-halved logo-icon"></i>
            <span class="logo-text">Administration</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item <?php echo ($page === 'accueil') ? 'active' : ''; ?>"><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a></li>
                
                <li class="nav-section-title">Gestion</li>
                <li class="nav-item">
                    <div class="menu-toggle-btn"><i class="fa-solid fa-users-gear icon"></i><span>Utilisateurs</span><i class="fa-solid fa-chevron-right arrow-icon"></i></div>
                    <ul class="submenu">
                        <li><a href="dashboard_admin.php?page=gestion_etudiants">Étudiants</a></li>
                        <li><a href="dashboard_admin.php?page=gestion_enseignants">Enseignants</a></li>
                        <li><a href="dashboard_admin.php?page=gestion_personnel">Personnel</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="dashboard_admin.php?page=gestion_roles"><i class="fa-solid fa-key icon"></i> Habilitations</a>
                </li>

                <li class="nav-section-title">Configuration</li>
                <li class="nav-item">
                    <a href="dashboard_admin.php?page=referentiels"><i class="fa-solid fa-list-check icon"></i> Référentiels</a>
                </li>
                <li class="nav-item">
                    <a href="dashboard_admin.php?page=parametres"><i class="fa-solid fa-sliders icon"></i> Paramètres Generaux</a>
                </li>

                <li class="nav-section-title">Supervision</li>
                <li class="nav-item">
                    <a href="dashboard_admin.php?page=audit_logs"><i class="fa-solid fa-file-shield icon"></i> Journaux d'Audit</a>
                </li>
                 <li class="nav-item">
                    <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i> Déconnexion</a>
                </li>
            </ul>
        </nav>
    </aside>

    <div class="main-container">
        <header class="header">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <h1 class="header-title"><?php echo $page_title; ?></h1>
        </header>

        <main class="content-area">
            <?php
            $allowed_pages = [
                'gestion_etudiants', 'gestion_enseignants', 'gestion_personnel', 
                'gestion_roles', 'referentiels', 'parametres', 'audit_logs'
            ];
            
            if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
                include 'admin_views/' . $_GET['page'] . '.php';
            } else {
                echo '<h2>Bienvenue, ' . htmlspecialchars($_SESSION['user_login']) . ' !</h2>';
                echo '<p>Sélectionnez une option dans le menu pour gérer la plateforme.</p>';
            }
            ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggleIcon = document.querySelector('.menu-toggle-icon');
            const sidebar = document.querySelector('.sidebar');
            menuToggleIcon.addEventListener('click', () => sidebar.classList.toggle('visible'));

            const dropdownToggles = document.querySelectorAll('.menu-toggle-btn');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    toggle.parentElement.classList.toggle('open');
                });
            });
        });
    </script>
</body>
</html>
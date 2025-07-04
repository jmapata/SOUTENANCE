<?php
// Début de session et vérification de sécurité
session_start();

// Si l'utilisateur n'est pas connecté ou n'est pas admin, on le redirige
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    header('Location: login.php');
    exit();
}

// Récupération des informations de l'utilisateur depuis la session
$user_login = $_SESSION['user_login'] ?? 'Admin';
$user_group_label = 'Administrateur Système'; // Vous pouvez rendre cela dynamique plus tard

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau de Contrôle - GestionMySoutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #1e40af;
            --sidebar-bg: #111827;
            --content-bg: #f3f4f6;
            --text-light: #f9fafb;
            --text-dark: #1f2937;
            --text-muted: #9ca3af;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--content-bg);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .logo-icon {
            font-size: 24px; color: var(--primary-color); margin-right: 10px;
        }
        .logo-text {
            font-size: 18px; font-weight: 600;
        }
        .user-profile {
            display: flex; align-items: center; padding: 15px 20px;
            border-top: 1px solid #374151; border-bottom: 1px solid #374151;
            margin-bottom: 20px;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color);
            color: white; display: flex; align-items: center; justify-content: center;
            margin-right: 12px; font-weight: bold;
        }
        .user-info h3 {
            font-size: 14px; font-weight: 600;
        }
        .user-info p {
            font-size: 12px; color: var(--text-muted);
        }
        .nav-menu {
            flex: 1; overflow-y: auto;
        }
        .nav-menu ul {
            list-style: none;
        }
        .nav-section-title {
            padding: 10px 20px; font-size: 11px; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .nav-item a {
            display: flex; align-items: center; padding: 12px 20px;
            color: #d1d5db; text-decoration: none;
            transition: all 0.2s ease;
        }
        .nav-item a:hover {
            background-color: #374151; color: white;
        }
        .nav-item.active a {
            background-color: var(--primary-color); color: white;
            border-left: 3px solid #a5b4fc;
        }
        .nav-item .icon {
            width: 20px; text-align: center; margin-right: 15px; font-size: 16px;
        }
        .sidebar-footer {
            padding: 20px; border-top: 1px solid #374151;
        }
        .logout-link {
            display: block; text-align: center; background-color: #374151; color: white;
            padding: 10px; border-radius: 6px; text-decoration: none; transition: background-color 0.2s;
        }
        .logout-link:hover {
            background-color: var(--primary-dark);
        }

        /* --- Main Content --- */
        .main-content {
            flex: 1; display: flex; flex-direction: column; overflow: hidden;
        }
        .header {
            background-color: white; padding: 0 30px; height: 70px;
            border-bottom: 1px solid #e5e7eb; display: flex;
            justify-content: space-between; align-items: center;
        }
        .header-left .menu-toggle {
            display: none; font-size: 24px; cursor: pointer; margin-right: 20px;
        }
        .header-title {
            font-size: 20px; font-weight: 600; color: var(--text-dark);
        }
        .header-right {
            display: flex; align-items: center; gap: 20px;
        }
        .search-box input {
            padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;
        }
        .notification-bell {
            font-size: 20px; color: #6b7280;
        }
        .content-area {
            flex: 1; padding: 30px; overflow-y: auto;
        }
        .content-area h2 {
            font-size: 24px; color: var(--text-dark); margin-bottom: 20px;
        }

        /* --- Responsive Design --- */
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                transform: translateX(-100%);
                height: 100%;
            }
            .sidebar.visible {
                transform: translateX(0);
                box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            }
            .header-left .menu-toggle {
                display: block;
            }
            .main-content.sidebar-visible-overlay::before {
                content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                background-color: rgba(0,0,0,0.5); z-index: 999;
            }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-shield-halved logo-icon"></i>
            <span class="logo-text">ValidMaster</span>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($user_login, 0, 2)); ?></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user_login); ?></h3>
                <p><?php echo htmlspecialchars($user_group_label); ?></p>
            </div>
        </div>

        <nav class="nav-menu">
            <ul>
                <li class="nav-item active">
                    <a href="dashboard_admin.php"><i class="fa-solid fa-house icon"></i> Tableau de Bord</a>
                </li>
                
                <li class="nav-section-title">Gestion Principale</li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-users-gear icon"></i> Gestion Utilisateurs</a>
                </li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-key icon"></i> Habilitations & Rôles</a>
                </li>

                <li class="nav-section-title">Configuration Système</li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-list-check icon"></i> Référentiels</a>
                </li>
                 <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-calendar-days icon"></i> Année Académique</a>
                </li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-sliders icon"></i> Paramètres Généraux</a>
                </li>

                <li class="nav-section-title">Supervision</li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-file-circle-check icon"></i> Suivi des Rapports</a>
                </li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-file-signature icon"></i> Suivi des PV</a>
                </li>
                <li class="nav-item">
                    <a href="#"><i class="fa-solid fa-triangle-exclamation icon"></i> Logs & Audit</a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
            </a>
        </div>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <i class="fa-solid fa-bars menu-toggle"></i>
                <h1 class="header-title">Tableau de Bord</h1>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher un utilisateur...">
                </div>
                <i class="fa-solid fa-bell notification-bell"></i>
            </div>
        </header>

        <main class="content-area">
            <h2>Bienvenue, <?php echo htmlspecialchars($user_login); ?> !</h2>
            <p>Ceci est votre panneau de contrôle principal. Le contenu des différentes pages s'affichera ici.</p>
            </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('visible');
                mainContent.classList.toggle('sidebar-visible-overlay');
            });

            // Optionnel: fermer la sidebar si on clique en dehors
            mainContent.addEventListener('click', function(e) {
                if (mainContent.classList.contains('sidebar-visible-overlay')) {
                    sidebar.classList.remove('visible');
                    mainContent.classList.remove('sidebar-visible-overlay');
                }
            });
        });
    </script>
</body>
</html>
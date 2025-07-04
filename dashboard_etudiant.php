<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Étudiant - GestionMySoutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --sidebar-gradient: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
            --content-bg: #f3f4f6;
            --card-bg: #ffffff;
            --accent-color: #3b82f6;
            --text-light: #e0e1dd;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background-color: var(--content-bg);
            display: flex; height: 100vh; overflow: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background: var(--sidebar-gradient);
            color: var(--text-light);
            display: flex; flex-direction: column;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 24px; display: flex; align-items: center; gap: 12px;
        }
        .logo-icon { font-size: 28px; color: #ffffff; }
        .logo-text { font-size: 20px; font-weight: 700; color: #ffffff; }

        .nav-menu {
            flex-grow: 1; display: flex; flex-direction: column;
        }
        .nav-menu ul {
            list-style: none; padding: 16px; flex-grow: 1;
        }
        .nav-item a, .nav-item .menu-toggle-btn {
            display: flex; align-items: center; padding: 12px 16px;
            color: #dbeafe; text-decoration: none; border-radius: 8px;
            transition: all 0.2s ease; cursor: pointer; margin-bottom: 4px;
            width: 100%; background: none; border: none; font-size: 15px; text-align: left;
        }
        .nav-item a:hover, .nav-item .menu-toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .nav-item.active > a, .nav-item.open > .menu-toggle-btn {
            background-color: #ffffff; color: var(--accent-color); font-weight: 600;
        }
        .nav-item .icon { width: 20px; text-align: center; margin-right: 16px; font-size: 18px; }

        /* --- CORRECTION APPLIQUÉE ICI --- */
        .submenu { 
            list-style: none; 
            background-color: rgba(0,0,0,0.2); 
            border-radius: 8px;
            max-height: 0; 
            overflow: hidden; 
            transition: all 0.4s ease-in-out;
            /* Le padding et la marge sont enlevés d'ici... */
        }
        .submenu a { padding: 10px 16px 10px 54px; font-size: 14px; }
        .nav-item.open > .submenu { 
            max-height: 200px;
            margin-top: 4px;  /* ...et appliqués seulement quand le menu est ouvert */
            padding: 8px 0;   /* <-- */
        }
        /* --- FIN DE LA CORRECTION --- */
        
        .arrow-icon { margin-left: auto; transition: transform 0.3s; }
        .nav-item.open .arrow-icon { transform: rotate(90deg); }

        .nav-separator { height: 1px; background-color: rgba(255, 255, 255, 0.2); margin: 16px 0; }
        .nav-item-logout a { color: #fecaca; }
        .nav-item-logout a:hover { background-color: #dc2626; color: white; }

        /* --- Main Content --- */
        .main-container { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header {
            background-color: var(--card-bg); padding: 0 30px; height: 70px;
            border-bottom: 1px solid var(--border-color);
            display: flex; align-items: center; flex-shrink: 0;
        }
        .menu-toggle-icon { display: none; font-size: 22px; cursor: pointer; margin-right: 20px; color: var(--text-dark); }
        .header-title { font-size: 24px; font-weight: 600; color: var(--text-dark); }
        .content-area { flex: 1; padding: 30px; overflow-y: auto; }

        /* --- Responsive Design --- */
        @media (max-width: 768px) {
            .sidebar { position: absolute; transform: translateX(-100%); }
            .sidebar.visible { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .menu-toggle-icon { display: block; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-graduation-cap logo-icon"></i>
            <span class="logo-text">ValidMaster</span>
        </div>
        
        <nav class="nav-menu">
            <ul>
                <li class="nav-item active"><a href="dashboard_etudiant.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a></li>
                <li class="nav-item">
                    <div class="menu-toggle-btn"><i class="fa-solid fa-file-pen icon"></i><span>Mon Rapport</span><i class="fa-solid fa-chevron-right arrow-icon"></i></div>
                    <ul class="submenu">
                        <li><a href="rapport_soumission.php">Soumettre / Modifier</a></li>
                        <li><a href="rapport_suivi.php">Suivi du processus</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a href="documents.php"><i class="fa-solid fa-folder-open icon"></i> Mes Documents</a></li>
                <li class="nav-item"><a href="reclamations.php"><i class="fa-solid fa-circle-question icon"></i> Mes Réclamations</a></li>
                <li class="nav-item"><a href="ressources.php"><i class="fa-solid fa-book-open icon"></i> Ressources & Aide</a></li>
                <li class="nav-separator"></li>
                <li class="nav-item"><a href="profil.php"><i class="fa-solid fa-user-circle icon"></i> Mon Profil</a></li>
                <li class="nav-item nav-item-logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i> Déconnexion</a></li>
            </ul>
        </nav>
    </aside>

    <div class="main-container">
        <header class="header">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <h1 class="header-title">Tableau de Bord</h1>
        </header>
        <main class="content-area">
             <h2>Bienvenue, Nom de l'Étudiant !</h2>
             <p>Ceci est votre espace personnel. Utilisez le menu de gauche pour naviguer.</p>
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
                    const parentItem = toggle.parentElement;
                    parentItem.classList.toggle('open');
                });
            });
        });
    </script>
</body>
</html>
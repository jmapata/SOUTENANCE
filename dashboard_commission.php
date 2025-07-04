<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Commission - GestionMySoutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/commission_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-gavel logo-icon"></i>
            <span class="logo-text">Commission</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item active">
                    <a href="dashboard_commission.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a>
                </li>
                
                <li class="nav-section-title">Évaluation des Rapports</li>
                <li class="nav-item">
                    <a href="dashboard_commission.php?page=rapports_a_traiter"><i class="fa-solid fa-folder-open icon"></i> Rapports à Traiter</a>
                </li>
                <li class="nav-item">
                    <a href="dashboard_commission.php?page=gestion_corrections"><i class="fa-solid fa-pen-to-square icon"></i> Gestion des Corrections</a>
                </li>

                <li class="nav-section-title">Procès-Verbaux</li>
                <li class="nav-item">
                    <a href="dashboard_commission.php?page=gestion_pv"><i class="fa-solid fa-file-signature icon"></i> Gestion des PV</a>
                </li>

                <li class="nav-section-title">Outils</li>
                <li class="nav-item">
                    <a href="dashboard_commission.php?page=communication"><i class="fa-solid fa-comments icon"></i> Communication</a>
                </li>
                <li class="nav-item">
                    <a href="dashboard_commission.php?page=historique"><i class="fa-solid fa-clock-rotate-left icon"></i> Historique & Archives</a>
                </li>
                
                <li class="nav-item" style="margin-top: auto; padding-top: 20px;">
                    <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i> Déconnexion</a>
                </li>
            </ul>
        </nav>
    </aside>

    <div class="main-container">
        <header class="header">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <h1 class="header-title">Tableau de Bord</h1>
        </header>

        <main class="content-area">
             <?php
            // La logique de chargement de page reste la même
            $allowed_pages = ['rapports_a_traiter', 'gestion_corrections', 'gestion_pv', 'communication', 'historique'];
            $page = $_GET['page'] ?? 'accueil';
            
            if (in_array($page, $allowed_pages)) {
                // Le dossier 'commission_views' contient les fichiers de contenu
                include 'commission_views/' . $page . '.php';
            } else {
                echo '<h2>Bienvenue, Membre de la Commission !</h2>';
                echo '<p>Utilisez les menus regroupés par section pour naviguer dans votre espace de travail.</p>';
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
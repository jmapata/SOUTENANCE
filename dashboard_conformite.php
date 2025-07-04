<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrôle de Conformité - GestionMySoutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/conformite_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-clipboard-check logo-icon"></i>
            <span class="logo-text">Conformité</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item active">
                    <a href="dashboard_conformite.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a>
                </li>
                
                <li class="nav-section-title">Tâches Principales</li>
                <li class="nav-item">
                    <a href="dashboard_conformite.php?page=rapports_a_verifier"><i class="fa-solid fa-file-circle-question icon"></i> Rapports à Vérifier</a>
                </li>
                <li class="nav-item">
                    <a href="dashboard_conformite.php?page=rapports_traites"><i class="fa-solid fa-clock-rotate-left icon"></i> Rapports Traités</a>
                </li>

                <li class="nav-section-title">Communication</li>
                <li class="nav-item">
                    <a href="dashboard_conformite.php?page=chat"><i class="fa-solid fa-comments icon"></i> Chat</a>
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
            // On ajoute 'chat' à la liste des pages autorisées
            $allowed_pages = ['rapports_a_verifier', 'rapports_traites', 'chat'];
            $page = $_GET['page'] ?? 'accueil';
            
            if (in_array($page, $allowed_pages)) {
                include 'conformite_views/' . $page . '.php';
            } else {
                echo '<h2>Bienvenue, Agent de Conformité !</h2>';
                echo '<p>Vous avez actuellement <strong>X rapports</strong> en attente de vérification.</p>';
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
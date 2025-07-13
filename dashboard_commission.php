<?php
session_start();

// Sécurité : vérifier que l'utilisateur est connecté et est bien un membre de la commission
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    header('Location: login.php?error=unauthorized');
    exit();
}

// ==========================================================
// ## DÉFINIR LES VARIABLES DE L'UTILISATEUR UNE SEULE FOIS ##
// ==========================================================
// On récupère le nom depuis la session, avec une valeur par défaut pour éviter les erreurs.
$fullName = $_SESSION['user_full_name'] ?? 'Membre Commission';

// On calcule les initiales de manière sécurisée
$name_parts = explode(' ', htmlspecialchars($fullName));
$initials = '';
if (isset($name_parts[0]) && !empty($name_parts[0])) {
    $initials .= strtoupper(substr($name_parts[0], 0, 1));
}
if (isset($name_parts[1]) && !empty($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
}
// Si aucune initiale n'est trouvée, on met 'C' par défaut
$initials = !empty($initials) ? $initials : 'C';
// ==========================================================


// Logique pour les titres de page
$page = $_GET['page'] ?? 'accueil';
$page_title = '';
// Vous pouvez ajouter des titres spécifiques à vos pages ici si vous le souhaitez
$titles = [
    'rapports_a_traiter' => '',
    'gestion_corrections' => '',
    'gestion_pv' => '',
    'communication' => '',
    'historique' => ''
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
    <title><?php echo $page_title; ?> - Espace Commission</title>
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
                <li class="nav-item <?php echo ($page === 'accueil') ? 'active' : ''; ?>">
                    <a href="dashboard_commission.php"><i class="fa-solid fa-table-columns icon"></i> Tableau de Bord</a>
                </li>
                
                <li class="nav-section-title">Évaluation des Rapports</li>
                <li class="nav-item <?php echo ($page === 'rapports_a_traiter') ? 'active' : ''; ?>">
                    <a href="dashboard_commission.php?page=rapports_a_traiter"><i class="fa-solid fa-folder-open icon"></i> Rapports à Traiter</a>
                </li>
                <li class="nav-item <?php echo ($page === 'gestion_corrections') ? 'active' : ''; ?>">
                    <a href="dashboard_commission.php?page=gestion_corrections"><i class="fa-solid fa-pen-to-square icon"></i> Gestion des Corrections</a>
                </li>

                <li class="nav-section-title">Procès-Verbaux</li>
                <li class="nav-item <?php echo ($page === 'gestion_pv') ? 'active' : ''; ?>">
                    <a href="dashboard_commission.php?page=gestion_pv"><i class="fa-solid fa-file-signature icon"></i> Gestion des PV</a>
                </li>

                <li class="nav-section-title">Outils</li>
                <li class="nav-item <?php echo ($page === 'communication') ? 'active' : ''; ?>">
                    <a href="dashboard_commission.php?page=communication"><i class="fa-solid fa-comments icon"></i> Communication</a>
                </li>
                <li class="nav-item <?php echo ($page === 'historique') ? 'active' : ''; ?>">
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
            <h1 class="header-title"><?php echo htmlspecialchars($page_title); ?></h1>

            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                <div class="user-avatar"><?php echo $initials; ?></div>
            </div>
        </header>

        <main class="content-area">
            <?php
            $allowed_pages = ['rapports_a_traiter', 'gestion_corrections', 'gestion_pv', 'communication', 'historique'];
            
            if (in_array($page, $allowed_pages)) {
                // Le dossier 'commission_views' contient les fichiers de contenu
                include 'commission_views/' . $page . '.php';
            } else {
                // Message d'accueil personnalisé
                echo '<h2>Bienvenue, ' . htmlspecialchars(explode(' ', $fullName)[0]) . ' !</h2>';
                echo '<p>Utilisez les menus pour naviguer dans votre espace de travail.</p>';
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
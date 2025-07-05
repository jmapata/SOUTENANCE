<?php
// session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT') {
//     header('Location: login.php');
//     exit();
// }

// Logique pour déterminer quelle page afficher
$page = $_GET['page'] ?? 'accueil';
$page_title = '';
$titles = [
    'rapport_soumission' => 'Soumettre mon Rapport',
    'rapport_suivi' => 'Suivi de mon Rapport',
    'documents' => 'Mes Documents',
    'reclamations' => 'Mes Réclamations',
    'ressources' => 'Ressources & Aide',
    'profil' => 'Mon Profil'
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
    <title><?php echo $page_title; ?> - Espace Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/etudiant_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <span class="logo-text">ValidMaster</span>
        </div>
        <nav class="nav-menu">
            <ul>
                <li class="nav-item <?php echo ($page === 'accueil') ? 'active' : ''; ?>"><a href="dashboard_etudiant.php"><i class="fa-solid fa-table-columns icon"></i><span>Tableau de Bord</span></a></li>
                <li class="nav-item">
                    <div class="menu-toggle-btn"><i class="fa-solid fa-file-pen icon"></i><span>Mon Rapport</span><i class="fa-solid fa-chevron-right arrow-icon"></i></div>
                    <ul class="submenu">
                        <li><a href="dashboard_etudiant.php?page=rapport_soumission">Soumettre / Modifier</a></li>
                        <li><a href="dashboard_etudiant.php?page=rapport_suivi">Suivi du processus</a></li>
                    </ul>
                </li>
                <li class="nav-item <?php echo ($page === 'documents') ? 'active' : ''; ?>"><a href="dashboard_etudiant.php?page=documents"><i class="fa-solid fa-folder-open icon"></i><span>Mes Documents</span></a></li>
                <li class="nav-item <?php echo ($page === 'reclamations') ? 'active' : ''; ?>"><a href="dashboard_etudiant.php?page=reclamations"><i class="fa-solid fa-circle-question icon"></i><span>Mes Réclamations</span></a></li>
                <li class="nav-item <?php echo ($page === 'ressources') ? 'active' : ''; ?>"><a href="dashboard_etudiant.php?page=ressources"><i class="fa-solid fa-book-open icon"></i><span>Ressources & Aide</span></a></li>
                <li class="nav-separator"></li>
                <li class="nav-item <?php echo ($page === 'profil') ? 'active' : ''; ?>"><a href="dashboard_etudiant.php?page=profil"><i class="fa-solid fa-user-circle icon"></i><span>Mon Profil</span></a></li>
                <li class="nav-item nav-item-logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket icon"></i><span>Déconnexion</span></a></li>
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
            // Liste des pages autorisées
            $allowed_pages = [
                'accueil', 'rapport_soumission', 'rapport_suivi', 
                'documents', 'reclamations', 'ressources', 'profil'
            ];
            
            // On inclut la bonne page de vue
            if (in_array($page, $allowed_pages)) {
                include 'etudiant_views/' . $page . '.php';
            }
            ?>
        </main>
    </div>
    
    <script src="assets/js/etudiant_script.js"></script>
</body>
</html>
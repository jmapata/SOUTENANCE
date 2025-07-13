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
$page_title = 'Tableau de Bord';
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
                echo '<h2>Bienvenue, ' . htmlspecialchars(explode(' ', $fullName)[0]) . ' !</h2>';
                echo '<p>Sélectionnez une option dans le menu pour commencer.</p>';
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
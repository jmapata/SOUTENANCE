<?php


$page = $_GET['page'] ?? 'accueil';
$page_title = 'Tableau de Bord';
$titles = [
    'creer_etudiant' => 'Créer une Fiche Étudiant',
    'gestion_inscriptions' => 'Gestion des Inscriptions',
    'activer_comptes' => 'Activation des Comptes',
    'gestion_notes' => 'Gestion des Notes',
    'gestion_stages' => 'Suivi des Stages',
    'generer_documents' => 'Génération de Documents',
    'traiter_reclamations' => 'Traitement des Réclamations'
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
            <div class="user-avatar">GS</div>
            <h3>Nom du Gestionnaire</h3>
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
        </header>

        <main class="content-area">
            <?php
            $allowed_pages = [
                'creer_etudiant', 'gestion_inscriptions', 'activer_comptes', 'gestion_notes',
                'gestion_stages', 'generer_documents', 'traiter_reclamations'
            ];
            
            if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
                // Créer un dossier 'personnel_views' pour ranger ces fichiers
                include 'personnel_views/' . $_GET['page'] . '.php';
            } else {
                echo '<h2>Bienvenue, Gestionnaire Scolarité !</h2>';
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
<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT') {
    header('Location: login.php');
    exit();
}
// Récupérer la photo de profil de l'étudiant
// On récupère la photo et les noms depuis utilisateur + etudiant
$stmt = $pdo->prepare("SELECT u.photo_profil, e.prenom, e.nom FROM utilisateur u JOIN etudiant e ON u.numero_utilisateur = e.numero_utilisateur WHERE u.numero_utilisateur = ?");
$stmt->execute([$_SESSION['numero_utilisateur']]);
$user = $stmt->fetch();
$photo_profil = $user['photo_profil'] ?? '';
$prenom = $user['prenom'] ?? '';
$nom = $user['nom'] ?? '';
$initials = '';
if (!empty($prenom)) $initials .= strtoupper(substr($prenom, 0, 1));
if (!empty($nom)) $initials .= strtoupper(substr($nom, 0, 1));
// Logique pour déterminer quelle page afficher
$page = $_GET['page'] ?? 'accueil';
$page_title = '';
$titles = [
    'rapport_soumission' => '',
    'rapport_suivi' => '',
    'documents' => '',
    'reclamations' => '',
    'ressources' => '',
    'profil' => ''
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
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
     <!-- Place the first <script> tag in your HTML's <head> -->
<script src="https://cdn.tiny.cloud/1/ab42dah00m96itwegph80cd2rt871imnngqvobbpv6ddnbi2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
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
        
        <div class="user-info">
            <span class="user-name">
                <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>
            </span>
            <div class="user-avatar">
                <?php if (!empty($photo_profil)): ?>
                    <img src="<?php echo htmlspecialchars($photo_profil); ?>" alt="Photo de profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <?php echo $initials; ?>
                <?php endif; ?>
            </div>
        </div>
        </header>

        <main class="content-area">
            <?php
            // Liste des pages autorisées
            $allowed_pages = [
                'accueil', 'rapport_soumission', 'rapport_suivi', 
                'documents', 'reclamations', 'ressources', 'profil',
                 'rapport_redaction_libre','rapport_redaction_modele',
                 'rapport_historique','rapport_modification'
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
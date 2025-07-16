<?php
session_start();
require_once 'config/database.php';

// Sécurité : vérifier que l'utilisateur est connecté et est bien un membre de la commission
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    header('Location: login.php?error=unauthorized');
    exit();
}

/// a. Statistiques générales
$rapports_en_attente = $pdo->query("SELECT COUNT(*) FROM rapport_etudiant WHERE id_statut_rapport = 'RAP_EN_COMMISSION'")->fetchColumn();
$pv_a_valider = $pdo->query("SELECT COUNT(*) FROM compte_rendu WHERE id_statut_pv = 'PV_SOUMIS_VALID'")->fetchColumn();
$rapports_approuves_total = $pdo->query("SELECT COUNT(DISTINCT id_rapport_etudiant) FROM vote_commission WHERE id_decision_vote = 'VOTE_APPROUVE' GROUP BY id_rapport_etudiant HAVING COUNT(DISTINCT numero_utilisateur) >= 4")->rowCount();

// b. Tâches spécifiques à l'utilisateur connecté
$stmt_mes_votes = $pdo->prepare("SELECT COUNT(r.id_rapport_etudiant) FROM rapport_etudiant r WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION' AND NOT EXISTS (SELECT 1 FROM vote_commission v WHERE v.id_rapport_etudiant = r.id_rapport_etudiant AND v.numero_utilisateur = ?)");
$stmt_mes_votes->execute([$_SESSION['numero_utilisateur']]);
$mes_votes_en_attente = $stmt_mes_votes->fetchColumn();

// c. Activités récentes
$recent_activities = $pdo->query("SELECT a.libelle_action, e.date_action, u.login_utilisateur FROM enregistrer e JOIN action a ON e.id_action = a.id_action JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur WHERE a.categorie_action = 'Commission' ORDER BY e.date_action DESC LIMIT 5")->fetchAll();

// d. Rapports clés (en attente de vote de l'utilisateur connecté)
$stmt_rapports_cles = $pdo->prepare("SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant FROM rapport_etudiant r WHERE r.id_statut_rapport = 'RAP_EN_COMMISSION' AND NOT EXISTS (SELECT 1 FROM vote_commission v WHERE v.id_rapport_etudiant = r.id_rapport_etudiant AND v.numero_utilisateur = ?) LIMIT 3");
$stmt_rapports_cles->execute([$_SESSION['numero_utilisateur']]);
$rapports_cles = $stmt_rapports_cles->fetchAll();
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
     <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
     <style>
       /* ==============================================
   /* ==============================================
   VARIABLES CSS & RESET
   ============================================== */
:root {
    --primary-color: #0d47a1;
    --primary-dark: #001970;
    --primary-light: #2962ff;
    --secondary-color: #ffffff;
    --accent-color: #1976d2;
    --success-color: #2e7d32;
    --warning-color: #f57c00;
    --danger-color: #d32f2f;
    --info-color: #1976d2;
    
    --sidebar-bg: #0d47a1;
    --sidebar-hover: rgba(255, 255, 255, 0.1);
    --sidebar-active: rgba(255, 255, 255, 0.15);
    --sidebar-text: #ffffff;
    --sidebar-text-muted: rgba(255, 255, 255, 0.7);
    
    --card-bg: #ffffff;
    --text-dark: #263238;
    --text-medium: #455a64;
    --text-light: #78909c;
    --text-muted: #90a4ae;
    --border-color: #e3f2fd;
    --border-light: #f5f5f5;
    --bg-light: #fafafa;
    --bg-body: #ffffff;
    
    --shadow-sm: 0 1px 3px rgba(13, 71, 161, 0.08);
    --shadow-md: 0 4px 12px rgba(13, 71, 161, 0.12);
    --shadow-lg: 0 8px 25px rgba(13, 71, 161, 0.15);
    --shadow-xl: 0 12px 40px rgba(13, 71, 161, 0.18);
    
    --gradient-primary: linear-gradient(135deg, #0d47a1 0%, #2962ff 100%);
    --gradient-primary-light: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
    --gradient-sidebar: linear-gradient(180deg, #0d47a1 0%, #001970 100%);
    --gradient-card: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    
    --sidebar-width: 280px;
    --header-height: 70px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--bg-body);
    color: var(--text-dark);
    line-height: 1.6;
    overflow-x: hidden;
}

/* ==============================================
   SIDEBAR STYLES
   ============================================== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--gradient-sidebar);
    box-shadow: var(--shadow-xl);
    z-index: 1000;
    transition: var(--transition);
    overflow-y: auto;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.sidebar-header {
    padding: 24px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    position: relative;
}

.sidebar-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
}

.logo {
    margin-bottom: 8px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.logo-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--sidebar-text);
    letter-spacing: 1px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* ==============================================
   NAVIGATION MENU
   ============================================== */
.nav-menu {
    padding: 24px 0;
}

.nav-menu ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-section-title {
    padding: 16px 24px 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--sidebar-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
}

.nav-section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 24px;
    right: 24px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
}

.nav-item {
    margin: 0 12px;
    border-radius: 12px;
    overflow: hidden;
    transition: var(--transition);
}

.nav-item a {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: var(--sidebar-text);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    border-radius: 12px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.nav-item a::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    opacity: 0;
    transition: var(--transition);
}

.nav-item a:hover {
    background: var(--sidebar-hover);
    transform: translateX(4px);
}

.nav-item a:hover::before {
    opacity: 1;
}

.nav-item.active a {
    background: rgba(255, 255, 255, 0.2);
    color: var(--sidebar-text);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
    transform: translateX(4px);
    border-left: 3px solid #ffffff;
}

.nav-item.active a::before {
    opacity: 0;
}

.nav-item .icon {
    margin-right: 12px;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
    transition: var(--transition);
}

.nav-item:hover .icon {
    transform: scale(1.1);
}

/* ==============================================
   MAIN CONTAINER
   ============================================== */
.main-container {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    transition: var(--transition);
}

/* ==============================================
   HEADER STYLES
   ============================================== */
.header {
    height: var(--header-height);
    background: #fff;
    border-bottom: 1px solid var(--border-color);
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(10px);
}

.menu-toggle-icon {
    display: none;
    font-size: 1.2rem;
    color: var(--primary-color);
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: var(--transition);
}

.menu-toggle-icon:hover {
    background: rgba(13, 71, 161, 0.1);
    color: var(--primary-dark);
}

.header-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-left: 16px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-name {
    font-weight: 500;
    color: var(--text-medium);
    font-size: 0.9rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-lg);
}

/* ==============================================
   CONTENT AREA
   ============================================== */
.content-area {
    flex: 1;
    padding: 32px;
    background: var(--bg-body);
    min-height: calc(100vh - var(--header-height));
}

/* ==============================================
   ALERT STYLES
   ============================================== */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    border: 1px solid transparent;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-sm);
    background: var(--card-bg);
}

.alert::before {
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 1.1rem;
}

.alert-success {
    background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
    color: var(--success-color);
    border-color: #c8e6c9;
}

.alert-success::before {
    content: '\f058';
    color: var(--success-color);
}

.alert-danger {
    background: linear-gradient(135deg, #fde7e7, #ffebee);
    color: var(--danger-color);
    border-color: #ffcdd2;
}

.alert-danger::before {
    content: '\f057';
    color: var(--danger-color);
}

.alert-warning {
    background: linear-gradient(135deg, #fff3e0, #fff8e1);
    color: var(--warning-color);
    border-color: #ffcc02;
}

.alert-warning::before {
    content: '\f071';
    color: var(--warning-color);
}

.alert-info {
    background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
    color: var(--info-color);
    border-color: #bbdefb;
}

.alert-info::before {
    content: '\f05a';
    color: var(--info-color);
}

/* ==============================================
   CARD STYLES
   ============================================== */
.card {
    background: var(--gradient-card);
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
    background: rgba(13, 71, 161, 0.02);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
}

.card-body {
    padding: 24px;
}

.card-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color);
    background: rgba(13, 71, 161, 0.02);
}

/* ==============================================
   BUTTON STYLES
   ============================================== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 0.9rem;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background: var(--gradient-primary);
    color: #ffffff;
}

.btn-primary:hover {
    background: var(--gradient-primary-light);
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--card-bg);
    color: var(--primary-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: rgba(13, 71, 161, 0.05);
    border-color: var(--primary-color);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 1rem;
}

/* ==============================================
   TABLE STYLES
   ============================================== */
.table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table th {
    background: rgba(13, 71, 161, 0.05);
    color: var(--primary-color);
    font-weight: 600;
    padding: 16px;
    text-align: left;
    border-bottom: 2px solid var(--border-color);
}

.table td {
    padding: 16px;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-dark);
}

.table tr:hover {
    background: rgba(13, 71, 161, 0.02);
}

.table tr:last-child td {
    border-bottom: none;
}

/* ==============================================
   FORM STYLES
   ============================================== */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-dark);
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: var(--transition);
    background: var(--card-bg);
    color: var(--text-dark);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
}

.form-control:hover {
    border-color: rgba(13, 71, 161, 0.3);
}

/* ==============================================
   BADGE STYLES
   ============================================== */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-primary {
    background: rgba(13, 71, 161, 0.1);
    color: var(--primary-color);
}

.badge-success {
    background: rgba(46, 125, 50, 0.1);
    color: var(--success-color);
}

.badge-warning {
    background: rgba(245, 124, 0, 0.1);
    color: var(--warning-color);
}

.badge-danger {
    background: rgba(211, 47, 47, 0.1);
    color: var(--danger-color);
}

/* ==============================================
   RESPONSIVE DESIGN
   ============================================== */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.visible {
        transform: translateX(0);
    }
    
    .main-container {
        margin-left: 0;
    }
    
    .menu-toggle-icon {
        display: block;
    }
    
    .header {
        padding: 0 20px;
    }
    
    .content-area {
        padding: 24px 20px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        max-width: 300px;
    }
    
    .header-title {
        font-size: 1.25rem;
    }
    
    .user-name {
        display: none;
    }
    
    .content-area {
        padding: 20px 16px;
    }
    
    .nav-section-title {
        padding: 12px 20px 6px;
    }
    
    .nav-item {
        margin: 0 8px;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 0 16px;
    }
    
    .sidebar-header {
        padding: 20px 16px;
    }
    
    .nav-menu {
        padding: 20px 0;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.8rem;
    }
    
    .btn {
        padding: 10px 16px;
        font-size: 0.85rem;
    }
}

/* ==============================================
   ANIMATIONS
   ============================================== */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sidebar {
    animation: slideInLeft 0.3s ease-out;
}

.content-area {
    animation: fadeIn 0.5s ease-out;
}

.card {
    animation: slideUp 0.4s ease-out;
}

/* ==============================================
   SCROLLBAR CUSTOMIZATION
   ============================================== */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--border-light);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: rgba(13, 71, 161, 0.3);
    border-radius: 4px;
    transition: var(--transition);
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(13, 71, 161, 0.5);
}

/* ==============================================
   FOCUS STYLES
   ============================================== */
.nav-item a:focus,
.menu-toggle-icon:focus,
.user-avatar:focus,
.btn:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* ==============================================
   ACCESSIBILITY
   ============================================== */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* ==============================================
   UTILITY CLASSES
   ============================================== */
.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 8px; }
.mb-2 { margin-bottom: 16px; }
.mb-3 { margin-bottom: 24px; }
.mb-4 { margin-bottom: 32px; }

.mt-0 { margin-top: 0; }
.mt-1 { margin-top: 8px; }
.mt-2 { margin-top: 16px; }
.mt-3 { margin-top: 24px; }
.mt-4 { margin-top: 32px; }

.d-flex { display: flex; }
.d-none { display: none; }
.d-block { display: block; }

.justify-content-center { justify-content: center; }
.justify-content-between { justify-content: space-between; }
.align-items-center { align-items: center; }

.text-primary { color: var(--primary-color); }
.text-secondary { color: var(--text-medium); }
.text-muted { color: var(--text-muted); }

.bg-primary { background: var(--gradient-primary); }
.bg-light { background: var(--bg-light); }
.bg-white { background: var(--card-bg); }

.border-radius-sm { border-radius: 8px; }
.border-radius-md { border-radius: 12px; }
.border-radius-lg { border-radius: 16px; }

.shadow-sm { box-shadow: var(--shadow-sm); }
.shadow-md { box-shadow: var(--shadow-md); }
.shadow-lg { box-shadow: var(--shadow-lg); }

/* ==============================================
   PRINT STYLES
   ============================================== */
@media print {
    .sidebar,
    .header {
        display: none;
    }
    
    .main-container {
        margin-left: 0;
    }
    
    .content-area {
        padding: 0;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ccc;
    }
}
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header" style="flex-direction:column;align-items:center;gap:8px;">
            <!-- Logo SVG ValidMaster -->
            <div class="logo" style="width:180px;max-width:90%;margin-bottom:2px;">
                <svg viewBox="0 0 300 80" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#f0f6ff;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="accentGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#e8f0ff;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="1" dy="1" stdDeviation="2" flood-color="#000" flood-opacity="0.3"/>
                        </filter>
                    </defs>
                    <g transform="translate(10, 10)">
                        <g transform="translate(0, 5)">
                            <path d="M25 5 L45 5 Q50 5 50 10 L50 35 Q50 45 35 50 L35 50 Q20 45 20 35 L20 10 Q20 5 25 5 Z" fill="rgba(255,255,255,0.9)" filter="url(#shadow)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
                            <path d="M28 30 L33 35 L42 20" fill="none" stroke="#4285f4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="35" cy="15" r="2" fill="#4285f4" opacity="0.7"/>
                            <rect x="30" y="40" width="10" height="2" fill="#4285f4" opacity="0.5" rx="1"/>
                        </g>
                        <g transform="translate(70, 0)">
                            <text x="0" y="30" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="white">Valid</text>
                            <text x="65" y="30" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="rgba(255,255,255,0.9)">Master</text>
                            <text x="0" y="50" font-family="Arial, sans-serif" font-size="10" fill="rgba(255,255,255,0.8)" font-weight="normal">Gestion des Soutenances</text>
                        </g>
                        <g opacity="0.6">
                            <circle cx="200" cy="15" r="1.5" fill="white"/>
                            <circle cx="220" cy="25" r="1" fill="rgba(255,255,255,0.7)"/>
                            <circle cx="240" cy="18" r="1.2" fill="white"/>
                            <line x1="70" y1="40" x2="180" y2="40" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                        </g>
                        <g transform="translate(210, 25)">
                            <rect x="0" y="0" width="60" height="18" rx="9" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.3)"/>
                            <text x="30" y="12" font-family="Arial, sans-serif" font-size="9" fill="white" text-anchor="middle" font-weight="500">UNIVERSITY</text>
                        </g>
                    </g>
                </svg>
            </div>
            <span class="logo-text" style="font-size:1.1rem;font-weight:600;color:#fff;letter-spacing:1px;">ESPACE COMMISSION</span>
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
             if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']); // On efface le message après l'avoir affiché
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']); // On efface le message
    }
            $allowed_pages = ['rapports_a_traiter', 'gestion_corrections', 'gestion_pv', 'communication','editer_pv', 'historique'];

            if (in_array($page, $allowed_pages)) {
                // Le dossier 'commission_views' contient les fichiers de contenu
                include 'commission_views/' . $page . '.php';
            } else {
                  // INCLUT MAINTENANT VOTRE FICHIER D'ACCUEIL PAR DÉFAUT
            include 'commission_views/accueil.php';
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
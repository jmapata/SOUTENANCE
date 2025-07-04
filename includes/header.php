<?php
// /includes/header.php
require_once __DIR__ . '/../includes/session_handler.php';
require_once __DIR__ . '/../config/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion MySoutenance</title>
    <link rel="stylesheet" href="/assets/style.css"> 
</head>
<body>

<header>
    <h1><a href="/">Gestion MySoutenance</a></h1>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Bonjour, <?php echo htmlspecialchars($_SESSION['user_data']['login_utilisateur']); ?>!</span>
            
            <?php // Menu dynamique basé sur les permissions ?>
            <?php if (checkPermission('TRAIT_ETUDIANT_DASHBOARD_ACCEDER')): ?>
                <a href="/dashboard_etudiant.php">Tableau de Bord Étudiant</a>
            <?php endif; ?>
            
            <?php if (checkPermission('TRAIT_PERS_ADMIN_CONFORMITE_LISTER')): ?>
                <a href="/conformite_liste.php">Vérification Conformité</a>
            <?php endif; ?>

            <?php if (checkPermission('TRAIT_ADMIN_GERER_UTILISATEURS_LISTER')): ?>
                <a href="/gestion_utilisateurs.php">Gérer Utilisateurs</a>
            <?php endif; ?>

            <a href="/logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="/login.php">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main>
<?php
// /includes/session_handler.php

session_start();

[cite_start]// Gère l'expiration de session après 30 minutes d'inactivité (RG3) [cite: 155]
$timeout_duration = 1800; // 30 minutes en secondes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: /login.php?reason=inactive"); // Redirige avec un message
    exit();
}

$_SESSION['last_activity'] = time(); // Met à jour le temps de la dernière activité

// Vérifie si l'utilisateur est connecté.
// Si on n'est pas sur la page de login et que l'utilisateur n'est pas connecté, on redirige.
$is_login_page = basename($_SERVER['PHP_SELF']) == 'login.php';

if (!isset($_SESSION['user_id']) && !$is_login_page) {
    header("Location: /login.php");
    exit();
}
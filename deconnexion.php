<?php
// logout.php
require_once 'includes/session_handler.php';

if (isset($_SESSION['user_id'])) {
    // Optionnel : enregistrer l'action de déconnexion
    // require_once 'config/functions.php';
    // logAction($_SESSION['user_id'], 'LOGOUT');
}

session_unset();
session_destroy();

header("Location: /login.php?reason=logout");
exit();
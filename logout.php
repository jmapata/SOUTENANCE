<?php
// Fichier : logout.php

// 1. On initialise la session. Il faut la démarrer pour pouvoir la détruire.
session_start();

// 2. On vide le tableau de session de toutes ses variables.
$_SESSION = array();

// 3. On détruit la session côté serveur.
session_destroy();

// 4. On redirige l'utilisateur vers la page de connexion.
header("location: login.php");
exit;
?>
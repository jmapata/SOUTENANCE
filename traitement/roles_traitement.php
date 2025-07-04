<?php
session_start();
require_once '../config/database.php';

// Sécurité : vérifier si l'utilisateur est un admin
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    header('Location: ../login.php');
    exit();
}

// Vérifier que le formulaire a bien été soumis et qu'une action est demandée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    try {
        // On utilise un switch pour diriger vers la bonne opération
        switch ($action) {
            // --- TRAITEMENTS POUR LES GROUPES ---
            case 'ajouter_groupe':
                $stmt = $pdo->prepare("INSERT INTO groupe_utilisateur (id_groupe_utilisateur, libelle_groupe_utilisateur) VALUES (?, ?)");
                $stmt->execute([$_POST['id_groupe'], $_POST['libelle_groupe']]);
                break;

            case 'modifier_groupe':
                $stmt = $pdo->prepare("UPDATE groupe_utilisateur SET libelle_groupe_utilisateur = ? WHERE id_groupe_utilisateur = ?");
                $stmt->execute([$_POST['libelle_groupe'], $_POST['id_groupe_original']]);
                break;

            case 'supprimer_groupe':
                $stmt = $pdo->prepare("DELETE FROM groupe_utilisateur WHERE id_groupe_utilisateur = ?");
                $stmt->execute([$_POST['id_groupe']]);
                break;

            // --- TRAITEMENTS POUR LES TYPES ---
            case 'ajouter_type':
                $stmt = $pdo->prepare("INSERT INTO type_utilisateur (id_type_utilisateur, libelle_type_utilisateur) VALUES (?, ?)");
                $stmt->execute([$_POST['id_type'], $_POST['libelle_type']]);
                break;
            
            case 'modifier_type':
                $stmt = $pdo->prepare("UPDATE type_utilisateur SET libelle_type_utilisateur = ? WHERE id_type_utilisateur = ?");
                $stmt->execute([$_POST['libelle_type'], $_POST['id_type_original']]);
                break;

            case 'supprimer_type':
                $stmt = $pdo->prepare("DELETE FROM type_utilisateur WHERE id_type_utilisateur = ?");
                $stmt->execute([$_POST['id_type']]);
                break;
        }

    } catch (PDOException $e) {
        // En cas d'erreur (ex: ID dupliqué), on peut la gérer ici
        // Pour l'instant, on redirige simplement.
    }
}

// Rediriger vers la page des habilitations après chaque opération
header('Location: ../dashboard_admin.php?page=gestion_roles');
exit();
?>
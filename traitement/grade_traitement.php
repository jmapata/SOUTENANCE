<?php
// /traitement/grade_traitement.php
require_once '../includes/session_handler.php';
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// if (!checkPermission('TRAIT_ADMIN_GERER_REFERENTIELS')) { redirect('/dashboard.php?error=Accès non autorisé'); }

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create':
        $libelle = $_POST['libelle_grade'] ?? '';
        $abrev = $_POST['abreviation_grade'] ?? '';
        
        $id_grade = generateUniqueID('GRD'); // Préfixe pour Grade

        $stmt = $p_pdo->prepare("INSERT INTO grade (id_grade, libelle_grade, abreviation_grade) VALUES (?, ?, ?)");
        $stmt->execute([$id_grade, $libelle, $abrev]);
        
        redirect('/gestion_grades.php?success=Grade créé avec succès.');
        break;

    case 'update':
        $id = $_POST['id_grade'] ?? '';
        $libelle = $_POST['libelle_grade'] ?? '';
        $abrev = $_POST['abreviation_grade'] ?? '';

        $stmt = $p_pdo->prepare("UPDATE grade SET libelle_grade = ?, abreviation_grade = ? WHERE id_grade = ?");
        $stmt->execute([$libelle, $abrev, $id]);
        
        redirect('/gestion_grades.php?success=Grade mis à jour avec succès.');
        break;

    case 'delete':
        $id = $_GET['id'] ?? '';
        
        // Attention: la suppression peut échouer à cause des contraintes de clé étrangère.
        // Une meilleure approche serait de faire une suppression "logique" (soft delete).
        try {
            $stmt = $p_pdo->prepare("DELETE FROM grade WHERE id_grade = ?");
            $stmt->execute([$id]);
            redirect('/gestion_grades.php?success=Grade supprimé avec succès.');
        } catch (PDOException $e) {
            redirect('/gestion_grades.php?error=Impossible de supprimer ce grade, il est probablement utilisé ailleurs.');
        }
        break;

    default:
        redirect('/gestion_grades.php');
        break;
}
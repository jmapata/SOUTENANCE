<?php
// /traitement/soumission_rapport_traitement.php
require_once '../includes/session_handler.php';
require_once '../config/db_connect.php';
require_once '../config/functions.php';

if (!checkPermission('TRAIT_ETUDIANT_RAPPORT_SOUMETTRE')) {
    redirect('/dashboard_etudiant.php?error=Accès non autorisé');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/soumission_rapport.php');
}

// --- Récupération des données du formulaire ---
$titre = $_POST['titre'] ?? '';
$theme = $_POST['theme'] ?? '';
$nb_pages = $_POST['nb_pages'] ?? 0;
$user_id = $_SESSION['user_id'];

// --- Validation des données ---
if (empty($titre) || empty($theme) || empty($_FILES['rapport_pdf']['name'])) {
    redirect('/soumission_rapport.php?error=Tous les champs sont requis.');
}

// --- Traitement du fichier uploadé ---
$file = $_FILES['rapport_pdf'];

// Vérification de l'erreur d'upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect('/soumission_rapport.php?error=Erreur lors du téléversement du fichier.');
}

[cite_start]// Vérification de la taille (RG10) [cite: 381]
$max_size = 50 * 1024 * 1024; // 50 Mo
if ($file['size'] > $max_size) {
    redirect('/soumission_rapport.php?error=Le fichier est trop volumineux (max 50 Mo).');
}

[cite_start]// Vérification du type MIME (RG10) [cite: 381]
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);
if ($mime_type !== 'application/pdf') {
    redirect('/soumission_rapport.php?error=Le fichier doit être au format PDF.');
}

// --- Sauvegarde du fichier ---
$upload_dir = __DIR__ . '/../uploads/rapports/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
// Générer un nom de fichier unique pour éviter les conflits
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'RAPPORT_' . $user_id . '_' . time() . '.' . $file_extension;
$destination = $upload_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    redirect('/soumission_rapport.php?error=Impossible de sauvegarder le fichier.');
}


// --- Insertion en base de données ---
try {
    $rapport_id = generateUniqueID('RAP');
    $stmt = $p_pdo->prepare(
        "INSERT INTO rapport_etudiant (id_rapport_etudiant, libelle_rapport_etudiant, theme, numero_carte_etudiant, nombre_pages, id_statut_rapport, date_soumission, date_derniere_modif) 
         VALUES (:id, :titre, :theme, :user_id, :nb_pages, 'SOUMIS', NOW(), NOW())"
    );
    $stmt->execute([
        'id' => $rapport_id,
        'titre' => $titre,
        'theme' => $theme,
        'user_id' => $user_id,
        'nb_pages' => $nb_pages
    ]);
    
    // Il faudrait aussi stocker le chemin du fichier, la table `rapport_etudiant` devrait avoir une colonne pour ça.
    // Ex: ALTER TABLE rapport_etudiant ADD COLUMN chemin_fichier VARCHAR(255);
    // $p_pdo->prepare("UPDATE rapport_etudiant SET chemin_fichier = :path WHERE id_rapport_etudiant = :id")->execute(['path' => $new_filename, 'id' => $rapport_id]);

    logAction($user_id, 'TRAIT_ETUDIANT_RAPPORT_SOUMETTRE', ['rapport_id' => $rapport_id]);
    redirect('/dashboard_etudiant.php?success=Rapport soumis avec succès !');

} catch(Exception $e) {
    // Supprimer le fichier si la BDD échoue
    if (file_exists($destination)) {
        unlink($destination);
    }
    error_log($e->getMessage());
    redirect('/soumission_rapport.php?error=Une erreur de base de données est survenue.');
}
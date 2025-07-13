<?php
// traitement/generer_rapport_modele.php (Version finale avec génération PDF)

session_start();

// Inclure l'autoloader de Composer pour utiliser mPDF
require_once '../vendor/autoload.php'; 
// Inclure la connexion à la base de données
require_once '../config/database.php';

/**
 * Fonction pour envoyer une réponse JSON propre et arrêter le script.
 */
function send_json_response(array $data) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// --- Sécurité et Validation ---
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Accès non autorisé.']);
}

// --- Récupération des données du formulaire ---
$theme = trim($_POST['theme'] ?? '');
$etudiant_nom = trim($_POST['etudiant'] ?? '');
$maitre_stage_nom = trim($_POST['maitre_stage'] ?? '');
$encadreur_nom = trim($_POST['encadreur'] ?? '');
$contenu_dynamique = $_POST['contenu'] ?? [];

// --- Validation des données ---
if (empty($theme) || empty($etudiant_nom)) {
    send_json_response(['success' => false, 'message' => 'Le thème et votre nom sont obligatoires.']);
}

try {
    // --- PARTIE 1 : SAUVEGARDE EN BASE DE DONNÉES ---
    $pdo->beginTransaction();

    // Récupérer le numero_carte_etudiant de l'étudiant connecté
    $stmt = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
    $stmt->execute([$_SESSION['numero_utilisateur']]);
    $etudiant_db = $stmt->fetch();
    if (!$etudiant_db) {
        throw new Exception("Utilisateur étudiant non trouvé dans la base de données.");
    }
    $numero_carte_etudiant = $etudiant_db['numero_carte_etudiant'];

    // Préparer les chemins et noms de fichiers pour le PDF
    $rapport_id = 'RAP-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
    $pdf_filename = $rapport_id . '.pdf';
    $upload_dir = '../rapports_pdf/';
    $pdf_path_sur_serveur = $upload_dir . $pdf_filename;
    $db_pdf_path_relatif = 'rapports_pdf/' . $pdf_filename; // Chemin à stocker en BDD

    // Créer le dossier pour les PDF s'il n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Préparer les métadonnées pour le champ `resume`
    $metadata = json_encode([
        'nom_etudiant' => $etudiant_nom,
        'maitre_stage' => $maitre_stage_nom,
        'encadreur' => $encadreur_nom
    ]);
    
    // Insérer les informations du rapport, y compris le chemin du PDF
    $sql_rapport = "INSERT INTO rapport_etudiant 
        (id_rapport_etudiant, libelle_rapport_etudiant, theme, resume, chemin_pdf, numero_carte_etudiant, id_statut_rapport, date_derniere_modif) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_rapport = $pdo->prepare($sql_rapport);
    $stmt_rapport->execute([$rapport_id, $theme, $theme, $metadata, $db_pdf_path_relatif, $numero_carte_etudiant, 'RAP_BROUILLON']);

    // Insérer les sections dynamiques du rapport
    if (!empty($contenu_dynamique)) {
        $sql_section = "INSERT INTO section_rapport (id_rapport_etudiant, titre_section, contenu_section, ordre) VALUES (?, ?, ?, ?)";
        $stmt_section = $pdo->prepare($sql_section);
        $ordre = 1;
        foreach ($contenu_dynamique as $section) {
            $type = $section['type'] ?? 'paragraphe';
            $valeur = trim($section['valeur'] ?? '');
            if (!empty($valeur)) {
                $titre_section = ucfirst($type) . ' ' . $ordre;
                $stmt_section->execute([$rapport_id, $titre_section, $valeur, $ordre]);
                $ordre++;
            }
        }
    }

    $pdo->commit(); // La transaction est validée, les données sont en base.

    // --- PARTIE 2 : GÉNÉRATION DU FICHIER PDF ---
    
    // On capture le contenu du template HTML dans une variable PHP
    ob_start();
    // Le template a accès à toutes les variables définies ci-dessus ($theme, $etudiant_nom, etc.)
    include '../templates/rapport_pdf_template.php';
    $html_content = ob_get_clean();

    // On crée une nouvelle instance de mPDF
    $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
    // On écrit le contenu HTML dans le document PDF
    $mpdf->WriteHTML($html_content);
    // On sauvegarde le fichier PDF sur le serveur à l'emplacement défini
    $mpdf->Output($pdf_path_sur_serveur, \Mpdf\Output\Destination::FILE);


    // --- PARTIE 3 : RÉPONSE AU CLIENT ---
    // On prépare l'URL de redirection vers la page de suivi (historique)
    $redirectUrl = 'dashboard_etudiant.php?page=rapport_suivi&status=success';
    send_json_response(['success' => true, 'message' => "Rapport sauvegardé et PDF généré avec succès.", 'redirectUrl' => $redirectUrl]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json_response(['success' => false, 'message' => "Erreur critique : " . $e->getMessage()]);
}
?>
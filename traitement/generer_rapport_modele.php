<?php
// traitement/generer_rapport_modele.php (Version finale avec génération PDF)

session_start();

// Inclure l'autoloader de Composer pour utiliser mPDF
require_once '../vendor/autoload.php'; 
// Inclure la connexion à la base de données
require_once '../config/database.php';

// Configuration de mPDF pour correspondre au style du modèle
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 25,
    'margin_right' => 25,
    'margin_top' => 30,
    'margin_bottom' => 30,
    'default_font' => 'times',
    'default_font_size' => 12,
    'debug' => true
]);

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

// Définition du CSS pour le PDF
$css = '
<style>
    body {
        font-family: "Times New Roman", Times, serif;
        line-height: 1.4;
        color: #000;
    }
    .preview-header {
        display: flex;
        justify-content: space-between;
        text-align: center;
        margin-bottom: 30px;
    }
    .header-left, .header-right {
        width: 48%;
        text-align: center;
    }
    .preview-header img {
        height: 60px;
        margin: 15px 0;
    }
    .logo-kyria {
        height: 40px;
    }
    .preview-body {
        text-align: center;
        margin-top: 30px;
    }
    .degree-info {
        font-size: 12pt;
        margin-bottom: 30px;
    }
    .theme {
        font-size: 18pt;
        font-weight: bold;
        text-transform: uppercase;
        padding: 15px 0;
        margin: 30px auto;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        width: 90%;
        text-align: center;
    }
    .author-block {
        margin: 30px 0 50px 0;
        text-align: center;
    }
    .preview-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
    }
    .footer-box {
        border: 1px solid #000;
        width: 48%;
        padding: 15px;
        text-align: center;
    }
    .footer-box strong {
        font-size: 11pt;
        text-transform: uppercase;
    }
    h2 {
        font-size: 14pt;
        margin-top: 20px;
    }
    h3 {
        font-size: 12pt;
        margin-top: 15px;
    }
    p {
        text-align: justify;
        margin: 10px 0;
    }
    #preview-content {
        margin-top: 60px;
    }
</style>
';

// Générer le contenu HTML du rapport
$html = '
<!DOCTYPE html>
<html>
<head>'.$css.'</head>
<body>
    <table width="100%" style="text-align: center;">
        <tr>
            <td width="48%" style="text-align: center; vertical-align: top;">
                <p style="margin: 0;">MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR<br>ET DE LA RECHERCHE SCIENTIFIQUE</p>
                <img src="../assets/images/UNIVT_CCD.jpg" alt="Logo Université" style="height: 60px; margin: 15px auto;">
                <p style="margin: 0;">UNIVERSITE FELIX HOUPHOUET BOIGNY</p>
                <p style="margin: 0;">UFR MATHEMATIQUES ET INFORMATIQUE<br>FILLIERES PROFESSIONNALISEES MIAGE-GI</p>
            </td>
            <td width="48%" style="text-align: center; vertical-align: top;">
                <p style="margin: 0;">REPUBLIQUE DE COTE D\'IVOIRE<br>Union - Discipline - Travail</p>
                <img src="../assets/images/EMB_CI.jpg" alt="Logo Côte d\'Ivoire" style="height: 60px; margin: 15px auto;">
                <img src="../assets/images/KYRIA.jpg" alt="Logo Kyria" style="height: 40px; margin: 15px auto;">
                <p style="margin: 0;">KYRIA CONSULTANCY SERVICES</p>
            </td>
        </tr>
    </table>

    <div class="preview-body">
        <p class="degree-info">
            Mémoire de fin de cycle pour l\'obtention du:<br>
            <strong>Diplôme d\'ingénieur de conception en informatique</strong><br>
            Option Méthodes Informatiques Appliquées à la Gestion des Entreprises
        </p>
        <div class="theme">'.htmlspecialchars($theme).'</div>
        <div class="author-block">
            <strong>PRESENTE PAR :</strong><br>
            <span>'.htmlspecialchars($etudiant_nom).'</span>
        </div>
    </div>

    <table width="100%" style="margin-top: 50px;">
        <tr>
            <td width="45%" style="text-align: center; border: 1px solid #000; padding: 15px;">
                <strong style="display: block; text-transform: uppercase; margin-bottom: 10px;">ENCADREUR</strong>
                <span>'.htmlspecialchars($encadreur_nom).'</span>
            </td>
            <td width="10%">&nbsp;</td>
            <td width="45%" style="text-align: center; border: 1px solid #000; padding: 15px;">
                <strong style="display: block; text-transform: uppercase; margin-bottom: 10px;">MAITRE DE STAGE</strong>
                <span>'.htmlspecialchars($maitre_stage_nom).'</span>
            </td>
        </tr>
    </table>

    <pagebreak />

    <div id="preview-content">';

// Ajouter le contenu dynamique
foreach ($contenu_dynamique as $element) {
    switch ($element["type"]) {
        case "title":
            $html .= '<h2>'.htmlspecialchars($element["valeur"]).'</h2>';
            break;
        case "subtitle":
            $html .= '<h3>'.htmlspecialchars($element["valeur"]).'</h3>';
            break;
        case "paragraph":
            $html .= '<p>'.htmlspecialchars($element["valeur"]).'</p>';
            break;
    }
}

$html .= '</div></body></html>';

try {
    // Écrire le contenu HTML dans le PDF
    $mpdf->WriteHTML($html);
    
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
    
    // Sauvegarder le PDF généré précédemment
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
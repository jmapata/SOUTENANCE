<?php
// On importe les classes de PHPMailer qui sont dans le dossier vendor
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// On inclut l'autoloader de Composer qui permet de charger automatiquement les librairies
require_once __DIR__ . '/../vendor/autoload.php';

function envoyerEmail($destinataire_email, $destinataire_nom, $sujet, $contenu_html) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURATION DU SERVEUR SMTP POUR GMAIL ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gesprojetapata@gmail.com';     // Votre adresse email Gmail
        $mail->Password   = 'kzvk buey mjgi bsdj';         // VOTRE MOT DE PASSE D'APPLICATION DE 16 CARACTÈRES
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- Expéditeur et Destinataire ---
        $mail->setFrom('gesprojetapata@gmail.com', 'Valid Master');
        $mail->addAddress($destinataire_email, $destinataire_nom);

        // --- Contenu de l'email ---
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $contenu_html;
        $mail->AltBody = strip_tags($contenu_html); // Version texte simple

        $mail->send();
        return true; // L'email a été envoyé
    } catch (Exception $e) {
        // En cas d'erreur, stocker le message pour le débogage
        // Vous pouvez décommenter la ligne ci-dessous pour voir les erreurs
        // error_log("L'email n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}");
        return false; // L'email n'a pas été envoyé
    }
}
?>
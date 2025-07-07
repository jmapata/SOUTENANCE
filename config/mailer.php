<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement depuis le fichier .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function envoyerEmail($destinataire_email, $destinataire_nom, $sujet, $contenu_html) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS']; // On utilise la variable d'environnement
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($_ENV['SMTP_USER'], 'Gestion MySoutenance');
        $mail->addAddress($destinataire_email, $destinataire_nom);

        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $contenu_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
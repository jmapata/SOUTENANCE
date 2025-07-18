<?php
// traitement/demande_reset_mdp.php
session_start();
require_once '../config/database.php';
require_once '../config/mailer.php'; // Assurez-vous que le chemin est correct

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../mot_de_passe_oublie.php');
    exit();
}

$email = $_POST['email'] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Veuillez fournir une adresse email valide.";
    header('Location: ../mot_de_passe_oublie.php');
    exit();
}

try {
    // 1. Vérifier si l'email correspond à un compte étudiant
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email_principal = ? AND id_type_utilisateur = 'TYPE_ETUD'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Générer un code sécurisé
        $code = random_int(100000, 999999); // Code à 6 chiffres
        $token_hache = password_hash($code, PASSWORD_DEFAULT);
        $date_expiration = date('Y-m-d H:i:s', time() + 60 * 5); // Expiration dans 5 minutes

        // 3. Stocker le token haché et la date d'expiration dans la BDD
        $stmt_update = $pdo->prepare("UPDATE utilisateur SET token_reset_mdp = ?, date_expiration_token_reset = ? WHERE numero_utilisateur = ?");
        $stmt_update->execute([$token_hache, $date_expiration, $user['numero_utilisateur']]);

        // 4. Envoyer l'email avec le code en clair
        $sujet = "Votre code de réinitialisation de mot de passe";
        $contenu_html = "<h1>Code de Vérification</h1><p>Bonjour,</p><p>Votre code de vérification est : <strong>{$code}</strong></p><p>Ce code expirera dans 5 minutes.</p>,
        <p>Cordialement,<br>L'equipe ValidMaster</p>";
        
        // Supposons que vous avez une fonction envoyerEmail()
        envoyerEmail($user['email_principal'], 'Étudiant', $sujet, $contenu_html);
    }

    // Pour des raisons de sécurité, on affiche toujours un message de succès,
    // même si l'email n'existe pas, pour ne pas révéler quels emails sont enregistrés.
    $_SESSION['info_message'] = "Si un compte est associé à cet email, un code de vérification a été envoyé.";
    header('Location: ../verifier_code.php?email=' . urlencode($email));
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Une erreur est survenue. Veuillez réessayer.";
    header('Location: ../mot_de_passe_oublie.php');
    exit();
}
?>
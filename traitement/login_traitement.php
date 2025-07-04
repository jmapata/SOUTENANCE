<?php
// Démarrer la session en tout début de script
session_start();

// 1. Inclure le fichier de connexion à la base de données
require_once '../config/database.php';

// 2. Vérifier que le formulaire a bien été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si non, rediriger vers la page de connexion
    header('Location: ../login.php');
    exit();
}

// 3. Récupérer les données du formulaire
$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

// Vérifier que les champs ne sont pas vides
if (empty($login) || empty($password)) {
    header('Location: ../login.php?error=missing_fields');
    exit();
}

try {
    // 4. Préparer la requête pour trouver l'utilisateur par son login
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login_utilisateur = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    // 5. VÉRIFICATION FINALE :
    // On vérifie si l'utilisateur existe ET si le mot de passe correspond au hachage "nettoyé"
    if ($user && password_verify($password, trim($user['mot_de_passe']))) {
        
        // Le mot de passe est correct !

        // 6. Régénérer l'ID de session pour la sécurité
        session_regenerate_id(true);

        // 7. Stocker les informations de l'utilisateur en session
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['numero_utilisateur'];
        $_SESSION['user_login'] = $user['login_utilisateur'];
        $_SESSION['user_group'] = $user['id_groupe_utilisateur'];
        $_SESSION['user_type'] = $user['id_type_utilisateur'];
        
        // 8. Mettre à jour la date de dernière connexion
        $updateStmt = $pdo->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE numero_utilisateur = ?");
        $updateStmt->execute([$user['numero_utilisateur']]);

        // 9. Rediriger vers le tableau de bord de l'administrateur
        header('Location: ../dashboard_admin.php');
        exit();

    } else {
        // L'utilisateur n'existe pas ou le mot de passe est incorrect
        header('Location: ../login.php?error=invalid_credentials');
        exit();
    }

} catch (PDOException $e) {
    // En cas d'erreur de base de données, on peut logger l'erreur
    // error_log("Erreur de connexion : " . $e->getMessage());
    // Et rediriger avec une erreur générique
    header('Location: ../login.php?error=dberror');
    exit();
}
?>
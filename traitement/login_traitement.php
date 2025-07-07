<?php
// Démarrer la session en tout début de script
session_start();

// 1. Inclure le fichier de connexion à la base de données
require_once '../config/database.php';

// 2. Vérifier que le formulaire a bien été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // 5. Vérifier si l'utilisateur existe ET si le mot de passe est correct
    if ($user && password_verify($password, trim($user['mot_de_passe']))) {
        
        // Régénérer l'ID de session pour la sécurité
        session_regenerate_id(true);

        // Stocker les informations de l'utilisateur en session
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['numero_utilisateur'];
        $_SESSION['user_login'] = $user['login_utilisateur'];
        $_SESSION['user_group'] = $user['id_groupe_utilisateur'];
        $_SESSION['user_type'] = $user['id_type_utilisateur'];
        
        // Mettre à jour la date de dernière connexion
        $updateStmt = $pdo->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE numero_utilisateur = ?");
        $updateStmt->execute([$user['numero_utilisateur']]);

        // ==============================================================
        // 9. LOGIQUE DE REDIRECTION BASÉE SUR LE GROUPE DE L'UTILISATEUR
        // ==============================================================
        $userGroup = $user['id_groupe_utilisateur'];
        $redirect_path = '../login.php?error=unknown_role'; // Redirection par défaut

        switch ($userGroup) {
            case 'GRP_ADMIN_SYS':
                $redirect_path = '../dashboard_admin.php';
                break;
            
            case 'GRP_ETUDIANT':
                $redirect_path = '../dashboard_etudiant.php';
                break;
            
            case 'GRP_SCOLARITE':
                $redirect_path = '../dashboard_gestion_scolarite.php';
                break;
                
            case 'GRP_CONFORMITE':
                $redirect_path = '../dashboard_conformite.php';
                break;
            
            case 'GRP_COMMISSION':
                $redirect_path = '../dashboard_commission.php';
                break;
        }
        
        header('Location: ' . $redirect_path);
        exit();
        // --- FIN DE LA LOGIQUE DE REDIRECTION ---

    } else {
        // L'utilisateur n'existe pas ou le mot de passe est incorrect
        header('Location: ../login.php?error=invalid_credentials');
        exit();
    }

} catch (PDOException $e) {
    // En cas d'erreur de base de données
    header('Location: ../login.php?error=dberror');
    exit();
}
?>
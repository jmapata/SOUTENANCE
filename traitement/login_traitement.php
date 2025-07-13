<?php
// traitement/login_traitement.php (Version améliorée)

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    header('Location: ../login.php?error=missing_fields');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login_utilisateur = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, trim($user['mot_de_passe']))) {
        
        session_regenerate_id(true);

        // Stocker les informations de base en session
        $_SESSION['loggedin'] = true;
        $_SESSION['numero_utilisateur'] = $user['numero_utilisateur']; // L'ID que vous vouliez
        $_SESSION['user_login'] = $user['login_utilisateur'];
        $_SESSION['user_group'] = $user['id_groupe_utilisateur'];
        $_SESSION['user_type'] = $user['id_type_utilisateur'];

        // ==========================================================
        // ## NOUVELLE PARTIE : RÉCUPÉRER LE NOM COMPLET ##
        // ==========================================================
        $userType = $user['id_type_utilisateur'];
        $userId = $user['numero_utilisateur'];
        $fullName = $user['login_utilisateur']; // Valeur par défaut si aucun nom n'est trouvé

        $query = '';
        $table = '';

        switch ($userType) {
            case 'TYPE_ETUD':
                $table = 'etudiant';
                break;
            case 'TYPE_ENS':
                $table = 'enseignant';
                break;
            case 'TYPE_PERS_ADMIN':
                $table = 'personnel_administratif';
                break;
        }

        if (!empty($table)) {
            $query = "SELECT nom, prenom FROM $table WHERE numero_utilisateur = ?";
            $nameStmt = $pdo->prepare($query);
            $nameStmt->execute([$userId]);
            $profile = $nameStmt->fetch();

            if ($profile) {
                $fullName = trim($profile['prenom'] . ' ' . $profile['nom']);
            }
        }
        
        // Stocker le nom complet dans la session
        $_SESSION['user_full_name'] = $fullName;
        // ==========================================================
        // ## FIN DE LA NOUVELLE PARTIE ##
        // ==========================================================

        $updateStmt = $pdo->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE numero_utilisateur = ?");
        $updateStmt->execute([$userId]);

        // Logique de redirection (inchangée)
        $userGroup = $user['id_groupe_utilisateur'];
        $redirect_path = '../login.php?error=unknown_role';

        switch ($userGroup) {
            case 'GRP_ADMIN_SYS':    $redirect_path = '../dashboard_admin.php'; break;
            case 'GRP_ETUDIANT':     $redirect_path = '../dashboard_etudiant.php'; break;
            case 'GRP_SCOLARITE':    $redirect_path = '../dashboard_gestion_scolarite.php'; break;
            case 'GRP_CONFORMITE':   $redirect_path = '../dashboard_conformite.php'; break;
            case 'GRP_COMMISSION':   $redirect_path = '../dashboard_commission.php'; break;
        }
        
        header('Location: ' . $redirect_path);
        exit();

    } else {
        header('Location: ../login.php?error=invalid_credentials');
        exit();
    }

} catch (PDOException $e) {
    header('Location: ../login.php?error=dberror');
    exit();
}
?>
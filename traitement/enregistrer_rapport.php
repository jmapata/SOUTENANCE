<?php
// actions/enregistrer_rapport.php

session_start();
require_once '../config/database.php'; // Inclut la connexion PDO ($pdo)

// --- SÉCURITÉ : Vérifier si l'utilisateur est un étudiant connecté ---
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ETUDIANT') {
    // Rediriger vers la page de connexion si non autorisé
    header('Location: ../login.php');
    exit();
}

// --- VÉRIFICATION : S'assurer que le formulaire a été soumis ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si la page est accédée directement, rediriger
    header('Location: ../dashboard_etudiant.php');
    exit();
}


// --- RÉCUPÉRATION DES DONNÉES DU FORMULAIRE ---
$titre = trim($_POST['rapport_titre'] ?? '');
$contenu = trim($_POST['rapport_contenu'] ?? '');
$action = $_POST['action'] ?? '';


// --- VALIDATION SIMPLE ---
if (empty($titre) || empty($contenu) || ($action !== 'sauvegarder_brouillon' && $action !== 'soumettre_rapport')) {
    $_SESSION['error_message'] = "Veuillez remplir tous les champs.";
    header('Location: ../dashboard_etudiant.php?page=rapport_redaction_libre');
    exit();
}


// --- LOGIQUE DE TRAITEMENT ---

// 1. Déterminer le statut du rapport en fonction du bouton cliqué
// Note: Ces IDs doivent correspondre à ceux dans votre table `statut_rapport_ref`
$id_statut_rapport = ($action === 'sauvegarder_brouillon') ? 'RAP_BROUILLON' : 'RAP_SOUMIS';

try {
    // 2. Commencer une transaction pour assurer l'intégrité des données
    $pdo->beginTransaction();

    // 3. Récupérer le numero_carte_etudiant à partir du numero_utilisateur stocké en session
    $stmt = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
    $stmt->execute([$_SESSION['numero_utilisateur']]);
    $etudiant = $stmt->fetch();

    if (!$etudiant) {
        throw new Exception("Impossible de trouver les informations de l'étudiant.");
    }
    $numero_carte_etudiant = $etudiant['numero_carte_etudiant'];

    // 4. Générer un ID unique pour le rapport (version simplifiée)
    // Idéalement, utiliser le service IdentifiantGenerator discuté
    $rapport_id = 'RAP-' . date('Y') . '-' . substr(uniqid(), -4);


    // 5. Insérer les métadonnées dans la table `rapport_etudiant`
    $sql_rapport = "INSERT INTO rapport_etudiant 
                        (id_rapport_etudiant, libelle_rapport_etudiant, theme, numero_carte_etudiant, id_statut_rapport, date_soumission, date_derniere_modif) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $date_soumission = ($id_statut_rapport === 'RAP_SOUMIS') ? date('Y-m-d H:i:s') : null;

    $stmt_rapport = $pdo->prepare($sql_rapport);
    $stmt_rapport->execute([
        $rapport_id,
        $titre,
        $titre, // On utilise le titre comme thème pour simplifier
        $numero_carte_etudiant,
        $id_statut_rapport,
        $date_soumission,
        date('Y-m-d H:i:s')
    ]);


    // 6. Insérer le contenu dans la table `section_rapport`
    // Pour un rapport libre, on stocke tout dans une seule section "Corps du rapport"
    $sql_section = "INSERT INTO section_rapport (id_rapport_etudiant, titre_section, contenu_section, ordre) VALUES (?, ?, ?, ?)";
    $stmt_section = $pdo->prepare($sql_section);
    $stmt_section->execute([
        $rapport_id,
        'Corps du rapport',
        $contenu,
        1 
    ]);

    
    // ## AUDIT DE L'ACTION ##
    $id_action_audit = ($id_statut_rapport === 'RAP_BROUILLON') ? 'ETUDIANT_CREATION_BROUILLON' : 'ETUDIANT_SOUMISSION_RAPPORT';
    $audit_id = 'AUDIT-' . strtoupper(uniqid());
    $stmt_audit = $pdo->prepare(
        "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, id_entite_concernee, type_entite_concernee) 
         VALUES (?, ?, ?, NOW(), ?, 'rapport_etudiant')"
    );
    $stmt_audit->execute([$audit_id, $_SESSION['numero_utilisateur'], $id_action_audit, $rapport_id]);
    

    // 7. Valider la transaction
    $pdo->commit();

    // 8. Préparer un message de succès et rediriger
    $_SESSION['success_message'] = "Votre rapport a été enregistré avec succès.";
    header('Location: ../dashboard_etudiant.php?page=rapport_suivi');
    exit();

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Préparer un message d'erreur et rediriger
    $_SESSION['error_message'] = "Une erreur est survenue lors de l'enregistrement de votre rapport : " . $e->getMessage();
    header('Location: ../dashboard_etudiant.php?page=rapport_redaction_libre');
    exit();
}
?>
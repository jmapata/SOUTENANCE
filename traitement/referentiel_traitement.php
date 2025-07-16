<?php
session_start();
require_once '../config/database.php';

// Sécurité
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_ADMIN_SYS') {
    header('Location: ../login.php');
    exit();
}

// --- Configuration des tables autorisées ---
$referentiel_map = [
    // Référentiels Académiques & Structurels
    'annee_academique' => ['pk' => 'id_annee_academique', 'label' => 'libelle_annee_academique', 'titre' => 'Années Académiques'],
    'niveau_etude' => ['pk' => 'id_niveau_etude', 'label' => 'libelle_niveau_etude', 'titre' => 'Niveaux d\'Étude'],
    'specialite' => ['pk' => 'id_specialite', 'label' => 'libelle_specialite', 'titre' => 'Spécialités de Formation'],
    'ue' => ['pk' => 'id_ue', 'label' => 'libelle_ue', 'titre' => 'Unités d\'Enseignement (UE)'],
    'ecue' => ['pk' => 'id_ecue', 'label' => 'libelle_ecue', 'titre' => 'Matières (ECUE)'],
    'entreprise' => ['pk' => 'id_entreprise', 'label' => 'libelle_entreprise', 'titre' => 'Entreprises Partenaires'],
    
    // Référentiels du Personnel
    'grade' => ['pk' => 'id_grade', 'label' => 'libelle_grade', 'titre' => 'Grades des Enseignants'],
    'fonction' => ['pk' => 'id_fonction', 'label' => 'libelle_fonction', 'titre' => 'Fonctions Administratives'],
    
    // Référentiels des Statuts & Décisions (Workflow)
    'statut_rapport_ref' => ['pk' => 'id_statut_rapport', 'label' => 'libelle_statut_rapport', 'titre' => 'Statuts de Rapport'],
    'statut_pv_ref' => ['pk' => 'id_statut_pv', 'label' => 'libelle_statut_pv', 'titre' => 'Statuts de Procès-Verbal'],
    'statut_conformite_ref' => ['pk' => 'id_statut_conformite', 'label' => 'libelle_statut_conformite', 'titre' => 'Statuts de Conformité'],
    'statut_jury' => ['pk' => 'id_statut_jury', 'label' => 'libelle_statut_jury', 'titre' => 'Rôles dans un Jury'],
    'statut_paiement_ref' => ['pk' => 'id_statut_paiement', 'label' => 'libelle_statut_paiement', 'titre' => 'Statuts de Paiement'],
    'statut_penalite_ref' => ['pk' => 'id_statut_penalite', 'label' => 'libelle_statut_penalite', 'titre' => 'Statuts de Pénalité'],
    'statut_reclamation_ref' => ['pk' => 'id_statut_reclamation', 'label' => 'libelle_statut_reclamation', 'titre' => 'Statuts de Réclamation'],
    'decision_vote_ref' => ['pk' => 'id_decision_vote', 'label' => 'libelle_decision_vote', 'titre' => 'Décisions de Vote (Commission)'],
    'decision_validation_pv_ref' => ['pk' => 'id_decision_validation_pv', 'label' => 'libelle_decision_validation_pv', 'titre' => 'Décisions de Validation de PV'],
    'decision_passage_ref' => ['pk' => 'id_decision_passage', 'label' => 'libelle_decision_passage', 'titre' => 'Décisions de Passage'],
    
    // Référentiels Système & Sécurité
    'type_utilisateur' => ['pk' => 'id_type_utilisateur', 'label' => 'libelle_type_utilisateur', 'titre' => 'Types d\'Utilisateur'],
    'groupe_utilisateur' => ['pk' => 'id_groupe_utilisateur', 'label' => 'libelle_groupe_utilisateur', 'titre' => 'Groupes d\'Utilisateurs (Rôles)'],
    'niveau_acces_donne' => ['pk' => 'id_niveau_acces_donne', 'label' => 'libelle_niveau_acces_donne', 'titre' => 'Niveaux d\'Accès aux Données'],
    'action' => ['pk' => 'id_action', 'label' => 'libelle_action', 'titre' => 'Actions pour l\'Audit'],
    'traitement' => ['pk' => 'id_traitement', 'label' => 'libelle_traitement', 'titre' => 'Permissions (Traitements)'],
    'type_document_ref' => ['pk' => 'id_type_document', 'label' => 'libelle_type_document', 'titre' => 'Types de Document'],
];

// Récupération des données
$action = $_POST['action'] ?? '';
$table_name = $_POST['table'] ?? '';
$id = $_POST['id'] ?? null;
$libelle = $_POST['libelle'] ?? null;

// Vérification de sécurité : la table est-elle autorisée ?
if (!array_key_exists($table_name, $referentiel_map)) {
    $_SESSION['error_message'] = "Opération non autorisée sur ce référentiel.";
    header('Location: ../dashboard_admin.php?page=referentiels');
    exit();
}

$config = $referentiel_map[$table_name];
$pk_column = $config['pk'];
$label_column = $config['label'];

try {
    switch ($action) {
        case 'ajouter':
            if ($id && $libelle) {
                $stmt = $pdo->prepare("INSERT INTO `$table_name` (`$pk_column`, `$label_column`) VALUES (?, ?)");
                $stmt->execute([$id, $libelle]);
                $_SESSION['success_message'] = "L'entrée a été ajoutée avec succès.";
            }
            break;

        case 'supprimer':
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM `$table_name` WHERE `$pk_column` = ?");
                $stmt->execute([$id]);
                $_SESSION['success_message'] = "L'entrée a été supprimée avec succès.";
            }
            break;
    }
} catch (PDOException $e) {
    // Gérer les erreurs de base de données (ex: suppression impossible à cause de clés étrangères)
    $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
}

// Redirection vers la page de gestion du référentiel concerné
header('Location: ../dashboard_admin.php?page=gerer_referentiel&table=' . $table_name);
exit();
?>
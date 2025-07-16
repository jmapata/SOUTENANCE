<?php
require_once(__DIR__ . '/../config/database.php');

// --- Configuration des tables de référentiels ---
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

$table_name = $_GET['table'] ?? '';
if (!array_key_exists($table_name, $referentiel_map)) {
    die("<div class='alert alert-danger'>Référentiel non valide.</div>");
}

$config = $referentiel_map[$table_name];
$pk_column = $config['pk'];
$label_column = $config['label'];
$titre_page = $config['titre'];

// Récupérer toutes les entrées du référentiel sélectionné
$items = $pdo->query("SELECT * FROM `$table_name` ORDER BY `$label_column` ASC")->fetchAll();
?>

<div class="page-header">
    <h1>Gestion : <?php echo htmlspecialchars($titre_page); ?></h1>
    <a href="dashboard_admin.php?page=referentiels" class="btn btn-secondary">&larr; Retour à la liste</a>
</div>

<div class="grid-layout">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Ajouter une nouvelle entrée</h2></div>
        <div class="card-content">
            <form action="traitement/referentiel_traitement.php" method="POST" class="form-inline">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="table" value="<?php echo $table_name; ?>">
                <input type="text" name="id" placeholder="ID (ex: M2_MIAGE)" required>
                <input type="text" name="libelle" placeholder="Libellé (ex: Master 2 MIAGE)" required>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon"></i> Ajouter</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h2 class="card-title">Entrées existantes</h2></div>
        <div class="card-content">
            <table class="table-spaced">
                <thead><tr><th>ID</th><th>Libellé</th><th style="width: 100px;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item[$pk_column]); ?></td>
                        <td><?php echo htmlspecialchars($item[$label_column]); ?></td>
                        <td class="table-actions">
                            <form action="traitement/referentiel_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer cette entrée ?');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="table" value="<?php echo $table_name; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($item[$pk_column]); ?>">
                                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash icon"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
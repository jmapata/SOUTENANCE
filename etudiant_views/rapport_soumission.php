<?php
// etudiant_views/rapport_soumission.php

// La variable $pdo est disponible car elle est incluse depuis dashboard_etudiant.php

// ## LOGIQUE POUR L'ALERTE VISUELLE ##
// On vérifie si l'étudiant a au moins un rapport en attente de correction
$has_report_to_correct = false;
// On s'assure que la variable de session pour l'utilisateur existe avant de faire la requête
if (isset($_SESSION['numero_utilisateur'])) {
    $stmt_check = $pdo->prepare("
        SELECT 1 FROM rapport_etudiant 
        WHERE numero_carte_etudiant = (SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?) 
        AND id_statut_rapport = 'RAP_NON_CONF' 
        LIMIT 1
    ");
    $stmt_check->execute([$_SESSION['numero_utilisateur']]);
    if ($stmt_check->fetch()) {
        $has_report_to_correct = true;
    }
}
?>

<div class="page-header">
    <h2><i class="fa-solid fa-file-pen"></i> Gestion de Mon Rapport</h2>
    <p class="page-description">Choisissez l'action que vous souhaitez effectuer pour votre rapport de stage.</p>
</div>

<div class="options-grid">

    <div class="option-card">
        <div class="card-header">
            <div class="card-icon">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <h3 class="card-title">Rédiger Rapport Libre</h3>
        </div>
        <div class="card-content">
            <p>Créez votre rapport de stage en partant de zéro avec une mise en page libre et personnalisée.</p>
        </div>
        <div class="card-footer">
            <a href="dashboard_etudiant.php?page=rapport_redaction_libre" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Commencer
            </a>
        </div>
    </div>

    <div class="option-card">
        <div class="card-header">
            <div class="card-icon">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <h3 class="card-title">Rédiger depuis un Modèle</h3>
        </div>
        <div class="card-content">
            <p>Utilisez un modèle pré-structuré avec des sections et formats prédéfinis pour faciliter la rédaction.</p>
        </div>
        <div class="card-footer">
            <a href="dashboard_etudiant.php?page=rapport_redaction_modele" class="btn btn-primary">
                <i class="fa-solid fa-magic-wand-sparkles"></i> Choisir un modèle
            </a>
        </div>
    </div>

    <div class="option-card <?php if ($has_report_to_correct) echo 'alert-danger'; ?>">
        <div class="card-header">
            <div class="card-icon">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <h3 class="card-title">Modifier Rapport en Cours</h3>
        </div>
        <div class="card-content">
            <p>Reprenez la rédaction de votre brouillon ou apportez des corrections demandées.</p>
            <?php if ($has_report_to_correct): ?>
                <p class="alert-text"><i class="fa-solid fa-triangle-exclamation"></i> Action requise : un de vos rapports a été retourné pour correction.</p>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="dashboard_etudiant.php?page=rapport_modification" class="btn btn-warning">
                <i class="fa-solid fa-edit"></i> Modifier / Corriger
            </a>
        </div>
    </div>

    <div class="option-card">
        <div class="card-header">
            <div class="card-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h3 class="card-title">Historique des Rapports</h3>
        </div>
        <div class="card-content">
            <p>Consultez l'historique de vos rapports, versions précédentes et suivez le statut de validation.</p>
        </div>
        <div class="card-footer">
            <a href="dashboard_etudiant.php?page=rapport_historique" class="btn btn-info">
                <i class="fa-solid fa-history"></i> Consulter
            </a>
        </div>
    </div>
</div>

<style>
    .option-card.alert-danger {
        border-top-color: #dc3545;
        box-shadow: 0 5px 20px rgba(220, 53, 69, 0.2);
    }
    .option-card.alert-danger .card-icon {
        color: #dc3545;
    }
    .alert-text {
        font-weight: bold;
        color: #dc3545;
        margin-top: 15px;
        font-size: 0.9rem;
    }
</style>
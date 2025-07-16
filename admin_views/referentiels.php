<?php
// admin_views/referentiels.php
// La connexion $pdo est déjà disponible depuis le fichier dashboard_admin.php

// On définit la liste de tous les référentiels à gérer
// Chaque élément contient l'ID (le nom de la table), le titre à afficher, et une icône Font Awesome.
$referentiels = [
    ['id' => 'annee_academique', 'titre' => 'Années Académiques', 'icon' => 'fa-calendar-days'],
    ['id' => 'niveau_etude', 'titre' => 'Niveaux d\'Étude', 'icon' => 'fa-layer-group'],
    ['id' => 'specialite', 'titre' => 'Spécialités', 'icon' => 'fa-star'],
    ['id' => 'ue', 'titre' => 'Unités d\'Enseignement', 'icon' => 'fa-book-open'],
    ['id' => 'ecue', 'titre' => 'Matières (ECUE)', 'icon' => 'fa-bookmark'],
    ['id' => 'grade', 'titre' => 'Grades Enseignants', 'icon' => 'fa-medal'],
    ['id' => 'fonction', 'titre' => 'Fonctions', 'icon' => 'fa-briefcase'],
    ['id' => 'entreprise', 'titre' => 'Entreprises', 'icon' => 'fa-building'],
    ['id' => 'statut_rapport_ref', 'titre' => 'Statuts de Rapport', 'icon' => 'fa-file-circle-check'],
    ['id' => 'statut_pv_ref', 'titre' => 'Statuts de PV', 'icon' => 'fa-file-signature'],
    ['id' => 'decision_vote_ref', 'titre' => 'Décisions de Vote', 'icon' => 'fa-check-to-slot'],
    ['id' => 'type_document_ref', 'titre' => 'Types de Document', 'icon' => 'fa-file-pdf'],
];

?>

<style>
    .page-header {
        margin-bottom: 24px;
    }
    .page-header h1 {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--text-dark);
    }
    .page-subtitle {
        color: var(--text-light);
        margin-top: 4px;
    }
    .referentiels-grid {
        display: grid;
        /* Crée des colonnes qui s'adaptent à la taille de l'écran */
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    .referentiel-card {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 24px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 20px;
        text-decoration: none;
        color: var(--text-dark);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .referentiel-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-color);
    }
    .referentiel-card .card-icon {
        font-size: 1.8rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background-color: var(--bg-light);
        color: var(--primary-color);
    }
    .referentiel-card .card-info .card-title {
        font-size: 1rem;
        font-weight: 600;
    }
</style>

<div class="page-header">
    <h1>Gestion des Référentiels</h1>
    <p class="page-subtitle">Cliquez sur un cadre pour gérer les données de référence de l'application.</p>
</div>

<div class="referentiels-grid">
    <?php foreach ($referentiels as $ref): ?>
        <a href="dashboard_admin.php?page=gerer_referentiel&table=<?php echo $ref['id']; ?>" class="referentiel-card">
            <div class="card-icon">
                <i class="fa-solid <?php echo $ref['icon']; ?>"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title"><?php echo $ref['titre']; ?></h3>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<div class="page-header">
    <h1>Paramètres Généraux</h1>
</div>
<p class="page-subtitle">Cliquez sur un cadre pour gérer les données de référence de l'application.</p>

<div class="referentiels-grid">
    <?php
    // La liste de tous vos paramètres généraux
    $referentiels = [
        ['id' => 'action', 'titre' => 'Actions (Audit)', 'icon' => 'fa-history'],
        ['id' => 'annee_academique', 'titre' => 'Années Académiques', 'icon' => 'fa-calendar-days'],
        ['id' => 'ecue', 'titre' => 'ECUE', 'icon' => 'fa-bookmark'],
        ['id' => 'entreprise', 'titre' => 'Entreprises', 'icon' => 'fa-building'],
        ['id' => 'fonction', 'titre' => 'Fonctions', 'icon' => 'fa-briefcase'],
        ['id' => 'grade', 'titre' => 'Grades', 'icon' => 'fa-medal'],
        ['id' => 'groupe_utilisateur', 'titre' => 'Groupes Utilisateur', 'icon' => 'fa-users'],
        ['id' => 'niv_acces_donnees', 'titre' => 'Niveaux d\'Accès', 'icon' => 'fa-lock'],
        ['id' => 'niveau_etude', 'titre' => 'Niveaux d\'étude', 'icon' => 'fa-layer-group'],
        ['id' => 'specialite', 'titre' => 'Spécialités', 'icon' => 'fa-star'],
        ['id' => 'statut_jury', 'titre' => 'Statuts Jury', 'icon' => 'fa-gavel'],
        ['id' => 'traitement', 'titre' => 'Traitements (Permissions)', 'icon' => 'fa-user-shield'],
        ['id' => 'type_utilisateur', 'titre' => 'Types Utilisateur', 'icon' => 'fa-user-tag'],
        ['id' => 'ue', 'titre' => 'UE', 'icon' => 'fa-book-open'],
        // Note : J'ai omis 'niveau_approbation' et 'utilisateur' car leur gestion est plus complexe qu'un simple CRUD.
    ];

    foreach ($referentiels as $ref): ?>
        <div class="referentiel-card" data-ref-id="<?php echo $ref['id']; ?>" data-ref-titre="<?php echo $ref['titre']; ?>">
            <div class="card-icon"><i class="fa-solid <?php echo $ref['icon']; ?>"></i></div>
            <div class="card-info">
                <h3 class="card-title"><?php echo $ref['titre']; ?></h3>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="referentiel-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Gestion</h2>
            <button id="close-modal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content" id="modal-content">
            <p>Chargement...</p>
        </div>
    </div>
</div>
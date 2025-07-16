<?php
// admin_views/parametres.php
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
<div class="page-header">
    <h1>Paramètres Généraux</h1>
    <p class="page-subtitle">Cliquez sur un cadre pour gérer les données de référence de l'application.</p>
</div>

<div class="referentiels-grid">
    <?php foreach ($referentiels as $ref): ?>
        <div class="referentiel-card" data-table-name="<?php echo $ref['id']; ?>" data-table-title="<?php echo htmlspecialchars($ref['titre']); ?>">
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

<style>
    /* ... (votre CSS existant pour les cartes) ... */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1050; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-container { background: #fff; border-radius: 10px; width: 90%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; }
    .modal-header { padding: 15px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .modal-title { margin: 0; font-size: 1.2rem; }
    .close-modal { background: none; border: none; font-size: 2rem; cursor: pointer; }
    .modal-content { padding: 25px; overflow-y: auto; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('referentiel-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');
    const closeModalBtn = document.getElementById('close-modal');

    document.querySelectorAll('.referentiel-card').forEach(card => {
        card.addEventListener('click', function() {
            const tableName = this.dataset.tableName;
            const tableTitle = this.dataset.tableTitle;

            modalTitle.textContent = 'Gestion : ' + tableTitle;
            modalContent.innerHTML = '<p>Chargement des données...</p>';
            modal.classList.add('active');

            // Appel AJAX pour charger le contenu de la modale
            fetch(`ajax/charger_referentiel.php?table=${tableName}`)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    modalContent.innerHTML = '<p>Erreur de chargement des données.</p>';
                    console.error('Error:', error);
                });
        });
    });

    closeModalBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });
});
</script>
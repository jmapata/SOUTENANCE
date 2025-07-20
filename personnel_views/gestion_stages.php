<?php
require_once 'config/database.php';

$search_query = $_GET['search_etudiant'] ?? '';
$where_clause = " WHERE i.id_statut_paiement IN ('PAY_SOLDE', 'PAY_PARTIEL')"; // Inclure les paiements partiels
$params = [];

if (!empty($search_query)) {
    $where_clause .= " AND (e.nom LIKE :search OR e.prenom LIKE :search OR e.numero_carte_etudiant LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$stmt_etudiants = $pdo->prepare("
    SELECT 
        e.numero_carte_etudiant, 
        e.nom, 
        e.prenom,
        (SELECT COUNT(*) FROM faire_stage fs WHERE fs.numero_carte_etudiant = e.numero_carte_etudiant) as stage_enregistre
    FROM etudiant e
    JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
    " . $where_clause . "
    ORDER BY e.nom, e.prenom
");
$stmt_etudiants->execute($params);
$etudiants = $stmt_etudiants->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="page-header">
    <h2><i class="fa-solid fa-graduation-cap"></i> Gestion des Stages Étudiants</h2>
    <p>Consultez les informations de stage des étudiants ayant soldé leur scolarité.</p>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Liste des Étudiants</h2></div>
    <div class="card-content">
        <form method="GET" action="dashboard_scolarite.php" class="filter-form">
            <input type="hidden" name="page" value="gestion_stages">
            <div class="form-row">
                <input type="text" name="search_etudiant" placeholder="Rechercher par nom, prénom, N° carte..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Rechercher</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard_scolarite.php?page=gestion_stages';"><i class="fa-solid fa-eraser"></i> Réinitialiser</button>
                <button type="button" class="btn btn-info" onclick="window.print();"><i class="fa-solid fa-print"></i> Imprimer</button>
            </div>
        </form>

        <div class="table-container">
            <table class="table-spaced">
                <thead>
                    <tr>
                        <th>N° Carte</th>
                        <th>Nom & Prénom</th>
                        <th>Stage Enregistré</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($etudiants)): ?>
                        <tr><td colspan="4" style="text-align: center;">Aucun étudiant trouvé avec scolarité soldée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?></td>
                                <td>
                                    <?php if ($etudiant['stage_enregistre'] > 0): ?>
                                        <span class="status-badge status-success"><i class="fa-solid fa-check"></i> Oui</span>
                                    <?php else: ?>
                                        <span class="status-badge status-warning"><i class="fa-solid fa-exclamation-triangle"></i> Non</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm view-details-btn" 
                                            data-etudiant-id="<?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?>" 
                                            data-etudiant-nom="<?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>">
                                        <i class="fa-solid fa-eye"></i> Voir Détails
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal pour afficher les détails du stage -->
<div id="stageDetailsModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Détails du Stage de <span id="modalEtudiantNom"></span></h2>
            <button id="closeStageDetailsModal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content">
            <div id="stageDetailsContent">
                <!-- Les détails du stage seront chargés ici via AJAX -->
                <p class="text-center text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des détails...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    const stageDetailsModal = document.getElementById('stageDetailsModal');
    const closeStageDetailsModal = document.getElementById('closeStageDetailsModal');
    const modalEtudiantNom = document.getElementById('modalEtudiantNom');
    const stageDetailsContent = document.getElementById('stageDetailsContent');

    // Helper pour ouvrir/fermer les modales (si non défini globalement)
    function openModal(modalElement) {
        modalElement.style.display = 'flex';
        setTimeout(() => modalElement.classList.add('active'), 10);
    }

    function closeModal(modalElement) {
        modalElement.classList.remove('active');
        setTimeout(() => modalElement.style.display = 'none', 300);
    }

    // Fermeture du modal via le bouton ou clic en dehors
    if (closeStageDetailsModal) {
        closeStageDetailsModal.addEventListener('click', () => closeModal(stageDetailsModal));
    }
    if (stageDetailsModal) {
        stageDetailsModal.addEventListener('click', (e) => {
            if (e.target === stageDetailsModal) {
                closeModal(stageDetailsModal);
            }
        });
    }

    // Gestion du bouton "Voir Détails"
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const etudiantId = this.dataset.etudiantId;
            const etudiantNom = this.dataset.etudiantNom;

            modalEtudiantNom.textContent = etudiantNom;
            stageDetailsContent.innerHTML = '<p class="text-center text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des détails...</p>';
            openModal(stageDetailsModal);

            // Appel AJAX pour récupérer les détails du stage
            fetch(`traitement/get_stage_details.php?etudiant_id=${etudiantId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stage = data.stage_info;
                        const proofDoc = data.proof_document;

                        let htmlContent = '';
                        if (stage) {
                            htmlContent += `
                                <h4 class="section-subtitle">Informations de Stage</h4>
                                <table class="info-table compact-table">
                                    <tr><th>Entreprise :</th><td>${htmlspecialchars(stage.libelle_entreprise)}</td></tr>
                                    <tr><th>Période :</th><td>Du ${formatDate(stage.date_debut_stage)} au ${stage.date_fin_stage ? formatDate(stage.date_fin_stage) : 'Non définie'}</td></tr>
                                    <tr><th>Sujet :</th><td>${htmlspecialchars(stage.sujet_stage)}</td></tr>
                                    <tr><th>Tuteur :</th><td>${htmlspecialchars(stage.nom_tuteur_entreprise)}</td></tr>
                                </table>
                            `;
                        } else {
                            htmlContent += `<p class="alert alert-info">Aucune information de stage enregistrée pour cet étudiant.</p>`;
                        }

                        htmlContent += `<h4 class="section-subtitle mt-4">Document de Preuve</h4>`;
                        if (proofDoc) {
                            htmlContent += `
                                <table class="info-table compact-table">
                                    <tr><th>Type :</th><td>${htmlspecialchars(proofDoc.libelle_type_document)}</td></tr>
                                    <tr><th>Fichier :</th><td>${htmlspecialchars(getFileName(proofDoc.chemin_fichier))}</td></tr>
                                    <tr><th>Date d'Upload :</th><td>${formatDateTime(proofDoc.date_generation)}</td></tr>
                                    <tr><th>Action :</th><td>
                                        <a href="${htmlspecialchars(proofDoc.chemin_fichier)}" target="_blank" class="btn btn-info btn-sm">
                                            <i class="fa-solid fa-eye"></i> Voir le document
                                        </a>
                                    </td></tr>
                                </table>
                            `;
                        } else {
                            htmlContent += `<p class="alert alert-warning">Aucun document de preuve de stage téléversé pour cet étudiant.</p>`;
                        }
                        
                        stageDetailsContent.innerHTML = htmlContent;

                    } else {
                        stageDetailsContent.innerHTML = `<p class="alert alert-danger">${htmlspecialchars(data.message)}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur Fetch lors du chargement des détails du stage:', error);
                    stageDetailsContent.innerHTML = `<p class="alert alert-danger">Erreur de communication lors du chargement des détails. Veuillez réessayer.</p>`;
                });
        });
    });

    // Fonctions utilitaires pour le formatage et l'échappement HTML
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str;
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
        return new Date(dateString).toLocaleDateString('fr-FR', options);
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleDateString('fr-FR', options);
    }

    function getFileName(filePath) {
        if (!filePath) return 'N/A';
        return filePath.split('/').pop().split('\\').pop(); // Gère les / et \
    }
});
</script>

<style>
/* Variables CSS (reprises de admin_style.css et mon_stage.php pour cohérence) */
/* Variables CSS */
:root {
    --primary-color: #0d47a1;
    --primary-light: rgba(13, 71, 161, 0.1);
    --primary-lighter: rgba(13, 71, 161, 0.05);
    --primary-dark: #08316f;
    
    --bg-white: #ffffff;
    --bg-light: #fafafa;
    --bg-gray: #f5f5f5;
    
    --text-primary: #212121;
    --text-secondary: #666666;
    --text-muted: #999999;
    --text-white: #ffffff;
    
    --border-light: #e0e0e0;
    --border-medium: #bdbdbd;
    
    --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-medium: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-strong: 0 8px 32px rgba(0, 0, 0, 0.16);
    
    --radius-sm: 6px;
    --radius-md: 12px;
    --radius-lg: 16px;
    
    --transition: all 0.3s ease;
    
    --success-color: #4caf50;
    --warning-color: #ff9800;
    --danger-color: #f44336;
    --info-color: #2196f3;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--bg-light);
    color: var(--text-primary);
    line-height: 1.6;
    margin: 0;
    padding: 0;
    font-size: 14px;
}

/* Header de la page */
.page-header {
    background: var(--primary-color);
    color: var(--text-white);
    padding: 2.5rem 2rem;
    border-radius: var(--radius-md);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-medium);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.page-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.page-header p {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
    font-weight: 300;
}

/* Cards */
.card {
    background-color: var(--bg-white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-light);
    border: 1px solid var(--border-light);
    overflow: hidden;
    margin-bottom: 2rem;
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--shadow-medium);
}

.card-header {
    background-color: var(--bg-gray);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-light);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.card-content {
    padding: 2rem;
}

/* Formulaire de recherche */
.filter-form .form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-gray);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-light);
}

.filter-form input[type="text"] {
    flex: 1;
    min-width: 250px;
    padding: 0.875rem 1rem;
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-sm);
    font-size: 0.95rem;
    color: var(--text-primary);
    background-color: var(--bg-white);
    transition: var(--transition);
}

.filter-form input[type="text"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.filter-form input[type="text"]::placeholder {
    color: var(--text-muted);
}

/* Boutons */
.btn {
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--text-white);
    box-shadow: var(--shadow-light);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: var(--text-secondary);
    color: var(--text-white);
    box-shadow: var(--shadow-light);
}

.btn-secondary:hover {
    background-color: #555555;
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.btn-info {
    background-color: var(--info-color);
    color: var(--text-white);
    box-shadow: var(--shadow-light);
}

.btn-info:hover {
    background-color: #1976d2;
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.btn-sm {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    gap: 0.375rem;
}

/* Tableau */
.table-container {
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-light);
    background: var(--bg-white);
}

.table-spaced {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--bg-white);
}

.table-spaced th {
    background-color: var(--primary-color);
    color: var(--text-white);
    padding: 1.25rem 1.5rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
}

.table-spaced th::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-dark);
}

.table-spaced td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-light);
    font-size: 0.95rem;
    color: var(--text-secondary);
    vertical-align: middle;
}

.table-spaced tr:last-child td {
    border-bottom: none;
}

.table-spaced tbody tr {
    transition: var(--transition);
}

.table-spaced tbody tr:hover {
    background-color: var(--primary-lighter);
}

/* Badges de statut */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: var(--shadow-light);
}

.status-success {
    background-color: #e8f5e8;
    color: var(--success-color);
    border: 1px solid #c8e6c9;
}

.status-warning {
    background-color: #fff3e0;
    color: var(--warning-color);
    border: 1px solid #ffcc02;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
}

.modal-container {
    background: var(--bg-white);
    border-radius: var(--radius-lg);
    max-width: 750px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: var(--shadow-strong);
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-overlay.active .modal-container {
    transform: scale(1);
}

.modal-header {
    background: var(--primary-color);
    color: var(--text-white);
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--primary-dark);
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.close-modal {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-white);
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-modal:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.modal-content {
    padding: 2rem;
}

/* Styles pour le contenu du modal */
.section-subtitle {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary-light);
    position: relative;
}

.section-subtitle:first-child {
    margin-top: 0;
}

.section-subtitle::before {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 50px;
    height: 2px;
    background: var(--primary-color);
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
    background: var(--bg-white);
    border-radius: var(--radius-sm);
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

.compact-table th {
    background-color: var(--bg-gray);
    color: var(--text-primary);
    padding: 1rem;
    font-weight: 600;
    width: 35%;
    border-bottom: 1px solid var(--border-light);
}

.compact-table td {
    padding: 1rem;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border-light);
}

.compact-table tr:last-child th,
.compact-table tr:last-child td {
    border-bottom: none;
}

/* Alertes */
.alert {
    padding: 1.25rem 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    font-size: 0.95rem;
    box-shadow: var(--shadow-light);
}

.alert-success {
    background-color: #e8f5e8;
    border-color: #c8e6c9;
    color: var(--success-color);
}

.alert-danger {
    background-color: #ffebee;
    border-color: #ffcdd2;
    color: var(--danger-color);
}

.alert-info {
    background-color: #e3f2fd;
    border-color: #bbdefb;
    color: var(--info-color);
}

.alert-warning {
    background-color: #fff3e0;
    border-color: #ffcc02;
    color: var(--warning-color);
}

/* États de chargement */
.text-center {
    text-align: center;
}

.text-muted {
    color: var(--text-muted);
}

.fa-spinner.fa-spin {
    color: var(--primary-color);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 2rem 1.5rem;
    }
    
    .page-header h2 {
        font-size: 1.75rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .card-content {
        padding: 1.5rem;
    }
    
    .filter-form .form-row {
        flex-direction: column;
        align-items: stretch;
        padding: 1.25rem;
    }
    
    .filter-form input[type="text"] {
        min-width: auto;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table-spaced {
        min-width: 600px;
    }
    
    .table-spaced th,
    .table-spaced td {
        padding: 1rem;
        font-size: 0.875rem;
    }
    
    .modal-container {
        width: 95%;
        margin: 1rem;
        max-height: 90vh;
    }
    
    .modal-header,
    .modal-content {
        padding: 1.5rem;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
    
    .compact-table th,
    .compact-table td {
        padding: 0.875rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 480px) {
    .page-header {
        padding: 1.5rem 1rem;
    }
    
    .page-header h2 {
        font-size: 1.5rem;
    }
    
    .card-content {
        padding: 1rem;
    }
    
    .filter-form .form-row {
        padding: 1rem;
    }
    
    .modal-header,
    .modal-content {
        padding: 1rem;
    }
}
<?php
require_once 'config/database.php';

$search_query = $_GET['search_etudiant'] ?? '';
$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = " WHERE nom LIKE :search OR prenom LIKE :search OR numero_carte_etudiant LIKE :search";
    $params[':search'] = '%' . $search_query . '%';
}

$stmt_etudiants = $pdo->prepare("
    SELECT 
        e.numero_carte_etudiant, 
        e.nom, 
        e.prenom,
        (SELECT COUNT(ev.numero_carte_etudiant) FROM evaluer ev WHERE ev.numero_carte_etudiant = e.numero_carte_etudiant) as total_notes_saisies
    FROM etudiant e
    " . $where_clause . "
    ORDER BY e.nom, e.prenom
");
$stmt_etudiants->execute($params);
$etudiants = $stmt_etudiants->fetchAll(PDO::FETCH_ASSOC);

// --- 2. RÉCUPÉRATION DES ECUEs POUR LE FORMULAIRE D'AJOUT DE NOTES (pour tous les étudiants) ---
// Note: Dans un système plus complexe, les ECUEs pourraient dépendre du niveau d'étude de l'étudiant.
// Ici, nous récupérons toutes les ECUEs pour la démonstration.

$stmt_ecues = $pdo->prepare("SELECT id_ecue, libelle_ecue, id_ue FROM ecue ORDER BY libelle_ecue");
$stmt_ecues->execute();
$ecues = $stmt_ecues->fetchAll(PDO::FETCH_ASSOC);

// Préparez un tableau des ECUEs par UE pour faciliter l'affichage
$ecues_by_ue = [];
foreach ($ecues as $ecue) {
    if (!isset($ecues_by_ue[$ecue['id_ue']])) {
        $stmt_ue_libelle = $pdo->prepare("SELECT libelle_ue FROM ue WHERE id_ue = ?");
        $stmt_ue_libelle->execute([$ecue['id_ue']]);
        $ue_libelle = $stmt_ue_libelle->fetchColumn();
        $ecues_by_ue[$ecue['id_ue']] = [
            'libelle_ue' => $ue_libelle,
            'ecues' => []
        ];
    }
    $ecues_by_ue[$ecue['id_ue']]['ecues'][] = $ecue;
}

?>

<div class="page-header">
    <h2><i class="fa-solid fa-graduation-cap"></i> Gestion des Notes des Étudiants</h2>
    <p>Gérez les notes des étudiants par UE/ECUE pour l'année académique en cours.</p>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Liste des Étudiants</h2></div>
    <div class="card-content">
        <form method="GET" action="dashboard_scolarite.php" class="filter-form">
            <input type="hidden" name="page" value="gestion_notes">
            <div class="form-row">
                <input type="text" name="search_etudiant" placeholder="Rechercher par nom, prénom, N° carte..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Rechercher</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard_scolarite.php?page=gestion_notes';"><i class="fa-solid fa-eraser"></i> Réinitialiser</button>
            </div>
        </form>

        <div class="table-container">
            <table class="table-spaced">
                <thead>
                    <tr>
                        <th>N° Carte</th>
                        <th>Nom & Prénom</th>
                        <th>Notes Saisies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($etudiants)): ?>
                        <tr><td colspan="4" style="text-align: center;">Aucun étudiant trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></td>
                                <td>
                                    <?php if ($etudiant['total_notes_saisies'] > 0): ?>
                                        <span class="status-badge status-conforme"><?php echo $etudiant['total_notes_saisies']; ?> notes</span>
                                    <?php else: ?>
                                        <span class="status-badge status-non-conforme">Aucune note</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-success btn-sm add-note-btn" 
                                            data-etudiant-id="<?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?>" 
                                            data-etudiant-nom="<?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>">
                                        <i class="fa-solid fa-plus-circle"></i> Ajouter Note
                                    </button>
                                    <button class="btn btn-info btn-sm view-notes-btn" 
                                            data-etudiant-id="<?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?>" 
                                            data-etudiant-nom="<?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>">
                                        <i class="fa-solid fa-eye"></i> Voir Notes
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

<div id="notesModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3 id="modal-title">Ajouter/Modifier les notes pour <span id="etudiantNomModal"></span></h3>
        <form id="notesForm">
            <input type="hidden" name="etudiant_id" id="modalEtudiantId">
            <div id="notesFormContent" class="form-grid">
                </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer les notes</button>
            </div>
        </form>
    </div>
</div>

<div id="viewNotesModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3 id="viewModalTitle">Notes de <span id="viewEtudiantNomModal"></span></h3>
        <div id="viewNotesContent">
            </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notesModal = document.getElementById('notesModal');
    const viewNotesModal = document.getElementById('viewNotesModal');
    const closeButtons = document.querySelectorAll('.close-button');
    const addNoteButtons = document.querySelectorAll('.add-note-btn');
    const viewNotesButtons = document.querySelectorAll('.view-notes-btn');
    const notesForm = document.getElementById('notesForm');
    const modalEtudiantId = document.getElementById('modalEtudiantId');
    const etudiantNomModal = document.getElementById('etudiantNomModal');
    const notesFormContent = document.getElementById('notesFormContent');
    const viewEtudiantNomModal = document.getElementById('viewEtudiantNomModal');
    const viewNotesContent = document.getElementById('viewNotesContent');

    // Génération des champs de notes pour le formulaire d'ajout/modification
    const ecues = <?php echo json_encode($ecues); ?>;
    const ecuesByUe = <?php echo json_encode($ecues_by_ue); ?>;

    // Fonction pour ouvrir une modale
    function openModal(modalElement) {
        modalElement.style.display = 'block';
        setTimeout(() => modalElement.classList.add('show'), 10); // Pour l'animation
    }

    // Fonction pour fermer une modale
    function closeModal(modalElement) {
        modalElement.classList.remove('show');
        setTimeout(() => modalElement.style.display = 'none', 300); // Attendre la fin de l'animation
    }

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            closeModal(notesModal);
            closeModal(viewNotesModal);
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === notesModal) {
            closeModal(notesModal);
        }
        if (event.target === viewNotesModal) {
            closeModal(viewNotesModal);
        }
    });

    // Gestion du bouton "Ajouter Note"
    addNoteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const etudiantId = this.dataset.etudiantId;
            const etudiantNom = this.dataset.etudiantNom;
            
            modalEtudiantId.value = etudiantId;
            etudiantNomModal.textContent = etudiantNom;
            notesFormContent.innerHTML = '<p style="text-align:center; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des notes existantes...</p>';

            openModal(notesModal);

            // Charger les notes existantes de l'étudiant via AJAX
            fetch('traitement/get_notes_etudiant.php?etudiant_id=' + etudiantId)
                .then(response => response.json())
                .then(data => {
                    notesFormContent.innerHTML = ''; // Nettoyer le message de chargement
                    if (data.success) {
                        const existingNotes = data.notes; // Tableau des notes de cet étudiant

                        for (const ueId in ecuesByUe) {
                            const ueData = ecuesByUe[ueId];
                            let ueHtml = `<div class="ue-group"><h4>UE: ${htmlspecialchars(ueData.libelle_ue)}</h4><div class="ecue-grid">`;
                            ueData.ecues.forEach(ecue => {
                                const currentNote = existingNotes.find(n => n.id_ecue === ecue.id_ecue);
                                const noteValue = currentNote ? currentNote.note : '';
                                ueHtml += `
                                    <div class="form-group">
                                        <label for="note_${htmlspecialchars(ecue.id_ecue)}">${htmlspecialchars(ecue.libelle_ecue)}</label>
                                        <input type="number" step="0.01" min="0" max="20" 
                                               name="notes[${htmlspecialchars(ecue.id_ecue)}]" 
                                               id="note_${htmlspecialchars(ecue.id_ecue)}" 
                                               value="${htmlspecialchars(noteValue)}">
                                    </div>
                                `;
                            });
                            ueHtml += `</div></div>`;
                            notesFormContent.innerHTML += ueHtml;
                        }

                        // Ajoutez une petite fonction pour échapper les caractères HTML spéciaux
                        function htmlspecialchars(str) {
                            var map = {
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '"': '&quot;',
                                "'": '&#039;'
                            };
                            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
                        }

                    } else {
                        notesFormContent.innerHTML = `<p class="alert alert-danger">${htmlspecialchars(data.message)}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur de chargement des notes:', error);
                    notesFormContent.innerHTML = `<p class="alert alert-danger">Erreur lors du chargement des notes. Veuillez réessayer.</p>`;
                });
        });
    });

    // Gestion du bouton "Voir Notes"
    viewNotesButtons.forEach(button => {
        button.addEventListener('click', function() {
            const etudiantId = this.dataset.etudiantId;
            const etudiantNom = this.dataset.etudiantNom;

            viewEtudiantNomModal.textContent = etudiantNom;
            viewNotesContent.innerHTML = '<p style="text-align:center; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des notes...</p>';
            openModal(viewNotesModal);

            fetch('traitement/get_notes_etudiant.php?etudiant_id=' + etudiantId)
                .then(response => response.json())
                .then(data => {
                    viewNotesContent.innerHTML = ''; // Nettoyer le message de chargement
                    if (data.success && data.notes.length > 0) {
                        let html = '<table><thead><tr><th>UE</th><th>ECUE</th><th>Note</th></tr></thead><tbody>';
                        // Reconstruire les UE/ECUE pour l'affichage
                        const notesByUe = {};
                        data.notes.forEach(note => {
                            const ecue = ecues.find(e => e.id_ecue === note.id_ecue);
                            if (ecue) {
                                const ueLibelle = ecuesByUe[ecue.id_ue] ? ecuesByUe[ecue.id_ue].libelle_ue : ecue.id_ue;
                                if (!notesByUe[ueLibelle]) {
                                    notesByUe[ueLibelle] = [];
                                }
                                notesByUe[ueLibelle].push({ ecueLibelle: ecue.libelle_ecue, note: note.note });
                            }
                        });

                        for (const ueLibelle in notesByUe) {
                            let firstEcue = true;
                            notesByUe[ueLibelle].forEach(item => {
                                html += `<tr>`;
                                if (firstEcue) {
                                    html += `<td rowspan="${notesByUe[ueLibelle].length}"><strong>${htmlspecialchars(ueLibelle)}</strong></td>`;
                                    firstEcue = false;
                                }
                                html += `
                                    <td>${htmlspecialchars(item.ecueLibelle)}</td>
                                    <td>${htmlspecialchars(item.note)}/20</td>
                                </tr>`;
                            });
                        }
                        html += '</tbody></table>';
                        viewNotesContent.innerHTML = html;
                    } else {
                        viewNotesContent.innerHTML = '<p class="alert alert-info" style="text-align:center;">Aucune note enregistrée pour cet étudiant.</p>';
                    }
                })
                .catch(error => {
                    console.error('Erreur de chargement des notes:', error);
                    viewNotesContent.innerHTML = '<p class="alert alert-danger" style="text-align:center;">Erreur lors du chargement des notes. Veuillez réessayer.</p>';
                });
        });
    });


    // Gestion de la soumission du formulaire de notes
    notesForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Empêcher la soumission normale du formulaire

        const formData = new FormData(this);
        // Ajoutez l'ID de l'année académique active si nécessaire
        // formData.append('id_annee_academique', 'ANNEE_ACADEMIQUE_ACTIVE_ID'); // Remplacez par l'ID réel

        fetch('traitement/traiter_notes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    closeModal(notesModal);
                    // Recharger la page ou mettre à jour le tableau si nécessaire
                    location.reload(); 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur !',
                    text: data.message,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Erreur Fetch:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur réseau',
                text: 'Une erreur de communication est survenue. Veuillez réessayer.',
                confirmButtonText: 'OK'
            });
        });
    });

    // Fonction helper pour échapper les caractères HTML pour l'affichage (à inclure si ce n'est pas déjà global)
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str;
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<style>
/* Variables CSS pour une palette cohérente */
:root {
    --primary-color: #1565c0;
    --primary-dark: #0d47a1;
    --primary-light: #1976d2;
    --success-color: #4caf50;
    --warning-color: #ff9800;
    --danger-color: #f44336;
    --info-color: #2196f3;
    
    --white: #ffffff;
    --gray-50: #fafafa;
    --gray-100: #f5f5f5;
    --gray-200: #e0e0e0;
    --gray-300: #bdbdbd;
    --gray-400: #9e9e9e;
    --gray-500: #757575;
    --gray-600: #616161;
    --gray-700: #424242;
    --gray-800: #303030;
    --gray-900: #212121;
    
    --text-primary: #212121;
    --text-secondary: #757575;
    --text-muted: #9e9e9e;
    --border-color: #e0e0e0;
    --background: #fafafa;
    
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
}

/* Base */
body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    background-color: var(--background);
    color: var(--text-primary);
    line-height: 1.6;
    margin: 0;
    padding: 0;
}

/* Header de page */
.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 2rem;
    border-radius: var(--radius-lg);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
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
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.page-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-header p {
    font-size: 0.95rem;
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

/* Cards */
.card {
    background: var(--white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.card-header {
    background: var(--white);
    border-bottom: 1px solid var(--border-color);
    padding: 1.25rem 1.5rem;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.card-content {
    padding: 1.5rem;
}

/* Formulaires */
.filter-form .form-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-form input[type="text"] {
    flex: 1;
    min-width: 300px;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    background: var(--white);
    transition: all 0.2s ease;
}

.filter-form input[type="text"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

.filter-form input[type="text"]::placeholder {
    color: var(--text-muted);
}

/* Boutons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--gray-200);
}

.btn-success {
    background: var(--success-color);
    color: var(--white);
}

.btn-success:hover {
    background: #45a049;
}

.btn-info {
    background: var(--info-color);
    color: var(--white);
}

.btn-info:hover {
    background: #1976d2;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
}

/* Tableaux */
.table-container {
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--white);
}

.table-spaced {
    width: 100%;
    border-collapse: collapse;
}

.table-spaced th {
    background: var(--gray-50);
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 1.25rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.table-spaced td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

.table-spaced tbody tr:last-child td {
    border-bottom: none;
}

.table-spaced tbody tr:hover {
    background: var(--gray-50);
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-conforme {
    background: #e8f5e8;
    color: var(--success-color);
}

.status-non-conforme {
    background: #ffebee;
    color: var(--danger-color);
}

/* Modales */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(33, 33, 33, 0.5);
    padding-top: 60px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    opacity: 1;
}

.modal-content {
    background: var(--white);
    margin: 2% auto;
    padding: 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    width: 90%;
    max-width: 900px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
}

.modal.show .modal-content {
    transform: translateY(0);
}

.close-button {
    color: var(--text-muted);
    float: right;
    font-size: 24px;
    font-weight: bold;
    position: absolute;
    top: 15px;
    right: 20px;
    cursor: pointer;
    transition: color 0.2s ease;
}

.close-button:hover {
    color: var(--text-primary);
}

.modal-content h3 {
    color: var(--primary-color);
    font-size: 1.375rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--primary-color);
}

/* Formulaire de notes - Optimisé pour 3 colonnes */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.ue-group {
    background: var(--gray-50);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}

.ue-group h4 {
    color: var(--primary-dark);
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

/* Grid pour les ECUES - Force 3 colonnes maximum */
.ecue-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.form-group {
    margin-bottom: 0.75rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-group input[type="number"] {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    color: var(--text-primary);
    background: var(--white);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.form-group input[type="number"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
    text-align: right;
}

/* Alertes */
.alert-danger, .alert-info {
    padding: 1rem;
    border-radius: var(--radius-sm);
    margin-bottom: 1rem;
    border: 1px solid transparent;
}

.alert-danger {
    background: #ffebee;
    border-color: #ffcdd2;
    color: var(--danger-color);
}

.alert-info {
    background: #e3f2fd;
    border-color: #bbdefb;
    color: var(--primary-dark);
}

/* Table dans la modal de visualisation */
#viewNotesContent table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: var(--white);
}

#viewNotesContent th {
    background: var(--gray-100);
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    text-align: left;
}

#viewNotesContent td {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    font-size: 0.875rem;
}

#viewNotesContent td strong {
    color: var(--text-primary);
}

/* Responsive */
@media (max-width: 1024px) {
    .ecue-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .filter-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-form input[type="text"] {
        min-width: auto;
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .modal-content {
        width: 95%;
        margin: 10px auto;
        padding: 1.5rem;
        max-height: 90vh;
    }

    .ecue-grid {
        grid-template-columns: 1fr;
    }

    .table-spaced th,
    .table-spaced td {
        padding: 0.75rem;
        font-size: 0.85rem;
    }

    .btn {
        font-size: 0.8rem;
        padding: 0.6rem 1rem;
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
    }

    .page-header {
        padding: 1.5rem;
    }

    .page-header h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .card-content,
    .card-header {
        padding: 1rem;
    }
    
    .modal-content {
        padding: 1rem;
    }
    
    .ue-group {
        padding: 1rem;
    }
}

/* Animation subtile pour les hovers */
.btn, .card, input[type="text"], input[type="number"] {
    transition: all 0.2s ease;
}

/* Focus states améliorés */
input:focus,
button:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Amélioration de la lisibilité */
.table-spaced tbody tr:nth-child(even) {
    background: rgba(245, 245, 245, 0.5);
}
</style>
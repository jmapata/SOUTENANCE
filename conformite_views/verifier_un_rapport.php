<?php
// conformite_views/verifier_un_rapport.php (Version finale avec AJAX)

// La variable $pdo est disponible car elle est incluse dans dashboard_conformite.php
$rapport_id = $_GET['id'] ?? null;
if (!$rapport_id) {
    echo '<div class="alert alert-danger">Aucun rapport sélectionné.</div>';
    return; // Arrête l'exécution si pas d'ID
}

// Récupérer les détails du rapport et de l'étudiant
$stmt = $pdo->prepare("
    SELECT r.*, e.nom, e.prenom 
    FROM rapport_etudiant r 
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_rapport_etudiant = ?
");
$stmt->execute([$rapport_id]);
$rapport = $stmt->fetch();

// On détermine si le rapport est un PDF pour la logique d'affichage
$is_pdf = !empty($rapport['chemin_pdf']);

// Si le rapport n'est pas un PDF, on reconstruit son contenu HTML pour l'éditeur
$rapport_html_content = '';
if (!$is_pdf) {
    $stmt_sections = $pdo->prepare("SELECT * FROM section_rapport WHERE id_rapport_etudiant = ? ORDER BY ordre ASC");
    $stmt_sections->execute([$rapport_id]);
    $sections = $stmt_sections->fetchAll();
    foreach ($sections as $section) {
        $rapport_html_content .= '<h3>' . htmlspecialchars($section['titre_section']) . '</h3>';
        $rapport_html_content .= '<p>' . nl2br(htmlspecialchars($section['contenu_section'])) . '</p>';
    }
}
?>

<div class="page-header">
    <h2>Vérification du rapport : "<?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?>"</h2>
    <p>Étudiant : <?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></p>
</div>

<div class="verification-container">
    <div class="editor-panel">
        <?php if ($is_pdf): ?>
            <div class="pdf-viewer-container">
                <embed src="<?php echo htmlspecialchars($rapport['chemin_pdf']); ?>" type="application/pdf" width="100%" height="100%">
            </div>
            <label for="editor-conformite" class="editor-label">Vos commentaires et annotations sur le PDF :</label>
        <?php endif; ?>
        <textarea id="editor-conformite">
            <?php 
                if (!$is_pdf) {
                    echo $rapport_html_content;
                }
            ?>
        </textarea>
    </div>

    <div class="decision-panel">
        <h3><i class="fa-solid fa-gavel"></i> Panneau de Décision</h3>
        <p>Après avoir consulté ou annoté le rapport, veuillez soumettre votre décision.</p>
        
        <form id="decisionForm" action="traitement/traiter_conformite.php" method="POST">
            <input type="hidden" name="rapport_id" value="<?php echo $rapport_id; ?>">
            <input type="hidden" name="commentaire" id="hidden-commentaire">
            <div class="decision-actions">
                <button type="submit" name="decision" value="non_conforme" class="btn btn-warning"><i class="fa-solid fa-times-circle"></i> Renvoyer (Non Conforme)</button>
                <button type="submit" name="decision" value="conforme" class="btn btn-success"><i class="fa-solid fa-check-circle"></i> Valider pour la Commission</button>
            </div>
            <div class="form-instructions">
                <ul>
                    <li><strong>Renvoyer :</strong> Le rapport et vos commentaires/annotations seront retournés à l'étudiant.</li>
                    <li><strong>Valider :</strong> Le rapport sera transmis à la commission de validation.</li>
                </ul>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let editorInstance = null;
    
    // On utilise CKEditor
    ClassicEditor
        .create(document.querySelector('#editor-conformite'))
        .then(editor => {
            editorInstance = editor;
            console.log('CKEditor a démarré !', editor);
            
            // On met à jour le champ caché à chaque changement
            editor.model.document.on('change:data', () => {
                const data = editor.getData();
                document.getElementById('hidden-commentaire').value = data;
            });
            // On met à jour une première fois
            document.getElementById('hidden-commentaire').value = editor.getData();
        })
        .catch(error => {
            console.error('Erreur lors du démarrage de CKEditor :', error);
        });

    // On gère la soumission du formulaire
    const decisionForm = document.getElementById('decisionForm');
    decisionForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Empêche la soumission classique qui recharge la page

        const clickedButton = event.submitter;
        const formData = new FormData(decisionForm);
        formData.append(clickedButton.name, clickedButton.value);

        // On désactive les boutons pour éviter les clics multiples
        const buttons = decisionForm.querySelectorAll('button');
        buttons.forEach(button => {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
        });

        fetch('traitement/traiter_conformite.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message); // Affiche le message de succès ou d'erreur
            
            if (data.success) {
                // Si c'est un succès, on retourne à la liste des rapports
                window.location.href = 'dashboard_conformite.php?page=rapports_a_verifier';
            } else {
                // Sinon, on réactive les boutons
                buttons.forEach(button => {
                    button.disabled = false;
                    if(button.value === 'non_conforme') button.innerHTML = '<i class="fa-solid fa-times-circle"></i> Renvoyer (Non Conforme)';
                    else button.innerHTML = '<i class="fa-solid fa-check-circle"></i> Valider pour la Commission';
                });
            }
        })
        .catch(error => {
            console.error('Erreur Fetch:', error);
            alert('Une erreur de communication est survenue.');
        });
    });
});
</script>

<style>
    :root {
        --primary-color: #7c3aed;
        --primary-light: #a855f7;
        --primary-dark: #5b21b6;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --card-bg: #ffffff;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --text-muted: #9ca3af;
        --border-color: #e5e7eb;
        --background: #f9fafb;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --shadow-soft: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
        background: var(--background);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--text-dark);
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }

    .page-header {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-soft);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-color);
    }

    .page-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        color: var(--text-dark);
    }

    .page-header p {
        margin: 0;
        color: var(--text-light);
        font-size: 0.95rem;
        font-weight: 500;
    }

    .verification-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
        height: calc(100vh - 200px);
        min-height: 600px;
    }

    .editor-panel {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--shadow-soft);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .editor-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 12px 12px 0 0;
    }

    .pdf-viewer-container {
        width: 100%;
        height: 60vh;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 1.5rem;
        flex-shrink: 0;
        background: var(--gray-50);
        overflow: hidden;
    }

    .pdf-viewer-container embed {
        width: 100%;
        height: 100%;
        border: none;
    }

    .editor-label {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-dark);
        margin-bottom: 0.75rem;
        display: block;
    }

    .decision-panel {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--shadow-soft);
        height: fit-content;
        position: sticky;
        top: 20px;
    }

    .decision-panel h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }

    .decision-panel h3 i {
        color: var(--primary-color);
        font-size: 1rem;
    }

    .decision-panel p {
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .decision-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-warning {
        background: var(--card-bg);
        color: var(--warning-color);
        border-color: var(--warning-color);
    }

    .btn-warning:hover:not(:disabled) {
        background: var(--warning-color);
        color: white;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
    }

    .btn-success {
        background: var(--card-bg);
        color: var(--success-color);
        border-color: var(--success-color);
    }

    .btn-success:hover:not(:disabled) {
        background: var(--success-color);
        color: white;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
    }

    .btn i {
        font-size: 0.875rem;
    }

    .form-instructions {
        background: var(--gray-50);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
    }

    .form-instructions ul {
        margin: 0;
        padding-left: 1.5rem;
        color: var(--text-light);
        font-size: 0.85rem;
        line-height: 1.6;
    }

    .form-instructions li {
        margin-bottom: 0.5rem;
    }

    .form-instructions li:last-child {
        margin-bottom: 0;
    }

    .form-instructions strong {
        color: var(--text-dark);
        font-weight: 600;
    }

    /* Styles pour CKEditor */
    .ck.ck-editor {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .ck-editor__main {
        flex-grow: 1;
    }

    .ck-editor__editable_inline {
        height: 100%;
        min-height: 200px;
        border: none;
        background: var(--card-bg);
    }

    .ck-editor__editable_inline:focus {
        box-shadow: inset 0 0 0 2px rgba(124, 58, 237, 0.1);
    }

    .ck.ck-toolbar {
        background: var(--gray-50);
        border-bottom: 1px solid var(--border-color);
    }

    /* Textarea de fallback */
    #editor-conformite {
        width: 100%;
        min-height: 200px;
        padding: 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.9rem;
        line-height: 1.5;
        resize: vertical;
        background: var(--card-bg);
    }

    #editor-conformite:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    /* Alerte */
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
    }

    .alert-danger {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .verification-container {
        animation: fadeIn 0.3s ease;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
        .verification-container {
            grid-template-columns: 1fr;
            height: auto;
            min-height: auto;
        }
        
        .pdf-viewer-container {
            height: 50vh;
        }
        
        .decision-panel {
            position: static;
            top: auto;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            padding: 1rem;
        }
        
        .page-header h2 {
            font-size: 1.25rem;
        }
        
        .editor-panel,
        .decision-panel {
            padding: 1rem;
        }
        
        .verification-container {
            gap: 1rem;
        }
        
        .pdf-viewer-container {
            height: 40vh;
        }
        
        .btn {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .decision-actions {
            gap: 0.5rem;
        }
    }

    /* État de chargement */
    .btn .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Focus visible pour l'accessibilité */
    .btn:focus-visible {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
    }
</style>
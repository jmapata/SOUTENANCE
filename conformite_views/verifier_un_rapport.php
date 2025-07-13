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
    .editor-panel {
        display: flex;
        flex-direction: column;
    }
    .pdf-viewer-container {
        width: 100%;
        height: 60vh; /* Hauteur de la visionneuse PDF */
        border: 1px solid #ccc;
        margin-bottom: 20px;
        flex-shrink: 0;
    }
    .editor-label {
        font-weight: bold;
        display: block;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }
    /* S'assurer que l'éditeur prend la place restante */
    .ck.ck-editor {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .ck-editor__main {
        flex-grow: 1;
    }
    .ck-editor__editable_inline {
        height: 100%;
        min-height: 150px; /* Hauteur minimale pour l'éditeur de commentaires */
    }
</style>
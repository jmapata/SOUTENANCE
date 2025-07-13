<?php
// etudiant_views/rapport_modification.php (Version finale : commentaires à gauche, rapport original à droite)

// 1. On cherche le rapport non-conforme de l'étudiant
$stmt_rapport = $pdo->prepare("
    SELECT * FROM rapport_etudiant 
    WHERE numero_carte_etudiant = (SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?) 
    AND id_statut_rapport = 'RAP_NON_CONF' 
    LIMIT 1
");
$stmt_rapport->execute([$_SESSION['numero_utilisateur']]);
$rapport_a_corriger = $stmt_rapport->fetch();

$commentaire_agent = '';
$rapport_original_html = '';

if ($rapport_a_corriger) {
    // 2. On récupère le commentaire de l'agent depuis la table `approuver`
    $stmt_comment = $pdo->prepare("
        SELECT commentaire_conformite 
        FROM approuver 
        WHERE id_rapport_etudiant = ? 
        ORDER BY date_verification_conformite DESC 
        LIMIT 1
    ");
    $stmt_comment->execute([$rapport_a_corriger['id_rapport_etudiant']]);
    $result = $stmt_comment->fetch();
    $commentaire_agent = $result['commentaire_conformite'] ?? '<p>Aucun commentaire spécifique n\'a été laissé.</p>';

    // 3. On récupère le contenu original du rapport de l'étudiant depuis la table `section_rapport`
    $stmt_sections = $pdo->prepare("SELECT * FROM section_rapport WHERE id_rapport_etudiant = ? ORDER BY ordre ASC");
    $stmt_sections->execute([$rapport_a_corriger['id_rapport_etudiant']]);
    $sections = $stmt_sections->fetchAll();
    foreach ($sections as $section) {
        $rapport_original_html .= '<h3>' . htmlspecialchars($section['titre_section']) . '</h3>';
        $rapport_original_html .= '<p>' . nl2br(htmlspecialchars($section['contenu_section'])) . '</p>';
    }
}
?>

<div class="page-header">
    <h2><i class="fa-solid fa-pen-to-square"></i> Correction de Rapport</h2>
</div>

<?php if (!$rapport_a_corriger): ?>
    <div class="alert alert-info">Vous n'avez aucun rapport à corriger pour le moment.</div>
<?php else: ?>
    <div class="alert alert-warning">
        <h4>Action Requise</h4>
        <p>Votre rapport "<?php echo htmlspecialchars($rapport_a_corriger['libelle_rapport_etudiant']); ?>" a été retourné. Veuillez lire les annotations dans le panneau de gauche et apporter les corrections nécessaires dans l'éditeur à droite.</p>
    </div>

    <div class="correction-container">

        <div class="comment-panel">
            <h3><i class="fa-solid fa-comments"></i> Annotations de l'Agent de Conformité</h3>
            <div class="comment-content">
                <?php echo $commentaire_agent; ?>
            </div>
        </div>

        <div class="editor-panel">
            <h3><i class="fa-solid fa-file-alt"></i> Votre Rapport (Version à Corriger)</h3>
            <form id="correctionForm" action="traitement/resoumettre_rapport.php" method="POST" class="rapport-form">
                <input type="hidden" name="rapport_id" value="<?php echo $rapport_a_corriger['id_rapport_etudiant']; ?>">

                <textarea id="editor-correction" name="contenu_corrige">
                    <?php echo $rapport_original_html; // On charge bien le contenu original ici ?>
                </textarea>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Renvoyer le rapport corrigé
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let editorInstance = null;
    const form = document.getElementById('correctionForm');
    const textarea = document.querySelector('#editor-correction');

    ClassicEditor
        .create(textarea)
        .then(editor => {
            editorInstance = editor;
        })
        .catch(error => {
            console.error('Erreur de démarrage de CKEditor :', error);
        });

    form.addEventListener('submit', function(event) {
        if (editorInstance) {
            textarea.value = editorInstance.getData();
        }
    });
});
</script>

<style>
    .correction-container { display: flex; gap: 1.5rem; margin-top: 20px; }
    .comment-panel, .editor-panel { background: #fff; padding: 25px; border-radius: 10px; border: 1px solid #eee; }
    .comment-panel { flex: 1; }
    .editor-panel { flex: 2; }
    .comment-panel h3, .editor-panel h3 { margin-top: 0; margin-bottom: 20px; color: #34495e; }
    .comment-content { background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef; min-height: 400px; }
    .rapport-form { display: flex; flex-direction: column; height: 100%; }
    .ck-editor { flex-grow: 1; display: flex; flex-direction: column; }
    .ck-editor__main { flex-grow: 1; }
    .ck-editor__editable_inline { height: 100%; min-height: 400px; }
    .form-actions { text-align: right; margin-top: 20px; }
    .alert-warning { padding: 15px; background-color: #fff3cd; border-left: 5px solid #ffeeba; }
</style>
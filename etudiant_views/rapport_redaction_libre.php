<?php
// etudiant_views/rapport_redaction_libre.php
// Ce fichier contient TOUT ce qui est nécessaire pour cette page.
?>

<script>
  tinymce.init({
    // CORRECTION N°1 : On cible l'ID spécifique de notre textarea
    selector: '#rapportEditeur', 
    
    // CORRECTION N°2 : On résout le problème de la "saisie impossible"
    model: 'dom', 

    // Le reste est votre configuration, avec toutes les fonctionnalités
    plugins: [
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
      'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    height: 600,
  });
</script>


<div class="page-header">
    <h2><i class="fa-solid fa-pen-to-square"></i> Rédiger un Rapport Libre</h2>
    <p class="page-description">Utilisez l'éditeur ci-dessous pour rédiger et mettre en forme votre rapport complet.</p>
</div>

<form action="traitement/enregistrer_rapport.php" method="POST" class="rapport-form">
    
    <div class="form-group">
        <label for="rapportTitre">Titre de votre rapport :</label>
        <input type="text" id="rapportTitre" name="rapport_titre" class="form-control" placeholder="Ex: Optimisation des processus..." required>
    </div>

    <div class="form-group">
        <label for="rapportEditeur">Contenu du rapport :</label>
        <textarea id="rapportEditeur" name="rapport_contenu"></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" name="action" value="sauvegarder_brouillon" class="btn btn-secondary">
            <i class="fa-solid fa-save"></i> Sauvegarder le brouillon
        </button>
        <button type="submit" name="action" value="soumettre_rapport" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane"></i> Soumettre le rapport
        </button>
    </div>

</form>

<style>
    .rapport-form { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #333; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
    .form-actions { text-align: right; margin-top: 30px; display: flex; justify-content: flex-end; gap: 15px; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-secondary:hover { background-color: #5a6268; }
</style>
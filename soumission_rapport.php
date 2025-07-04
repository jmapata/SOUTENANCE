<?php
// /soumission_rapport.php
require_once __DIR__ . '/includes/header.php';

if (!checkPermission('TRAIT_ETUDIANT_RAPPORT_SOUMETTRE')) {
    die("Accès non autorisé.");
}

// Vérifier si l'étudiant peut soumettre (pas de rapport déjà ACCEPTÉ, etc.)
// Cette logique peut être ajoutée ici.
?>

<h2>Soumission de votre rapport de stage</h2>
[cite_start]<p>Veuillez remplir les informations et joindre votre rapport au format PDF (Taille maximale : 50 Mo)[cite: 381].</p>

<?php if (isset($_GET['error'])): ?>
    <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
<?php endif; ?>

<form action="/traitement/soumission_rapport_traitement.php" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="titre">Titre du mémoire</label>
        <input type="text" id="titre" name="titre" required>
    </div>
    <div class="form-group">
        <label for="theme">Thème principal</label>
        <input type="text" id="theme" name="theme" required>
    </div>
     <div class="form-group">
        <label for="nb_pages">Nombre de pages (estimation)</label>
        <input type="number" id="nb_pages" name="nb_pages" required>
    </div>
    <div class="form-group">
        <label for="rapport_pdf">Fichier du rapport (PDF)</label>
        <input type="file" id="rapport_pdf" name="rapport_pdf" accept=".pdf" required>
    </div>
    <button type="submit">Soumettre mon rapport</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
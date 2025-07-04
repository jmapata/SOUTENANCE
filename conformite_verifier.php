<?php
// /conformite_verifier.php
require_once __DIR__ . '/includes/header.php';
// if (!checkPermission('TRAIT_CONFORMITE_VERIFIER')) { die("Accès non autorisé."); }

$id_rapport = $_GET['id'] ?? null;
if (!$id_rapport) {
    die("ID du rapport manquant.");
}

// Récupérer les détails du rapport et de l'étudiant
$stmt_rapport = $p_pdo->prepare(
    "SELECT r.*, e.nom, e.prenom, e.email_principal as email_etudiant
     FROM rapport_etudiant r
     JOIN utilisateur e ON r.numero_carte_etudiant = e.numero_utilisateur
     WHERE r.id_rapport_etudiant = ?"
);
$stmt_rapport->execute([$id_rapport]);
$rapport = $stmt_rapport->fetch();

if (!$rapport) {
    die("Rapport non trouvé.");
}

// Récupérer les critères de conformité depuis la BDD (RG16)
$criteres = $p_pdo->query("SELECT * FROM critere_conformite_ref WHERE est_actif = 1 ORDER BY libelle_critere")->fetchAll();
?>

<h2>Vérification du rapport : "<?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?>"</h2>
<p><strong>Étudiant :</strong> <?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></p>
<p><strong>Date de soumission :</strong> <?php echo (new DateTime($rapport['date_soumission']))->format('d/m/Y H:i'); ?></p>

<a href="/uploads/rapports/<?php /* chemin_fichier à récupérer de la BDD */ ?>" target="_blank" class="btn">Télécharger et visualiser le rapport</a>

<hr>

<h3>Checklist de conformité</h3>
<form action="/traitement/conformite_verifier_traitement.php" method="POST">
    <input type="hidden" name="id_rapport" value="<?php echo $rapport['id_rapport_etudiant']; ?>">
    <input type="hidden" name="email_etudiant" value="<?php echo $rapport['email_etudiant']; ?>">

    <?php foreach ($criteres as $critere): ?>
    <div class="form-group checklist-item">
        <label><?php echo htmlspecialchars($critere['libelle_critere']); ?></label>
        <div>
            <input type="radio" name="critere[<?php echo $critere['id_critere']; ?>]" value="Conforme" required> Conforme
            <input type="radio" name="critere[<?php echo $critere['id_critere']; ?>]" value="Non Conforme"> Non Conforme
        </div>
    </div>
    <?php endforeach; ?>

    <hr>
    
    <h3>Décision finale</h3>
    <div class="form-group">
        <label for="decision">Décision</label>
        <select name="decision" id="decision" required>
            <option value="CONFORME">Déclarer Conforme</option>
            <option value="INCOMPLET">Déclarer Non Conforme (Incomplet)</option>
        </select>
    </div>

    <div class="form-group">
        <label for="commentaire">Commentaires pour l'étudiant (obligatoire si non conforme - RG12)</label>
        <textarea name="commentaire" id="commentaire" rows="5" style="width:100%;"></textarea>
    </div>

    <button type="submit">Valider la vérification</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
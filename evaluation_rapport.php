<?php
// /evaluation_rapport.php
require_once __DIR__ . '/includes/header.php';
// if (!checkPermission('TRAIT_COMMISSION_EVALUER')) { die("Accès non autorisé."); }

$id_rapport = $_GET['id'] ?? null;
if (!$id_rapport) {
    die("ID du rapport manquant.");
}

// Récupérer les détails du rapport
$stmt_rapport = $p_pdo->prepare("SELECT * FROM rapport_etudiant WHERE id_rapport_etudiant = ?");
$stmt_rapport->execute([$id_rapport]);
$rapport = $stmt_rapport->fetch();

if (!$rapport) {
    die("Rapport non trouvé.");
}

// Vérifier si ce membre a déjà voté pour ce rapport pour éviter un double vote
$stmt_vote = $p_pdo->prepare("SELECT * FROM vote_commission WHERE id_rapport_etudiant = ? AND numero_enseignant = ?");
$stmt_vote->execute([$id_rapport, $_SESSION['user_id']]);
if ($stmt_vote->fetch()) {
    die("Vous avez déjà voté pour ce rapport.");
}

?>

<h2>Évaluation du rapport : "<?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?>"</h2>

<a href="/uploads/rapports/<?php /* chemin_fichier */ ?>" target="_blank" class="btn">Télécharger et visualiser le rapport</a>

<hr>

<h3>Mon Évaluation</h3>
<p>Après lecture, veuillez soumettre votre décision. Votre vote sera enregistré.</p>

<form action="/traitement/evaluation_rapport_traitement.php" method="POST">
    <input type="hidden" name="id_rapport" value="<?php echo htmlspecialchars($rapport['id_rapport_etudiant']); ?>">

    <div class="form-group">
        <label for="decision_vote">Votre décision</label>
        <select name="decision_vote" id="decision_vote" required>
            <option value="APPROUVE">Approuvé</option>
            <option value="APPROUVE_CORRECTIONS">Approuvé sous réserve de corrections mineures</option>
            <option value="REFUSE">Refusé</option>
        </select>
    </div>

    <div class="form-group">
        <label for="commentaire_vote">Commentaire (obligatoire si la décision n'est pas "Approuvé" - RG22)</label>
        <textarea name="commentaire_vote" id="commentaire_vote" rows="5" style="width:100%;"></textarea>
    </div>

    <button type="submit">Soumettre mon évaluation</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
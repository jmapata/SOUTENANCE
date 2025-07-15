<?php
require_once(__DIR__ . '/../config/database.php');

// Vérification que l'utilisateur est membre de la commission
$stmt = $pdo->prepare("
    SELECT u.numero_utilisateur, g.libelle_groupe_utilisateur
    FROM utilisateur u
    JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
    WHERE u.numero_utilisateur = ?
");
$stmt->execute([$_SESSION['numero_utilisateur']]);
$utilisateur = $stmt->fetch();

if (!$utilisateur || $utilisateur['libelle_groupe_utilisateur'] !== 'Membre de Commission') {
    die("<div class='alert alert-danger'>Accès refusé.</div>");
}

$numero_utilisateur = $utilisateur['numero_utilisateur'];

// Récupération des rapports ayant le statut de "corrigé"
$stmt = $pdo->prepare("
    SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.chemin_pdf,
           r.date_soumission, e.nom, e.prenom
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_CORRIGE'
    ORDER BY r.date_soumission DESC
");
$stmt->execute();
$rapports = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fa-solid fa-file-pen"></i> Gestion des Corrections</h2>
    <p>Liste des rapports corrigés soumis par les étudiants.</p>
</div>

<?php if (empty($rapports)): ?>
    <div class="alert alert-info">Aucun rapport corrigé à examiner.</div>
<?php else: ?>
    <?php foreach ($rapports as $rapport): ?>
        <div class="report-card">
            <div class="report-card-header">
                <h4><?= htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></h4>
                <span>Par : <?= htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></span>
            </div>
            <div class="report-card-body">
                <p><strong>Date de dernière soumission :</strong> <?= $rapport['date_soumission']; ?></p>
                <a href="<?= $rapport['chemin_pdf']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                    📄 Voir le rapport corrigé
                </a>

                <form action="traitement/statuer_correction.php" method="POST" class="mt-3">
                    <input type="hidden" name="id_rapport_etudiant" value="<?= $rapport['id_rapport_etudiant']; ?>">
                    <div class="form-group mt-2">
                        <label>Décision :</label>
                        <select name="decision_correction" class="form-control" required>
                            <option value="">-- Choisir --</option>
                            <option value="accepte">✅ Accepter la correction</option>
                            <option value="refuse">❌ Refuser la correction</option>
                            <option value="discussion">💬 Demander une concertation</option>
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Commentaire (optionnel) :</label>
                        <textarea name="commentaire_commission" class="form-control" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary mt-2">Valider la décision</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
.report-card { background: #fff; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
.report-card-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; }
.report-card-body { padding: 20px; }
.btn-sm { padding: 5px 10px; font-size: 13px; }
</style>

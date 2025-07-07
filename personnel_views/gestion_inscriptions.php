<?php
require_once 'config/database.php';

// Récupération des inscriptions pour la liste
$stmt = $pdo->query(
    "SELECT e.numero_carte_etudiant, e.nom, e.prenom, u.statut_compte, i.montant_inscription, i.id_annee_academique, i.id_niveau_etude, e.numero_utilisateur
     FROM etudiant e
     JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
     LEFT JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur
     ORDER BY e.nom, e.prenom"
);
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_scolarite = 1300000;
?>

<div class="page-header">
    <h1>Suivi de la Scolarité</h1>
</div>

<div class="card">
    <div class="card-content">
        <table class="table-spaced">
            <thead><tr><th>Étudiant</th><th>Montant Versé</th><th>Reste à Payer</th><th>Statut Compte</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($inscriptions as $inscription): 
                    $reste_a_payer = $total_scolarite - $inscription['montant_inscription'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></td>
                    <td><?php echo number_format($inscription['montant_inscription'], 0, ',', ' '); ?> FCFA</td>
                    <td style="font-weight: 600; color: <?php echo ($reste_a_payer > 0) ? '#b91c1c' : '#15803d'; ?>;">
                        <?php echo number_format($reste_a_payer, 0, ',', ' '); ?> FCFA
                    </td>
                    <td>
                        <span class="status-badge <?php echo htmlspecialchars(strtolower($inscription['statut_compte'] ?? 'non-cree')); ?>">
                            <?php echo htmlspecialchars($inscription['statut_compte'] ?? 'Non créé'); ?>
                        </span>
                    </td>
                    <td class="table-actions">
                        <?php if ($reste_a_payer <= 0 && $inscription['statut_compte'] === 'inactif'): ?>
                            <form action="traitement/inscription_traitement.php" method="POST">
                                <input type="hidden" name="action" value="activer_compte">
                                <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($inscription['numero_utilisateur']); ?>">
                                <button type="submit" class="btn btn-sm btn-success">Activer</button>
                            </form>
                        <?php elseif ($reste_a_payer > 0): ?>
                            <button class="btn btn-sm btn-secondary open-payment-modal" 
                                    data-num-etudiant="<?php echo htmlspecialchars($inscription['numero_carte_etudiant']); ?>"
                                    data-nom-etudiant="<?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?>">
                                Ajouter Versement
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="payment-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Ajouter un versement pour <span id="student-name-in-modal"></span></h2>
            <button id="close-payment-modal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="traitement/inscription_traitement.php" method="POST" class="form-layout">
                <input type="hidden" name="action" value="ajouter_versement">
                <input type="hidden" name="numero_carte_etudiant" id="modal-num-etudiant">
                
                <div class="form-group">
                    <label for="montant_versement">Montant du nouveau versement</label>
                    <input type="number" id="montant_versement" name="montant" min="1" required>
                </div>
                <div class="form-group">
                    <label for="numero_recu_versement">Numéro du reçu de ce versement</label>
                    <input type="text" id="numero_recu_versement" name="numero_recu" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer le Versement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('payment-modal');
    const closeBtn = document.getElementById('close-payment-modal');
    const studentNameSpan = document.getElementById('student-name-in-modal');
    const numEtudiantInput = document.getElementById('modal-num-etudiant');
    
    // On écoute les clics sur TOUS les boutons "Ajouter Versement"
    document.querySelectorAll('.open-payment-modal').forEach(button => {
        button.addEventListener('click', function() {
            // On récupère les informations de l'étudiant depuis le bouton cliqué
            const numEtudiant = this.dataset.numEtudiant;
            const nomEtudiant = this.dataset.nomEtudiant;
            
            // On met à jour le titre et le champ caché du formulaire dans la modale
            studentNameSpan.textContent = nomEtudiant;
            numEtudiantInput.value = numEtudiant;

            // On affiche la modale
            modal.classList.add('active');
        });
    });

    // Logique pour fermer la modale
    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    }
    if (modal) {
        modal.addEventListener('click', e => { 
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }
});
</script>
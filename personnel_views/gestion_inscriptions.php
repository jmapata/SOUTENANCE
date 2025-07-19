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
<style>
    /* CSS pour la page gestion_inscriptions.php */
body {
    background-color: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 0;
    padding: 20px;
    color: #2c3e50;
    line-height: 1.6;
}

/* En-tête de page */
.page-header {
    margin-bottom: 30px;
    text-align: center;
    padding: 30px 0;
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    border-radius: 12px;
    color: white;
    box-shadow: 0 4px 20px rgba(13, 71, 161, 0.15);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Cartes principales */
.card {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 20px rgba(13, 71, 161, 0.08);
    border: 1px solid rgba(13, 71, 161, 0.1);
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0d47a1, #1565c0);
}

.card-content {
    padding: 25px;
}

/* Tableau */
.table-spaced {
    width: 100%;
    border-collapse: collapse;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 0 1px rgba(13, 71, 161, 0.1);
}

.table-spaced th {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-spaced td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 0.95rem;
    vertical-align: middle;
}

.table-spaced tr:hover {
    background-color: rgba(13, 71, 161, 0.02);
}

.table-spaced tr:last-child td {
    border-bottom: none;
}

/* Styles spécifiques pour les montants */
.table-spaced td[style*="color: #b91c1c"] {
    color: #dc2626 !important;
    font-weight: 600;
}

.table-spaced td[style*="color: #15803d"] {
    color: #059669 !important;
    font-weight: 600;
}

/* Badges de statut */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.actif {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #22c55e;
}

.status-badge.inactif {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #f59e0b;
}

.status-badge.non-cree {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #ef4444;
}

.status-badge.suspendu {
    background-color: #f3f4f6;
    color: #6b7280;
    border: 1px solid #9ca3af;
}

/* Actions du tableau */
.table-actions {
    text-align: center;
    min-width: 150px;
}

.table-actions form {
    display: inline-block;
    margin: 0;
}

/* Boutons */
.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    min-width: 100px;
}

.btn-primary {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    box-shadow: 0 2px 10px rgba(13, 71, 161, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1565c0, #0d47a1);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(13, 71, 161, 0.4);
}

.btn-secondary {
    background-color: #ffffff;
    color: #0d47a1;
    border: 2px solid #0d47a1;
}

.btn-secondary:hover {
    background-color: #0d47a1;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #059669, #10b981);
    color: #ffffff;
    box-shadow: 0 2px 10px rgba(5, 150, 105, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #10b981, #059669);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
    min-width: 80px;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-container {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(13, 71, 161, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.modal-overlay.active .modal-container {
    transform: scale(1);
}

.modal-header {
    padding: 25px 25px 0 25px;
    border-bottom: 1px solid rgba(13, 71, 161, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.modal-header::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #0d47a1, #1565c0);
}

.modal-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #0d47a1;
    margin: 0;
    flex: 1;
}

.close-modal {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.close-modal:hover {
    background-color: rgba(13, 71, 161, 0.1);
    color: #0d47a1;
}

.modal-content {
    padding: 25px;
}

/* Formulaire dans la modal */
.form-layout {
    max-width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #ffffff;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0d47a1;
    box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
}

.form-group input:hover,
.form-group select:hover,
.form-group textarea:hover {
    border-color: #1565c0;
}

.form-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(13, 71, 161, 0.1);
}

.form-actions .btn {
    min-width: 150px;
    padding: 12px 24px;
    font-size: 1rem;
}

/* Responsive design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .page-header {
        padding: 20px 0;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .card-content {
        padding: 15px;
    }
    
    .table-spaced {
        font-size: 0.85rem;
    }
    
    .table-spaced th,
    .table-spaced td {
        padding: 10px 12px;
    }
    
    .table-actions {
        min-width: 120px;
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
        min-width: 70px;
    }
    
    .modal-container {
        width: 95%;
        margin: 10px;
    }
    
    .modal-header,
    .modal-content {
        padding: 20px;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .form-actions .btn {
        width: 100%;
        max-width: 250px;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Amélioration visuelle des badges */
.status-badge {
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.status-badge:hover::before {
    left: 100%;
}

/* Amélioration des interactions */
.table-spaced tr {
    transition: background-color 0.3s ease;
}

.open-payment-modal {
    position: relative;
    overflow: hidden;
}

.open-payment-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.open-payment-modal:hover::before {
    left: 100%;
}
</style>
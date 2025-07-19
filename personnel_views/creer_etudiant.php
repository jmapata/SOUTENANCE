<?php
// On se connecte à la BDD pour récupérer les données nécessaires.
require_once 'config/database.php';

// On récupère tous les étudiants déjà créés pour les lister.
// La requête joint la table 'inscrire' pour obtenir le montant déjà versé.
$stmt = $pdo->query(
    "SELECT e.numero_carte_etudiant, e.nom, e.prenom, e.date_naissance, e.nationalite, e.telephone, i.montant_inscription
     FROM etudiant e
     LEFT JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
     ORDER BY e.nom, e.prenom"
);
$etudiants_inscrits = $stmt->fetchAll();

// On définit le montant total de la scolarité pour référence.
$total_scolarite = 1300000;
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>


<div class="page-header">
    <h1>Création et Suivi des Fiches Étudiants (Master 2)</h1>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Inscrire un Nouvel Étudiant</h2></div>
    <div class="card-content">
        <form action="traitement/scolarite_traitement.php" method="POST" class="form-layout">
            <input type="hidden" name="action" value="creer_fiche_etudiant">
            
            <div class="form-section">
                <h3>Informations Personnelles</h3>
                <div class="form-grid">
                    <div class="form-group"><label for="nom">Nom</label><input type="text" id="nom" name="nom" required></div>
                    <div class="form-group"><label for="prenom">Prénom</label><input type="text" id="prenom" name="prenom" required></div>
                    <div class="form-group">
                        <label for="email">Email de Contact</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group"><label for="date_naissance">Date de naissance</label><input type="date" id="date_naissance" name="date_naissance"></div>
                    <div class="form-group"><label for="nationalite">Nationalité</label><input type="text" id="nationalite" name="nationalite"></div>
                    <div class="form-group"><label for="telephone">Contact</label><input type="tel" id="telephone" name="telephone"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Inscription et Premier Versement</h3>
                <p>La scolarité totale est de <strong><?php echo number_format($total_scolarite, 0, ',', ' '); ?> FCFA</strong>.</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="montant">Montant du premier versement (Minimum : 550 000)</label>
                        <input type="number" id="montant" name="montant" min="550000" placeholder="550000" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_recu">Numéro du Reçu de Paiement</label>
                        <input type="text" id="numero_recu" name="numero_recu" required>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer la Fiche et le Paiement</button>
            </div>
        </form>
    </div>
</div>


<div class="card" style="margin-top: 30px;">
    <div class="card-header"><h2 class="card-title">Liste des Étudiants Inscrits</h2></div>
    <div class="card-content">
        <table class="table-spaced">
            <thead>
                <tr>
                    <th>N° Carte</th>
                    <th>Nom & Prénom</th>
                    <th>Date de Naissance</th>
                    <th>Nationalité</th>
                    <th>Contact</th>
                    <th>Montant Restant à Payer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($etudiants_inscrits)): ?>
                    <tr><td colspan="7" style="text-align: center;">Aucun étudiant n'a encore été créé.</td></tr>
                <?php else: ?>
                    <?php foreach ($etudiants_inscrits as $etudiant): 
                        // Calcul du reste à payer
                        $reste_a_payer = $total_scolarite - ($etudiant['montant_inscription'] ?? 0);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($etudiant['numero_carte_etudiant']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($etudiant['date_naissance']))); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['nationalite']); ?></td>
                        <td><?php echo htmlspecialchars($etudiant['telephone']); ?></td>
                        <td style="font-weight: 600; color: <?php echo ($reste_a_payer > 0) ? '#b91c1c' : '#15803d'; ?>;">
                            <?php echo number_format($reste_a_payer, 0, ',', ' '); ?> FCFA
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
    /* CSS pour la page creer_etudiant.php */
body {
    background-color: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 0;
    padding: 20px;
    color: #2c3e50;
    line-height: 1.6;
}

/* Messages de succès et d'erreur */
.success-message {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #22c55e;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(34, 197, 94, 0.1);
}

.error-message {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #dc2626;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #ef4444;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(239, 68, 68, 0.1);
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

.card-header {
    padding: 25px 25px 0 25px;
    border-bottom: 1px solid rgba(13, 71, 161, 0.1);
    margin-bottom: 25px;
}

.card-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #0d47a1;
    margin: 0 0 15px 0;
}

.card-content {
    padding: 0 25px 25px 25px;
}

/* Formulaire */
.form-layout {
    max-width: 100%;
}

.form-section {
    margin-bottom: 35px;
    padding: 20px;
    background: rgba(13, 71, 161, 0.02);
    border-radius: 8px;
    border-left: 4px solid #0d47a1;
}

.form-section h3 {
    color: #0d47a1;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-section h3::before {
    content: '';
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    border-radius: 50%;
    display: inline-block;
}

.form-section p {
    color: #64748b;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.form-section p strong {
    color: #0d47a1;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #ffffff;
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

/* Actions du formulaire */
.form-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(13, 71, 161, 0.1);
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    min-width: 200px;
}

.btn-primary {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1565c0, #0d47a1);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 71, 161, 0.4);
}

.btn-secondary {
    background-color: #ffffff;
    color: #0d47a1;
    border: 2px solid #0d47a1;
}

.btn-secondary:hover {
    background-color: #0d47a1;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 71, 161, 0.3);
}

/* Tableau */
.table-spaced {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 0 1px rgba(13, 71, 161, 0.1);
}

.table-spaced th {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-spaced td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 0.95rem;
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
    
    .card-header,
    .card-content {
        padding: 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .table-spaced {
        font-size: 0.85rem;
    }
    
    .table-spaced th,
    .table-spaced td {
        padding: 10px 12px;
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

.card:nth-child(2) {
    animation-delay: 0.1s;
}

.card:nth-child(3) {
    animation-delay: 0.2s;
}

/* Styles pour les champs obligatoires */
.form-group input[required] {
    border-left: 4px solid #0d47a1;
}

.form-group input[required]:focus {
    border-left-color: #1565c0;
}

/* Amélioration visuelle des placeholders */
.form-group input::placeholder {
    color: #9ca3af;
    font-style: italic;
}

/* Style pour les sections de formulaire */
.form-section:nth-child(1) {
    border-left-color: #0d47a1;
}

.form-section:nth-child(2) {
    border-left-color: #1565c0;
}
</style>
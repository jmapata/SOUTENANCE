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
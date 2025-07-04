<?php
// /conformite_liste.php
require_once __DIR__ . '/includes/header.php';

// Vous devriez créer un traitement spécifique pour cette page, par exemple 'TRAIT_CONFORMITE_LISTER'
// if (!checkPermission('TRAIT_CONFORMITE_LISTER')) { die("Accès non autorisé."); }

// Récupérer les rapports à l'état "SOUMIS"
$stmt = $p_pdo->query(
    "SELECT r.id_rapport_etudiant, r.libelle_rapport_etudiant, r.date_soumission, e.nom, e.prenom
     FROM rapport_etudiant r
     JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
     WHERE r.id_statut_rapport = 'SOUMIS'
     ORDER BY r.date_soumission ASC"
);
$rapports_a_verifier = $stmt->fetchAll();
?>

<h2>Rapports en attente de vérification de conformité</h2>
<p>Liste des mémoires soumis par les étudiants et attendant une validation administrative.</p>

<?php if (isset($_GET['success'])): ?>
    <p class="success"><?php echo htmlspecialchars($_GET['success']); ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Date de soumission</th>
            <th>Étudiant</th>
            <th>Titre du rapport</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rapports_a_verifier)): ?>
            <tr>
                <td colspan="4">Aucun rapport en attente de vérification.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($rapports_a_verifier as $rapport): 
            // Alerte si le rapport est en attente depuis plus de 5 jours (RG11)
            $date_soumission = new DateTime($rapport['date_soumission']);
            $aujourdhui = new DateTime();
            $diff = $aujourdhui->diff($date_soumission)->days;
            $class_alerte = $diff > 5 ? 'alerte-delai' : '';
        ?>
        <tr class="<?php echo $class_alerte; ?>">
            <td><?php echo $date_soumission->format('d/m/Y H:i'); ?> <?php if($class_alerte) echo '(Délai dépassé !)'; ?></td>
            <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
            <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
            <td>
                <a href="conformite_verifier.php?id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn">Vérifier</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
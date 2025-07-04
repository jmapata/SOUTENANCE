<?php
// /dashboard_etudiant.php
require_once __DIR__ . '/includes/header.php';

// Sécuriser la page pour les étudiants uniquement
if (!checkPermission('TRAIT_ETUDIANT_DASHBOARD_ACCEDER')) {
    echo "<p>Accès non autorisé.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// Récupérer les informations du rapport de l'étudiant
$stmt = $p_pdo->prepare(
    "SELECT r.*, sr.libelle_statut_rapport 
     FROM rapport_etudiant r
     JOIN statut_rapport_ref sr ON r.id_statut_rapport = sr.id_statut_rapport
     WHERE r.numero_carte_etudiant = :num_etu
     ORDER BY r.date_derniere_modif DESC LIMIT 1"
);
$stmt->execute(['num_etu' => $_SESSION['user_id']]); // L'ID utilisateur est le numéro de carte étudiant
$rapport = $stmt->fetch();
?>

<h2>Tableau de Bord Étudiant</h2>

<div class="dashboard-widget">
    <h3>Statut de votre rapport</h3>
    <?php if ($rapport): ?>
        <p><strong>Titre :</strong> <?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></p>
        <p><strong>Statut actuel :</strong> <span class="status-<?php echo strtolower($rapport['id_statut_rapport']); ?>"><?php echo htmlspecialchars($rapport['libelle_statut_rapport']); ?></span></p>
        <p>Dernière modification : <?php echo (new DateTime($rapport['date_derniere_modif']))->format('d/m/Y H:i'); ?></p>
        <a href="/suivi_rapport.php">Voir le suivi détaillé</a>
    <?php else: ?>
        <p>Vous n'avez pas encore soumis de rapport.</p>
        <a href="/soumission_rapport.php">Soumettre mon rapport</a>
    <?php endif; ?>
</div>

<div class="dashboard-widget">
    <h3>Actions rapides</h3>
    <ul>
        <li><a href="/soumission_rapport.php">Soumettre ou modifier mon rapport</a></li>
        <li><a href="/profil_etudiant.php">Gérer mon profil</a></li>
        <li><a href="/mes_documents.php">Consulter mes documents officiels</a></li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
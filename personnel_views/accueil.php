<?php
require_once 'config/database.php';

// --- 1. RÉCUPÉRATION DES STATISTIQUES CLÉS ---
// Nombre d'étudiants en attente d'activation de compte (inscrits mais sans compte utilisateur)
$etudiants_a_activer = $pdo->query(
    "SELECT COUNT(*) FROM etudiant e 
     JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant 
     WHERE e.numero_utilisateur IS NULL AND i.id_statut_paiement = 'PAY_SOLDE'"
)->fetchColumn();

// Nombre total d'étudiants inscrits cette année
$total_inscrits_annee = $pdo->query("SELECT COUNT(*) FROM inscrire WHERE id_annee_academique = 'ANNEE-2025-2026'")->fetchColumn(); // Année à rendre dynamique

// Nombre de réclamations à traiter
$reclamations_en_attente = $pdo->query("SELECT COUNT(*) FROM reclamation WHERE id_statut_reclamation = 'RECLAM_RECUE'")->fetchColumn();


// --- 2. RÉCUPÉRATION DE LA LISTE DES ÉTUDIANTS À ACTIVER ---
$liste_etudiants_a_activer = $pdo->query(
    "SELECT e.numero_carte_etudiant, e.nom, e.prenom, i.date_inscription
     FROM etudiant e
     JOIN inscrire i ON e.numero_carte_etudiant = i.numero_carte_etudiant
     WHERE e.numero_utilisateur IS NULL AND i.id_statut_paiement = 'PAY_SOLDE'
     ORDER BY i.date_inscription ASC
     LIMIT 5"
)->fetchAll();

?>

<style>
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    .stat-card {
        background-color: #ffffff; border-radius: 12px; padding: 24px;
        display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .stat-card .icon-container {
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
    }
    .stat-card .info h3 { font-size: 2rem; font-weight: 700; color: #1e293b; }
    .stat-card .info p { font-size: 1rem; color: #64748b; }

    .card { background-color: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
    .card-title { font-size: 1.2rem; font-weight: 600; color: #32325d; margin-bottom: 20px; }
    .table-container { margin-top: 2rem; }
</style>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="icon-container" style="background-color: #e0e7ff; color: #4338ca;">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="info">
            <h3><?php echo $etudiants_a_activer; ?></h3>
            <p>Comptes à Activer</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon-container" style="background-color: #dcfce7; color: #166534;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="info">
            <h3><?php echo $total_inscrits_annee; ?></h3>
            <p>Étudiants Inscrits</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon-container" style="background-color: #ffedd5; color: #9a3412;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="info">
            <h3><?php echo $reclamations_en_attente; ?></h3>
            <p>Réclamations à Traiter</p>
        </div>
    </div>
</div>

<div class="card table-container">
    <h2 class="card-title">Activation de Comptes Prioritaires</h2>
    <p>Liste des étudiants ayant finalisé leur inscription et en attente d'activation de leur compte.</p>
    <table class="table-spaced" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date d'inscription</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($liste_etudiants_a_activer)): ?>
                <tr><td colspan="4" style="text-align: center; padding: 20px;">Aucun compte à activer pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($liste_etudiants_a_activer as $etudiant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($etudiant['nom']); ?></td>
                    <td><?php echo htmlspecialchars($etudiant['prenom']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($etudiant['date_inscription'])); ?></td>
                    <td>
                        <a href="dashboard_gestion_scolarite.php?page=activer_comptes&etudiant_id=<?php echo $etudiant['numero_carte_etudiant']; ?>" class="btn btn-sm btn-primary">Activer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div style="text-align: right; margin-top: 20px;">
        <a href="dashboard_gestion_scolarite.php?page=activer_comptes" class="btn btn-secondary">Voir tous les étudiants à activer</a>
    </div>
</div>
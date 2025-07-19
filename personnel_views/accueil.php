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
    /* CSS pour la page d'accueil */
body {
    background-color: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 0;
    padding: 20px;
    color: #2c3e50;
    line-height: 1.6;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.stat-card {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 20px rgba(13, 71, 161, 0.08);
    border: 1px solid rgba(13, 71, 161, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0d47a1, #1565c0);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(13, 71, 161, 0.12);
}

.stat-card .icon-container {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
}

.stat-card .info h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #0d47a1;
    margin: 0 0 5px 0;
}

.stat-card .info p {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
    font-weight: 500;
}

.card {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 20px rgba(13, 71, 161, 0.08);
    border: 1px solid rgba(13, 71, 161, 0.1);
    position: relative;
    overflow: hidden;
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

.card-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #0d47a1;
    margin-bottom: 20px;
}

.table-container {
    margin-top: 2rem;
}

.table-container p {
    color: #64748b;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.table-spaced {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.table-spaced th {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-spaced th:first-child {
    border-top-left-radius: 8px;
}

.table-spaced th:last-child {
    border-top-right-radius: 8px;
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
}

.btn-primary {
    background: linear-gradient(135deg, #0d47a1, #1565c0);
    color: #ffffff;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1565c0, #0d47a1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 71, 161, 0.3);
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
    box-shadow: 0 4px 12px rgba(13, 71, 161, 0.3);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

/* Responsive design */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .card {
        padding: 20px;
    }
    
    .table-spaced {
        font-size: 0.85rem;
    }
    
    .table-spaced th,
    .table-spaced td {
        padding: 10px 12px;
    }
}

/* Animation pour les cartes */
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

.stat-card,
.card {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(2) {
    animation-delay: 0.1s;
}

.stat-card:nth-child(3) {
    animation-delay: 0.2s;
}

.table-container {
    animation-delay: 0.3s;
}
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
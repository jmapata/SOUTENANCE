<?php
// conformite_views/rapports_a_verifier.php

// On récupère tous les rapports avec le statut 'Soumis'
$stmt = $pdo->prepare("
    SELECT 
        r.id_rapport_etudiant, 
        r.libelle_rapport_etudiant, 
        r.date_soumission, 
        e.nom, 
        e.prenom
    FROM rapport_etudiant r
    JOIN etudiant e ON r.numero_carte_etudiant = e.numero_carte_etudiant
    WHERE r.id_statut_rapport = 'RAP_SOUMIS'
    ORDER BY r.date_soumission ASC
");
$stmt->execute();
$rapports_a_verifier = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fa-solid fa-file-circle-question"></i> Rapports en attente de vérification</h2>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre du Rapport</th>
                <th>Étudiant</th>
                <th>Date de soumission</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rapports_a_verifier)): ?>
                <tr><td colspan="4" style="text-align:center;">Aucun rapport à vérifier pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($rapports_a_verifier as $rapport): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rapport['libelle_rapport_etudiant']); ?></td>
                        <td><?php echo htmlspecialchars($rapport['prenom'] . ' ' . $rapport['nom']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($rapport['date_soumission'])); ?></td>
                        <td>
                            <a href="dashboard_conformite.php?page=verifier_un_rapport&id=<?php echo $rapport['id_rapport_etudiant']; ?>" class="btn btn-primary">
                                <i class="fa-solid fa-search"></i> Vérifier
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    <style>
    :root {
        --primary-color: #7c3aed;
        --primary-light: #a855f7;
        --primary-dark: #5b21b6;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --card-bg: #ffffff;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --text-muted: #9ca3af;
        --border-color: #e5e7eb;
        --background: #f9fafb;
        --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
        background: var(--background);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--text-dark);
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        padding: 2rem 2rem 2.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .page-header h2 {
        font-size: 1.875rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        position: relative;
        z-index: 1;
    }

    .page-header h2 i {
        font-size: 1.5rem;
        opacity: 0.9;
    }

    .table-container {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 0;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
        overflow: hidden;
        position: relative;
    }

    .table-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        background: transparent;
    }

    thead {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        position: relative;
    }

    thead::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        opacity: 0.3;
    }

    th {
        padding: 1.25rem 1.5rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: none;
        position: relative;
    }

    th:first-child {
        padding-left: 2rem;
    }

    th:last-child {
        padding-right: 2rem;
    }

    tbody tr {
        transition: all 0.2s ease;
        position: relative;
    }

    tbody tr:hover {
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.02) 0%, rgba(168, 85, 247, 0.01) 100%);
        transform: translateX(2px);
    }

    tbody tr:hover::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        border-radius: 0 2px 2px 0;
    }

    td {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.95rem;
        color: var(--text-dark);
        vertical-align: middle;
    }

    td:first-child {
        padding-left: 2rem;
        font-weight: 500;
    }

    td:last-child {
        padding-right: 2rem;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    /* Message d'état vide */
    tbody tr td[colspan="4"] {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--text-muted);
        font-style: italic;
        font-size: 1.1rem;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.02) 0%, rgba(168, 85, 247, 0.01) 100%);
        border-radius: 12px;
        margin: 1rem;
        position: relative;
    }

    tbody tr td[colspan="4"]::before {
        content: '🎉';
        display: block;
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.7;
    }

    /* Boutons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        white-space: nowrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(124, 58, 237, 0.3);
        text-decoration: none;
        color: white;
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn i {
        font-size: 0.875rem;
    }

    /* Styles pour les données du tableau */
    td:nth-child(1) {
        font-weight: 600;
        color: var(--text-dark);
    }

    td:nth-child(2) {
        color: var(--text-light);
        font-weight: 500;
    }

    td:nth-child(3) {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-family: 'Monaco', 'Menlo', monospace;
    }

    /* Badge de statut (si nécessaire) */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
        color: var(--warning-color);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    /* Responsive design */
    @media (max-width: 1024px) {
        .page-header {
            padding: 1.5rem;
        }
        
        .page-header h2 {
            font-size: 1.5rem;
        }
        
        th, td {
            padding: 1rem;
        }
        
        td:first-child, th:first-child {
            padding-left: 1.5rem;
        }
        
        td:last-child, th:last-child {
            padding-right: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .page-header h2 {
            font-size: 1.25rem;
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }
        
        .table-container {
            margin: 0 -1rem;
            border-radius: 12px;
        }
        
        table {
            font-size: 0.875rem;
        }
        
        th, td {
            padding: 0.75rem 0.5rem;
        }
        
        td:first-child, th:first-child {
            padding-left: 1rem;
        }
        
        td:last-child, th:last-child {
            padding-right: 1rem;
        }
        
        .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .btn span {
            display: none;
        }
    }

    /* Animation d'apparition */
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

    .table-container {
        animation: fadeInUp 0.5s ease;
    }

    tbody tr {
        animation: fadeInUp 0.3s ease;
        animation-fill-mode: both;
    }

    tbody tr:nth-child(1) { animation-delay: 0.1s; }
    tbody tr:nth-child(2) { animation-delay: 0.2s; }
    tbody tr:nth-child(3) { animation-delay: 0.3s; }
    tbody tr:nth-child(4) { animation-delay: 0.4s; }
    tbody tr:nth-child(5) { animation-delay: 0.5s; }
</style>
</style>
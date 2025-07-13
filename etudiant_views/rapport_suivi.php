<?php
// etudiant_views/rapport_suivi.php

// Récupérer le rapport le plus récent de l'étudiant qui n'est pas un brouillon
$stmt = $pdo->prepare("
    SELECT r.*, s.libelle_statut_rapport, s.etape_workflow
    FROM rapport_etudiant r
    JOIN statut_rapport_ref s ON r.id_statut_rapport = s.id_statut_rapport
    WHERE r.numero_carte_etudiant = (SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?)
    AND r.id_statut_rapport != 'RAP_BROUILLON'
    ORDER BY r.date_soumission DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['numero_utilisateur']]);
$rapport_actuel = $stmt->fetch();

// Récupérer toutes les étapes possibles du workflow
$etapes_workflow = $pdo->query("SELECT * FROM statut_rapport_ref WHERE etape_workflow IS NOT NULL ORDER BY etape_workflow")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fa-solid fa-route"></i> Suivi de votre Rapport</h2>
    <p>Visualisez en temps réel l'avancement de votre dernier rapport soumis.</p>
</div>

<?php if (!$rapport_actuel): ?>
    <div class="alert alert-info">Vous n'avez aucun rapport actuellement en cours de validation.</div>
<?php else: ?>
    <div class="suivi-container">
        <h3>Rapport : "<?php echo htmlspecialchars($rapport_actuel['libelle_rapport_etudiant']); ?>"</h3>
        <ul class="workflow-stepper">
            <?php foreach ($etapes_workflow as $etape): ?>
                <?php
                    $status_class = '';
                    if ($rapport_actuel['etape_workflow'] > $etape['etape_workflow']) {
                        $status_class = 'completed'; // Étape terminée
                    } elseif ($rapport_actuel['etape_workflow'] == $etape['etape_workflow']) {
                        $status_class = 'active'; // Étape actuelle
                    }
                ?>
                <li class="step <?php echo $status_class; ?>">
                    <div class="step-icon">
                        <?php if ($status_class === 'completed'): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label"><?php echo htmlspecialchars($etape['libelle_statut_rapport']); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<style>
    .suivi-container { background: #fff; padding: 30px; border-radius: 10px; }
    .suivi-container h3 { margin-top:0; }
    .workflow-stepper { list-style: none; padding: 0; display: flex; justify-content: space-between; position: relative; margin-top: 40px; }
    .workflow-stepper::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        height: 4px;
        background-color: #e0e0e0;
        z-index: 1;
    }
    .workflow-stepper .step {
        text-align: center;
        position: relative;
        z-index: 2;
        width: 120px; /* Largeur pour chaque étape */
    }
    .step-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #e0e0e0;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #fff;
    }
    .step-label { margin-top: 10px; font-size: 0.9rem; font-weight: 500; color: #777; }
    
    .step.active .step-icon { background-color: #007bff; }
    .step.active .step-label { color: #000; font-weight: bold; }

    .step.completed .step-icon { background-color: #28a745; }
    .step.completed .step-label { color: #555; }
</style>
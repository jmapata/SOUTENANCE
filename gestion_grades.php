<?php
// /gestion_grades.php
require_once __DIR__ . '/includes/header.php';
// if (!checkPermission('TRAIT_ADMIN_GERER_REFERENTIELS')) { die("Accès non autorisé."); }

$stmt = $p_pdo->query("SELECT * FROM grade ORDER BY libelle_grade");
$grades = $stmt->fetchAll();
?>

<h2>Gestion des Grades</h2>
<a href="grade_form.php" class="btn-add">Ajouter un grade</a>

<?php if (isset($_GET['success'])): ?>
    <p class="success"><?php echo htmlspecialchars($_GET['success']); ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Libellé</th>
            <th>Abréviation</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($grades as $grade): ?>
        <tr>
            <td><?php echo htmlspecialchars($grade['id_grade']); ?></td>
            <td><?php echo htmlspecialchars($grade['libelle_grade']); ?></td>
            <td><?php echo htmlspecialchars($grade['abreviation_grade']); ?></td>
            <td>
                <a href="grade_form.php?id=<?php echo $grade['id_grade']; ?>">Modifier</a>
                <a href="/traitement/grade_traitement.php?action=delete&id=<?php echo $grade['id_grade']; ?>" onclick="return confirm('Êtes-vous sûr ?');">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
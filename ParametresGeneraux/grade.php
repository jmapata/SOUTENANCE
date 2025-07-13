<?php
// On remonte de deux niveaux pour trouver le fichier de config
require_once __DIR__ . '/../../config/database.php';

$items = $pdo->query("SELECT * FROM grade ORDER BY libelle_grade")->fetchAll();
$ref_name = 'grade';
$id_column = 'id_grade';
$libelle_column = 'libelle_grade';
?>

<form action="traitement/parametres_traitement.php" method="POST" class="form-inline">
    <input type="hidden" name="ref_name" value="<?php echo $ref_name; ?>">
    <input type="hidden" name="action" value="ajouter">
    <input type="text" name="id_value" placeholder="ID (ex: MA)" required>
    <input type="text" name="libelle_value" placeholder="Libellé (ex: Maître Assistant)" required>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter</button>
</form>

<div class="table-responsive" style="margin-top: 20px;">
    <table class="table-spaced">
        <thead><tr><th>ID</th><th>Libellé</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item[$id_column]); ?></td>
                <td><?php echo htmlspecialchars($item[$libelle_column]); ?></td>
                <td class="table-actions">
                    <form action="traitement/parametres_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer ?');">
                        <input type="hidden" name="ref_name" value="<?php echo $ref_name; ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_value" value="<?php echo htmlspecialchars($item[$id_column]); ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
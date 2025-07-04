<?php
require_once 'config/database.php';
$types_utilisateur = $pdo->query("SELECT * FROM type_utilisateur ORDER BY id_type_utilisateur")->fetchAll();
$groupes_utilisateur = $pdo->query("SELECT * FROM groupe_utilisateur ORDER BY id_groupe_utilisateur")->fetchAll();
?>

<div class="page-header">
    <h1>Gestion des Habilitations</h1>
</div>

<div class="grid-layout">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Groupes d'Utilisateurs (Rôles)</h2></div>
        <div class="card-content">
            <form action="traitement/roles_traitement.php" method="POST" class="form-inline">
                <input type="hidden" name="action" value="ajouter_groupe">
                <input type="text" name="id_groupe" placeholder="ID (ex: GRP_COM)" required>
                <input type="text" name="libelle_groupe" placeholder="Libellé du groupe" required>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon"></i> Ajouter</button>
            </form>
            <table class="table-spaced">
                <thead><tr><th>ID</th><th>Libellé</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($groupes_utilisateur as $groupe): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?></td>
                        <td><?php echo htmlspecialchars($groupe['libelle_groupe_utilisateur']); ?></td>
                        <td class="table-actions">
                            <a href="dashboard_admin.php?page=modifier_groupe&id=<?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?>" class="btn btn-secondary"><i class="fa-solid fa-pen icon"></i></a>
                            <form action="traitement/roles_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer ?');">
                                <input type="hidden" name="action" value="supprimer_groupe">
                                <input type="hidden" name="id_groupe" value="<?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?>">
                                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash icon"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Types d'Utilisateurs</h2></div>
        <div class="card-content">
             <form action="traitement/roles_traitement.php" method="POST" class="form-inline">
                <input type="hidden" name="action" value="ajouter_type">
                <input type="text" name="id_type" placeholder="ID (ex: TYPE_PERSO)" required>
                <input type="text" name="libelle_type" placeholder="Libellé du type" required>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon"></i> Ajouter</button>
            </form>
            <table class="table-spaced">
                <thead><tr><th>ID</th><th>Libellé</th><th>Actions</th></tr></thead>
                <tbody>
                     <?php foreach ($types_utilisateur as $type): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($type['id_type_utilisateur']); ?></td>
                        <td><?php echo htmlspecialchars($type['libelle_type_utilisateur']); ?></td>
                        <td class="table-actions">
                            <a href="dashboard_admin.php?page=modifier_type&id=<?php echo htmlspecialchars($type['id_type_utilisateur']); ?>" class="btn btn-secondary"><i class="fa-solid fa-pen icon"></i></a>
                            <form action="traitement/roles_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer ?');">
                                <input type="hidden" name="action" value="supprimer_type">
                                <input type="hidden" name="id_type" value="<?php echo htmlspecialchars($type['id_type_utilisateur']); ?>">
                                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash icon"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
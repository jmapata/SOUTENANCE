<?php
require_once 'config/database.php';

// --- DÉTERMINER L'ACTION À FAIRE ---
$action = $_GET['action'] ?? 'lister';
$id_enseignant = $_GET['id'] ?? null;
$enseignant_a_modifier = null;

// --- LOGIQUE POUR LA RECHERCHE ---
$search_term = $_GET['search'] ?? '';
$sql_where = '';
$params = [];
if (!empty($search_term)) {
    $sql_where = "WHERE e.nom LIKE ? OR e.prenom LIKE ? OR u.login_utilisateur LIKE ?";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term, $like_term];
}

// --- LOGIQUE POUR PRÉPARER LA MODIFICATION ---
if ($action === 'modifier' && $id_enseignant) {
    $stmt = $pdo->prepare("SELECT e.*, u.login_utilisateur, u.email_principal, u.id_groupe_utilisateur 
                           FROM enseignant e
                           LEFT JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur
                           WHERE e.numero_enseignant = ?");
    $stmt->execute([$id_enseignant]);
    $enseignant_a_modifier = $stmt->fetch();
}

// --- RÉCUPÉRATION DES DONNÉES NÉCESSAIRES POUR LA PAGE ---
$stmt_enseignants = $pdo->prepare("SELECT e.numero_enseignant, e.numero_utilisateur, e.nom, e.prenom, u.login_utilisateur, u.statut_compte, g.libelle_groupe_utilisateur 
                                   FROM enseignant e 
                                   LEFT JOIN utilisateur u ON e.numero_utilisateur = u.numero_utilisateur 
                                   LEFT JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
                                   $sql_where
                                   ORDER BY e.nom, e.prenom");
$stmt_enseignants->execute($params);
$enseignants = $stmt_enseignants->fetchAll();
$groupes = $pdo->query("SELECT * FROM groupe_utilisateur WHERE id_groupe_utilisateur LIKE 'GRP_ENS%' OR id_groupe_utilisateur LIKE 'GRP_COMMISSION'")->fetchAll();
$types = $pdo->query("SELECT * FROM type_utilisateur WHERE id_type_utilisateur = 'TYPE_ENS'")->fetchAll();
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<?php if ($action === 'lister'): ?>
    <div class="page-header">
        <h1>Gestion des Enseignants</h1>
        <button id="open-add-modal" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter un Enseignant</button>
    </div>

    <div class="card search-card">
        <form action="dashboard_admin.php" method="GET">
            <input type="hidden" name="page" value="gestion_enseignants">
            <div class="search-box">
                <input type="text" name="search" placeholder="Rechercher par nom, prénom, login..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-content">
            <table class="table-spaced">
                <thead><tr><th>Nom</th><th>Prénom</th><th>Login</th><th>Groupe</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($enseignants)): ?>
                        <tr><td colspan="6" style="text-align: center;">Aucun résultat trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enseignants as $enseignant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enseignant['nom']); ?></td>
                            <td><?php echo htmlspecialchars($enseignant['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($enseignant['login_utilisateur'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($enseignant['libelle_groupe_utilisateur'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars(strtolower($enseignant['statut_compte'] ?? 'non-cree')); ?>"><?php echo htmlspecialchars($enseignant['statut_compte'] ?? 'Non créé'); ?></span>
                            </td>
                            <td class="table-actions">
                                <a href="dashboard_admin.php?page=gestion_enseignants&action=modifier&id=<?php echo $enseignant['numero_enseignant']; ?>" class="btn btn-sm btn-secondary" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                <?php if (isset($enseignant['statut_compte'])): ?>
                                    <form action="traitement/enseignant_traitement.php" method="POST">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($enseignant['numero_utilisateur']); ?>">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($enseignant['statut_compte']); ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $enseignant['statut_compte'] === 'actif' ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo $enseignant['statut_compte'] === 'actif' ? 'Désactiver' : 'Activer'; ?>"><i class="fa-solid <?php echo $enseignant['statut_compte'] === 'actif' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form action="traitement/enseignant_traitement.php" method="POST" onsubmit="return confirm('Vraiment supprimer ?');">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id_enseignant" value="<?php echo $enseignant['numero_enseignant']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'modifier' && $enseignant_a_modifier): ?>
    <div class="page-header"><h1>Modifier : <?php echo htmlspecialchars($enseignant_a_modifier['prenom'] . ' ' . $enseignant_a_modifier['nom']); ?></h1></div>
    <div class="card">
        <div class="card-content">
            <form action="traitement/enseignant_traitement.php" method="POST" class="form-layout">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id_enseignant" value="<?php echo htmlspecialchars($enseignant_a_modifier['numero_enseignant']); ?>">
                <div class="form-section">
                    <h3>Informations Personnelles</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="nom">Nom</label><input type="text" name="nom" value="<?php echo htmlspecialchars($enseignant_a_modifier['nom']); ?>" required></div>
                        <div class="form-group"><label for="prenom">Prénom</label><input type="text" name="prenom" value="<?php echo htmlspecialchars($enseignant_a_modifier['prenom']); ?>" required></div>
                    </div>
                </div>
                <div class="form-section">
                    <h3>Informations du Compte</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="email_pro">Email</label><input type="email" name="email_pro" value="<?php echo htmlspecialchars($enseignant_a_modifier['email_principal']); ?>" required></div>
                        <div class="form-group"><label for="login">Login</label><input type="text" name="login" value="<?php echo htmlspecialchars($enseignant_a_modifier['login_utilisateur']); ?>" required></div>
                        <div class="form-group">
                            <label for="id_groupe">Groupe (Rôle)</label>
                            <select name="id_groupe" required>
                                <option value="">Choisir un groupe...</option>
                                <?php foreach ($groupes as $groupe): ?>
                                    <option value="<?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?>" <?php echo ($enseignant_a_modifier['id_groupe_utilisateur'] === $groupe['id_groupe_utilisateur']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($groupe['libelle_groupe_utilisateur']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="dashboard_admin.php?page=gestion_enseignants" class="btn">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="error-message">Enseignant non trouvé ou action invalide. <a href="dashboard_admin.php?page=gestion_enseignants">Retour à la liste</a></div>
<?php endif; ?>


<div id="add-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Ajouter un nouvel enseignant</h2>
            <button id="close-add-modal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="traitement/enseignant_traitement.php" method="POST" class="form-layout">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-section">
                    <h3>Informations Personnelles</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="nom">Nom</label><input type="text" name="nom" required></div>
                        <div class="form-group"><label for="prenom">Prénom</label><input type="text" name="prenom" required></div>
                    </div>
                </div>
                <div class="form-section">
                    <h3>Informations du Compte</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="email_pro">Email Professionnel</label><input type="email" name="email_pro" required></div>
                        <div class="form-group"><label for="login">Login</label><input type="text" name="login" required></div>
                        <div class="form-group">
                            <label for="id_groupe">Groupe (Rôle)</label>
                            <select name="id_groupe" required>
                                <option value="">Choisir un groupe...</option>
                                <?php foreach ($groupes as $groupe): ?><option value="<?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?>"><?php echo htmlspecialchars($groupe['libelle_groupe_utilisateur']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b;">Le mot de passe sera généré automatiquement.</p>
                <div class="form-actions"><button type="submit" class="btn btn-primary">Créer l'Enseignant</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('add-modal');
    const openBtn = document.getElementById('open-add-modal');
    const closeBtn = document.getElementById('close-add-modal');

    if (openBtn) {
        openBtn.addEventListener('click', function() { modal.classList.add('active'); });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function() { modal.classList.remove('active'); });
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }
})
</script>
<?php
// On inclut la connexion à la BDD une seule fois au début
require_once 'config/database.php';

// --- LOGIQUE DE RECHERCHE ---
$search_term = $_GET['search'] ?? '';
$sql_where_clause = '';
$params = [];
if (!empty($search_term)) {
    $sql_where_clause = "WHERE p.nom LIKE ? OR p.prenom LIKE ? OR u.email_principal LIKE ?";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term, $like_term];
}

// --- LOGIQUE D'AFFICHAGE (LISTE OU MODIFICATION) ---
$action = $_GET['action'] ?? 'lister';
$id_personnel = $_GET['id'] ?? null;
$personnel_a_modifier = null;

// Si l'action est de modifier, on récupère les données de la personne
if ($action === 'modifier' && $id_personnel) {
    $stmt = $pdo->prepare("SELECT p.*, u.login_utilisateur, u.email_principal 
                           FROM personnel_administratif p
                           LEFT JOIN utilisateur u ON p.numero_utilisateur = u.numero_utilisateur
                           WHERE p.numero_personnel_administratif = ?");
    $stmt->execute([$id_personnel]);
    $personnel_a_modifier = $stmt->fetch();
}

// --- RÉCUPÉRATION DES DONNÉES NÉCESSAIRES ---
// La liste du personnel (potentiellement filtrée par la recherche)
$stmt_personnels = $pdo->prepare("SELECT p.numero_personnel_administratif, p.numero_utilisateur, p.nom, p.prenom, u.login_utilisateur, u.statut_compte, g.libelle_groupe_utilisateur 
                                 FROM personnel_administratif p
                                 LEFT JOIN utilisateur u ON p.numero_utilisateur = u.numero_utilisateur
                                 LEFT JOIN groupe_utilisateur g ON u.id_groupe_utilisateur = g.id_groupe_utilisateur
                                 $sql_where_clause
                                 ORDER BY p.nom, p.prenom");
$stmt_personnels->execute($params);
$personnels = $stmt_personnels->fetchAll();

// Les listes pour les menus déroulants du formulaire d'ajout
$groupes = $pdo->query("SELECT * FROM groupe_utilisateur")->fetchAll();
$types = $pdo->query("SELECT * FROM type_utilisateur WHERE id_type_utilisateur = 'TYPE_PERS_ADMIN'")->fetchAll();
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>


<?php if ($action === 'lister'): ?>
    <div class="page-header">
        <h1>Gestion du Personnel Administratif</h1>
        <button id="open-add-modal" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ajouter un Membre</button>
    </div>

    <div class="card search-card">
        <form action="dashboard_admin.php" method="GET">
            <input type="hidden" name="page" value="gestion_personnel">
            <div class="search-box">
                <input type="text" name="search" placeholder="Rechercher par nom, prénom, email..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-content">
            <table class="table-spaced">
                <thead><tr><th>Nom</th><th>Prénom</th><th>Login</th><th>Groupe</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($personnels)): ?>
                        <tr><td colspan="6" style="text-align: center;">Aucun résultat trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($personnels as $personnel): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($personnel['nom']); ?></td>
                            <td><?php echo htmlspecialchars($personnel['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($personnel['login_utilisateur'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($personnel['libelle_groupe_utilisateur'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($personnel['statut_compte']): ?>
                                    <span class="status-badge <?php echo htmlspecialchars(strtolower($personnel['statut_compte'])); ?>">
                                        <?php echo htmlspecialchars($personnel['statut_compte']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge">Non créé</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <a href="dashboard_admin.php?page=gestion_personnel&action=modifier&id=<?php echo $personnel['numero_personnel_administratif']; ?>" class="btn btn-sm btn-secondary" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                
                                <?php if (isset($personnel['statut_compte'])): ?>
                                    <form action="traitement/personnel_traitement.php" method="POST">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($personnel['numero_utilisateur']); ?>">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($personnel['statut_compte']); ?>">
                                        <?php if ($personnel['statut_compte'] === 'actif'): ?>
                                            <button type="submit" class="btn btn-sm btn-warning" title="Désactiver"><i class="fa-solid fa-user-slash"></i></button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-success" title="Activer"><i class="fa-solid fa-user-check"></i></button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>

                                <form action="traitement/personnel_traitement.php" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer ce membre ?');">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id_personnel" value="<?php echo htmlspecialchars($personnel['numero_personnel_administratif']); ?>">
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

<?php elseif ($action === 'modifier' && $personnel_a_modifier): ?>
    <div class="page-header">
        <h1>Modifier : <?php echo htmlspecialchars($personnel_a_modifier['prenom'] . ' ' . $personnel_a_modifier['nom']); ?></h1>
    </div>
    <div class="card">
        <div class="card-content">
            <form action="traitement/personnel_traitement.php" method="POST" class="form-layout">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id_personnel" value="<?php echo htmlspecialchars($personnel_a_modifier['numero_personnel_administratif']); ?>">
                <div class="form-grid">
                    <div class="form-group"><label for="nom">Nom</label><input type="text" name="nom" value="<?php echo htmlspecialchars($personnel_a_modifier['nom']); ?>" required></div>
                    <div class="form-group"><label for="prenom">Prénom</label><input type="text" name="prenom" value="<?php echo htmlspecialchars($personnel_a_modifier['prenom']); ?>" required></div>
                    <div class="form-group"><label for="email_pro">Email Professionnel</label><input type="email" name="email_pro" value="<?php echo htmlspecialchars($personnel_a_modifier['email_principal']); ?>" required></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="dashboard_admin.php?page=gestion_personnel" class="btn">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div id="add-personnel-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Ajouter un nouveau membre</h2>
            <button id="close-add-modal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="traitement/personnel_traitement.php" method="POST" class="form-layout">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-section">
                    <h3>Informations Personnelles</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="add-nom">Nom</label><input type="text" id="add-nom" name="nom" required></div>
                        <div class="form-group"><label for="add-prenom">Prénom</label><input type="text" id="add-prenom" name="prenom" required></div>
                    </div>
                </div>
                <div class="form-section">
                    <h3>Informations du Compte</h3>
                    <div class="form-grid">
                        <div class="form-group"><label for="add-email">Email</label><input type="email" id="add-email" name="email_pro" required></div>
                        <div class="form-group"><label for="add-login">Login</label><input type="text" id="add-login" name="login" required></div>
                        <div class="form-group">
                            <label for="add-groupe">Groupe (Rôle)</label>
                            <select id="add-groupe" name="id_groupe" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($groupes as $groupe): ?><option value="<?php echo htmlspecialchars($groupe['id_groupe_utilisateur']); ?>"><?php echo htmlspecialchars($groupe['libelle_groupe_utilisateur']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-type">Type d'utilisateur</label>
                            <select id="add-type" name="id_type" required>
                                <?php foreach ($types as $type): ?><option value="<?php echo htmlspecialchars($type['id_type_utilisateur']); ?>"><?php echo htmlspecialchars($type['libelle_type_utilisateur']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin-top: 15px;">Le mot de passe sera généré automatiquement.</p>
                <div class="form-actions"><button type="submit" class="btn btn-primary">Créer le Membre</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('add-personnel-modal');
    const openBtn = document.getElementById('open-add-modal');
    const closeBtn = document.getElementById('close-add-modal');

    if (openBtn) {
        openBtn.addEventListener('click', function() { modal.classList.add('active'); });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function() { modal.classList.remove('active'); });
    }
    if (modal) {
        modal.addEventListener('click', function(e) { if (e.target === modal) { modal.classList.remove('active'); } });
    }
});
</script>
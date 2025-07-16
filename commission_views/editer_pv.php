<?php
// On utilise un chemin relatif depuis ce fichier pour garantir que la connexion fonctionne
require_once __DIR__ . '/../config/database.php';

// --- 1. SÉCURITÉ ET RÉCUPÉRATION DES DONNÉES ---
if (!isset($_SESSION['loggedin']) || $_SESSION['user_group'] !== 'GRP_COMMISSION') {
    header('Location: ../login.php');
    exit();
}
$pv_id = $_GET['id'] ?? null;
if (!$pv_id) {
    echo "<div class='alert alert-danger'>Erreur : Aucun Procès-Verbal n'a été sélectionné.</div>";
    return;
}

// On récupère l'ID de l'utilisateur connecté depuis la session
$user_id_session = $_SESSION['numero_utilisateur'];

// On récupère les informations du PV
$stmt_pv = $pdo->prepare("SELECT * FROM compte_rendu WHERE id_compte_rendu = ?");
$stmt_pv->execute([$pv_id]);
$pv = $stmt_pv->fetch();

if (!$pv) {
    echo "<div class='alert alert-danger'>Erreur : PV introuvable.</div>";
    return;
}

// --- 2. LOGIQUE DE DROITS ---
// On compare l'ID du rédacteur du PV avec l'ID de l'utilisateur en session.
$is_redacteur = ($pv['id_redacteur'] == $user_id_session);
// L'utilisateur peut éditer SEULEMENT s'il est le rédacteur ET que le PV est un brouillon.
$can_edit = ($is_redacteur && $pv['id_statut_pv'] == 'PV_BROUILLON');

$contenu_pv = $pv['libelle_compte_rendu'];

// --- 3. LOGIQUE DE PRÉ-REMPLISSAGE ---
// Si le contenu du PV est vide, on génère le template HTML qui imite le document Word.
if (empty(trim(strip_tags($contenu_pv)))) {
    
    // Récupérer les données du rapport lié pour le template
    $stmt_rapport_info = $pdo->prepare("SELECT r.theme, e.nom, e.prenom FROM rapport_etudiant r WHERE r.id_rapport_etudiant = ?");
    $stmt_rapport_info->execute([$pv['id_rapport_etudiant']]);
    $rapport_info = $stmt_rapport_info->fetch();
    
    ob_start();
    ?>
    <div style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; line-height: 1.5;">
        <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td style="width: 50%; text-align: center; vertical-align: top; font-size: 11pt;">
                        <p style="margin:0;">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br>ET DE LA RECHERCHE SCIENTIFIQUE</p>
                        <img src="assets/images/UNIVT_CCD.jpg" alt="Logo UFHB" style="height: 70px; margin: 10px 0;">
                        <p style="margin:0; font-weight:bold;">UNIVERSITE FELIX HOUPHOUET BOIGNY</p>
                        <p style="margin:0;">UFR MATHEMATIQUES ET INFORMATIQUE</p>
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: top; font-size: 11pt;">
                        <p style="margin:0; font-weight:bold;">REPUBLIQUE DE COTE D'IVOIRE<br><em style="font-weight:normal;">Union - Discipline - Travail</em></p>
                        <img src="assets/images/EMB_CI.jpg" alt="Logo RCI" style="height: 70px; margin: 10px 0;">
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2 style="text-align: center; margin: 30px 0; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0;">Procès-Verbal de séance de validation de thèmes</h2>
        
        <p>Dans le bureau du Professeur, le <?php echo date('d F Y'); ?>, s'est tenue une séance de validation de thèmes des étudiants en fin de cycle de la filière MIAGE-GI.</p>
        
        <h3 style="margin-top: 20px; font-weight: bold; text-decoration: underline;">Informations</h3>
        <p>[Rédigez ici les informations générales sur la session...]</p>
        
        <h3 style="margin-top: 20px; font-weight: bold; text-decoration: underline;">Validation du thème</h3>
        <div style="margin-top: 15px;">
            <p><strong>Étudiant :</strong> <?php echo htmlspecialchars($rapport_info['prenom'] . ' ' . $rapport_info['nom']); ?></p>
            <p><strong>Thème :</strong> <?php echo htmlspecialchars($rapport_info['theme']); ?></p>
            <p><strong>Recommandations de la commission :</strong></p>
            <ul><li>&nbsp;</li></ul>
        </div>
        
        <h3 style="margin-top: 20px; font-weight: bold; text-decoration: underline;">Divers</h3>
        <p>&nbsp;</p>
    </div>
    <?php
    $contenu_pv = ob_get_clean();
}
?>

<header class="header">
    <div class="header-left">
        <h1 class="header-title"><?php echo $can_edit ? 'Rédaction' : 'Consultation'; ?> du Procès-Verbal</h1>
    </div>
    <div class="header-right">
        <a href="dashboard_commission.php?page=gestion_pv" class="btn btn-secondary">&larr; Retour à la liste</a>
    </div>
</header>

<div class="pv-editor-container">
    <?php if ($can_edit): ?>
        <form id="pvForm" action="traitement/enregistrer_pv.php" method="POST">
            <input type="hidden" name="pv_id" value="<?php echo htmlspecialchars($pv_id); ?>">
            <textarea id="pv-editor" name="contenu_pv"><?php echo $contenu_pv; ?></textarea>
            <div class="form-actions">
                <button type="submit" name="action" value="sauvegarder_brouillon" class="btn btn-secondary">💾 Enregistrer le brouillon</button>
                <button type="submit" name="action" value="soumettre_validation" class="btn btn-primary">📤 Soumettre pour validation</button>
            </div>
        </form>
    <?php else: ?>
        <div class="pv-content-readonly">
            <?php echo $contenu_pv; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// --- BLOC DE VALIDATION (s'affiche si le PV est soumis ET que vous n'êtes pas le rédacteur) ---
if ($pv['id_statut_pv'] == 'PV_SOUMIS_VALID' && !$is_redacteur) {
    
    $stmt_ens = $pdo->prepare("SELECT numero_enseignant FROM enseignant WHERE numero_utilisateur = ?");
    $stmt_ens->execute([$user_id_session]);
    $numero_enseignant_connecte = $stmt_ens->fetchColumn();

    $stmt_check_vote = $pdo->prepare("SELECT id_decision_validation_pv FROM validation_pv WHERE id_compte_rendu = ? AND numero_enseignant = ?");
    $stmt_check_vote->execute([$pv_id, $numero_enseignant_connecte]);
    $a_deja_valide = $stmt_check_vote->fetch();
?>
    <div class="validation-section">
        <h3><i class="fa-solid fa-check-double"></i> Validation du Procès-Verbal</h3>
        <p>Le rédacteur a soumis ce PV pour approbation. Veuillez le relire et enregistrer votre décision.</p>
        
        <?php if (!$a_deja_valide): ?>
            <form action="traitement/approuver_pv.php" method="POST" class="vote-form">
                <input type="hidden" name="pv_id" value="<?php echo htmlspecialchars($pv_id); ?>">
                <div class="form-group">
                    <label><input type="radio" name="decision_validation" value="APPROB_PV_OUI" required> ✅ J’approuve ce PV</label>
                </div>
                <div class="form-group">
                    <label><input type="radio" name="decision_validation" value="APPROB_PV_NON"> ❌ Je demande une modification</label>
                </div>
                <textarea name="commentaire_validation" class="form-control" placeholder="Justification si vous demandez une modification..."></textarea>
                <button type="submit" class="btn btn-primary mt-2">Soumettre ma décision</button>
            </form>
        <?php else: ?>
            <p class="vote-confirmation"><i>Merci, vous avez déjà statué sur ce PV.</i></p>
        <?php endif; ?>
    </div>
<?php } ?>

<?php if ($can_edit): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let editorInstance;
        const form = document.getElementById('pvForm');
        const textarea = document.querySelector('#pv-editor');

        ClassicEditor.create(textarea).then(editor => {
            editorInstance = editor;
        }).catch(error => console.error(error));

        form.addEventListener('submit', function() {
            if (editorInstance) {
                textarea.value = editorInstance.getData();
            }
        });
    });
</script>
<?php endif; ?>

<style>
    .pv-editor-container, .validation-section {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        margin-top: 1.5rem;
    }
    .pv-content-readonly {
        border: 1px solid #e9ecef;
        padding: 2cm; /* Simule les marges A4 */
        min-height: 70vh;
        background-color: #f8f9fa;
    }
    .form-actions {
        text-align: right;
        margin-top: 20px;
    }
    .ck-editor__editable_inline {
        min-height: 70vh;
        border: 1px solid #ccc !important;
        padding: 2cm !important; /* Marges A4 */
    }
    .validation-section h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .vote-form { margin-top: 15px; }
</style>
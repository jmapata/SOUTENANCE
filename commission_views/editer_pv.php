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
   /* Reset et base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #343a40;
    line-height: 1.6;
    min-height: 100vh;
}

/* Header */
.header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 1.5rem 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left .header-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.header-right .btn {
    background: #6c757d;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.15);
}

.header-right .btn:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.25);
}

/* Conteneur principal */
.pv-editor-container, .validation-section {
    background: white;
    margin: 2rem auto;
    max-width: 1200px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.pv-editor-container {
    padding: 0;
}

/* Contenu en lecture seule */
.pv-content-readonly {
    padding: 3rem;
    background: #fdfdfd;
    border: 1px solid #e9ecef;
    min-height: 70vh;
    margin: 2rem;
    border-radius: 8px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Éditeur CK */
.ck-editor__editable_inline {
    min-height: 70vh !important;
    border: none !important;
    padding: 3rem !important;
    background: #fdfdfd !important;
    font-family: 'Times New Roman', Times, serif !important;
}

.ck-editor {
    border: 1px solid #dee2e6 !important;
    border-radius: 0 0 8px 8px !important;
}

.ck-toolbar {
    border: 1px solid #dee2e6 !important;
    border-bottom: none !important;
    background: linear-gradient(to bottom, #ffffff, #f8f9fa) !important;
    border-radius: 8px 8px 0 0 !important;
}

/* Actions du formulaire */
.form-actions {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    padding: 1.5rem 2rem;
    text-align: right;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.2);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    color: #495057;
    border: 1px solid #ced4da;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #dee2e6 0%, #ced4da 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Section de validation */
.validation-section {
    padding: 2rem;
    margin: 2rem auto;
    max-width: 1200px;
}

.validation-section h3 {
    margin: 0 0 1.5rem 0;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
    font-size: 1.4rem;
    color: #495057;
    font-weight: 600;
}

.validation-section p {
    margin-bottom: 1.5rem;
    color: #6c757d;
    font-size: 1rem;
}

/* Formulaire de vote */
.vote-form {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    margin-top: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.form-group label:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.form-group input[type="radio"] {
    margin-right: 0.75rem;
    transform: scale(1.2);
}

.form-group input[type="radio"]:checked + span,
.form-group label:has(input[type="radio"]:checked) {
    background: #e9ecef;
    border-color: #6c757d;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.95rem;
    background: white;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    margin-top: 1rem;
    resize: vertical;
    min-height: 100px;
}

.form-control:focus {
    border-color: #6c757d;
    box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.1);
    outline: none;
}

.form-control::placeholder {
    color: #adb5bd;
    font-style: italic;
}

/* Confirmation de vote */
.vote-confirmation {
    background: #e9ecef;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
    color: #495057;
    font-style: italic;
    margin-top: 1rem;
}

/* Alertes */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin: 1rem auto;
    max-width: 1200px;
    font-weight: 500;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Utilitaires */
.mt-2 {
    margin-top: 0.5rem;
}

/* Icônes */
.fa-solid, .fa-check-double {
    margin-right: 0.5rem;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .header-left .header-title {
        font-size: 1.5rem;
    }
    
    .pv-editor-container, 
    .validation-section {
        margin: 1rem;
        border-radius: 8px;
    }
    
    .pv-content-readonly {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .ck-editor__editable_inline {
        padding: 1.5rem !important;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .vote-form {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.625rem 1.25rem;
        font-size: 0.9rem;
    }
    
    .pv-content-readonly,
    .ck-editor__editable_inline {
        padding: 1rem !important;
    }
}
</style>
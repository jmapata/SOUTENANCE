<?php
// etudiant_views/mon_stage.php
// Assurez-vous que $pdo est disponible depuis le dashboard principal.

// --- 1. Récupérer le numero_carte_etudiant de l'utilisateur connecté ---
$numero_utilisateur_connecte = $_SESSION['numero_utilisateur'] ?? null;
$numero_carte_etudiant = null;

if ($numero_utilisateur_connecte) {
    $stmt_etudiant_id = $pdo->prepare("SELECT numero_carte_etudiant FROM etudiant WHERE numero_utilisateur = ?");
    $stmt_etudiant_id->execute([$numero_utilisateur_connecte]);
    $numero_carte_etudiant = $stmt_etudiant_id->fetchColumn();
}

if (!$numero_carte_etudiant) {
    echo '<div class="alert alert-danger">Impossible de trouver les informations de l\'étudiant connecté.</div>';
    return;
}

// --- 2. Récupérer les informations du stage de l'étudiant (le plus récent) ---
$stage_info = null;
$stmt_stage = $pdo->prepare("
    SELECT fs.*, e.libelle_entreprise
    FROM faire_stage fs
    LEFT JOIN entreprise e ON fs.id_entreprise = e.id_entreprise
    WHERE fs.numero_carte_etudiant = ?
    ORDER BY fs.date_debut_stage DESC
    LIMIT 1
");
$stmt_stage->execute([$numero_carte_etudiant]);
$stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

// --- 3. Récupérer le document de preuve principal pour le stage (si un stage existe) ---
$proof_document = null;
if ($stage_info) {
    $stmt_proof_doc = $pdo->prepare("
        SELECT dg.*, tdr.libelle_type_document
        FROM document_genere dg
        JOIN type_document_ref tdr ON dg.id_type_document = tdr.id_type_document
        WHERE dg.id_entite_concernee = ? 
          AND dg.type_entite_concernee = 'etudiant_stage'
          AND dg.id_type_document = 'DOC_STAGE_PREUVE'
        ORDER BY dg.date_generation DESC
        LIMIT 1
    ");
    $stmt_proof_doc->execute([$numero_carte_etudiant]);
    $proof_document = $stmt_proof_doc->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Stage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .page-header h2 {
            color: #5e72e4;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-weight: 600;
        }

        .page-header p {
            color: #6c757d;
            font-size: 16px;
        }

        .main-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 40px;
            align-items: start;
        }

        .left-column {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .right-column {
            position: sticky;
            top: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #5e72e4, #4c63d2);
            color: white;
            padding: 20px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .card-header:hover::before {
            left: 100%;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .card-content {
            padding: 30px;
        }

        .form-layout {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-grid .form-group:last-child {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label::before {
            content: '';
            width: 3px;
            height: 16px;
            background: #5e72e4;
            border-radius: 2px;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #5e72e4;
            box-shadow: 0 0 0 4px rgba(94, 114, 228, 0.1);
            transform: translateY(-1px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            grid-column: 1 / -1;
        }

        input[type="file"] {
            padding: 12px;
            cursor: pointer;
            border: 2px dashed #5e72e4;
            background: rgba(94, 114, 228, 0.05);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        input[type="file"]:hover {
            background: rgba(94, 114, 228, 0.1);
            border-color: #4c63d2;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transition: all 0.3s ease;
            transform: translate(-50%, -50%);
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #5e72e4, #4c63d2);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4c63d2, #3b4ec7);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(94, 114, 228, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .summary-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .summary-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .summary-header h3 {
            color: #5e72e4;
            font-size: 22px;
            font-weight: 600;
        }

        .summary-header i {
            font-size: 24px;
            color: #5e72e4;
        }

        .info-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 18px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border-left: 4px solid #5e72e4;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-label::before {
            content: '•';
            color: #5e72e4;
            font-size: 16px;
        }

        .info-value {
            color: #333;
            font-size: 15px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            gap: 6px;
        }

        .status-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border-color: #bee5eb;
            color: #0c5460;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-color: #ffeaa7;
            color: #856404;
        }

        .text-muted {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .actions-grid .btn {
            font-size: 13px;
            padding: 10px 16px;
        }

        .no-stage-message {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .no-stage-message i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #dee2e6;
            opacity: 0.7;
        }

        .no-stage-message h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #495057;
        }

        .no-stage-message p {
            font-size: 16px;
            opacity: 0.8;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-container {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #5e72e4, #4c63d2);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-content {
            padding: 30px;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .right-column {
                position: static;
                order: -1;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .page-header {
                padding: 25px 20px;
            }

            .page-header h2 {
                font-size: 26px;
                flex-direction: column;
                gap: 10px;
            }

            .card-content {
                padding: 25px 20px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .modal-container {
                margin: 20px;
                width: calc(100% - 40px);
            }

            .modal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <!-- Colonne de gauche : Formulaires -->
        <div class="left-column">
            <!-- Formulaire de saisie des informations de stage -->
            <div class="card" id="stageInfoFormCard">
                <div class="card-header">
                    <h3><?php echo $stage_info ? 'Modifier mon Stage' : 'Nouveau Stage'; ?></h3>
                </div>
                <div class="card-content">
                    <form id="stageInfoForm" action="traitement/save_stage_info.php" method="POST" class="form-layout">
                        <input type="hidden" name="numero_carte_etudiant" value="<?php echo htmlspecialchars($numero_carte_etudiant); ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="libelle_entreprise">Nom de l'Entreprise</label>
                                <input type="text" id="libelle_entreprise" name="libelle_entreprise" 
                                       value="<?php echo htmlspecialchars($stage_info['libelle_entreprise'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_debut_stage">Date de Début</label>
                                <input type="date" id="date_debut_stage" name="date_debut_stage" 
                                       value="<?php echo htmlspecialchars($stage_info['date_debut_stage'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_fin_stage">Date de Fin (prévue)</label>
                                <input type="date" id="date_fin_stage" name="date_fin_stage" 
                                       value="<?php echo htmlspecialchars($stage_info['date_fin_stage'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="nom_tuteur_entreprise">Tuteur en Entreprise</label>
                                <input type="text" id="nom_tuteur_entreprise" name="nom_tuteur_entreprise" 
                                       value="<?php echo htmlspecialchars($stage_info['nom_tuteur_entreprise'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="sujet_stage">Sujet du Stage</label>
                                <textarea id="sujet_stage" name="sujet_stage" required><?php echo htmlspecialchars($stage_info['sujet_stage'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                    <div id="stageInfoMessage"></div>
                </div>
            </div>

            <!-- Section Téléversement du document de preuve -->
            <?php if ($stage_info && !$proof_document): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Document de Preuve</h3>
                </div>
                <div class="card-content">
                    <form id="uploadProofForm" action="traitement/upload_stage_document.php" method="POST" enctype="multipart/form-data" class="form-layout">
                        <input type="hidden" name="numero_carte_etudiant" value="<?php echo htmlspecialchars($numero_carte_etudiant); ?>">
                        <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($numero_utilisateur_connecte); ?>">
                        <input type="hidden" name="document_type" value="DOC_STAGE_PREUVE">
                        
                        <div class="form-group">
                            <label for="document_file">Fichier de Preuve</label>
                            <input type="file" id="document_file" name="document_file" accept=".pdf,.doc,.docx" required>
                            <p class="text-muted">PDF, DOC, DOCX - Max 5 Mo</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-upload"></i> Téléverser
                            </button>
                        </div>
                    </form>
                    <div id="uploadMessage"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne de droite : Affichage/Récapitulatif -->
        <div class="right-column">
            <?php if ($stage_info): ?>
            <div class="summary-section">
                <div class="summary-header">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h3>Récapitulatif</h3>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Entreprise</div>
                        <div class="info-value"><?php echo htmlspecialchars($stage_info['libelle_entreprise']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Période</div>
                        <div class="info-value">
                            Du <?php echo date('d/m/Y', strtotime($stage_info['date_debut_stage'])); ?> 
                            au <?php echo $stage_info['date_fin_stage'] ? date('d/m/Y', strtotime($stage_info['date_fin_stage'])) : 'Non définie'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Tuteur</div>
                        <div class="info-value"><?php echo htmlspecialchars($stage_info['nom_tuteur_entreprise']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Sujet</div>
                        <div class="info-value"><?php echo htmlspecialchars($stage_info['sujet_stage']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Document de Preuve</div>
                        <div class="info-value">
                            <?php if ($proof_document): ?>
                                <span class="status-badge status-success">
                                    <i class="fa-solid fa-check"></i> Téléversé
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-warning">
                                    <i class="fa-solid fa-clock"></i> En attente
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="actions-grid">
                    <button type="button" class="btn btn-secondary" id="modifyStageBtn">
                        <i class="fa-solid fa-pen"></i> Modifier
                    </button>
                    
                    <?php if ($proof_document): ?>
                    <a href="<?php echo htmlspecialchars($proof_document['chemin_fichier']); ?>" target="_blank" class="btn btn-info">
                        <i class="fa-solid fa-eye"></i> Voir Doc
                    </a>
                    <button type="button" class="btn btn-warning" id="replaceDocBtn">
                        <i class="fa-solid fa-sync-alt"></i> Remplacer
                    </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-danger" id="deleteStageBtn">
                        <i class="fa-solid fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="summary-section">
                <div class="no-stage-message">
                    <i class="fa-solid fa-briefcase"></i>
                    <h3>Aucun Stage Enregistré</h3>
                    <p>Commencez par remplir le formulaire pour ajouter les informations de votre stage.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de remplacement de document -->
<div id="replaceDocModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Remplacer le Document</h2>
            <button id="closeModal" class="close-modal">&times;</button>
        </div>
        <div class="modal-content">
            <form id="replaceDocForm" action="traitement/upload_stage_document.php" method="POST" enctype="multipart/form-data" class="form-layout">
                <input type="hidden" name="numero_carte_etudiant" value="<?php echo htmlspecialchars($numero_carte_etudiant); ?>">
                <input type="hidden" name="numero_utilisateur" value="<?php echo htmlspecialchars($numero_utilisateur_connecte); ?>">
                <input type="hidden" name="document_id_to_replace" value="<?php echo htmlspecialchars($proof_document['id_document_genere'] ?? ''); ?>">
                <input type="hidden" name="document_type" value="DOC_STAGE_PREUVE">
                
                <div class="form-group">
                    <label for="new_document_file">Nouveau Fichier</label>
                    <input type="file" id="new_document_file" name="document_file" accept=".pdf,.doc,.docx" required>
                    <p class="text-muted">PDF, DOC, DOCX - Max 5 Mo</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-upload"></i> Remplacer
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelReplace">
                        Annuler
                    </button>
                </div>
            </form>
            <div id="replaceMessage"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stageInfoForm = document.getElementById('stageInfoForm');
    const uploadProofForm = document.getElementById('uploadProofForm');
    const replaceDocForm = document.getElementById('replaceDocForm');
    const modifyStageBtn = document.getElementById('modifyStageBtn');
    const replaceDocBtn = document.getElementById('replaceDocBtn');
    const deleteStageBtn = document.getElementById('deleteStageBtn');
    const replaceDocModal = document.getElementById('replaceDocModal');
    const closeModal = document.getElementById('closeModal');
    const cancelReplace = document.getElementById('cancelReplace');

    function showMessage(elementId, type, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<div class="alert alert-${type}"><i class="fa-solid fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i> ${message}</div>`;
        }
    }

    function openModal() {
        replaceDocModal.style.display = 'flex';
    }

    function closeModalFunc() {
        replaceDocModal.style.display = 'none';
        replaceDocForm.reset();
        document.getElementById('replaceMessage').innerHTML = '';
    }

    // Gestion du formulaire d'informations de stage
    if (stageInfoForm) {
        stageInfoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            showMessage('stageInfoMessage', 'info', 'Enregistrement des informations...');

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('stageInfoMessage', 'success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('stageInfoMessage', 'danger', data.message);
                }
            })
            .catch(error => {
                showMessage('stageInfoMessage', 'danger', 'Erreur de communication. Veuillez réessayer.');
            });
        });
    }

    // Gestion du téléversement de preuve
    if (uploadProofForm) {
        uploadProofForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            showMessage('uploadMessage', 'info', 'Téléversement en cours...');

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('uploadMessage', 'success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('uploadMessage', 'danger', data.message);
                }
            })
            .catch(error => {
                showMessage('uploadMessage', 'danger', 'Erreur de communication. Veuillez réessayer.');
            });
        });
    }

    // Gestion du remplacement de document
    if (replaceDocForm) {
        replaceDocForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            showMessage('replaceMessage', 'info', 'Remplacement en cours...');

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('replaceMessage', 'success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('replaceMessage', 'danger', data.message);
                }
            })
            .catch(error => {
                showMessage('replaceMessage', 'danger', 'Erreur de communication. Veuillez réessayer.');
            });
        });
    }

    // Bouton modifier stage
    if (modifyStageBtn) {
        modifyStageBtn.addEventListener('click', function() {
            document.getElementById('stageInfoFormCard').scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Bouton remplacer document
    if (replaceDocBtn) {
        replaceDocBtn.addEventListener('click', openModal);
    }

    // Fermeture du modal
    if (closeModal) {
        closeModal.addEventListener('click', closeModalFunc);
    }
    if (cancelReplace) {
        cancelReplace.addEventListener('click', closeModalFunc);
    }
    if (replaceDocModal) {
        replaceDocModal.addEventListener('click', function(e) {
            if (e.target === replaceDocModal) {
                closeModalFunc();
            }
        });
    }

    // Bouton supprimer stage
    if (deleteStageBtn) {
        deleteStageBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action supprimera toutes les informations de votre stage !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('traitement/delete_stage_info.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `numero_carte_etudiant=<?php echo urlencode($numero_carte_etudiant); ?>`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Supprimé !', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Erreur !', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Erreur !', 'Erreur de communication. Veuillez réessayer.', 'error');
                    });
                }
            });
        });
    }
});
</script>

</body>
</html>
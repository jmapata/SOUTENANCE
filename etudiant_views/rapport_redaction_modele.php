<?php
// etudiant_views/rapport_redaction_modele.php
?>

<style>
    /* =========== STRUCTURE GÉNÉRALE =========== */
    .live-editor-container {
        display: flex;
        gap: 1.5rem;
        height: calc(100vh - 160px);
    }
    .form-panel, .preview-panel {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow-y: auto;
    }
    .form-panel { flex: 2; padding: 25px; }
    .preview-panel { flex: 3; padding: 40px; }

    /* =========== PANNEAU DE GAUCHE : FORMULAIRE (Pas de changement) =========== */
    .form-panel h3, .form-panel h4 { margin-bottom: 20px; color: #333; }
    .form-section { margin-bottom: 20px; }
    .form-section label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
    .form-control, .form-textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; }
    #dynamic-content-inputs .dynamic-input-group { border: 1px dashed #ccc; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
    #dynamic-content-inputs .dynamic-input-group textarea { min-height: 100px; }
    .add-buttons button { margin-right: 10px; }

    /* =========== PANNEAU DE DROITE : NOUVELLE MISE EN PAGE STYLE PDF =========== */
    .preview-panel {
        font-family: 'Times New Roman', Times, serif;
        color: #000;
        line-height: 1.4;
    }

    /* En-tête en 2 colonnes */
    .preview-header {
        display: flex;
        justify-content: space-between;
        text-align: center;
        font-size: 0.9rem;
    }
    .header-left, .header-right {
        flex-basis: 48%;
    }
    .header-left p, .header-right p {
        margin: 0;
        line-height: 1.2;
    }
    .preview-header img {
        max-height: 60px;
        margin-top: 15px;
        margin-bottom: 15px;
    }
    .header-right img.logo-kyria {
        max-height: 40px; /* Logo Kyria est plus petit */
    }

    /* Corps de la page de garde */
    .preview-body {
        text-align: center;
        margin-top: 30px;
    }
    .preview-body .degree-info {
        font-size: 1rem;
        margin-bottom: 30px;
    }

    #preview-theme {
        font-size: 1.5rem;
        font-weight: bold;
        text-transform: uppercase;
        padding: 15px 0;
        margin: 30px auto;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        width: 90%;
    }

    .author-block {
        margin: 30px 0 50px 0;
    }
    .author-block strong {
        font-size: 0.9rem;
    }
    .author-block span {
        font-size: 1.1rem;
        font-weight: bold;
    }

    /* Pied de page en 2 colonnes avec bordures */
    .preview-footer {
        display: flex;
        justify-content: space-between;
        align-items: stretch; /* Aligne les hauteurs */
    }
    .footer-box {
        border: 1px solid #000;
        width: 48%;
        padding: 15px;
        text-align: center;
    }
    .footer-box strong {
        font-size: 0.9rem;
        text-transform: uppercase;
    }
    .footer-box span {
        display: block;
        margin-top: 10px;
        font-size: 1.1rem;
        min-height: 1.2em;
    }

    /* Contenu dynamique */
    #preview-content {
        margin-top: 60px;
    }
    #preview-content h2, #preview-content h3, #preview-content p {
        text-align: left; /* Le contenu n'est pas centré */
    }
</style>


<div class="live-editor-container">

    <div class="form-panel">
        <h3><i class="fa-solid fa-edit"></i> Panneau de Rédaction</h3>
        <form id="rapportForm" action="traitement/generer_rapport_modele.php" method="POST">
            
            <h4>Informations de la page de garde</h4>
            <div class="form-section">
                <label for="theme">Thème du mémoire</label>
            <input type="text" id="theme" name="theme" data-target="#preview-theme" class="form-control live-update" placeholder="Thème de votre mémoire">
            </div>
            <div class="form-section">
                <label for="etudiant">Présenté par</label>
               <input type="text" id="etudiant" name="etudiant" data-target="#preview-etudiant" class="form-control live-update" value="M. KOET BI BOH CHABEL BAHI">
            </div>
            <div class="form-section">
                <label for="encadreur">Encadreur Pédagogique</label>
              <input type="text" id="encadreur" name="encadreur" data-target="#preview-encadreur" class="form-control live-update" placeholder="Nom complet de l'encadreur">
            </div>
            <div class="form-section">
                <label for="maitre_stage">Maître de stage</label>
                <input type="text" id="maitre_stage" name="maitre_stage" data-target="#preview-maitre-stage" class="form-control live-update" placeholder="Nom complet du maître de stage">
            </div>
            <hr>
            <h4>Contenu Structuré</h4>
            <div id="dynamic-content-inputs"></div>
            <div class="add-buttons">
                <button type="button" id="add-title-btn" class="btn btn-info"><i class="fa-solid fa-heading"></i> Titre</button>
                <button type="button" id="add-subtitle-btn" class="btn btn-secondary"><i class="fa-solid fa-heading" style="font-size:0.8em;"></i> Sous-titre</button>
                <button type="button" id="add-paragraph-btn" class="btn btn-light" style="border:1px solid #ccc;"><i class="fa-solid fa-paragraph"></i> Paragraphe</button>
            </div>
            <hr>
            <div class="form-actions" style="margin-top: 20px; text-align:right;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Générer le PDF Final</button>
            </div>
        </form>
    </div>

    <div class="preview-panel">
        
        <div class="preview-header">
            <div class="header-left">
                <p>MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br>ET DE LA RECHERCHE SCIENTIFIQUE</p>
                <img src="assets/images/UNIVT_CCD.jpg" alt="Logo Université">
                <p>UNIVERSITE FELIX HOUPHOUET BOIGNY</p>
                <p>UFR MATHEMATIQUES ET INFORMATIQUE<br>FILLIERES PROFESSIONNALISEES MIAGE-GI</p>
            </div>
            <div class="header-right">
                <p>REPUBLIQUE DE COTE D'IVOIRE<br>Union - Discipline - Travail</p>
                <img src="assets/images/EMB_CI.jpg" alt="Logo Côte d'Ivoire">
                <img src="assets/images/KYRIA.jpg" alt="Logo Kyria" class="logo-kyria">
                <p>KYRIA CONSULTANCY SERVICES</p>
            </div>
        </div>

        <div class="preview-body">
            <p class="degree-info">
                Mémoire de fin de cycle pour l'obtention du:<br>
                <strong>Diplôme d'ingénieur de conception en informatique</strong><br>
                Option Méthodes Informatiques Appliquées à la Gestion des Entreprises
            </p>
            <h1 id="preview-theme">[Thème du mémoire]</h1>
            <div class="author-block">
                <strong>PRESENTE PAR :</strong><br>
                <span id="preview-etudiant">M. KOET BI BOH CHABEL BAHI</span>
            </div>
        </div>

        <div class="preview-footer">
            <div class="footer-box" id="encadreur-box">
                <strong>ENCADREUR</strong><br>
                <span id="preview-encadreur">[Nom de l'encadreur]</span>
            </div>
            <div class="footer-box" id="maitre-stage-box">
                <strong>MAITRE DE STAGE</strong><br>
                <span id="preview-maitre-stage">[Nom du maître de stage]</span>
            </div>
        </div>

        <div id="preview-content"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. MISE À JOUR DES CHAMPS SIMPLES (inchangé) ---
    // ... (code existant) ...
    const liveUpdateInputs = document.querySelectorAll('.live-update');
    liveUpdateInputs.forEach(input => {
        const targetElement = document.querySelector(input.dataset.target);
        if (targetElement) {
            input.placeholder = targetElement.textContent.trim();
            if(input.value) { targetElement.textContent = input.value; }
        }
        input.addEventListener('input', function() {
            if (targetElement) {
                targetElement.textContent = this.value || input.placeholder;
            }
        });
    });


    // --- 2. GESTION DU CONTENU DYNAMIQUE (inchangé) ---
    // ... (code existant) ...
    const addTitleBtn = document.getElementById('add-title-btn');
    const addSubtitleBtn = document.getElementById('add-subtitle-btn');
    const addParagraphBtn = document.getElementById('add-paragraph-btn');
    const inputsContainer = document.getElementById('dynamic-content-inputs');
    const previewContainer = document.getElementById('preview-content');
    let elementCounter = 0;
    function addContentElement(type) {
        elementCounter++;
        const inputId = `dyn-input-${elementCounter}`;
        const previewId = `dyn-preview-${elementCounter}`;
        const inputGroup = document.createElement('div');
        inputGroup.className = 'dynamic-input-group';
        let newElement;
        let previewElement;
        switch(type) {
            case 'title':
                newElement = document.createElement('input');
                newElement.type = 'text';
                newElement.placeholder = 'Saisissez votre Titre (Ex: I. INTRODUCTION)';
                previewElement = document.createElement('h2');
                break;
            case 'subtitle':
                newElement = document.createElement('input');
                newElement.type = 'text';
                newElement.placeholder = 'Saisissez votre Sous-titre (Ex: 1. Généralités)';
                previewElement = document.createElement('h3');
                break;
            default:
                newElement = document.createElement('textarea');
                newElement.placeholder = 'Rédigez votre paragraphe...';
                previewElement = document.createElement('p');
                break;
        }
        newElement.name = `contenu[${elementCounter}][valeur]`;
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = `contenu[${elementCounter}][type]`;
        typeInput.value = type;
        newElement.id = inputId;
        newElement.className = (type === 'paragraph') ? 'form-textarea' : 'form-control';
        inputGroup.appendChild(newElement);
        inputGroup.appendChild(typeInput);
        inputsContainer.appendChild(inputGroup);
        previewElement.id = previewId;
        previewContainer.appendChild(previewElement);
        newElement.addEventListener('input', function() {
            document.getElementById(previewId).textContent = this.value;
        });
        newElement.focus();
    }
    addTitleBtn.addEventListener('click', () => addContentElement('title'));
    addSubtitleBtn.addEventListener('click', () => addContentElement('subtitle'));
    addParagraphBtn.addEventListener('click', () => addContentElement('paragraph'));


    // --- 3. NOUVELLE PARTIE : GESTION DE LA SOUMISSION DU FORMULAIRE ---
    const rapportForm = document.getElementById('rapportForm');
    rapportForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Empêche la page de se recharger

        const formData = new FormData(rapportForm);
        const submitButton = document.querySelector('button[type="submit"]');
        submitButton.disabled = true; // Désactive le bouton pour éviter les clics multiples
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...';

        fetch('traitement/generer_rapport_modele.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Si le serveur répond que c'est un succès, on redirige
                window.location.href = data.redirectUrl;
            } else {
                // Sinon, on affiche une alerte avec le message d'erreur
                alert('Erreur : ' + data.message);
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Générer le PDF Final';
            }
        })
        .catch(error => {
            console.error('Erreur Fetch:', error);
            alert('Une erreur de communication avec le serveur est survenue.');
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Générer le PDF Final';
        });
    });
});
</script>
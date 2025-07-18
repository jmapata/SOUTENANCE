<?php
// etudiant_views/ressources.php
// Pour l'instant, cette page contient du contenu statique.
// Plus tard, les liens et les FAQ pourraient être gérés depuis la base de données.
?>

<style>
    :root {
        --primary-color: #5e72e4;
        --card-bg: #ffffff;
        --text-dark: #32325d;
        --text-light: #8898aa;
        --border-color: #e2e8f0;
        --shadow-soft: 0 7px 14px 0 rgba(60, 66, 87, 0.08), 0 3px 6px 0 rgba(0, 0, 0, 0.08);
    }
    .page-header h1 { font-size: 1.8rem; font-weight: 600; color: var(--text-dark); }
    .page-header p { color: var(--text-light); }
    
    .ressources-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 24px;
        margin-top: 30px;
    }
    .card {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow-soft);
        border: 1px solid var(--border-color);
    }
    .card-full-width {
        grid-column: 1 / -1;
    }
    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }
    .card-title i { color: var(--primary-color); }
    
    .resource-list .resource-item {
        display: flex;
        align-items: center;
        padding: 12px;
        text-decoration: none;
        color: #475569;
        border-radius: 8px;
        transition: background-color 0.2s;
        border-bottom: 1px solid #f1f5f9;
    }
    .resource-list .resource-item:last-child { border-bottom: none; }
    .resource-list .resource-item:hover { background-color: #f8fafc; }
    .resource-list .resource-item i { margin-right: 15px; color: var(--primary-color); font-size: 1.2rem; }

    .criteria-list { list-style: none; padding-left: 5px; }
    .criteria-list li { padding-left: 25px; position: relative; margin-bottom: 10px; }
    .criteria-list li::before {
        content: '\f00c'; /* Font Awesome check icon */
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        left: 0;
        top: 2px;
        color: #2dce89;
    }
    
    .faq-item {
        margin-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 15px;
    }
    .faq-question { font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .faq-question::after { content: '\f078'; /* down arrow */ font-family: 'Font Awesome 6 Free'; font-weight: 900; transition: transform 0.2s; }
    .faq-item.open .faq-question::after { transform: rotate(180deg); }
    .faq-answer { display: none; margin-top: 10px; color: var(--text-light); padding-left: 10px; border-left: 3px solid var(--primary-color); }
</style>

<div class="page-header">
    <h1><i class="fas fa-book-open"></i> Ressources & Aide</h1>
    <p>Trouvez ici tous les guides, modèles et informations pour réussir votre rapport.</p>
</div>

<div class="ressources-grid">
    <div class="card">
        <h3 class="card-title"><i class="fas fa-file-download"></i> Guides et Modèles</h3>
        <div class="resource-list">
            <a href="#" download>
                <i class="fas fa-file-pdf"></i>
                <span>Guide de rédaction officiel du rapport</span>
            </a>
            <a href="#" download>
                <i class="fas fa-file-word"></i>
                <span>Modèle de Page de Garde (.docx)</span>
            </a>
            <a href="#" download>
                <i class="fas fa-file-word"></i>
                <span>Modèle de Remerciements (.docx)</span>
            </a>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title"><i class="fas fa-check-double"></i> Critères d'Évaluation</h3>
        <ul class="criteria-list">
            <li>Clarté et pertinence du thème</li>
            <li>Qualité de la méthodologie de recherche</li>
            <li>Rigueur de l'analyse et des résultats</li>
            <li>Respect des normes de citation et de bibliographie</li>
            <li>Qualité de la langue et de la présentation</li>
        </ul>
    </div>
    
    <div class="card">
        <h3 class="card-title"><i class="fas fa-address-book"></i> Contacts Utiles</h3>
        <div class="resource-list">
            <div class="resource-item">
                <i class="fas fa-school"></i>
                <span><strong>Service de la Scolarité :</strong> scolarite@univ.edu</span>
            </div>
            <div class="resource-item">
                <i class="fas fa-headset"></i>
                <span><strong>Support Technique :</strong> support.soutenance@univ.edu</span>
            </div>
        </div>
    </div>

    <div class="card card-full-width">
        <h3 class="card-title"><i class="fas fa-question-circle"></i> Foire Aux Questions (FAQ)</h3>
        <div class="faq-list">
            <div class="faq-item">
                <p class="faq-question">Quelle est la date limite pour soumettre mon rapport ?</p>
                <div class="faq-answer">La date limite est fixée au 30 septembre 2025. Consultez le calendrier académique pour toute mise à jour.</div>
            </div>
            <div class="faq-item">
                <p class="faq-question">Quel format de fichier dois-je utiliser pour le rapport ?</p>
                <div class="faq-answer">Tous les rapports doivent être soumis au format PDF. Si vous utilisez les outils de rédaction de la plateforme, la conversion se fait automatiquement.</div>
            </div>
             <div class="faq-item">
                <p class="faq-question">Mon rapport a été retourné pour "Non Conformité", que faire ?</p>
                <div class="faq-answer">Vous devez vous rendre dans la section "Mon Rapport", puis "Corriger Rapport". Vous y trouverez les commentaires de l'agent de conformité vous indiquant les points à modifier.</div>
            </div>
        </div>
    </div>
</div>

<script>
// Script simple pour l'accordéon de la FAQ
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        const item = question.parentElement;
        
        if (answer.style.display === 'block') {
            answer.style.display = 'none';
            item.classList.remove('open');
        } else {
            answer.style.display = 'block';
            item.classList.add('open');
        }
    });
});
</script>
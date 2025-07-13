<style>
    /* Styles CSS optimisés pour le rendu PDF */
    body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 12pt; }
    .preview-header { display: flex; justify-content: space-between; text-align: center; font-size: 10pt; }
    .header-left, .header-right { width: 48%; }
    .preview-header img { max-height: 60px; margin-top: 15px; margin-bottom: 15px; }
    .preview-body { text-align: center; margin-top: 30px; }
    .degree-info { font-size: 11pt; margin-bottom: 30px; }
    h1 { font-size: 16pt; font-weight: bold; text-transform: uppercase; padding: 15px 0; margin: 30px auto; border-top: 2px solid #000; border-bottom: 2px solid #000; width: 90%; }
    .author-block { margin: 30px 0 50px 0; }
    .preview-footer { display: flex; justify-content: space-between; }
    .footer-box { border: 1px solid #000; width: 48%; padding: 15px; text-align: center; }
    h2 { font-size: 14pt; font-weight: bold; margin-top: 30px; text-transform: uppercase; }
    h3 { font-size: 13pt; font-weight: bold; margin-top: 25px; }
    p { text-align: justify; line-height: 1.5; }
</style>

<body>
    <div class="preview-header">
        <div class="header-left">
            <p>MINISTERE DE L'ENSEIGNEMENT SUPERIEUR...</p>
            <img src="../assets/images/UNIVT_CCD.jpg">
            <p>UNIVERSITE FELIX HOUPHOUET BOIGNY...</p>
        </div>
        <div class="header-right">
            <p>REPUBLIQUE DE COTE D'IVOIRE...</p>
            <img src="../assets/images/EMB_CI.jpg">
            <img src="../assets/images/KYRIA.jpg" style="max-height: 40px;">
            <p>KYRIA CONSULTANCY SERVICES</p>
        </div>
    </div>
    <div class="preview-body">
        <p class="degree-info">Mémoire de fin de cycle...</p>
        <h1><?php echo htmlspecialchars($theme); ?></h1>
        <div class="author-block">
            <strong>PRESENTE PAR :</strong><br>
            <span><?php echo htmlspecialchars($etudiant_nom); ?></span>
        </div>
    </div>
    <div class="preview-footer">
        <div class="footer-box">
            <strong>ENCADREUR</strong><br>
            <span><?php echo htmlspecialchars($encadreur_nom); ?></span>
        </div>
        <div class="footer-box">
            <strong>MAITRE DE STAGE</strong><br>
            <span><?php echo htmlspecialchars($maitre_stage_nom); ?></span>
        </div>
    </div>
    <div id="preview-content">
        <?php foreach ($contenu_dynamique as $section): ?>
            <?php if ($section['type'] === 'title'): ?>
                <h2><?php echo htmlspecialchars($section['valeur']); ?></h2>
            <?php elseif ($section['type'] === 'subtitle'): ?>
                <h3><?php echo htmlspecialchars($section['valeur']); ?></h3>
            <?php else: ?>
                <p><?php echo nl2br(htmlspecialchars($section['valeur'])); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</body>
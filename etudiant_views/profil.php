<?php
// etudiant_views/profil.php
// La variable $pdo est disponible depuis le fichier dashboard principal.

// On récupère toutes les informations de l'étudiant et de son compte utilisateur
$stmt = $pdo->prepare("
    SELECT u.login_utilisateur, u.email_principal, u.photo_profil, e.* FROM utilisateur u 
    JOIN etudiant e ON u.numero_utilisateur = e.numero_utilisateur 
    WHERE u.numero_utilisateur = ?
");
$stmt->execute([$_SESSION['numero_utilisateur']]);
$student_data = $stmt->fetch();

// On prépare les initiales pour l'avatar par défaut
$initials = '';
if (!empty($student_data['prenom'])) $initials .= strtoupper(substr($student_data['prenom'], 0, 1));
if (!empty($student_data['nom'])) $initials .= strtoupper(substr($student_data['nom'], 0, 1));
?>

<style>
  :root {
    --primary-color: #2563eb;
    --primary-hover: #1d4ed8;
    --secondary-color: #64748b;
    --accent-color: #3b82f6;
    --card-bg: #ffffff;
    --background-color: #f8fafc;
    --text-dark: #1e293b;
    --text-medium: #475569;
    --text-light: #64748b;
    --border-color: #e2e8f0;
    --border-light: #f1f5f9;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--background-color);
    color: var(--text-dark);
    line-height: 1.6;
}

.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-light);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    letter-spacing: -0.025em;
}

.profil-grid {
    display: grid;
    grid-template-columns: 350px 1fr 1fr;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.card {
    background-color: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: var(--shadow-lg);
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--border-light);
    position: relative;
}

.card-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 50px;
    height: 2px;
    background-color: var(--primary-color);
}

.profile-summary-card {
    text-align: center;
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.5rem;
    font-weight: 700;
    border: 4px solid white;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.profile-avatar-large::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
}

.profile-avatar-large:hover::before {
    left: 100%;
}

.profile-avatar-large img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.profile-summary-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.profile-summary-card p {
    color: var(--text-light);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.profile-summary-card hr {
    border: none;
    border-top: 1px solid var(--border-light);
    margin: 1.5rem 0;
}

.profile-details-panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    grid-column: span 2;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--text-medium);
    font-size: 0.9rem;
}

.form-control {
    max-width: 400px;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all 0.2s ease;
    background-color: var(--card-bg);
    color: var(--text-dark);
}

.form-control[type="email"] {
    max-width: 350px;
}

.form-control[type="tel"] {
    max-width: 250px;
}

.form-control[type="password"] {
    max-width: 300px;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    background-color: white;
}

.form-control:disabled {
    background-color: var(--border-light);
    color: var(--text-light);
    cursor: not-allowed;
}

.form-control[type="file"] {
    padding: 0.5rem;
    border: 2px dashed var(--border-color);
    background-color: var(--background-color);
    transition: all 0.2s ease;
}

.form-control[type="file"]:hover {
    border-color: var(--primary-color);
    background-color: rgba(37, 99, 235, 0.05);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background-color: white;
    color: var(--text-medium);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
}

.btn-secondary:hover {
    background-color: var(--background-color);
    border-color: var(--secondary-color);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn:active {
    transform: translateY(0);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeIn 0.6s ease-out;
}

.card:nth-child(1) {
    animation-delay: 0.1s;
}

.card:nth-child(2) {
    animation-delay: 0.2s;
}

.card:nth-child(3) {
    animation-delay: 0.3s;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .profil-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .profile-details-panels {
        grid-column: span 1;
        grid-template-columns: 1fr;
    }
    
    .profile-summary-card {
        position: static;
    }
}

@media (max-width: 1024px) {
    .form-control {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .card {
        padding: 1.5rem;
    }
    
    .profile-avatar-large {
        width: 100px;
        height: 100px;
        font-size: 2rem;
    }
    
    .profile-summary-card h3 {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .profil-grid {
        gap: 1rem;
    }
    
    .card {
        padding: 1rem;
    }
    
    .btn {
        width: 100%;
        padding: 0.875rem 1rem;
    }
    
    .form-control {
        font-size: 16px; /* Évite le zoom sur iOS */
    }
}

/* Focus indicators for accessibility */
.btn:focus-visible,
.form-control:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Alertes */
.alert {
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    border: 1px solid;
    font-weight: 500;
}

.alert-success {
    background-color: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.alert-danger {
    background-color: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

/* Selection styling */
::selection {
    background-color: rgba(37, 99, 235, 0.2);
    color: var(--text-dark);
}
</style>

<div class="page-header">
    <h1>Mon Profil</h1>
</div>

<div class="profil-grid">
    <div class="card profile-summary-card">
        <div class="profile-avatar-large">
            <?php if (!empty($student_data['photo_profil'])): ?>
                <img src="<?php echo htmlspecialchars($student_data['photo_profil']); ?>" alt="Photo de profil">
            <?php else: ?>
                <span><?php echo $initials; ?></span>
            <?php endif; ?>
        </div>
        <h3><?php echo htmlspecialchars($student_data['prenom'] . ' ' . $student_data['nom']); ?></h3>
        <p><?php echo htmlspecialchars($student_data['numero_carte_etudiant']); ?></p>
        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 20px 0;">
        <form action="traitement/profil_etudiant_traitement.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_photo">
            <div class="form-group">
                <label for="photo">Changer la photo de profil</label>
                <input type="file" name="photo" id="photo" class="form-control" style="padding: 5px;">
            </div>
            <button type="submit" class="btn btn-secondary">Mettre à jour la photo</button>
        </form>
    </div>

    <div class="profile-details-panels">
        <div class="card">
            <h3 class="card-title">Informations de Contact</h3>
            <form action="traitement/profil_etudiant_traitement.php" method="POST">
                <input type="hidden" name="action" value="update_contact">
                <div class="form-group">
                    <label for="email_principal">Email Principal (Login)</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($student_data['email_principal'] ?? ''); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" class="form-control" value="<?php echo htmlspecialchars($student_data['telephone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email_secondaire">Email de contact secondaire</label>
                    <input type="email" id="email_secondaire" name="email_secondaire" class="form-control" value="<?php echo htmlspecialchars($student_data['email_contact_secondaire'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-title">Changer le Mot de Passe</h3>
            <form action="traitement/profil_etudiant_traitement.php" method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                 <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
            </form>
        </div>
    </div>
</div>
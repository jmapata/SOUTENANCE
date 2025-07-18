<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le Mot de Passe - ValidMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f0ff 0%, #f0f6ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 750px;
            min-height: 500px;
            display: flex;
            animation: slideIn 0.8s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
            padding: 40px 30px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s linear infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-100px); }
        }
        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 15px;
            z-index: 1;
        }
        .subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 25px;
            z-index: 1;
        }
        .read-more-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 1;
        }
        .read-more-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .login-right {
            flex: 1;
            padding: 40px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        .welcome-text h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 8px;
        }
        .welcome-text p {
            color: #666;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .input-container {
            position: relative;
        }
        .input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4285f4;
            background: white;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(66, 133, 244, 0.3);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #4285f4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .forgot-password a:hover {
            color: #1a73e8;
        }
        .error {
            background: #ffe6e6;
            color: #d63384;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d63384;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }
        .error i { margin-right: 10px; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 400px; }
            .login-left { padding: 40px 30px; }
            .login-right { padding: 40px 30px; }
            .logo { font-size: 2rem; }
            .welcome-text h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Partie gauche avec le branding -->
        <div class="login-left">
            <div class="logo">
                <svg viewBox="0 0 300 80" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#f0f6ff;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="accentGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#e8f0ff;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="1" dy="1" stdDeviation="2" flood-color="#000" flood-opacity="0.3"/>
                        </filter>
                    </defs>
                    <g transform="translate(10, 10)">
                        <g transform="translate(0, 5)">
                            <path d="M25 5 L45 5 Q50 5 50 10 L50 35 Q50 45 35 50 L35 50 Q20 45 20 35 L20 10 Q20 5 25 5 Z" fill="rgba(255,255,255,0.9)" filter="url(#shadow)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
                            <path d="M28 30 L33 35 L42 20" fill="none" stroke="#4285f4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="35" cy="15" r="2" fill="#4285f4" opacity="0.7"/>
                            <rect x="30" y="40" width="10" height="2" fill="#4285f4" opacity="0.5" rx="1"/>
                        </g>
                        <g transform="translate(70, 0)">
                            <text x="0" y="30" font-family="Arial, sans-serif" font-size="24" font-weight="bold" fill="white">Valid</text>
                            <text x="65" y="30" font-family="Arial, sans-serif" font-size="24" font-weight="bold" fill="rgba(255,255,255,0.9)">Master</text>
                            <text x="0" y="50" font-family="Arial, sans-serif" font-size="11" fill="rgba(255,255,255,0.8)" font-weight="normal">Gestion des Soutenances</text>
                        </g>
                        <g opacity="0.6">
                            <circle cx="200" cy="15" r="1.5" fill="white"/>
                            <circle cx="220" cy="25" r="1" fill="rgba(255,255,255,0.7)"/>
                            <circle cx="240" cy="18" r="1.2" fill="white"/>
                            <line x1="70" y1="40" x2="180" y2="40" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                        </g>
                        <g transform="translate(210, 25)">
                            <rect x="0" y="0" width="60" height="18" rx="9" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.3)"/>
                            <text x="30" y="12" font-family="Arial, sans-serif" font-size="9" fill="white" text-anchor="middle" font-weight="500">UNIVERSITY</text>
                        </g>
                    </g>
                </svg>
            </div>
            <div class="subtitle">Système de gestion des soutenances universitaires</div>
            <a href="index.php" class="read-more-btn">En savoir plus</a>
        </div>
        <!-- Partie droite avec le formulaire -->
        <div class="login-right">
            <div class="welcome-text">
                <h2>Réinitialiser votre mot de passe</h2>
                <p>Veuillez choisir un nouveau mot de passe sécurisé.</p>
            </div>
            <form action="traitement/reinitialiser_mdp_traitement.php" method="POST" id="resetPwdForm">
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <div class="input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" required placeholder="Nouveau mot de passe">
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <div class="input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirmez le mot de passe">
                    </div>
                </div>
                <button type="submit" class="login-btn">
                    <span class="btn-text">Réinitialiser le mot de passe</span>
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
            <div class="forgot-password">
                <a href="login.php">Retour à la connexion</a>
            </div>
        </div>
    </div>
    <script>
        // Animation de chargement lors de la soumission du formulaire
        document.getElementById('resetPwdForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.login-btn');
            const btnText = document.querySelector('.btn-text');
            const loading = document.querySelector('.loading');
            btnText.style.opacity = '0';
            loading.style.display = 'block';
            btn.disabled = true;
        });
        // Animation d'entrée pour les éléments
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
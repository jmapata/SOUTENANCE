<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Soutenance - Master 2 MIAGE - UFHB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ufhb-blue: #0056b3;
            --ufhb-gold: #ffd700;
            --ufhb-light: #e6f2ff;
            --ufhb-dark: #003366;
        }
        
        .hero-bg {
            background-image: linear-gradient(rgba(0, 51, 102, 0.9), rgba(0, 51, 102, 0.9)), url('assets/images/UFHB.avif');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 86, 179, 0.3), 0 4px 6px -2px rgba(0, 86, 179, 0.1);
        }
        
        .transition-all {
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--ufhb-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--ufhb-dark);
        }
        
        .btn-accent {
            background-color: var(--ufhb-gold);
            color: var(--ufhb-dark);
        }
        
        .btn-accent:hover {
            background-color: #e6c200;
        }

        /* Styles pour les liens qui ressemblent à des boutons */
        .btn-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            /* Reprenez les styles de padding, font-weight, border-radius etc. de vos boutons originaux */
            padding: 12px 24px; /* Exemple, ajustez selon vos styles Tailwind ou les classes btn-primary/btn-accent */
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none; /* Enlève le soulignement du lien */
            /* Ajoutez les classes de transition si elles ne sont pas déjà incluses par Tailwind */
            transition: all 0.3s ease; 
        }

    </style>
</head>
<body class="font-sans bg-gray-100">
    <nav class="bg-[var(--ufhb-dark)] text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="assets/images/UNIVT_CCD.jpg" alt="Logo UFHB" class="h-10 w-10 rounded-full object-cover">
                <div>
                    <h1 class="font-bold text-lg">UFHB - MIAGE</h1>
                    <p class="text-xs text-green-200">Gestion des Soutenances Master 2</p>
                </div>
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="#" class="hover:text-green-300 transition-all">Accueil</a>
                <a href="#" class="hover:text-green-300 transition-all">Calendrier</a>
                <a href="#" class="hover:text-green-300 transition-all">Soutenances</a>
                <a href="#" class="hover:text-green-300 transition-all">Contact</a>
                <a href="login.php" class="hover:text-green-300 transition-all">Connexion</a>
            </div>
            <button class="md:hidden text-white focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </nav>

    <section class="hero-bg text-white py-20 md:py-32 px-4">
        <div class="container mx-auto text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">Plateforme de Gestion des Soutenances</h1>
            <p class="text-xl md:text-2xl mb-8">Master 2 MIAGE - Université Félix Houphouët-Boigny</p>
            <div class="flex flex-col md:flex-row justify-center gap-4">
                <a href="login.php" class="btn-primary btn-link py-3 px-6 rounded-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Connexion Étudiant
                </a>
                <a href="login.php" class="btn-accent btn-link py-3 px-6 rounded-lg">
                    <i class="fas fa-user-tie mr-2"></i>Espace Enseignant
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 px-4">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Fonctionnalités de la Plateforme</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md card-hover transition-all">
                    <div class="text-green-600 text-4xl mb-4">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Planification des Soutenances</h3>
                    <p class="text-gray-600">Gérez facilement les dates et horaires des soutenances avec un calendrier interactif.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md card-hover transition-all">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Dépôt des Mémoires</h3>
                    <p class="text-gray-600">Les étudiants peuvent déposer leurs mémoires en ligne avec suivi des versions.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md card-hover transition-all">
                    <div class="text-purple-600 text-4xl mb-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Gestion des Jurys</h3>
                    <p class="text-gray-600">Affectez les membres du jury et envoyez automatiquement les convocations.</p>
                </div>
            </div>
        </div>
    </section>


    <section class="py-16 px-4">
        <div class="container mx-auto flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-8 md:mb-0 md:pr-8">
                <img src="assets/images/MIAGE.jpg" alt="Université Félix Houphouët-Boigny" class="rounded-lg shadow-xl w-full h-auto">
            </div>
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold mb-6 text-gray-800">À propos de la MIAGE UFHB</h2>
                <p class="text-gray-600 mb-4">
                    La filière MIAGE (Méthodes Informatiques Appliquées à la Gestion des Entreprises) de l'Université Félix Houphouët-Boigny forme des experts en ingénierie des systèmes d'information.
                </p>
                <p class="text-gray-600 mb-4">
                    Le Master 2 MIAGE prépare les étudiants à concevoir, développer et mettre en œuvre des solutions informatiques complexes pour répondre aux besoins des organisations.
                </p>
                <p class="text-gray-600 mb-6">
                    Cette plateforme digitale a été développée pour moderniser et simplifier le processus d'organisation des soutenances de mémoire de fin d'études.
                </p>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-all">
                    En savoir plus sur la MIAGE <i class="fas fa-info-circle ml-2"></i>
                </button>
            </div>
        </div>
    </section>

    <section class="py-16 px-4 bg-[var(--ufhb-dark)] text-white">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-center mb-12">Contactez-nous</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Coordonnées</h3>
                    <p class="mb-4"><i class="fas fa-map-marker-alt mr-3 text-green-400"></i> UFR Mathématiques et Informatique, Université Félix Houphouët-Boigny, Abidjan, Côte d'Ivoire</p>
                    <p class="mb-4"><i class="fas fa-phone-alt mr-3 text-green-400"></i> +225 07 49 26 01 46</p>
                    <p class="mb-4"><i class="fas fa-envelope mr-3 text-green-400"></i> miage-master@ufhb.edu.ci</p>
                    
                    <h3 class="text-xl font-semibold mt-8 mb-4">Heures d'ouverture</h3>
                    <p class="mb-2">Lundi - Vendredi: 8h00 - 17h00</p>
                    <p>Samedi: 9h00 - 13h00</p>
                </div>
                
                <div>
                    <form class="space-y-4">
                        <div>
                            <label for="name" class="block mb-1">Nom complet</label>
                            <input type="text" id="name" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="email" class="block mb-1">Email</label>
                            <input type="email" id="email" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="subject" class="block mb-1">Sujet</label>
                            <input type="text" id="subject" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label for="message" class="block mb-1">Message</label>
                            <textarea id="message" rows="4" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-all">
                            Envoyer <i class="fas fa-paper-plane ml-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-[var(--ufhb-blue)] text-white py-8 px-4">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">MIAGE UFHB</h3>
                    <p class="text-gray-400">Plateforme de gestion des soutenances pour les étudiants de Master 2 MIAGE de l'Université Félix Houphouët-Boigny.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Liens rapides</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Accueil</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Calendrier</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Soutenances</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Ressources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Guide des soutenances</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Modèles de mémoire</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-all">Règlement intérieur</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Suivez-nous</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-400 text-xl transition-all"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-400 text-xl transition-all"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 text-xl transition-all"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="text-gray-400 hover:text-purple-500 text-xl transition-all"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-center text-gray-500">
                <p>&copy; 2023 Université Félix Houphouët-Boigny - UFR Mathématiques et Informatique - MIAGE. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple script for mobile menu toggle (could be expanded)
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('.md\\:hidden');
           // Your JavaScript code might be missing here if the previous snippet was incomplete.
           // Ensure the DOMContentLoaded listener is properly closed.
        }); 
    </script>
</body>
</html>
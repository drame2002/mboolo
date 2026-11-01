<?php
// views/home.php — Page d'Accueil & de Bienvenue MBOOLO
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/SchoolService.php';
require_once __DIR__ . '/../src/Auth.php';

$schools = SchoolService::getAllSchools();
$isLoggedIn = Auth::check();
$currentUser = Auth::user();
$pageTitle = "Bienvenue sur MBOOLO — Plateforme Scolaire Intégrée";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Application instantanée du thème pour éviter tout scintillement -->
    <script>
        (function() {
            var theme = localStorage.getItem('mboolo_theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <link rel="icon" type="image/png" href="<?= baseUrl('assets/img/logos/mboolo_logo.png') ?>">

    <style>
        /* Styles spécifiques à la page d'accueil */
        .home-navbar {
            background: var(--bg-topbar);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.25s ease, border-color 0.25s ease;
        }

        .home-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .home-brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 10px;
            background: #ffffff;
            padding: 2px;
            box-shadow: var(--shadow-sm);
        }

        .home-brand-text h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .home-brand-text span {
            font-size: 11px;
            color: var(--primary-light);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
        }

        .home-nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Hero Banner */
        .hero-section {
            background: linear-gradient(135deg, #0b1120 0%, #1e3a8a 50%, #0f172a 100%);
            color: #ffffff;
            padding: 80px 24px 70px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-container {
            max-width: 960px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f8fafc;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }

        .hero-title {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -1px;
            margin-bottom: 18px;
        }

        .hero-title span {
            background: linear-gradient(90deg, #60a5fa 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 18px;
            color: #cbd5e1;
            max-width: 760px;
            margin: 0 auto 40px auto;
            line-height: 1.6;
        }

        /* Boîte centrale de choix : Se connecter ou S'inscrire */
        .choice-card-wrapper {
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.25);
            max-width: 820px;
            margin: 0 auto;
            color: var(--text-main);
            transition: background 0.25s ease, border-color 0.25s ease;
        }

        .choice-header {
            margin-bottom: 28px;
            text-align: center;
        }

        .choice-header h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .choice-header p {
            font-size: 15px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .choice-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .choice-box {
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px;
            text-align: center;
            background: var(--bg-main);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            text-decoration: none;
            color: inherit;
        }

        .choice-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -5px rgba(0, 0, 0, 0.15);
        }

        .choice-box.login-box {
            border-color: rgba(59, 130, 246, 0.4);
        }

        .choice-box.login-box:hover {
            border-color: var(--primary-light);
            background: rgba(59, 130, 246, 0.04);
        }

        .choice-box.register-box {
            border-color: rgba(245, 158, 11, 0.4);
        }

        .choice-box.register-box:hover {
            border-color: var(--accent);
            background: rgba(245, 158, 11, 0.04);
        }

        .choice-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 16px auto;
            box-shadow: var(--shadow-sm);
        }

        .login-box .choice-icon-circle {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
        }

        .register-box .choice-icon-circle {
            background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%);
            color: #ffffff;
        }

        .choice-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .choice-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .choice-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-choice-login {
            background: var(--primary-light);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        }

        .btn-choice-login:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-choice-register {
            background: var(--accent);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        }

        .btn-choice-register:hover {
            background: var(--accent-hover);
            color: #ffffff;
        }

        /* Section Présentation des Rôles */
        .features-section {
            padding: 70px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-tag {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary-light);
            margin-bottom: 8px;
            display: block;
        }

        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .section-subtitle {
            font-size: 15px;
            color: var(--text-muted);
            margin-top: 6px;
            max-width: 680px;
            margin-left: auto;
            margin-right: auto;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .role-feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            transition: all 0.25s;
        }

        .role-feature-card:hover {
            border-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .role-feature-icon {
            font-size: 32px;
            margin-bottom: 14px;
            display: inline-block;
        }

        .role-feature-card h4 {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        .role-feature-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.8;
        }

        .role-feature-card ul li::before {
            content: '✓ ';
            color: var(--success);
            font-weight: 800;
        }

        /* Bannière Déjà Connecté */
        .logged-in-banner {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: var(--text-main);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 28px;
            }
            .hero-subtitle {
                font-size: 15px;
            }
            .choice-grid {
                grid-template-columns: 1fr;
            }
            .choice-card-wrapper {
                padding: 24px 18px;
            }
            .home-navbar {
                padding: 14px 18px;
            }
        }
    </style>
</head>
<body>

    <!-- Barre de Navigation de l'Accueil -->
    <header class="home-navbar">
        <a href="<?= baseUrl('index.php?page=home') ?>" class="home-brand">
            <img src="<?= baseUrl('assets/img/logos/mboolo_logo.png') ?>" alt="Logo MBOOLO" class="home-brand-logo">
            <div class="home-brand-text">
                <h1>MBOOLO</h1>
                <span>Plateforme Scolaire Intégrée</span>
            </div>
        </a>

        <div class="home-nav-actions">
            <!-- Bouton Sélecteur de Thème (Lumineux / Sombre) -->
            <button id="theme-toggle-btn" class="theme-toggle-btn" type="button" title="Changer de thème (Clair / Sombre)">
                <span class="theme-icon">🌙</span>
            </button>

            <?php if ($isLoggedIn): ?>
                <a href="<?= baseUrl('index.php?page=dashboard') ?>" class="btn btn-primary btn-sm">
                    📊 Mon Espace (<?= ucfirst(e(Auth::role())) ?>)
                </a>
            <?php else: ?>
                <a href="<?= baseUrl('index.php?page=login') ?>" class="btn btn-outline btn-sm">
                    🔑 Connexion
                </a>
                <a href="<?= baseUrl('index.php?page=register') ?>" class="btn btn-primary btn-sm">
                    📝 Inscription
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Section Héro avec Bienvenue Chaleureux -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-badge">
                <span>✨</span> Écosystème Éducatif & Gestion Centralisée Multi-Écoles
            </div>
            <h2 class="hero-title">
                Bienvenue sur <span>MBOOLO</span>
            </h2>
            <p class="hero-subtitle">
                La plateforme numérique dédiée à l'ensemble des établissements scolaires. Elle réunit directeurs, professeurs, élèves, parents et secrétaires pour simplifier la communication et centraliser la gestion pédagogique, administrative et financière.
            </p>

            <!-- BOÎTE DE DÉCISION : Connexion ou Inscription (Demande Utilisateur) -->
            <div class="choice-card-wrapper">
                <?php if ($isLoggedIn): ?>
                    <div class="logged-in-banner">
                        <div>
                            <strong>Session active :</strong> Vous êtes connecté en tant que <strong><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></strong> (<?= ucfirst(e(Auth::role())) ?>).
                        </div>
                        <a href="<?= baseUrl('index.php?page=dashboard') ?>" class="btn btn-success btn-sm">
                            Accéder à mon tableau de bord &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <div class="choice-header">
                    <h3>Que souhaitez-vous faire ?</h3>
                    <p>Veuillez choisir une option pour accéder à votre espace scolaire ou créer votre compte</p>
                </div>

                <div class="choice-grid">
                    <!-- Option 1 : Se Connecter -->
                    <a href="<?= baseUrl('index.php?page=login') ?>" class="choice-box login-box">
                        <div>
                            <div class="choice-icon-circle">
                                🔑
                            </div>
                            <h4 class="choice-title">Se Connecter</h4>
                            <p class="choice-desc">
                                Vous possédez déjà un compte ? Saisissez votre identifiant ou adresse e-mail, votre mot de passe et votre rôle pour ouvrir votre session.
                            </p>
                        </div>
                        <div class="choice-btn btn-choice-login">
                            J'ai déjà un compte &rarr;
                        </div>
                    </a>

                    <!-- Option 2 : S'Inscrire -->
                    <a href="<?= baseUrl('index.php?page=register') ?>" class="choice-box register-box">
                        <div>
                            <div class="choice-icon-circle">
                                📝
                            </div>
                            <h4 class="choice-title">Créer un Compte</h4>
                            <p class="choice-desc">
                                Nouveau sur MBOOLO ? Inscrivez-vous en renseignant votre rôle (directeur, surveillant, professeur, secrétaire, élève avec cycle/série, ou parent).
                            </p>
                        </div>
                        <div class="choice-btn btn-choice-register">
                            Créer un nouveau compte &rarr;
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Espaces Utilisateurs Dédiés -->
    <section class="features-section">
        <div class="section-header">
            <span class="section-tag">Une Plateforme Complète</span>
            <h3 class="section-title">Des Espaces Dédiés pour Chaque Rôle</h3>
            <p class="section-subtitle">
                De l'Élémentaire (CI à CM2) au Collège (6ème à 3ème) et au Lycée (Séries Scientifiques S, Littéraires L, Gestion G, Techniques T) : chaque acteur bénéficie d'un espace adapté à ses prérogatives pédagogiques.
            </p>
        </div>

        <div class="roles-grid">
            <!-- Directeur -->
            <div class="role-feature-card">
                <span class="role-feature-icon">🎓</span>
                <h4>Espace Directeur</h4>
                <ul>
                    <li>Publication de communiqués officiels</li>
                    <li>Convocations de professeurs</li>
                    <li>Supervision des classes et programmes</li>
                    <li>Validation des bulletins semestriels</li>
                    <li>Journal d'audit et sauvegarde SQL</li>
                </ul>
            </div>

            <!-- Professeur -->
            <div class="role-feature-card">
                <span class="role-feature-icon">👨‍🏫</span>
                <h4>Espace Professeur</h4>
                <ul>
                    <li>Emploi du temps avec durées en minutes</li>
                    <li>Saisie et calcul des notes</li>
                    <li>Publication de résumés de cours</li>
                    <li>Consultation sécurisée des salaires</li>
                    <li>Cloisonnement strict des données</li>
                </ul>
            </div>

            <!-- Élève -->
            <div class="role-feature-card">
                <span class="role-feature-icon">🎒</span>
                <h4>Espace Élève</h4>
                <ul>
                    <li>Suivi scolaire et relevé des notes en direct</li>
                    <li>Moyennes générales et coefficients</li>
                    <li>Bulletins semestriels officiels en PDF</li>
                    <li>Emploi du temps hebdomadaire</li>
                    <li>Paiement en ligne de la scolarité</li>
                </ul>
            </div>

            <!-- Parent d'Élève -->
            <div class="role-feature-card">
                <span class="role-feature-icon">👨‍👩‍👧‍👦</span>
                <h4>Espace Parent</h4>
                <ul>
                    <li>Rattachement sécurisé des enfants</li>
                    <li>Suivi des résultats et appréciations</li>
                    <li>Téléchargement des bulletins scolaires</li>
                    <li>Paiement en ligne Wave, OM, CB</li>
                    <li>Consultation des communiqués</li>
                </ul>
            </div>

            <!-- Secrétaire -->
            <div class="role-feature-card">
                <span class="role-feature-icon">💼</span>
                <h4>Espace Secrétaire</h4>
                <ul>
                    <li>Gestion du recouvrement des mensualités</li>
                    <li>Encaissements guichet avec reçus</li>
                    <li>Suivi des sommes restant dues</li>
                    <li>Gestion des fiches de paie autorisées</li>
                    <li>Journal des flux financiers</li>
                </ul>
            </div>

            <!-- Surveillant Général -->
            <div class="role-feature-card">
                <span class="role-feature-icon">⏱️</span>
                <h4>Espace Surveillant</h4>
                <ul>
                    <li>Organisation des emplois du temps</li>
                    <li>Pointage des absences et des retards</li>
                    <li>Justification et régularisation des absences</li>
                    <li>Registre de discipline et sanctions</li>
                    <li>Suivi des effectifs par classe</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Section Établissements Partenaires Multi-Tenant -->
    <section style="background: var(--bg-card); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 50px 24px; transition: background 0.25s;">
        <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
            <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--primary-light); letter-spacing: 1px;">Architecture Multi-Établissements</span>
            <h4 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 6px; margin-bottom: 24px;">Établissements Scolaires Affiliés</h4>
            
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 24px;">
                <?php foreach ($schools as $s): ?>
                    <div style="background: var(--bg-main); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px 24px; display: flex; align-items: center; gap: 14px; min-width: 260px; text-align: left;">
                        <img src="<?= baseUrl($s['logo_path']) ?>" alt="Logo <?= e($s['name']) ?>" style="width: 44px; height: 44px; object-fit: contain; background: #fff; border-radius: 8px; padding: 2px;">
                        <div>
                            <div style="font-weight: 700; font-size: 14px; color: var(--text-main);"><?= e($s['name']) ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= e($s['city'] ?? 'Dakar') ?> &bullet; 📞 <?= e($s['phone']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Pied de Page Sombre et Large Élégant -->
    <footer class="site-footer">
        <div class="footer-content-grid">
            <!-- Colonne 1 : Plateforme & Marque -->
            <div class="footer-school-brand">
                <img src="<?= baseUrl('assets/img/logos/mboolo_logo.png') ?>" alt="Logo MBOOLO" class="footer-school-logo">
                <div>
                    <div class="footer-school-name">MBOOLO — Plateforme Scolaire Intégrée</div>
                    <div class="footer-school-slogan">« L'Excellence et la Proximité au Service de l'Éducation »</div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                        <span class="badge badge-success" style="font-size: 11px;">Plateforme Nationale SaaS</span>
                        <span class="badge badge-secondary" style="font-size: 11px;">Multi-Établissements</span>
                    </div>
                </div>
            </div>

            <!-- Colonne 2 : Siège & Assistance Technique -->
            <div>
                <div class="footer-section-title">Support & Contact</div>
                <div class="footer-school-coords">
                    <div>
                        <span>📍</span>
                        <span>Siège : Dakar, Sénégal</span>
                    </div>
                    <div>
                        <span>📞</span>
                        <span>Support Dédié : +221 33 800 00 00 (7j/7)</span>
                    </div>
                    <div>
                        <span>✉️</span>
                        <span>Assistance : contact@mboolo.sn</span>
                    </div>
                </div>
            </div>

            <!-- Colonne 3 : Modules & Cycles Couverts -->
            <div>
                <div class="footer-section-title">Modules Intégrés</div>
                <p style="font-size: 12.5px; color: var(--footer-muted); line-height: 1.6; margin-bottom: 10px;">
                    Élémentaire (CI-CM2), Collège (6e-3e), Lycée (Séries S, L, G, T).
                </p>
                <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--footer-muted);">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: #10b981; font-weight: bold;">✓</span> Emplois du temps & Assiduité en temps réel
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: #10b981; font-weight: bold;">✓</span> Alertes Parents (SMS & WhatsApp)
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: #10b981; font-weight: bold;">✓</span> Reçus de paiement Wave certifiés
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre inférieure Copyright -->
        <div class="footer-bottom-bar">
            <div>
                <span class="footer-brand-tag">MBOOLO</span> &copy; <?= date('Y') ?> — Système Global de Gestion Scolaire Multi-Tenant. Tous droits réservés.
            </div>
            <div>
                <span>Direction &bullet; Professeurs &bullet; Surveillants &bullet; Secrétaires &bullet; Parents &bullet; Élèves</span>
            </div>
        </div>
    </footer>

    <script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>

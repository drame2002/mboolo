<?php
// views/auth/login.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/SchoolService.php';

$schools = SchoolService::getAllSchools();
$pageTitle = "Connexion Sécurisée";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — MBOOLO</title>
    <script>
        (function() {
            var theme = localStorage.getItem('mboolo_theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
        })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            padding: 24px;
        }
        .auth-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .auth-header {
            background: var(--bg-table-header);
            padding: 28px;
            text-align: center;
            border-bottom: 1px solid var(--border);
            position: relative;
        }
        .auth-logo {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            margin-bottom: 12px;
            object-fit: contain;
        }
        .auth-body {
            padding: 28px;
        }
        .demo-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 14px;
            margin-bottom: 18px;
        }
        .demo-pill {
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-main);
        }
        .demo-pill:hover {
            background: var(--primary-light);
            color: #ffffff;
            border-color: var(--primary-light);
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <!-- Bouton retour Accueil -->
            <a href="<?= baseUrl('index.php?page=home') ?>" style="position: absolute; top: 16px; left: 16px; font-size: 12px; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); transition: all 0.2s;" title="Retour à l'Accueil">
                ← Accueil
            </a>
            <!-- Bouton Thème -->
            <button id="theme-toggle-btn" class="theme-toggle-btn" title="Changer de thème (Clair / Sombre)" style="position: absolute; top: 16px; right: 16px;">
                <span class="theme-icon">🌙</span>
            </button>
            <img src="<?= baseUrl('assets/img/logos/mboolo_logo.png') ?>" alt="Logo MBOOLO" class="auth-logo">
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">MBOOLO</h2>
            <p style="font-size: 13px; color: var(--text-muted);">Plateforme Intégrée de Gestion et Communication Scolaire</p>
        </div>

        <div class="auth-body">
            <!-- Alertes Flash -->
            <?php foreach (getFlashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endforeach; ?>

            <!-- Démonstration rapide : Sélection en 1 clic -->
            <div style="background: rgba(var(--primary-rgb), 0.06); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <span>⚡ Comptes de Démonstration (Cloisonnement Strict) :</span>
                    <span class="badge badge-success" style="font-size: 10px;">Multi-Tenant Isolé</span>
                </div>

                <!-- Établissement 1 : Lycée Excellence MBOOLO Dakar -->
                <div style="margin-bottom: 10px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--primary-light); margin-bottom: 4px;">
                        🏫 Établissement 1 : Lycée Excellence MBOOLO (Dakar)
                    </div>
                    <div class="demo-pills" style="margin-top: 4px; margin-bottom: 6px;">
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'directeur@mboolo.sn', 'pass123', 'directeur')" title="Directeur Cheikh Anta DIOP">👔 Dir. Diop</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'surveillant@mboolo.sn', 'pass123', 'surveillant')" title="Surveillant Mansour NDIAYE">⏱️ Surveillant</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'prof.maths@mboolo.sn', 'pass123', 'professeur')" title="Professeur Ousmane SOW">👨‍🏫 Prof. Maths</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'secretaire@mboolo.sn', 'pass123', 'secretaire')" title="Secrétaire Aïssatou BA">💼 Secrétaire</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'fatou.diop@mboolo.sn', 'pass123', 'eleve')" title="Élève Fatou DIOP">🎓 Élève</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(1, 'parent.diop@mboolo.sn', 'pass123', 'parent')" title="Parent Ibrahima DIOP">👨‍👩‍👧 Parent</button>
                    </div>
                </div>

                <!-- Établissement 2 : Collège & Lycée TÉRANGA Saint-Louis -->
                <div style="padding-top: 8px; border-top: 1px dashed var(--border);">
                    <div style="font-size: 11px; font-weight: 700; color: #10b981; margin-bottom: 4px;">
                        🏫 Établissement 2 : Collège & Lycée TÉRANGA (Saint-Louis)
                    </div>
                    <div class="demo-pills" style="margin-top: 4px; margin-bottom: 2px;">
                        <button type="button" class="demo-pill" onclick="fillDemo(2, 'directeur@teranga.sn', 'pass123', 'directeur')" title="Directeur Amadou KANE (Indépendant)">👔 Dir. Kane (Indépendant)</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(2, 'surveillant@teranga.sn', 'pass123', 'surveillant')" title="Surveillant Pape FALL">⏱️ Surveillant</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(2, 'prof.svt@teranga.sn', 'pass123', 'professeur')" title="Professeur Khady DIOUF">👩‍🏫 Prof. SVT</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(2, 'ibrahima.sall@teranga.sn', 'pass123', 'eleve')" title="Élève Ibrahima SALL">🎓 Élève</button>
                        <button type="button" class="demo-pill" onclick="fillDemo(2, 'parent.sall@teranga.sn', 'pass123', 'parent')" title="Parent Abdoulaye SALL">👨‍👩‍👦 Parent</button>
                    </div>
                </div>
            </div>

            <form action="<?= baseUrl('index.php?action=login') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="school_id">Établissement Scolaire</label>
                    <select name="school_id" id="school_id" class="form-control" required>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['id'] ?>">
                                <?= e($school['name']) ?> (<?= e($school['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Votre Rôle</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="directeur">Directeur / Chef d'établissement</option>
                        <option value="surveillant">Surveillant Général / Vie Scolaire</option>
                        <option value="professeur">Professeur / Enseignant</option>
                        <option value="secretaire">Secrétaire / Intendant(e)</option>
                        <option value="eleve">Élève</option>
                        <option value="parent">Parent d'élève</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="identifier">Identifiant ou Adresse E-mail</label>
                    <input type="text" name="identifier" id="identifier" class="form-control" placeholder="ex: fatou.diop@mboolo.sn ou nom_utilisateur" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; margin-top: 8px;">
                    Se Connecter à MBOOLO
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted);">
                Vous n'avez pas encore de compte ? <br>
                <a href="<?= baseUrl('index.php?page=register') ?>" style="font-weight: 700; color: var(--primary-light);">
                    Créer un nouveau compte utilisateur &rarr;
                </a>
            </div>
            <div style="text-align: center; margin-top: 14px;">
                <a href="<?= baseUrl('index.php?page=home') ?>" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">
                    ← Retour à l'Accueil MBOOLO
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
<script>
function fillDemo(schoolId, identifier, password, role) {
    document.getElementById('school_id').value = schoolId;
    document.getElementById('identifier').value = identifier;
    document.getElementById('password').value = password;
    document.getElementById('role').value = role;
}
</script>
</body>
</html>

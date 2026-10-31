<?php
// views/auth/register.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/SchoolService.php';
require_once __DIR__ . '/../../src/AcademicService.php';

$schools = SchoolService::getAllSchools();
$pdo = getDbConnection();
$allClasses = $pdo->query("SELECT id, school_id, name, level, cycle, track FROM classes ORDER BY cycle ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = "Création de Compte";
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
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            padding: 30px 16px;
        }
        .register-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 680px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .register-header {
            background: var(--bg-table-header);
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
            text-align: center;
            position: relative;
        }
        .register-body {
            padding: 32px;
        }
        .conditional-box {
            background: var(--bg-table-header);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <!-- Bouton retour Accueil -->
            <a href="<?= baseUrl('index.php?page=home') ?>" style="position: absolute; top: 16px; left: 16px; font-size: 12px; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); transition: all 0.2s;" title="Retour à l'Accueil">
                ← Accueil
            </a>
            <!-- Bouton Thème -->
            <button id="theme-toggle-btn" class="theme-toggle-btn" title="Changer de thème (Clair / Sombre)" style="position: absolute; top: 16px; right: 16px;">
                <span class="theme-icon">🌙</span>
            </button>
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Créer un Compte — MBOOLO</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Remplissez vos informations selon votre statut au sein de l'établissement</p>
        </div>

        <div class="register-body">
            <!-- Alertes Flash -->
            <?php foreach (getFlashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endforeach; ?>

            <form action="<?= baseUrl('index.php?action=register') ?>" method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="school_id">Établissement Scolaire *</label>
                        <select name="school_id" id="school_id" class="form-control" onchange="updateClassesList()" required>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['id'] ?>">
                                    <?= e($school['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">Rôle dans l'école *</label>
                        <select name="role" id="role" class="form-control" onchange="handleRoleChange()" required>
                            <option value="eleve">Élève</option>
                            <option value="parent">Parent d'élève</option>
                            <option value="professeur">Professeur / Enseignant</option>
                            <option value="surveillant">Surveillant Général / Vie Scolaire</option>
                            <option value="secretaire">Secrétaire / Intendant</option>
                            <option value="directeur">Directeur d'établissement</option>
                        </select>
                    </div>
                </div>

                <!-- Section spécifique ÉLÈVE (Cycle, Classe, Séries complètes S, L, G, T) -->
                <div id="student-section" class="conditional-box">
                    <div style="font-weight: 700; font-size: 13px; color: #1e40af; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <span>🎓 Informations Scolaires de l'Élève</span>
                        <span id="cycle-badge" class="badge badge-info" style="font-size: 11px;">Lycée</span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="cycle">Cycle Scolaire *</label>
                            <select name="cycle" id="cycle" class="form-control" onchange="handleCycleChange()">
                                <option value="Lycee" selected>Lycée (Secondaire : Seconde, Première, Terminale)</option>
                                <option value="College">Collège (Moyen : 6ème, 5ème, 4ème, 3ème)</option>
                                <option value="Elementaire">Élémentaire (Primaire : CI, CP, CE1, CE2, CM1, CM2)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="class_id">Classe d'affectation *</label>
                            <select name="class_id" id="class_id" class="form-control">
                                <!-- Rempli dynamiquement en JS selon l'école et le cycle sélectionné -->
                            </select>
                        </div>
                    </div>

                    <!-- Encadré informatif sur les matières enseignées selon le cycle -->
                    <div id="cycle-subjects-hint" style="font-size: 12px; line-height: 1.5; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2);">
                        <!-- Texte injecté en JS -->
                    </div>

                    <div class="form-row">
                        <!-- Champ Série pour Lycéens (Toutes les séries disponibles : S, L, G, T) -->
                        <div class="form-group" id="track-group">
                            <label class="form-label" for="track">Série du Lycée (Toutes séries disponibles : S, L, G, T) *</label>
                            <select name="track" id="track" class="form-control">
                                <optgroup label="🔬 Séries Scientifiques (S)">
                                    <option value="Scientifique (S1)">Série S1 — Mathématiques & Sciences Physiques (Série Pure)</option>
                                    <option value="Scientifique (S2)" selected>Série S2 — Sciences Expérimentales (SVT, PC & Maths)</option>
                                    <option value="Scientifique (S3)">Série S3 — Sciences & Technologies (Sciences de l'Ingénieur)</option>
                                </optgroup>
                                <optgroup label="📚 Séries Littéraires (L)">
                                    <option value="Littéraire (L1)">Série L1 — Langues & Littérature (L1a, L1b)</option>
                                    <option value="Littéraire (L2)">Série L2 — Sciences Humaines & Philosophie</option>
                                    <option value="Littéraire (L')">Série L' — Lettres Classiques & Langues Anciennes</option>
                                </optgroup>
                                <optgroup label="💼 Séries Tertiaires & Économie-Gestion (G)">
                                    <option value="Économie & Gestion (G)">Série G / STEG — Sciences & Technologies de l'Économie et de la Gestion</option>
                                    <option value="Techniques Administratives (G1)">Série G1 — Secrétariat & Administration</option>
                                    <option value="Techniques Quantitatives (G2)">Série G2 — Gestion Financière & Comptabilité</option>
                                </optgroup>
                                <optgroup label="⚙️ Séries Technologiques & Industrielles (T)">
                                    <option value="Génie Mécanique (T1)">Série T1 — Fabrication Mécanique & Conception Industrielle</option>
                                    <option value="Génie Électrique (T2)">Série T2 — Électronique & Électrotechnique</option>
                                </optgroup>
                                <optgroup label="🎯 Troncs Communs de Seconde">
                                    <option value="Scientifique (S)">Seconde S — Tronc Commun Scientifique</option>
                                    <option value="Littéraire (L)">Seconde L — Tronc Commun Littéraire</option>
                                    <option value="Économie & Gestion (G)">Seconde G — Tronc Commun Économie & Gestion</option>
                                    <option value="Technique Industrielle (T)">Seconde T — Tronc Commun Technique</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student_matricule">Matricule Scolaire *</label>
                            <input type="text" name="student_matricule" id="student_matricule" class="form-control" placeholder="ex: MB-2025-099">
                        </div>
                    </div>
                </div>

                <!-- Section spécifique PARENT (Spécification 7) -->
                <div id="parent-section" class="conditional-box" style="display: none;">
                    <div style="font-weight: 700; font-size: 13px; color: #7e22ce; margin-bottom: 12px;">
                        👨‍👩‍👧 Rapprochement avec un Enfant
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="parent_relation">Lien de parenté *</label>
                            <select name="parent_relation" id="parent_relation" class="form-control">
                                <option value="Pere">Père</option>
                                <option value="Mere">Mère</option>
                                <option value="Tuteur">Tuteur / Tutrice légale</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="child_matricule">Numéro de matricule de l'enfant (optionnel)</label>
                            <input type="text" name="child_matricule" id="child_matricule" class="form-control" placeholder="ex: MB-2025-001 (Peut aussi être associé après)">
                        </div>
                    </div>
                </div>

                <!-- Section de Vérification Administrative (Spécification 2 : Protection contre les accès non autorisés) -->
                <div id="admin-security-section" class="conditional-box" style="display: none; background: #fffbeb; border-color: #fef3c7;">
                    <div style="font-weight: 700; font-size: 13px; color: #b45309; margin-bottom: 8px;">
                        🔒 Habilitation et Vérification Institutionnelle
                    </div>
                    <p style="font-size: 12px; color: #92400e; margin-bottom: 12px;">
                        La création d'un compte de Direction ou de Secrétariat requiert un code d'habilitation officiel afin d'empêcher les accès non autorisés.
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="admin_access_code">Code d'Habilitation Administrative *</label>
                        <input type="password" name="admin_access_code" id="admin_access_code" class="form-control" placeholder="Entrez le code officiel (ex: MBOOLO-ADMIN-SECURE)">
                    </div>
                </div>

                <!-- Informations personnelles générales -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Prénom *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required placeholder="ex: Mouhamed">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Nom *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required placeholder="ex: Ndiaye">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="username">Identifiant de connexion unique *</label>
                        <input type="text" name="username" id="username" class="form-control" required placeholder="ex: mndiaye">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Adresse E-mail *</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="ex: mndiaye@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Numéro de Téléphone</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="+221 77 ...">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gender">Genre</label>
                        <select name="gender" id="gender" class="form-control">
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Mot de passe *</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Min. 6 caractères">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirmer le mot de passe *</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Retapez le mot de passe">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; margin-top: 10px;">
                    Finaliser mon Inscription sur MBOOLO
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 13px;">
                Vous possédez déjà un compte ? <br>
                <a href="<?= baseUrl('index.php?page=login') ?>" style="font-weight: 700; color: var(--primary-light);">
                    &larr; Retourner à la page de connexion
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

<script>
const allClasses = <?= json_encode($allClasses) ?>;

const cycleInfo = {
    'Elementaire': {
        label: 'Élémentaire (CI, CP, CE1, CE2, CM1, CM2)',
        badge: 'Élémentaire',
        hint: '📘 <strong>Cours Élémentaire :</strong> Mathématiques, Français (lecture/grammaire), IST (Initiation Scientifique et Technologique), Vivre ensemble, Vivre dans son milieu, EPS, Histoire-Géographie (HG), Arts plastiques.'
    },
    'College': {
        label: 'Collège (6ème, 5ème, 4ème, 3ème)',
        badge: 'Collège',
        hint: '📗 <strong>Cours Collège :</strong> Français, Mathématiques, SVT, Physique-Chimie (PC — <em>dès la classe de 4ème</em>), Latin, Éducation Musicale, Anglais (LV1), Espagnol (LV2 — <em>dès la 4ème</em>), Histoire-Géographie (HG), EPS.'
    },
    'Lycee': {
        label: 'Lycée (Seconde, Première, Terminale)',
        badge: 'Lycée',
        hint: '📙 <strong>Cours Lycée & Séries :</strong> Français, Philosophie, Mathématiques, Physique-Chimie (PC), SVT, Histoire-Géographie (HG), Anglais, Espagnol, Sciences Économiques & Gestion (Séries G), Sciences de l\'Ingénieur & Génie Industriel (Séries T), EPS.'
    }
};

function handleRoleChange() {
    const role = document.getElementById('role').value;
    const studentSec = document.getElementById('student-section');
    const parentSec = document.getElementById('parent-section');
    const adminSec = document.getElementById('admin-security-section');

    studentSec.style.display = (role === 'eleve') ? 'block' : 'none';
    parentSec.style.display = (role === 'parent') ? 'block' : 'none';
    adminSec.style.display = (role === 'directeur' || role === 'secretaire' || role === 'surveillant') ? 'block' : 'none';
}

function handleCycleChange() {
    const cycle = document.getElementById('cycle').value;
    const trackGroup = document.getElementById('track-group');
    const badge = document.getElementById('cycle-badge');
    const hint = document.getElementById('cycle-subjects-hint');

    // Affichage ou masquage de la série (Lycée uniquement)
    trackGroup.style.display = (cycle === 'Lycee') ? 'block' : 'none';

    // Mise à jour de l'encadré des cours
    if (cycleInfo[cycle]) {
        badge.textContent = cycleInfo[cycle].badge;
        hint.innerHTML = cycleInfo[cycle].hint;
    }

    updateClassesList();
}

function updateClassesList() {
    const schoolId = parseInt(document.getElementById('school_id').value, 10);
    const cycle = document.getElementById('cycle').value;
    const classSelect = document.getElementById('class_id');

    // Filtrer les classes selon l'établissement et le cycle
    const filtered = allClasses.filter(c => parseInt(c.school_id, 10) === schoolId && c.cycle === cycle);

    classSelect.innerHTML = '';
    if (filtered.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Aucune classe configurée pour ce cycle';
        classSelect.appendChild(opt);
    } else {
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            let label = c.name;
            if (c.track) {
                label += ' (' + c.track + ')';
            }
            opt.textContent = label;
            classSelect.appendChild(opt);
        });
    }
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    handleRoleChange();
    handleCycleChange();
});
</script>
<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
</body>
</html>

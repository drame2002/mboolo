<?php
// templates/header.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Auth.php';

$currentUser = Auth::user();
$currentSchool = Auth::school();

// Si l'utilisateur est connecté mais pas d'école en session, charger l'école
if ($currentUser && !$currentSchool) {
    require_once __DIR__ . '/../src/SchoolService.php';
    $currentSchool = SchoolService::getSchoolById($currentUser['school_id']);
    $_SESSION['mboolo_school'] = $currentSchool;
}

$pageTitle = $pageTitle ?? 'Plateforme Scolaire Intégrée';
$schoolLogo = !empty($currentSchool['logo_path']) ? baseUrl($currentSchool['logo_path']) : baseUrl('assets/img/logos/mboolo_logo.png');

// Pré-détection du thème (depuis cookie si présent)
$savedTheme = $_COOKIE['mboolo_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= e($savedTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — MBOOLO</title>
    
    <!-- Script d'application instantanée du thème et de l'état de la barre latérale pour éviter tout scintillement -->
    <script>
        (function() {
            var theme = localStorage.getItem('mboolo_theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
            if (localStorage.getItem('mboolo_sidebar_collapsed') === '1' && window.innerWidth > 992) {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <link rel="icon" type="image/png" href="<?= $schoolLogo ?>">
</head>
<body>

<?php if (Auth::check()): ?>
<!-- Fond semi-transparent pour fermeture de la barre sur petits écrans -->
<div id="sidebar-backdrop" class="sidebar-backdrop"></div>

<div class="app-wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="topbar">
            <div class="topbar-left">
                <!-- Un seul bouton coche/icône pour masquer ou remettre la barre latérale sans écriture -->
                <button id="sidebar-toggle-btn" class="sidebar-toggle-btn" type="button" aria-label="Menu" title="Afficher ou masquer la barre latérale">
                    <svg class="sidebar-toggle-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                
                <!-- Logo et Nom de l'établissement en haut des pages (Spécification 8) -->
                <div class="school-badge">
                    <img src="<?= $schoolLogo ?>" alt="Logo <?= e($currentSchool['name'] ?? 'MBOOLO') ?>">
                    <div class="school-badge-info">
                        <h4><?= e($currentSchool['name'] ?? 'MBOOLO Éducation') ?></h4>
                    </div>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Sélecteur de Thème compact (Lumineux / Sombre) -->
                <button id="theme-toggle-btn" class="theme-toggle-btn" type="button" title="Changer de thème (Clair / Sombre)">
                    <span class="theme-icon">🌙</span>
                </button>

                <!-- Profil Utilisateur -->
                <div class="user-profile-btn">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1) . substr($currentUser['last_name'] ?? 'M', 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <h5><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h5>
                        <span class="role-tag role-<?= e($currentUser['role']) ?>"><?= ucfirst(e($currentUser['role'])) ?></span>
                    </div>
                </div>

                <a href="<?= baseUrl('index.php?page=logout') ?>" class="btn btn-outline btn-sm" title="Se déconnecter" style="color:#ef4444; border-color:#fecaca;">
                    🚪 Déconnexion
                </a>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="content-body">
            <!-- Messages Flash -->
            <?php foreach (getFlashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span><?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'danger' ? '⚠️' : 'ℹ️') ?></span>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endforeach; ?>
<?php endif; ?>

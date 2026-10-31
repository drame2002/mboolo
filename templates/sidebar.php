<?php
// templates/sidebar.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Auth.php';

$role = Auth::role();
$page = $_GET['page'] ?? 'dashboard';
$currentSchool = Auth::school();
$schoolLogo = !empty($currentSchool['logo_path']) ? baseUrl($currentSchool['logo_path']) : baseUrl('assets/img/logos/mboolo_logo.png');
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= $schoolLogo ?>" alt="Logo" class="sidebar-logo">
        <div class="brand-text">
            <h1>MBOOLO</h1>
            <span><?= strtoupper(e($role)) ?></span>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" title="Masquer le menu" aria-label="Masquer le menu">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Navigation Principale</div>
        
        <a href="<?= baseUrl('index.php?page=dashboard') ?>" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
            <span class="icon">📊</span>
            <span>Tableau de bord</span>
        </a>

        <?php if ($role === 'directeur'): ?>
            <div class="nav-section-title">Direction & Pédagogie</div>
            <a href="<?= baseUrl('index.php?page=announcements') ?>" class="nav-item <?= $page === 'announcements' ? 'active' : '' ?>">
                <span class="icon">📢</span>
                <span>Communiqués officiels</span>
            </a>
            <a href="<?= baseUrl('index.php?page=director_meetings') ?>" class="nav-item <?= $page === 'director_meetings' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Convocations réunions</span>
            </a>
            <a href="<?= baseUrl('index.php?page=director_classes') ?>" class="nav-item <?= $page === 'director_classes' ? 'active' : '' ?>">
                <span class="icon">🏫</span>
                <span>Classes & Programmes</span>
            </a>
            <a href="<?= baseUrl('index.php?page=surveillant_classes') ?>" class="nav-item <?= $page === 'surveillant_classes' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Classes & Effectifs</span>
            </a>
            <a href="<?= baseUrl('index.php?page=director_report_cards') ?>" class="nav-item <?= $page === 'director_report_cards' ? 'active' : '' ?>">
                <span class="icon">📜</span>
                <span>Bulletins scolaires</span>
            </a>

            <div class="nav-section-title">Administration & Sécurité</div>
            <a href="<?= baseUrl('index.php?page=director_school_settings') ?>" class="nav-item <?= $page === 'director_school_settings' ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span>Personnalisation école</span>
            </a>
            <a href="<?= baseUrl('index.php?page=director_audit') ?>" class="nav-item <?= $page === 'director_audit' ? 'active' : '' ?>">
                <span class="icon">🛡️</span>
                <span>Audit & Traçabilité</span>
            </a>
            <a href="<?= baseUrl('index.php?page=director_backup') ?>" class="nav-item <?= $page === 'director_backup' ? 'active' : '' ?>">
                <span class="icon">💾</span>
                <span>Sauvegarde BDD</span>
            </a>

        <?php elseif ($role === 'surveillant'): ?>
            <div class="nav-section-title">Emplois du Temps & Organisation</div>
            <a href="<?= baseUrl('index.php?page=surveillant_timetable') ?>" class="nav-item <?= $page === 'surveillant_timetable' ? 'active' : '' ?>">
                <span class="icon">📅</span>
                <span>Gestion Emplois du Temps</span>
            </a>

            <div class="nav-section-title">Vie Scolaire & Assiduité</div>
            <a href="<?= baseUrl('index.php?page=surveillant_attendance') ?>" class="nav-item <?= $page === 'surveillant_attendance' ? 'active' : '' ?>">
                <span class="icon">⏱️</span>
                <span>Absences & Retards</span>
            </a>
            <a href="<?= baseUrl('index.php?page=surveillant_discipline') ?>" class="nav-item <?= $page === 'surveillant_discipline' ? 'active' : '' ?>">
                <span class="icon">⚖️</span>
                <span>Registre de Discipline</span>
            </a>
            <a href="<?= baseUrl('index.php?page=surveillant_classes') ?>" class="nav-item <?= $page === 'surveillant_classes' ? 'active' : '' ?>">
                <span class="icon">🏫</span>
                <span>Classes & Effectifs</span>
            </a>

            <div class="nav-section-title">Communication Direction</div>
            <a href="<?= baseUrl('index.php?page=announcements') ?>" class="nav-item <?= $page === 'announcements' ? 'active' : '' ?>">
                <span class="icon">📢</span>
                <span>Communiqués Officiels</span>
            </a>

        <?php elseif ($role === 'professeur'): ?>
            <div class="nav-section-title">Espace Pédagogique</div>
            <a href="<?= baseUrl('index.php?page=teacher_timetable') ?>" class="nav-item <?= $page === 'teacher_timetable' ? 'active' : '' ?>">
                <span class="icon">📅</span>
                <span>Emploi du temps & Durée</span>
            </a>
            <a href="<?= baseUrl('index.php?page=teacher_classes') ?>" class="nav-item <?= $page === 'teacher_classes' ? 'active' : '' ?>">
                <span class="icon">🎓</span>
                <span>Mes Classes & Matières</span>
            </a>
            <a href="<?= baseUrl('index.php?page=teacher_grades') ?>" class="nav-item <?= $page === 'teacher_grades' ? 'active' : '' ?>">
                <span class="icon">✍️</span>
                <span>Saisie des Notes</span>
            </a>
            <a href="<?= baseUrl('index.php?page=teacher_lessons') ?>" class="nav-item <?= $page === 'teacher_lessons' ? 'active' : '' ?>">
                <span class="icon">📖</span>
                <span>Résumés des Leçons</span>
            </a>
            <a href="<?= baseUrl('index.php?page=teacher_meetings') ?>" class="nav-item <?= $page === 'teacher_meetings' ? 'active' : '' ?>">
                <span class="icon">📨</span>
                <span>Convocations du Directeur</span>
            </a>

            <div class="nav-section-title">Rémunération Autorisée</div>
            <a href="<?= baseUrl('index.php?page=teacher_salaries') ?>" class="nav-item <?= $page === 'teacher_salaries' ? 'active' : '' ?>">
                <span class="icon">💰</span>
                <span>Mes Fiches de Paie</span>
            </a>

        <?php elseif ($role === 'eleve'): ?>
            <div class="nav-section-title">Mon Parcours Scolaire</div>
            <a href="<?= baseUrl('index.php?page=student_timetable') ?>" class="nav-item <?= $page === 'student_timetable' ? 'active' : '' ?>">
                <span class="icon">📅</span>
                <span>Emploi du temps</span>
            </a>
            <a href="<?= baseUrl('index.php?page=student_grades') ?>" class="nav-item <?= $page === 'student_grades' ? 'active' : '' ?>">
                <span class="icon">📈</span>
                <span>Notes & Moyennes</span>
            </a>
            <a href="<?= baseUrl('index.php?page=student_bulletin') ?>" class="nav-item <?= $page === 'student_bulletin' ? 'active' : '' ?>">
                <span class="icon">📜</span>
                <span>Bulletins Semestriels</span>
            </a>
            <a href="<?= baseUrl('index.php?page=student_lessons') ?>" class="nav-item <?= $page === 'student_lessons' ? 'active' : '' ?>">
                <span class="icon">📖</span>
                <span>Résumés de cours</span>
            </a>
            <a href="<?= baseUrl('index.php?page=announcements') ?>" class="nav-item <?= $page === 'announcements' ? 'active' : '' ?>">
                <span class="icon">📢</span>
                <span>Communiqués</span>
            </a>

            <div class="nav-section-title">Finances & Mensualités</div>
            <a href="<?= baseUrl('index.php?page=student_payments') ?>" class="nav-item <?= $page === 'student_payments' ? 'active' : '' ?>">
                <span class="icon">💳</span>
                <span>Payer ma mensualité</span>
            </a>
            <a href="<?= baseUrl('index.php?page=student_history') ?>" class="nav-item <?= $page === 'student_history' ? 'active' : '' ?>">
                <span class="icon">🧾</span>
                <span>Historique des paiements</span>
            </a>

        <?php elseif ($role === 'secretaire'): ?>
            <div class="nav-section-title">Scolarité & Mensualités</div>
            <a href="<?= baseUrl('index.php?page=secretary_tuition') ?>" class="nav-item <?= $page === 'secretary_tuition' ? 'active' : '' ?>">
                <span class="icon">💳</span>
                <span>Mensualités des élèves</span>
            </a>
            <a href="<?= baseUrl('index.php?page=secretary_record_payment') ?>" class="nav-item <?= $page === 'secretary_record_payment' ? 'active' : '' ?>">
                <span class="icon">💵</span>
                <span>Encaisser au Guichet</span>
            </a>
            <a href="<?= baseUrl('index.php?page=secretary_unpaid') ?>" class="nav-item <?= $page === 'secretary_unpaid' ? 'active' : '' ?>">
                <span class="icon">⏳</span>
                <span>Sommes restant dues</span>
            </a>

            <div class="nav-section-title">Salaires & Comptabilité</div>
            <a href="<?= baseUrl('index.php?page=secretary_salaries') ?>" class="nav-item <?= $page === 'secretary_salaries' ? 'active' : '' ?>">
                <span class="icon">💼</span>
                <span>Salaires des professeurs</span>
            </a>
            <a href="<?= baseUrl('index.php?page=secretary_ledger') ?>" class="nav-item <?= $page === 'secretary_ledger' ? 'active' : '' ?>">
                <span class="icon">📑</span>
                <span>Journal des opérations</span>
            </a>
            <a href="<?= baseUrl('index.php?page=announcements') ?>" class="nav-item <?= $page === 'announcements' ? 'active' : '' ?>">
                <span class="icon">📢</span>
                <span>Communiqués</span>
            </a>

        <?php elseif ($role === 'parent'): ?>
            <div class="nav-section-title">Mes Enfants & Scolarité</div>
            <a href="<?= baseUrl('index.php?page=parent_children') ?>" class="nav-item <?= $page === 'parent_children' ? 'active' : '' ?>">
                <span class="icon">👨‍👩‍👧‍👦</span>
                <span>Mes Enfants Rattachés</span>
            </a>
            <a href="<?= baseUrl('index.php?page=parent_link_child') ?>" class="nav-item <?= $page === 'parent_link_child' ? 'active' : '' ?>">
                <span class="icon">🔗</span>
                <span>Associer un enfant</span>
            </a>
            <a href="<?= baseUrl('index.php?page=parent_student_grades') ?>" class="nav-item <?= $page === 'parent_student_grades' ? 'active' : '' ?>">
                <span class="icon">📈</span>
                <span>Notes & Résultats</span>
            </a>
            <a href="<?= baseUrl('index.php?page=parent_student_bulletin') ?>" class="nav-item <?= $page === 'parent_student_bulletin' ? 'active' : '' ?>">
                <span class="icon">📜</span>
                <span>Bulletins Scolaires</span>
            </a>
            <a href="<?= baseUrl('index.php?page=parent_tuition') ?>" class="nav-item <?= $page === 'parent_tuition' ? 'active' : '' ?>">
                <span class="icon">💳</span>
                <span>Payer la mensualité</span>
            </a>
            <a href="<?= baseUrl('index.php?page=announcements') ?>" class="nav-item <?= $page === 'announcements' ? 'active' : '' ?>">
                <span class="icon">📢</span>
                <span>Communiqués du Directeur</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>

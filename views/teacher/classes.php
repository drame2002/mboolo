<?php
// views/teacher/classes.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['professeur']);

$teacherId = Auth::id();
$schoolId = Auth::schoolId();
$classes = AcademicService::getTeacherClassesAndSubjects($schoolId, $teacherId);
$pageTitle = "Mes Classes & Matières Enseignées";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🎓 Mes Classes & Matières</h1>
        <p class="page-subtitle">Liste des classes dont vous avez la charge pédagogique</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    <?php foreach ($classes as $c): ?>
        <div class="card">
            <div class="card-header" style="border-top: 4px solid <?= e($c['subject_color']) ?>;">
                <div>
                    <h3 class="card-title" style="font-size: 17px;"><?= e($c['class_name']) ?></h3>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Cycle : <?= e($c['cycle']) ?><?= $c['track'] ? ' &bullet; Série : ' . e($c['track']) : '' ?>
                    </div>
                </div>
                <span class="badge badge-info">Année <?= e($c['academic_year']) ?></span>
            </div>
            <div class="card-body">
                <div style="margin-bottom: 16px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block;">Matière enseignée :</span>
                    <strong style="font-size: 16px; color: var(--text-main);"><?= e($c['subject_name']) ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; background: var(--bg-main); border: 1px solid var(--border); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Coefficient</span>
                        <div style="font-weight: 800; font-size: 16px; color: var(--primary);"><?= $c['coefficient'] ?></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: var(--text-muted);">Volume horaire</span>
                        <div style="font-weight: 800; font-size: 16px; color: var(--primary);"><?= $c['weekly_hours'] ?> h / sem</div>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <a href="<?= baseUrl('index.php?page=teacher_grades&class_subject_id=' . $c['id']) ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                        ✍️ Saisir Notes
                    </a>
                    <a href="<?= baseUrl('index.php?page=teacher_lessons&class_subject_id=' . $c['id']) ?>" class="btn btn-outline btn-sm" style="flex: 1;">
                        📖 Résumés de leçons
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

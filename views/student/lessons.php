<?php
// views/student/lessons.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['eleve', 'parent']);

$user = Auth::user();
$schoolId = Auth::schoolId();
$profile = $user['student_profile'];
$classId = (int)($profile['class_id'] ?? 1);

$summaries = AcademicService::getSummariesForClass($schoolId, $classId, 40);
$pageTitle = "Cahier de Textes & Résumés des Leçons";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📖 Résumés des Cours & Devoirs</h1>
        <p class="page-subtitle">Cahier de texte numérique tenu par l'équipe des enseignants</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📚 Séances Récentes & Travail à Faire</h3>
    </div>
    <div class="card-body">
        <?php if (empty($summaries)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">
                Aucun résumé de leçon publié pour votre classe récemment.
            </p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 18px;">
                <?php foreach ($summaries as $s): ?>
                    <div style="border: 1px solid var(--border); border-left: 5px solid <?= e($s['subject_color']) ?>; border-radius: var(--radius-md); padding: 18px; background: var(--bg-card);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="badge" style="background: <?= e($s['subject_color']) ?>; color: #fff;">
                                    <?= e($s['subject_name']) ?>
                                </span>
                                <strong style="font-size: 16px;"><?= e($s['title']) ?></strong>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                Séance du <strong><?= formatDate($s['lesson_date']) ?></strong> &bullet; M. <?= e($s['teacher_last_name']) ?>
                            </div>
                        </div>

                        <p style="font-size: 14px; line-height: 1.6; color: var(--text-main); margin-bottom: 12px; white-space: pre-line;">
                            <?= nl2br(e($s['summary_content'])) ?>
                        </p>

                        <?php if (!empty($s['homework'])): ?>
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px;">
                                <strong style="color: #1e40af;">📝 Travail à faire / Devoirs :</strong> <?= e($s['homework']) ?>
                                <?php if (!empty($s['homework_due_date'])): ?>
                                    &bullet; <span style="color: #dc2626; font-weight: 700;">Pour le : <?= formatDate($s['homework_due_date']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<?php
// views/student/timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['eleve']);

$user = Auth::user();
$schoolId = Auth::schoolId();
$profile = $user['student_profile'];
$classId = (int)($profile['class_id'] ?? 1);

$timetable = AcademicService::getTimetableForClass($schoolId, $classId);
$pageTitle = "Mon Emploi du Temps";

$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$grouped = [];
foreach ($days as $d) {
    $grouped[$d] = [];
}
foreach ($timetable as $t) {
    $grouped[$t['day_of_week']][] = $t;
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📅 Mon Emploi du Temps</h1>
        <p class="page-subtitle">
            Classe : <strong><?= e($profile['class_name'] ?? 'Classe') ?></strong> 
            <?php if (($profile['cycle'] ?? '') === 'Elementaire'): ?>
                &bullet; Cycle : <span class="badge badge-success" style="font-size: 11px;">🎒 Élémentaire</span>
            <?php elseif (($profile['cycle'] ?? '') === 'College'): ?>
                &bullet; Cycle : <span class="badge badge-warning" style="font-size: 11px;">🏫 Collège</span>
            <?php else: ?>
                &bullet; Cycle : <span class="badge badge-info" style="font-size: 11px;">🎓 Lycée</span>
                <?php if (!empty($profile['track'])): ?>
                    &bullet; Série : <strong style="color: var(--primary-light);"><?= e($profile['track']) ?></strong>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="timetable-grid">
    <?php foreach ($days as $day): ?>
        <div class="timetable-day-col">
            <div class="day-header"><?= $day ?></div>
            <div style="padding: 6px; min-height: 240px;">
                <?php if (empty($grouped[$day])): ?>
                    <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 24px 0;">
                        Pas de cours
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped[$day] as $slot): ?>
                        <div class="time-slot" style="border-left-color: <?= e($slot['subject_color']) ?>;">
                            <div class="slot-time" style="display:flex; justify-content:space-between;">
                                <span><?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?></span>
                                <span class="badge badge-secondary" style="font-size:10px;"><?= $slot['duration_minutes'] ?> min</span>
                            </div>
                            <div class="slot-subject"><?= e($slot['subject_name']) ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                Prof. <?= e($slot['teacher_last_name']) ?>
                            </div>
                            <div class="slot-room">
                                📍 <?= e($slot['room'] ?? 'Salle habituelle') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

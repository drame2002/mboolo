<?php
// views/teacher/timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['professeur']);

$teacherId = Auth::id();
$schoolId = Auth::schoolId();
$timetable = AcademicService::getTimetableForTeacher($schoolId, $teacherId);
$classes = AcademicService::getTeacherClassesAndSubjects($schoolId, $teacherId);
$pageTitle = "Mon Emploi du Temps Pédagogique";

$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$grouped = [];
foreach ($days as $d) {
    $grouped[$d] = [];
}
$totalMinutes = 0;
foreach ($timetable as $t) {
    $grouped[$t['day_of_week']][] = $t;
    $totalMinutes += (int)$t['duration_minutes'];
}
$totalHours = round($totalMinutes / 60, 1);

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📅 Mon Emploi du Temps Hebdomadaire</h1>
        <p class="page-subtitle">Consultez l'ensemble de vos créneaux, les durées de cours et les salles affectées</p>
    </div>
    <div>
        <span class="badge badge-info" style="font-size: 14px; padding: 8px 16px;">
            Volume Total : <strong><?= $totalHours ?> heures</strong> (<?= $totalMinutes ?> minutes / semaine)
        </span>
    </div>
</div>

<div class="timetable-grid" style="margin-bottom: 28px;">
    <?php foreach ($days as $day): ?>
        <div class="timetable-day-col">
            <div class="day-header"><?= $day ?></div>
            <div style="padding: 6px; min-height: 220px;">
                <?php if (empty($grouped[$day])): ?>
                    <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 24px 0;">
                        Aucun cours
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped[$day] as $slot): ?>
                        <div class="time-slot" style="border-left-color: <?= e($slot['subject_color']) ?>;">
                            <div class="slot-time" style="display: flex; justify-content: space-between; align-items: center;">
                                <span><?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?></span>
                                <span class="badge badge-secondary" style="font-size: 10px; padding: 2px 6px;">
                                    ⏱️ <?= $slot['duration_minutes'] ?> min
                                </span>
                            </div>
                            <div class="slot-subject"><?= e($slot['subject_name']) ?></div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--primary-light);">
                                Classe : <?= e($slot['class_name']) ?>
                            </div>
                            <div class="slot-room">
                                📍 <?= e($slot['room'] ?? 'Non assignée') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Synthèse des classes et matières enseignées -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🎓 Synthèse de mes Enseignements & Horaires</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Classe</th>
                        <th>Cycle / Série</th>
                        <th>Volume Horaire Hebdo</th>
                        <th>Coefficient</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $c): ?>
                        <tr>
                            <td>
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?= e($c['subject_color']) ?>; margin-right:6px;"></span>
                                <strong><?= e($c['subject_name']) ?></strong> (<?= e($c['subject_code']) ?>)
                            </td>
                            <td><strong><?= e($c['class_name']) ?></strong></td>
                            <td><?= e($c['cycle']) ?><?= $c['track'] ? ' - ' . e($c['track']) : '' ?></td>
                            <td><strong><?= $c['weekly_hours'] ?> h / sem</strong></td>
                            <td><span class="badge badge-secondary">Coef <?= $c['coefficient'] ?></span></td>
                            <td>
                                <a href="<?= baseUrl('index.php?page=teacher_grades&class_subject_id=' . $c['id']) ?>" class="btn btn-primary btn-sm">
                                    ✍️ Saisir Notes
                                </a>
                                <a href="<?= baseUrl('index.php?page=teacher_lessons&class_subject_id=' . $c['id']) ?>" class="btn btn-outline btn-sm">
                                    📖 Cahier de texte
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<?php
// views/surveillant/timetable_manage.php — Organisation des Emplois du Temps par le Surveillant
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['surveillant', 'directeur']);

$schoolId = Auth::schoolId();
$classes = AcademicService::getClassesBySchool($schoolId);
$selectedClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['id'] ?? 0);

// Récupération des cours affectés à cette classe (Matière + Professeur)
$classSubjects = AcademicService::getClassSubjectsByClass($schoolId, $selectedClassId);

// Récupération de l'emploi du temps actuel de la classe
$timetableSlots = AcademicService::getTimetableForClass($schoolId, $selectedClassId);

// Organisation par jour
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$slotsByDay = [];
foreach ($days as $d) {
    $slotsByDay[$d] = [];
}
foreach ($timetableSlots as $slot) {
    if (isset($slotsByDay[$slot['day_of_week']])) {
        $slotsByDay[$slot['day_of_week']][] = $slot;
    }
}

$pageTitle = "Organisation des Emplois du Temps";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📅 Organisation des Emplois du Temps</h1>
        <p class="page-subtitle">Planification des cours, affectation des salles et détection automatique des conflits d'horaires</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-add-slot').style.display='flex'">
            ➕ Planifier un Nouveau Cours
        </button>
    </div>
</div>

<!-- Filtre par classe -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="surveillant_timetable">
            <label for="class_id" style="font-weight: 700; font-size: 14px; color: var(--text-main);">
                🏫 Sélectionner la Classe à organiser :
            </label>
            <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()" style="max-width: 380px; font-weight: 600;">
                <?php
                $classesByCycle = [
                    'Elementaire' => ['label' => '🎒 Élémentaire (CI à CM2)', 'items' => []],
                    'College'     => ['label' => '🏫 Collège (6ème à 3ème)', 'items' => []],
                    'Lycee'       => ['label' => '🎓 Lycée (Séries S, L, G, T)', 'items' => []],
                ];
                foreach ($classes as $c) {
                    $cyc = $c['cycle'] ?? 'Lycee';
                    if (isset($classesByCycle[$cyc])) {
                        $classesByCycle[$cyc]['items'][] = $c;
                    }
                }
                foreach ($classesByCycle as $grp):
                    if (empty($grp['items'])) continue;
                ?>
                    <optgroup label="<?= e($grp['label']) ?>">
                        <?php foreach ($grp['items'] as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] === $selectedClassId ? 'selected' : '' ?>>
                                <?= e($c['name']) ?><?= $c['track'] ? ' — ' . e($c['track']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <span style="font-size: 13px; color: var(--text-muted);">
                Total créneaux planifiés : <strong><?= count($timetableSlots) ?></strong>
            </span>
        </form>
    </div>
</div>

<!-- Grille Hebdomadaire de l'Emploi du Temps -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">🗓️ Grille Hebdomadaire des Cours</h3>
        <span style="font-size: 12px; color: var(--text-muted);">Durées calculées automatiquement en minutes</span>
    </div>
    <div class="card-body">
        <div class="timetable-grid">
            <?php foreach ($days as $day): ?>
                <div class="timetable-day-col">
                    <div class="day-header"><?= $day ?></div>
                    <?php if (empty($slotsByDay[$day])): ?>
                        <div style="padding: 24px 12px; text-align: center; color: var(--text-muted); font-size: 12px;">
                            Aucun cours
                        </div>
                    <?php else: ?>
                        <?php foreach ($slotsByDay[$day] as $s): ?>
                            <div class="time-slot" style="position: relative; border-left: 4px solid <?= e($s['subject_color'] ?? 'var(--primary-light)') ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div class="slot-time">
                                        <?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>
                                        <span style="font-size: 10px; font-weight: 600; color: var(--primary-light); background: rgba(59,130,246,0.1); padding: 1px 5px; border-radius: 4px; margin-left: 4px;">
                                            <?= $s['duration_minutes'] ?> min
                                        </span>
                                    </div>
                                    
                                    <!-- Bouton de suppression du créneau -->
                                    <form action="<?= baseUrl('index.php?action=delete_timetable_slot') ?>" method="POST" onsubmit="return confirm('Confirmer la suppression de ce créneau ?');" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="slot_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 13px; padding: 0 4px;" title="Supprimer ce créneau">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="slot-subject" style="font-weight: 700; font-size: 13px; margin-top: 4px; color: var(--text-main);">
                                    <?= e($s['subject_name']) ?>
                                </div>
                                <div class="slot-teacher" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    👨‍🏫 <?= e($s['teacher_first_name'] . ' ' . $s['teacher_last_name']) ?>
                                </div>
                                <div class="slot-room" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    📍 <?= e($s['room'] ?? 'Salle standard') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal d'Ajout d'un Créneau de Cours -->
<div id="modal-add-slot" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">➕ Planifier un Créneau de Cours</h3>
            <button type="button" onclick="document.getElementById('modal-add-slot').style.display='none'" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=add_timetable_slot') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">

                <div class="form-group">
                    <label class="form-label" for="class_subject_id">Matière & Enseignant Assigné *</label>
                    <select name="class_subject_id" id="class_subject_id" class="form-control" required>
                        <?php if (empty($classSubjects)): ?>
                            <option value="">Aucune matière affectée à cette classe</option>
                        <?php else: ?>
                            <?php foreach ($classSubjects as $cs): ?>
                                <option value="<?= $cs['id'] ?>">
                                    <?= e($cs['subject_name']) ?> (Coef. <?= $cs['coefficient'] ?>) — Prof. <?= e($cs['teacher_first_name'] . ' ' . $cs['teacher_last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="day_of_week">Jour de la Semaine *</label>
                    <select name="day_of_week" id="day_of_week" class="form-control" required>
                        <?php foreach ($days as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_time">Heure de Début *</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" value="08:00" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_time">Heure de Fin *</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" value="10:00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="room">Salle de Cours / Laboratoire *</label>
                    <input type="text" name="room" id="room" class="form-control" placeholder="ex: Salle 12, Labo SVT, Salle Informatique" value="Salle 12" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-add-slot').style.display='none'">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Enregistrer le Créneau
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

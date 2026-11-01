<?php
// views/surveillant/attendance.php — Suivi de l'Assiduité, Absences et Retards par le Surveillant
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';
require_once __DIR__ . '/../../src/SupervisorService.php';

Auth::requireRole(['surveillant', 'directeur']);

$schoolId = Auth::schoolId();
$classes = AcademicService::getClassesBySchool($schoolId);
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedClassId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Liste des absences et retards enregistrés
$attendances = SupervisorService::getAttendanceList($schoolId, $selectedDate, $selectedClassId);

// Liste de tous les élèves de l'école pour le formulaire de saisie
$pdo = getDbConnection();
$stmtStudents = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, sp.student_matricule, c.id as class_id, c.name as class_name
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    JOIN `classes` c ON sp.class_id = c.id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
    ORDER BY c.name ASC, u.last_name ASC
");
$stmtStudents->execute([$schoolId]);
$allStudents = $stmtStudents->fetchAll();

$pageTitle = "Suivi des Absences & Retards";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⏱️ Assiduité Scolaire — Absences & Retards</h1>
        <p class="page-subtitle">Pointage quotidien, régularisation des billets d'admission et suivi de l'assiduité</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-add-attendance').style.display='flex'">
            📝 Enregistrer une Absence / un Retard
        </button>
    </div>
</div>

<!-- Filtres de recherche -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="surveillant_attendance">
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="date" style="font-size: 13px; font-weight: 700; color: var(--text-main);">📅 Date :</label>
                <input type="date" name="date" id="date" class="form-control" value="<?= e($selectedDate) ?>" onchange="this.form.submit()" style="width: auto;">
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="class_id" style="font-size: 13px; font-weight: 700; color: var(--text-main);">🏫 Classe :</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()" style="width: auto;">
                    <option value="">Toutes les classes</option>
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
                                <option value="<?= $c['id'] ?>" <?= $selectedClassId === $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
        </form>
    </div>
</div>

<!-- Tableau du registre d'assiduité -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">📋 Registre du <?= date('d/m/Y', strtotime($selectedDate)) ?></h3>
        <span style="font-size: 13px; color: var(--text-muted);">
            Total signalements : <strong><?= count($attendances) ?></strong>
        </span>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($attendances)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">✅</div>
                <strong>Aucune absence ou retard signalé pour cette date.</strong>
                <p style="font-size: 13px; margin-top: 4px;">Tous les élèves sont considérés présents ou aucun pointage n'a été effectué.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Matricule & Classe</th>
                            <th>Créneau / Heure</th>
                            <th>Statut</th>
                            <th>Alerte Parent</th>
                            <th>Justification</th>
                            <th>Enregistré par</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendances as $a): ?>
                            <tr>
                                <td>
                                    <strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
                                    <?php if (!empty($a['student_phone'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted);">📞 <?= e($a['student_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary-light);"><?= e($a['class_name']) ?></span>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($a['student_matricule']) ?></div>
                                </td>
                                <td><?= e($a['time_slot'] ?? 'Journée') ?></td>
                                <td>
                                    <?php if ($a['status'] === 'retard'): ?>
                                        <span class="badge badge-warning">⏱️ En Retard</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">❌ Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; padding: 3px 8px; border-radius: 9999px; background: rgba(37, 211, 102, 0.12); color: #25d366; font-weight: 600; border: 1px solid rgba(37, 211, 102, 0.3);">
                                        <span>📲 Alerte Envoyée</span>
                                    </div>
                                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">SMS • WhatsApp • App</div>
                                </td>
                                <td>
                                    <?php if ($a['is_justified']): ?>
                                        <span class="badge badge-success">✓ Justifié</span>
                                        <?php if (!empty($a['justification_reason'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                                                <em><?= e($a['justification_reason']) ?></em>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Non justifié</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted);">
                                    <?= e(($a['recorder_first_name'] ?? 'Surveillant') . ' ' . ($a['recorder_last_name'] ?? '')) ?>
                                </td>
                                <td>
                                    <?php if (!$a['is_justified']): ?>
                                        <button class="btn btn-outline btn-sm" onclick="openJustifyModal(<?= $a['id'] ?>, '<?= e(addslashes($a['first_name'] . ' ' . $a['last_name'])) ?>')">
                                            📝 Justifier
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--success);">Régularisé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Saisie Absence / Retard -->
<div id="modal-add-attendance" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 540px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">📝 Pointer une Absence ou un Retard</h3>
            <button type="button" onclick="document.getElementById('modal-add-attendance').style.display='none'" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=record_attendance') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="student_id">Sélectionner l'Élève *</label>
                    <select name="student_id" id="student_id" class="form-control" required>
                        <option value="">-- Choisir un élève --</option>
                        <?php foreach ($allStudents as $st): ?>
                            <option value="<?= $st['id'] ?>">
                                [<?= e($st['class_name']) ?>] <?= e($st['last_name']) ?> <?= e($st['first_name']) ?> (<?= e($st['student_matricule']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="att_date">Date *</label>
                        <input type="date" name="date" id="att_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Nature du Pointage *</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="absent">Absence</option>
                            <option value="retard">Retard</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="time_slot">Créneau Horaire ou Matière concernée</label>
                    <input type="text" name="time_slot" id="time_slot" class="form-control" placeholder="ex: 08:00 - 10:00 (Mathématiques) ou Journée entière" value="Matinée (08:00 - 12:00)">
                </div>

                <!-- Notifications aux Parents -->
                <div style="background: rgba(var(--primary-rgb), 0.08); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary-light); display: flex; align-items: center; gap: 6px;">
                            <span>🔔 Notification immédiate des Parents</span>
                        </span>
                        <span class="badge badge-success" style="font-size: 10px;">Automatique</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            <input type="checkbox" name="notify_parent" value="1" checked>
                            <span>📲 Notification In-App & Email tuteur légal</span>
                        </label>
                        <div style="display: flex; gap: 16px; margin-left: 24px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px;">
                                <input type="checkbox" name="notify_sms" value="1" checked>
                                <span>💬 Alerte SMS (+221)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 12px;">
                                <input type="checkbox" name="notify_whatsapp" value="1" checked>
                                <span>🟢 Message WhatsApp</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                        <input type="checkbox" name="is_justified" value="1" onchange="document.getElementById('reason-group').style.display = this.checked ? 'block' : 'none'">
                        L'élève a fourni un justificatif recevable (billet médical, mot des parents)
                    </label>
                </div>

                <div id="reason-group" class="form-group" style="display: none;">
                    <label class="form-label" for="justification_reason">Motif de Justification</label>
                    <textarea name="justification_reason" id="justification_reason" class="form-control" rows="2" placeholder="ex: Certificat médical présenté ou panne de transport"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-add-attendance').style.display='none'">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Enregistrer le Pointage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Justifier Absence -->
<div id="modal-justify" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 480px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">📝 Justifier une Absence / Retard</h3>
            <button type="button" onclick="document.getElementById('modal-justify').style.display='none'" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=justify_attendance') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="attendance_id" id="justify-attendance-id" value="">
                
                <p style="font-size: 14px; margin-bottom: 14px;">
                    Élève concerné : <strong id="justify-student-name"></strong>
                </p>

                <div class="form-group">
                    <label class="form-label" for="reason">Motif / Justificatif présenté *</label>
                    <textarea name="justification_reason" id="reason" class="form-control" rows="3" placeholder="ex: Mot écrit du tuteur légal ou ordonnance médicale" required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-justify').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-success">✅ Valider le Justificatif</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openJustifyModal(attendanceId, studentName) {
    document.getElementById('justify-attendance-id').value = attendanceId;
    document.getElementById('justify-student-name').textContent = studentName;
    document.getElementById('modal-justify').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

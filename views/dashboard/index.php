<?php
// views/dashboard/index.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/CommunicationService.php';
require_once __DIR__ . '/../../src/AcademicService.php';
require_once __DIR__ . '/../../src/FinanceService.php';
require_once __DIR__ . '/../../src/ParentService.php';
require_once __DIR__ . '/../../src/SupervisorService.php';

Auth::requireRole(['directeur', 'surveillant', 'professeur', 'secretaire', 'eleve', 'parent']);

$user = Auth::user();
$school = Auth::school();
$schoolId = Auth::schoolId();
$role = Auth::role();
$pageTitle = "Tableau de Bord — " . ucfirst($role);

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Bienvenue, <?= e($user['first_name'] . ' ' . $user['last_name']) ?> 👋</h1>
        <p class="page-subtitle">
            Espace <?= ucfirst(e($role)) ?> &bullet; Établissement : <strong><?= e($school['name']) ?></strong>
        </p>
    </div>
    <div>
        <span class="badge badge-info" style="font-size: 13px; padding: 6px 14px;">
            Année Académique 2025-2026
        </span>
    </div>
</div>

<?php if ($role === 'directeur'): ?>
    <?php
    $pdo = getDbConnection();
    $totalStudents = $pdo->prepare("SELECT COUNT(*) FROM users WHERE school_id = ? AND role = 'eleve' AND is_active = 1");
    $totalStudents->execute([$schoolId]);
    $countStudents = $totalStudents->fetchColumn();

    $totalTeachers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE school_id = ? AND role = 'professeur' AND is_active = 1");
    $totalTeachers->execute([$schoolId]);
    $countTeachers = $totalTeachers->fetchColumn();

    $totalClasses = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE school_id = ?");
    $totalClasses->execute([$schoolId]);
    $countClasses = $totalClasses->fetchColumn();

    $meetings = CommunicationService::getMeetingsForDirector($schoolId);
    $announcements = CommunicationService::getAnnouncementsFor($schoolId, 'directeur');
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">🎓</div>
            <div>
                <div class="stat-value"><?= $countStudents ?></div>
                <div class="stat-label">Élèves Inscrits</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">👨‍🏫</div>
            <div>
                <div class="stat-value"><?= $countTeachers ?></div>
                <div class="stat-label">Professeurs Enseignants</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5; color:#059669;">🏫</div>
            <div>
                <div class="stat-value"><?= $countClasses ?></div>
                <div class="stat-label">Classes Ouvertes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fffbeb; color:#d97706;">👥</div>
            <div>
                <div class="stat-value"><?= count($meetings) ?></div>
                <div class="stat-label">Réunions Convoquées</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div>
            <!-- Convocations & Réunions urgentes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👥 Convocations des Professeurs & Réunions</h3>
                    <a href="<?= baseUrl('index.php?page=director_meetings') ?>" class="btn btn-outline btn-sm">Gérer les convocations</a>
                </div>
                <div class="card-body">
                    <?php if (empty($meetings)): ?>
                        <p style="color: var(--text-muted);">Aucune réunion planifiée pour le moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Intitulé & Type</th>
                                        <th>Lieu</th>
                                        <th>Présences / Réponses</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($meetings, 0, 4) as $m): ?>
                                        <tr>
                                            <td><strong><?= formatDate($m['meeting_date'], true) ?></strong></td>
                                            <td>
                                                <?= e($m['title']) ?><br>
                                                <span class="badge <?= $m['meeting_type'] === 'urgente' ? 'badge-danger' : 'badge-info' ?>">
                                                    <?= strtoupper(e($m['meeting_type'])) ?>
                                                </span>
                                            </td>
                                            <td><?= e($m['location']) ?></td>
                                            <td>
                                                <span class="badge badge-success"><?= $m['count_present'] ?> Présent(s)</span>
                                                <span class="badge badge-warning"><?= $m['count_excused'] ?> Excusé(s)</span>
                                                <span style="font-size: 11px; color: var(--text-muted);">/ <?= $m['total_invited'] ?> profs</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary"><?= ucfirst(e($m['status'])) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Derniers communiqués officiels -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📢 Communiqués Récents Diffusés</h3>
                    <a href="<?= baseUrl('index.php?page=announcements') ?>" class="btn btn-outline btn-sm">Publier un communiqué</a>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <p style="color: var(--text-muted);">Aucun communiqué pour l'instant.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($announcements, 0, 3) as $ann): ?>
                            <div style="border-left: 4px solid <?= $ann['urgency_level'] === 'urgent' ? '#ef4444' : '#3b82f6' ?>; padding-left: 14px; margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h4 style="font-size: 15px; font-weight: 700;"><?= e($ann['title']) ?></h4>
                                    <span class="badge <?= $ann['urgency_level'] === 'urgent' ? 'badge-danger' : 'badge-secondary' ?>">
                                        <?= strtoupper(e($ann['urgency_level'])) ?>
                                    </span>
                                </div>
                                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 6px 0;">
                                    Publié le <?= formatDate($ann['published_at'], true) ?> &bullet; Cible : <strong><?= ucfirst(e($ann['target_audience'])) ?></strong>
                                </p>
                                <p style="font-size: 13px; line-height: 1.4; color: var(--text-main);">
                                    <?= nl2br(e(substr($ann['content'], 0, 180))) ?>...
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <!-- Actions Rapides Directeur -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚡ Actions Directes</h3>
                </div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="<?= baseUrl('index.php?page=announcements') ?>" class="btn btn-primary">
                        📢 Nouveau Communiqué
                    </a>
                    <a href="<?= baseUrl('index.php?page=director_meetings') ?>" class="btn btn-accent">
                        👥 Convoquer une Réunion
                    </a>
                    <a href="<?= baseUrl('index.php?page=director_school_settings') ?>" class="btn btn-outline">
                        ⚙️ Personnaliser Logo & Établissement
                    </a>
                    <a href="<?= baseUrl('index.php?page=director_report_cards') ?>" class="btn btn-outline">
                        📜 Voir les Bulletins Scolaires
                    </a>
                    <a href="<?= baseUrl('index.php?page=director_backup') ?>" class="btn btn-outline" style="border-color: #cbd5e1;">
                        💾 Sauvegarder la Base de Données
                    </a>
                </div>
            </div>

            <!-- Identité de l'école -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🏫 Établissement</h3>
                </div>
                <div class="card-body" style="text-align: center;">
                    <img src="<?= baseUrl($school['logo_path'] ?? 'assets/img/logos/mboolo_logo.png') ?>" alt="Logo" style="width: 75px; height: 75px; object-fit: contain; margin-bottom: 10px;">
                    <h4 style="font-size: 15px; font-weight: 800;"><?= e($school['name']) ?></h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= e($school['slogan']) ?></p>
                    <div style="margin-top: 12px; font-size: 12px; text-align: left; background: var(--bg-table-hover); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        <div><strong>Téléphone :</strong> <?= e($school['phone']) ?></div>
                        <div><strong>Email :</strong> <?= e($school['email']) ?></div>
                        <div><strong>Adresse :</strong> <?= e($school['address']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($role === 'surveillant'): ?>
    <?php
    $stats = SupervisorService::getDashboardStats($schoolId);
    $todayAttendance = SupervisorService::getAttendanceList($schoolId, date('Y-m-d'));
    $openDisciplines = SupervisorService::getDisciplinaryRecords($schoolId, null, 'ouvert');
    $announcements = CommunicationService::getAnnouncementsFor($schoolId, 'surveillant');
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">📅</div>
            <div>
                <div class="stat-value"><?= $stats['total_slots'] ?></div>
                <div class="stat-label">Créneaux de Cours Planifiés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2; color:#ef4444;">❌</div>
            <div>
                <div class="stat-value"><?= $stats['today_absences'] ?></div>
                <div class="stat-label">Absences du Jour</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fffbeb; color:#d97706;">⏱️</div>
            <div>
                <div class="stat-value"><?= $stats['today_retards'] ?></div>
                <div class="stat-label">Retards du Jour</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf2f8; color:#db2777;">⚖️</div>
            <div>
                <div class="stat-value"><?= $stats['open_incidents'] ?></div>
                <div class="stat-label">Incidents en Cours</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div>
            <!-- Actions Rapides Vie Scolaire -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h3 class="card-title">⚡ Actions du Surveillant Général</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                        <a href="<?= baseUrl('index.php?page=surveillant_timetable') ?>" class="btn btn-outline" style="padding: 16px; text-align: left; display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 24px;">📅</span>
                            <div>
                                <strong style="display: block; color: var(--text-main);">Emplois du Temps</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">Organiser et affecter les cours</span>
                            </div>
                        </a>
                        <a href="<?= baseUrl('index.php?page=surveillant_attendance') ?>" class="btn btn-outline" style="padding: 16px; text-align: left; display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 24px;">⏱️</span>
                            <div>
                                <strong style="display: block; color: var(--text-main);">Assiduité & Absences</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">Faire l'appel et justifier</span>
                            </div>
                        </a>
                        <a href="<?= baseUrl('index.php?page=surveillant_discipline') ?>" class="btn btn-outline" style="padding: 16px; text-align: left; display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 24px;">⚖️</span>
                            <div>
                                <strong style="display: block; color: var(--text-main);">Discipline & Sanctions</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">Consigner un incident</span>
                            </div>
                        </a>
                        <a href="<?= baseUrl('index.php?page=surveillant_classes') ?>" class="btn btn-outline" style="padding: 16px; text-align: left; display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 24px;">🏫</span>
                            <div>
                                <strong style="display: block; color: var(--text-main);">Classes & Effectifs</strong>
                                <span style="font-size: 11px; color: var(--text-muted);">Effectifs et suivi des élèves</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Absences & Retards du Jour -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">⏱️ Pointage de la Journée</h3>
                    <a href="<?= baseUrl('index.php?page=surveillant_attendance') ?>" class="btn btn-outline btn-sm">Voir tout le registre</a>
                </div>
                <div class="card-body">
                    <?php if (empty($todayAttendance)): ?>
                        <p style="color: var(--text-muted);">Aucune absence ou retard signalé aujourd'hui.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Classe</th>
                                        <th>Statut</th>
                                        <th>Justificatif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($todayAttendance, 0, 5) as $att): ?>
                                        <tr>
                                            <td><strong><?= e($att['first_name'] . ' ' . $att['last_name']) ?></strong></td>
                                            <td><?= e($att['class_name']) ?></td>
                                            <td>
                                                <span class="badge <?= $att['status'] === 'retard' ? 'badge-warning' : 'badge-danger' ?>">
                                                    <?= ucfirst(e($att['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $att['is_justified'] ? '<span class="badge badge-success">Justifié</span>' : '<span class="badge badge-secondary">Non régularisé</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <!-- Communiqués de la Direction -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📢 Communiqués de la Direction</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <p style="color: var(--text-muted); font-size: 13px;">Aucun communiqué officiel en cours.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($announcements, 0, 4) as $ann): ?>
                            <div style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="font-size: 13px; color: var(--text-main);"><?= e($ann['title']) ?></strong>
                                    <?php if ($ann['priority'] === 'urgent'): ?>
                                        <span class="badge badge-danger">Urgent</span>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    <?= e(substr($ann['content'], 0, 100)) ?>...
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($role === 'professeur'): ?>
    <?php
    $teacherId = Auth::id();
    $classes = AcademicService::getTeacherClassesAndSubjects($schoolId, $teacherId);
    $timetable = AcademicService::getTimetableForTeacher($schoolId, $teacherId);
    $meetings = CommunicationService::getMeetingsForTeacher($schoolId, $teacherId);
    $salaries = FinanceService::getTeacherAuthorizedSalaries($schoolId, $teacherId);

    $totalWeeklyHours = 0;
    foreach ($classes as $c) {
        $totalWeeklyHours += (float)$c['weekly_hours'];
    }
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">⏰</div>
            <div>
                <div class="stat-value"><?= $totalWeeklyHours ?> h</div>
                <div class="stat-label">Volume Horaire Hebdo</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5; color:#059669;">📚</div>
            <div>
                <div class="stat-value"><?= count($classes) ?></div>
                <div class="stat-label">Cours & Classes Assignés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">📅</div>
            <div>
                <div class="stat-value"><?= count($timetable) ?></div>
                <div class="stat-label">Créneaux d'Emploi du Temps</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fffbeb; color:#d97706;">📨</div>
            <div>
                <div class="stat-value"><?= count($meetings) ?></div>
                <div class="stat-label">Convocations Reçues</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div>
            <!-- Emploi du temps résumé avec durée des cours -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📅 Mon Emploi du Temps Hebdomadaire (avec durée des cours)</h3>
                    <a href="<?= baseUrl('index.php?page=teacher_timetable') ?>" class="btn btn-outline btn-sm">Vue complète</a>
                </div>
                <div class="card-body">
                    <?php if (empty($timetable)): ?>
                        <p style="color: var(--text-muted);">Aucun cours planifié pour le moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Jour</th>
                                        <th>Horaires</th>
                                        <th>Durée</th>
                                        <th>Matière</th>
                                        <th>Classe</th>
                                        <th>Salle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timetable as $t): ?>
                                        <tr>
                                            <td><strong><?= e($t['day_of_week']) ?></strong></td>
                                            <td><?= substr($t['start_time'], 0, 5) ?> - <?= substr($t['end_time'], 0, 5) ?></td>
                                            <td><span class="badge badge-info"><?= $t['duration_minutes'] ?> min</span></td>
                                            <td>
                                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?= e($t['subject_color']) ?>; margin-right:5px;"></span>
                                                <strong><?= e($t['subject_name']) ?></strong>
                                            </td>
                                            <td><?= e($t['class_name']) ?></td>
                                            <td><?= e($t['room'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Convocations de réunions par le Directeur -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📨 Convocations aux Réunions du Directeur</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($meetings)): ?>
                        <p style="color: var(--text-muted);">Aucune convocation pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($meetings as $meet): ?>
                            <div style="border: 1px solid <?= $meet['meeting_type'] === 'urgente' ? 'rgba(239, 68, 68, 0.4)' : 'var(--border)' ?>; background: <?= $meet['meeting_type'] === 'urgente' ? 'rgba(239, 68, 68, 0.08)' : 'var(--bg-card)' ?>; border-radius: var(--radius-md); padding: 16px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <h4 style="font-size: 15px; font-weight: 700; color: <?= $meet['meeting_type'] === 'urgente' ? 'var(--danger)' : 'var(--text-main)' ?>;">
                                        <?= e($meet['title']) ?>
                                    </h4>
                                    <span class="badge <?= $meet['meeting_type'] === 'urgente' ? 'badge-danger' : 'badge-info' ?>">
                                        <?= strtoupper(e($meet['meeting_type'])) ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                                    📅 <?= formatDate($meet['meeting_date'], true) ?> &bullet; 📍 <?= e($meet['location']) ?> &bullet; Par : M. <?= e($meet['org_last_name']) ?>
                                </div>
                                <p style="font-size: 13px; margin-bottom: 12px;"><?= nl2br(e($meet['agenda'])) ?></p>
                                
                                <form action="<?= baseUrl('index.php?action=teacher_respond_meeting') ?>" method="POST" style="display: flex; gap: 10px; align-items: center;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="meeting_id" value="<?= $meet['id'] ?>">
                                    
                                    <span style="font-size: 13px; font-weight: 600;">Mon statut :</span>
                                    <select name="attendance_status" class="form-control" style="width: auto; padding: 4px 10px; font-size: 12px;">
                                        <option value="present" <?= $meet['attendance_status'] === 'present' ? 'selected' : '' ?>>Présent</option>
                                        <option value="excuse" <?= $meet['attendance_status'] === 'excuse' ? 'selected' : '' ?>>Excusé / Empêché</option>
                                    </select>
                                    <input type="text" name="comment" value="<?= e($meet['response_comment'] ?? '') ?>" placeholder="Observation ou justification" class="form-control" style="font-size: 12px; padding: 4px 10px;">
                                    <button type="submit" class="btn btn-primary btn-sm">Valider</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <!-- Actions Rapides Professeur -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚡ Actions Rapides</h3>
                </div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="<?= baseUrl('index.php?page=teacher_grades') ?>" class="btn btn-primary">
                        ✍️ Saisir les Notes des Élèves
                    </a>
                    <a href="<?= baseUrl('index.php?page=teacher_lessons') ?>" class="btn btn-accent">
                        📖 Publier un Résumé de Leçon
                    </a>
                    <a href="<?= baseUrl('index.php?page=teacher_timetable') ?>" class="btn btn-outline">
                        📅 Consulter mon Emploi du Temps
                    </a>
                    <a href="<?= baseUrl('index.php?page=teacher_salaries') ?>" class="btn btn-outline">
                        💰 Consulter mes Fiches de Paie
                    </a>
                </div>
            </div>

            <!-- Aperçu Rémunération Autorisée (Spécification 4) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">💰 Mon Salaire (Accès Autorisé)</h3>
                    <a href="<?= baseUrl('index.php?page=teacher_salaries') ?>" class="btn btn-outline btn-sm">Détails</a>
                </div>
                <div class="card-body">
                    <?php if (empty($salaries)): ?>
                        <p style="font-size: 13px; color: var(--text-muted);">Aucun bulletin de salaire disponible.</p>
                    <?php else: ?>
                        <?php $lastSal = $salaries[0]; ?>
                        <div style="background: var(--bg-table-hover); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <div style="font-size: 12px; color: var(--text-muted);">Dernière fiche de paie enregistrée :</div>
                            <div style="font-size: 15px; font-weight: 800; color: var(--text-main); margin: 4px 0;">
                                <?= e($lastSal['month']) ?> <?= e($lastSal['year']) ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 800; color: var(--success); margin: 6px 0;">
                                <?= formatMoney($lastSal['net_salary'], $lastSal['currency']) ?>
                            </div>
                            <span class="badge <?= $lastSal['payment_status'] === 'paye' ? 'badge-success' : 'badge-warning' ?>">
                                <?= strtoupper(e($lastSal['payment_status'])) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 10px; font-style: italic;">
                        Conformément aux règles de sécurité, vous n'avez accès qu'à votre rémunération personnelle sans visibilité sur les finances globales.
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($role === 'eleve'): ?>
    <?php
    $studentId = Auth::id();
    $profile = $user['student_profile'];
    $gradesData = AcademicService::getStudentGradesOverview($schoolId, $studentId, 'Semestre 1');
    $feeStatus = FinanceService::getStudentFeeStatus($schoolId, $studentId);
    $summaries = AcademicService::getSummariesForClass($schoolId, $profile['class_id'] ?? 1, 5);
    ?>

    <div class="card" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff; margin-bottom: 24px;">
        <div class="card-body" style="padding: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #fff; margin-bottom: 8px;">
                    Matricule : <?= e($profile['student_matricule'] ?? 'MB-2025') ?>
                </span>
                <h2 style="font-size: 22px; font-weight: 800;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                <p style="font-size: 14px; opacity: 0.9; margin-top: 4px;">
                    Classe : <strong><?= e($profile['class_name'] ?? 'Terminale S2') ?></strong> 
                    &bullet; Cycle : <strong><?= e($profile['cycle'] ?? 'Lycée') ?></strong>
                    <?php if (!empty($profile['track'])): ?>
                        &bullet; Série : <strong style="color: var(--accent);"><?= e($profile['track']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85;">Moyenne Générale (Semestre 1)</div>
                <div style="font-size: 32px; font-weight: 800; color: #fef08a;">
                    <?= $gradesData['general_average'] ? number_format($gradesData['general_average'], 2) . ' / 20' : 'En attente' ?>
                </div>
                <a href="<?= baseUrl('index.php?page=student_bulletin') ?>" class="btn btn-sm" style="background: #ffffff; color: #1e3a8a; font-weight: 700; margin-top: 6px;">
                    📜 Consulter mon Bulletin Officiel
                </a>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">📈</div>
            <div>
                <div class="stat-value"><?= $gradesData['general_average'] ? number_format($gradesData['general_average'], 2) : '-' ?></div>
                <div class="stat-label">Moyenne Semestre 1</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5; color:#059669;">💳</div>
            <div>
                <div class="stat-value"><?= formatMoney($feeStatus['total_paid']) ?></div>
                <div class="stat-label">Mensualités Réglées</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">⏳</div>
            <div>
                <div class="stat-value"><?= formatMoney($feeStatus['balance_remaining']) ?></div>
                <div class="stat-label">Reste à Payer</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">📖</div>
            <div>
                <div class="stat-value"><?= count($summaries) ?></div>
                <div class="stat-label">Résumés de Cours Récents</div>
            </div>
        </div>
    </div>

    <!-- Aperçu des notes scolaires -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📈 Aperçu de mes Résultats Scolaires</h3>
            <a href="<?= baseUrl('index.php?page=student_grades') ?>" class="btn btn-outline btn-sm">Détail par devoir & notes</a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($gradesData['subjects'] as $sub): ?>
                    <div style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; background: var(--bg-card);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 12px; height: 12px; border-radius: 3px; background: <?= e($sub['subject_color']) ?>;"></span>
                                <strong style="font-size: 14px;"><?= e($sub['subject_name']) ?></strong>
                            </div>
                            <span class="badge badge-secondary">Coef <?= $sub['coefficient'] ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; background: var(--bg-table-hover); padding: 8px 12px; border-radius: var(--radius-sm); margin-top: 6px;">
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted); display:block;">Moyenne Éléve</span>
                                <strong style="font-size: 18px; color: var(--primary-light);">
                                    <?= $sub['student_average'] !== null ? number_format($sub['student_average'], 2) : '-' ?>
                                </strong>
                            </div>
                            <div style="text-align: right; font-size: 11px; color: var(--text-muted);">
                                Moy. Classe : <strong><?= $sub['class_avg'] ? number_format($sub['class_avg'], 2) : '-' ?></strong><br>
                                Min : <?= $sub['class_min'] ?? '-' ?> &bullet; Max : <?= $sub['class_max'] ?? '-' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php elseif ($role === 'secretaire'): ?>
    <?php
    $pdo = getDbConnection();
    // Somme totale encaissée
    $stmtPaid = $pdo->prepare("SELECT SUM(amount_paid) FROM student_payments WHERE school_id = ? AND payment_status = 'paye'");
    $stmtPaid->execute([$schoolId]);
    $totalEncaisse = (float)$stmtPaid->fetchColumn();

    // Salaires du mois
    $stmtSal = $pdo->prepare("SELECT SUM(net_salary) FROM teacher_salaries WHERE school_id = ? AND payment_status = 'paye'");
    $stmtSal->execute([$schoolId]);
    $totalSalairesPayes = (float)$stmtSal->fetchColumn();

    $ledger = FinanceService::getFinancialLedger($schoolId, 6);
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5; color:#059669;">💵</div>
            <div>
                <div class="stat-value"><?= formatMoney($totalEncaisse) ?></div>
                <div class="stat-label">Total Scolarité Encaissé</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">💼</div>
            <div>
                <div class="stat-value"><?= formatMoney($totalSalairesPayes) ?></div>
                <div class="stat-label">Salaires Professeurs Réglés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">📑</div>
            <div>
                <div class="stat-value"><?= count($ledger) ?></div>
                <div class="stat-label">Opérations Récentes</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📑 Journal des Opérations Financières Récentes</h3>
                    <a href="<?= baseUrl('index.php?page=secretary_ledger') ?>" class="btn btn-outline btn-sm">Journal complet</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Nature & Description</th>
                                    <th>Mode</th>
                                    <th>Sens</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ledger as $op): ?>
                                    <tr>
                                        <td><?= formatDate($op['op_date'], true) ?></td>
                                        <td>
                                            <strong><?= e($op['description']) ?></strong><br>
                                            <span style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= e($op['reference']) ?></span>
                                        </td>
                                        <td><span class="badge badge-secondary"><?= strtoupper(e($op['payment_method'])) ?></span></td>
                                        <td>
                                            <span class="badge <?= $op['direction'] === 'CREDIT' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $op['direction'] === 'CREDIT' ? '+ ENCAISSEMENT' : '- DÉCAISSEMENT' ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 800; color: <?= $op['direction'] === 'CREDIT' ? '#166534' : '#991b1b' ?>;">
                                            <?= formatMoney($op['amount']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚡ Actions Guichet & Finances</h3>
                </div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="<?= baseUrl('index.php?page=secretary_record_payment') ?>" class="btn btn-primary">
                        💵 Enregistrer un Paiement Guichet
                    </a>
                    <a href="<?= baseUrl('index.php?page=secretary_unpaid') ?>" class="btn btn-accent">
                        ⏳ Consulter les Sommes Restant Dues
                    </a>
                    <a href="<?= baseUrl('index.php?page=secretary_salaries') ?>" class="btn btn-outline">
                        💼 Gérer les Salaires des Professeurs
                    </a>
                    <a href="<?= baseUrl('index.php?page=secretary_tuition') ?>" class="btn btn-outline">
                        💳 Grille des Mensualités
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($role === 'parent'): ?>
    <?php
    $parentId = Auth::id();
    $children = ParentService::getChildren($schoolId, $parentId);
    ?>

    <div class="page-header" style="margin-top: 10px;">
        <h2 style="font-size: 18px; font-weight: 700;">👨‍👩‍👧 Suivi Scolaire de mes Enfants</h2>
        <a href="<?= baseUrl('index.php?page=parent_link_child') ?>" class="btn btn-primary btn-sm">
            ➕ Associer un Enfant à mon Compte
        </a>
    </div>

    <?php if (empty($children)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 40px;">
                <div style="font-size: 40px; margin-bottom: 12px;">👨‍👩‍👧</div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Aucun élève rattaché pour le moment</h3>
                <p style="font-size: 14px; color: var(--text-muted); max-width: 500px; margin: 0 auto 20px auto;">
                    Pour suivre la scolarité, consulter les notes, les bulletins et régler les mensualités de votre enfant, veuillez l'associer à l'aide de son nom ou de son matricule.
                </p>
                <a href="<?= baseUrl('index.php?page=parent_link_child') ?>" class="btn btn-primary">
                    Associer mon Enfant Maintenant &rarr;
                </a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            <?php foreach ($children as $child): ?>
                <?php
                $childOverview = AcademicService::getStudentGradesOverview($schoolId, $child['id'], 'Semestre 1');
                $childFees = FinanceService::getStudentFeeStatus($schoolId, $child['id']);
                ?>
                <div class="card">
                    <div class="card-header" style="background: var(--bg-table-header);">
                        <div>
                            <span class="badge badge-info" style="font-size: 11px; margin-bottom: 4px;">
                                Matricule : <?= e($child['student_matricule']) ?>
                            </span>
                            <h3 class="card-title" style="font-size: 16px;">
                                <?= e($child['first_name'] . ' ' . $child['last_name']) ?>
                            </h3>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                Classe : <strong><?= e($child['class_name']) ?></strong> (<?= e($child['cycle']) ?><?= $child['track'] ? ' - Série ' . e($child['track']) : '' ?>)
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 14px; background: var(--bg-table-hover); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted);">Moyenne Semestre 1</span>
                                <div style="font-size: 20px; font-weight: 800; color: var(--primary-light);">
                                    <?= $childOverview['general_average'] ? number_format($childOverview['general_average'], 2) . ' / 20' : '-' ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 11px; color: var(--text-muted);">Reste scolarité dû</span>
                                <div style="font-size: 16px; font-weight: 800; color: <?= $childFees['balance_remaining'] > 0 ? '#dc2626' : '#166534' ?>;">
                                    <?= formatMoney($childFees['balance_remaining']) ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="<?= baseUrl('index.php?page=parent_student_grades&child_id=' . $child['id']) ?>" class="btn btn-outline btn-sm" style="text-align: center;">
                                📈 Consulter les Notes & Résultats
                            </a>
                            <a href="<?= baseUrl('index.php?page=parent_student_bulletin&child_id=' . $child['id']) ?>" class="btn btn-outline btn-sm" style="text-align: center;">
                                📜 Télécharger le Bulletin Scolaire
                            </a>
                            <a href="<?= baseUrl('index.php?page=parent_tuition&child_id=' . $child['id']) ?>" class="btn btn-accent btn-sm" style="text-align: center;">
                                💳 Régler la Mensualité en Ligne (Wave / OM / CB)
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

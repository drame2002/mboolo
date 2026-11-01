<?php
/**
 * MBOOLO — Contrôleur Frontal & Routeur Principal
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Audit.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SchoolService.php';
require_once __DIR__ . '/../src/AcademicService.php';
require_once __DIR__ . '/../src/CommunicationService.php';
require_once __DIR__ . '/../src/FinanceService.php';
require_once __DIR__ . '/../src/ParentService.php';
require_once __DIR__ . '/../src/SupervisorService.php';
require_once __DIR__ . '/../src/BackupService.php';

$action = $_GET['action'] ?? null;
$page = $_GET['page'] ?? 'home';

// =========================================================================
// TRAITEMENT DES ACTIONS (POST)
// =========================================================================

if ($action) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Vérification CSRF systématique pour toute action modifiant l'état
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('danger', "Erreur de sécurité : Jeton CSRF invalide ou session expirée.");
        header("Location: " . baseUrl('index.php?page=login'));
        exit;
    }

    switch ($action) {
        case 'login':
            $schoolId = (int)($_POST['school_id'] ?? 0);
            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = trim($_POST['role'] ?? '');

            $res = Auth::attempt($schoolId, $identifier, $password, $role);
            if ($res['success']) {
                setFlash('success', "Bienvenue sur MBOOLO, {$res['user']['first_name']} !");
                header("Location: " . baseUrl('index.php?page=dashboard'));
            } else {
                setFlash('danger', $res['message']);
                header("Location: " . baseUrl('index.php?page=login'));
            }
            exit;

        case 'register':
            $res = Auth::register($_POST);
            if ($res['success']) {
                setFlash('success', $res['message']);
                header("Location: " . baseUrl('index.php?page=login'));
            } else {
                setFlash('danger', $res['message']);
                header("Location: " . baseUrl('index.php?page=register'));
            }
            exit;

        case 'create_announcement':
            Auth::requireRole(['directeur']);
            $res = CommunicationService::createAnnouncement(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=announcements'));
            exit;

        case 'delete_announcement':
            Auth::requireRole(['directeur']);
            $annId = (int)($_POST['announcement_id'] ?? 0);
            CommunicationService::deleteAnnouncement(Auth::schoolId(), $annId);
            setFlash('success', "Le communiqué a été supprimé.");
            header("Location: " . baseUrl('index.php?page=announcements'));
            exit;

        case 'create_meeting':
            Auth::requireRole(['directeur']);
            $res = CommunicationService::createMeeting(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=director_meetings'));
            exit;

        case 'teacher_respond_meeting':
            Auth::requireRole(['professeur']);
            $meetingId = (int)($_POST['meeting_id'] ?? 0);
            $status = $_POST['attendance_status'] ?? 'present';
            $comment = trim($_POST['comment'] ?? '');
            CommunicationService::respondToMeeting($meetingId, Auth::id(), $status, $comment);
            setFlash('success', "Votre statut de présence a été transmis à la Direction.");
            header("Location: " . baseUrl('index.php?page=dashboard'));
            exit;

        case 'update_school_settings':
            Auth::requireRole(['directeur']);
            $res = SchoolService::updateSchool(Auth::schoolId(), $_POST, $_FILES['logo'] ?? null);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=director_school_settings'));
            exit;

        case 'create_evaluation':
            Auth::requireRole(['professeur']);
            $res = AcademicService::createEvaluation(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            $targetUrl = 'index.php?page=teacher_grades&class_subject_id=' . (int)($_POST['class_subject_id'] ?? 0);
            if (!empty($res['evaluation_id'])) {
                $targetUrl .= '&eval_id=' . $res['evaluation_id'];
            }
            header("Location: " . baseUrl($targetUrl));
            exit;

        case 'save_grades':
            Auth::requireRole(['professeur']);
            $evalId = (int)($_POST['eval_id'] ?? 0);
            $grades = $_POST['grades'] ?? [];
            $res = AcademicService::saveGrades(Auth::schoolId(), Auth::id(), $evalId, $grades);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=teacher_grades&class_subject_id=' . (int)($_POST['class_subject_id'] ?? 0) . '&eval_id=' . $evalId));
            exit;

        case 'publish_lesson_summary':
            Auth::requireRole(['professeur']);
            $res = AcademicService::publishLessonSummary(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=teacher_lessons'));
            exit;

        case 'online_payment':
            Auth::requireRole(['eleve', 'parent']);
            $studentId = (int)($_POST['student_id'] ?? Auth::id());
            $feeId = (int)($_POST['fee_schedule_id'] ?? 0);
            $method = $_POST['payment_method'] ?? 'wave';
            
            $res = FinanceService::processOnlinePayment(Auth::schoolId(), $studentId, $feeId, $method, $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            
            if (Auth::role() === 'parent') {
                header("Location: " . baseUrl('index.php?page=parent_tuition&child_id=' . $studentId));
            } else {
                header("Location: " . baseUrl('index.php?page=student_payments'));
            }
            exit;

        case 'record_counter_payment':
            Auth::requireRole(['secretaire', 'directeur']);
            $res = FinanceService::recordSecretaryPayment(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=secretary_tuition'));
            exit;

        case 'manage_teacher_salary':
            Auth::requireRole(['secretaire', 'directeur']);
            $res = FinanceService::createOrUpdateTeacherSalary(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=secretary_salaries'));
            exit;

        case 'link_child':
            Auth::requireRole(['parent']);
            $query = trim($_POST['student_query'] ?? '');
            $relation = trim($_POST['relationship'] ?? 'Tuteur');
            $res = ParentService::linkChild(Auth::schoolId(), Auth::id(), $query, $relation);
            
            if ($res['success']) {
                setFlash('success', $res['message']);
                header("Location: " . baseUrl('index.php?page=parent_children'));
            } else {
                setFlash('danger', $res['message']);
                header("Location: " . baseUrl('index.php?page=parent_link_child'));
            }
            exit;

        case 'download_backup':
            Auth::requireRole(['directeur']);
            $backupContent = BackupService::generateSqlBackup(Auth::schoolId());
            $filename = 'backup_mboolo_school_' . Auth::schoolId() . '_' . date('Ymd_His') . '.sql';
            
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($backupContent));
            echo $backupContent;
            exit;

        // --- GESTION SURVEILLANT GÉNÉRAL / VIE SCOLAIRE ---
        case 'add_timetable_slot':
            Auth::requireRole(['surveillant', 'directeur']);
            $res = AcademicService::addTimetableSlot(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            $classId = (int)($_POST['class_id'] ?? 0);
            header("Location: " . baseUrl('index.php?page=surveillant_timetable' . ($classId ? '&class_id=' . $classId : '')));
            exit;

        case 'delete_timetable_slot':
            Auth::requireRole(['surveillant', 'directeur']);
            $slotId = (int)($_POST['slot_id'] ?? 0);
            $classId = (int)($_POST['class_id'] ?? 0);
            $res = AcademicService::deleteTimetableSlot(Auth::schoolId(), Auth::id(), $slotId);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=surveillant_timetable' . ($classId ? '&class_id=' . $classId : '')));
            exit;

        case 'record_attendance':
            Auth::requireRole(['surveillant', 'directeur']);
            $res = SupervisorService::recordAttendance(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=surveillant_attendance'));
            exit;

        case 'justify_attendance':
            Auth::requireRole(['surveillant', 'directeur']);
            $attId = (int)($_POST['attendance_id'] ?? 0);
            $reason = trim($_POST['justification_reason'] ?? '');
            $res = SupervisorService::justifyAttendance(Auth::schoolId(), $attId, $reason);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=surveillant_attendance'));
            exit;

        case 'add_disciplinary_record':
            Auth::requireRole(['surveillant', 'directeur']);
            $res = SupervisorService::addDisciplinaryRecord(Auth::schoolId(), Auth::id(), $_POST);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=surveillant_discipline'));
            exit;

        case 'update_discipline_status':
            Auth::requireRole(['surveillant', 'directeur']);
            $recId = (int)($_POST['record_id'] ?? 0);
            $status = trim($_POST['status'] ?? 'traite');
            $res = SupervisorService::updateDisciplineStatus(Auth::schoolId(), $recId, $status);
            setFlash($res['success'] ? 'success' : 'danger', $res['message']);
            header("Location: " . baseUrl('index.php?page=surveillant_discipline'));
            exit;

        case 'mark_notification_read':
            Auth::requireAuth();
            require_once __DIR__ . '/../src/NotificationService.php';
            $notifId = (int)($_POST['notification_id'] ?? 0);
            NotificationService::markAsRead(Auth::schoolId(), Auth::id(), $notifId);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? baseUrl('index.php?page=parent_notifications')));
            exit;

        case 'mark_all_notifications_read':
            Auth::requireAuth();
            require_once __DIR__ . '/../src/NotificationService.php';
            NotificationService::markAllAsRead(Auth::schoolId(), Auth::id());
            setFlash('success', 'Toutes vos notifications ont été marquées comme lues.');
            header("Location: " . baseUrl('index.php?page=parent_notifications'));
            exit;

        default:
            header("Location: " . baseUrl('index.php'));
            exit;
    }
}

// =========================================================================
// ROUTAGE DES PAGES (GET)
// =========================================================================

// Déconnexion
if ($page === 'logout') {
    Auth::logout();
    setFlash('info', "Vous avez été déconnecté avec succès. À bientôt !");
    header("Location: " . baseUrl('index.php?page=home'));
    exit;
}

// Page d'Accueil publique (Bienvenue — Connexion ou Inscription)
if ($page === 'home') {
    require __DIR__ . '/../views/home.php';
    exit;
}

// Pages publiques (Connexion / Inscription)
if ($page === 'login') {
    if (Auth::check()) {
        header("Location: " . baseUrl('index.php?page=dashboard'));
        exit;
    }
    require __DIR__ . '/../views/auth/login.php';
    exit;
}

if ($page === 'register') {
    if (Auth::check()) {
        header("Location: " . baseUrl('index.php?page=dashboard'));
        exit;
    }
    require __DIR__ . '/../views/auth/register.php';
    exit;
}

// Toute autre page requiert d'être connecté
if (!Auth::check()) {
    header("Location: " . baseUrl('index.php?page=home'));
    exit;
}

// Mapping des routes sécurisées
$viewMap = [
    // Dashboard & Communication
    'dashboard'               => __DIR__ . '/../views/dashboard/index.php',
    'announcements'           => __DIR__ . '/../views/communication/announcements.php',

    // Directeur
    'director_meetings'       => __DIR__ . '/../views/director/meetings.php',
    'director_classes'        => __DIR__ . '/../views/director/classes.php',
    'director_report_cards'   => __DIR__ . '/../views/director/report_cards.php',
    'director_school_settings'=> __DIR__ . '/../views/director/school_settings.php',
    'director_audit'          => __DIR__ . '/../views/director/audit.php',
    'director_backup'         => __DIR__ . '/../views/director/backup.php',

    // Professeur
    'teacher_timetable'       => __DIR__ . '/../views/teacher/timetable.php',
    'teacher_classes'         => __DIR__ . '/../views/teacher/classes.php',
    'teacher_grades'          => __DIR__ . '/../views/teacher/grades.php',
    'teacher_lessons'         => __DIR__ . '/../views/teacher/lessons.php',
    'teacher_salaries'        => __DIR__ . '/../views/teacher/salaries.php',
    'teacher_meetings'        => __DIR__ . '/../views/dashboard/index.php',

    // Élève
    'student_timetable'       => __DIR__ . '/../views/student/timetable.php',
    'student_grades'          => __DIR__ . '/../views/student/grades.php',
    'student_bulletin'        => __DIR__ . '/../views/student/bulletin.php',
    'student_lessons'         => __DIR__ . '/../views/student/lessons.php',
    'student_payments'        => __DIR__ . '/../views/student/payments.php',
    'student_history'         => __DIR__ . '/../views/student/history.php',

    // Secrétaire
    'secretary_tuition'       => __DIR__ . '/../views/secretary/tuition.php',
    'secretary_record_payment'=> __DIR__ . '/../views/secretary/record_payment.php',
    'secretary_unpaid'        => __DIR__ . '/../views/secretary/unpaid.php',
    'secretary_salaries'      => __DIR__ . '/../views/secretary/salaries.php',
    'secretary_ledger'        => __DIR__ . '/../views/secretary/ledger.php',

    // Parent d'élève
    'parent_children'         => __DIR__ . '/../views/parent/children.php',
    'parent_link_child'       => __DIR__ . '/../views/parent/link_child.php',
    'parent_student_grades'   => __DIR__ . '/../views/parent/student_grades.php',
    'parent_student_bulletin' => __DIR__ . '/../views/parent/student_bulletin.php',
    'parent_tuition'          => __DIR__ . '/../views/parent/tuition.php',
    'parent_notifications'    => __DIR__ . '/../views/parent/notifications.php',

    // Surveillant Général / Vie Scolaire
    'surveillant_timetable'   => __DIR__ . '/../views/surveillant/timetable_manage.php',
    'surveillant_attendance'  => __DIR__ . '/../views/surveillant/attendance.php',
    'surveillant_discipline'  => __DIR__ . '/../views/surveillant/discipline.php',
    'surveillant_classes'     => __DIR__ . '/../views/surveillant/classes.php',
];

if (isset($viewMap[$page]) && file_exists($viewMap[$page])) {
    require $viewMap[$page];
} else {
    require __DIR__ . '/../views/dashboard/index.php';
}

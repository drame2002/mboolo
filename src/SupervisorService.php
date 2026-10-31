<?php
/**
 * Service de Vie Scolaire, Assiduité et Discipline — MBOOLO
 * Dédié au Surveillant Général
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class SupervisorService {

    // =========================================================================
    // 1. STATISTIQUES DU TABLEAU DE BORD DU SURVEILLANT
    // =========================================================================

    public static function getDashboardStats(int $schoolId): array {
        $pdo = getDbConnection();
        $today = date('Y-m-d');

        // Nombre de créneaux d'emploi du temps
        $stmtSlots = $pdo->prepare("SELECT COUNT(*) FROM `timetables` WHERE `school_id` = ?");
        $stmtSlots->execute([$schoolId]);
        $totalSlots = (int)$stmtSlots->fetchColumn();

        // Absences aujourd'hui
        $stmtAbs = $pdo->prepare("SELECT COUNT(*) FROM `attendances` WHERE `school_id` = ? AND `date` = ? AND `status` = 'absent'");
        $stmtAbs->execute([$schoolId, $today]);
        $todayAbsences = (int)$stmtAbs->fetchColumn();

        // Retards aujourd'hui
        $stmtRetards = $pdo->prepare("SELECT COUNT(*) FROM `attendances` WHERE `school_id` = ? AND `date` = ? AND `status` = 'retard'");
        $stmtRetards->execute([$schoolId, $today]);
        $todayRetards = (int)$stmtRetards->fetchColumn();

        // Incidents de discipline ouverts
        $stmtDisc = $pdo->prepare("SELECT COUNT(*) FROM `disciplinary_records` WHERE `school_id` = ? AND `status` = 'ouvert'");
        $stmtDisc->execute([$schoolId]);
        $openIncidents = (int)$stmtDisc->fetchColumn();

        // Nombre total d'élèves
        $stmtStudents = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `school_id` = ? AND `role` = 'eleve' AND `is_active` = 1");
        $stmtStudents->execute([$schoolId]);
        $totalStudents = (int)$stmtStudents->fetchColumn();

        // Nombre total de classes
        $stmtClasses = $pdo->prepare("SELECT COUNT(*) FROM `classes` WHERE `school_id` = ?");
        $stmtClasses->execute([$schoolId]);
        $totalClasses = (int)$stmtClasses->fetchColumn();

        return [
            'total_slots' => $totalSlots,
            'today_absences' => $todayAbsences,
            'today_retards' => $todayRetards,
            'open_incidents' => $openIncidents,
            'total_students' => $totalStudents,
            'total_classes' => $totalClasses
        ];
    }

    // =========================================================================
    // 2. ASSIDUITÉ : GESTION DES ABSENCES ET RETARDS
    // =========================================================================

    public static function getAttendanceList(int $schoolId, string $date, ?int $classId = null): array {
        $pdo = getDbConnection();
        $params = [$schoolId, $date];
        $sql = "
            SELECT a.*, 
                   u.first_name, u.last_name, u.phone as student_phone,
                   sp.student_matricule,
                   c.name as class_name,
                   rec.first_name as recorder_first_name, rec.last_name as recorder_last_name
            FROM `attendances` a
            JOIN `users` u ON a.student_id = u.id
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            LEFT JOIN `users` rec ON a.recorded_by_user_id = rec.id
            WHERE a.school_id = ? AND a.date = ?
        ";

        if ($classId) {
            $sql .= " AND sp.class_id = ? ";
            $params[] = $classId;
        }

        $sql .= " ORDER BY c.name ASC, u.last_name ASC, a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function recordAttendance(int $schoolId, int $recorderId, array $data): array {
        $pdo = getDbConnection();

        $studentId = (int)($data['student_id'] ?? 0);
        $date = trim($data['date'] ?? date('Y-m-d'));
        $timeSlot = trim($data['time_slot'] ?? 'Journée');
        $status = $data['status'] ?? 'absent';
        $isJustified = !empty($data['is_justified']) ? 1 : 0;
        $reason = trim($data['justification_reason'] ?? '');

        if (!$studentId || empty($date)) {
            return ['success' => false, 'message' => "L'élève et la date sont obligatoires."];
        }

        // Vérification de l'appartenance de l'élève à l'établissement
        $stmtCheck = $pdo->prepare("SELECT id, first_name, last_name FROM `users` WHERE `id` = ? AND `school_id` = ? AND `role` = 'eleve'");
        $stmtCheck->execute([$studentId, $schoolId]);
        $student = $stmtCheck->fetch();
        if (!$student) {
            return ['success' => false, 'message' => "Élève introuvable dans cet établissement."];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `attendances` 
                (`school_id`, `student_id`, `recorded_by_user_id`, `date`, `time_slot`, `status`, `is_justified`, `justification_reason`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $schoolId, $studentId, $recorderId, $date, $timeSlot, $status, $isJustified, $reason
            ]);
            $attendanceId = (int)$pdo->lastInsertId();

            $statusLabel = ($status === 'retard') ? 'Retard' : 'Absence';
            Audit::log($schoolId, $recorderId, 'ENREGISTREMENT_ASSIDUITE', 'attendances', $attendanceId, "{$statusLabel} signalée pour l'élève {$student['first_name']} {$student['last_name']} le {$date}");

            return ['success' => true, 'message' => "Le pointage d'assiduité a été enregistré avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    public static function justifyAttendance(int $schoolId, int $attendanceId, string $reason): array {
        $pdo = getDbConnection();
        try {
            $stmt = $pdo->prepare("
                UPDATE `attendances` 
                SET `is_justified` = 1, `justification_reason` = ?
                WHERE `id` = ? AND `school_id` = ?
            ");
            $stmt->execute([$reason, $attendanceId, $schoolId]);
            return ['success' => true, 'message' => "L'absence ou le retard a été justifié avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    // =========================================================================
    // 3. VIE SCOLAIRE & REGISTRE DE DISCIPLINE
    // =========================================================================

    public static function getDisciplinaryRecords(int $schoolId, ?int $classId = null, ?string $status = null): array {
        $pdo = getDbConnection();
        $params = [$schoolId];
        $sql = "
            SELECT d.*, 
                   u.first_name, u.last_name,
                   sp.student_matricule,
                   c.name as class_name,
                   rec.first_name as recorder_first_name, rec.last_name as recorder_last_name
            FROM `disciplinary_records` d
            JOIN `users` u ON d.student_id = u.id
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            LEFT JOIN `users` rec ON d.recorded_by_user_id = rec.id
            WHERE d.school_id = ?
        ";

        if ($classId) {
            $sql .= " AND sp.class_id = ? ";
            $params[] = $classId;
        }

        if ($status) {
            $sql .= " AND d.status = ? ";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.date DESC, d.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function addDisciplinaryRecord(int $schoolId, int $recorderId, array $data): array {
        $pdo = getDbConnection();

        $studentId = (int)($data['student_id'] ?? 0);
        $date = trim($data['date'] ?? date('Y-m-d'));
        $incidentType = $data['incident_type'] ?? 'indiscipline';
        $sanction = $data['sanction'] ?? 'observation';
        $details = trim($data['details'] ?? $data['description'] ?? '');

        if (!$studentId || empty($details)) {
            return ['success' => false, 'message' => "L'élève et la description de l'incident sont obligatoires."];
        }

        $stmtCheck = $pdo->prepare("SELECT id, first_name, last_name FROM `users` WHERE `id` = ? AND `school_id` = ? AND `role` = 'eleve'");
        $stmtCheck->execute([$studentId, $schoolId]);
        $student = $stmtCheck->fetch();
        if (!$student) {
            return ['success' => false, 'message' => "Élève introuvable dans cet établissement."];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `disciplinary_records` 
                (`school_id`, `student_id`, `recorded_by_user_id`, `date`, `incident_type`, `sanction`, `details`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ouvert')
            ");
            $stmt->execute([
                $schoolId, $studentId, $recorderId, $date, $incidentType, $sanction, $details
            ]);
            $recordId = (int)$pdo->lastInsertId();

            Audit::log($schoolId, $recorderId, 'SIGNALEMENT_DISCIPLINE', 'disciplinary_records', $recordId, "Incident {$incidentType} sanctionné par {$sanction} pour l'élève {$student['first_name']} {$student['last_name']}");

            return ['success' => true, 'message' => "Le rapport de discipline a été consigné avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    public static function updateDisciplineStatus(int $schoolId, int $recordId, string $status): array {
        $pdo = getDbConnection();
        try {
            $stmt = $pdo->prepare("
                UPDATE `disciplinary_records` 
                SET `status` = ?
                WHERE `id` = ? AND `school_id` = ?
            ");
            $stmt->execute([$status, $recordId, $schoolId]);
            return ['success' => true, 'message' => "Le statut du dossier disciplinaire a été mis à jour."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }
}

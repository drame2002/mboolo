<?php
/**
 * Service de Communication et Convocations Scolaires — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Auth.php';

class CommunicationService {

    // --- COMMUNIQUÉS DU DIRECTEUR ---

    public static function createAnnouncement(int $schoolId, int $authorId, array $data): array {
        $pdo = getDbConnection();

        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $targetAudience = $data['target_audience'] ?? 'all';
        $urgencyLevel = $data['urgency_level'] ?? 'normal';
        $isPinned = !empty($data['is_pinned']) ? 1 : 0;

        if (empty($title) || empty($content)) {
            return ['success' => false, 'message' => "Le titre et le contenu du communiqué sont obligatoires."];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `announcements` (`school_id`, `author_id`, `title`, `content`, `target_audience`, `urgency_level`, `is_pinned`, `published_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$schoolId, $authorId, $title, $content, $targetAudience, $urgencyLevel, $isPinned]);
            $id = $pdo->lastInsertId();

            Audit::log($schoolId, $authorId, 'COMMUNIQUE_PUBLIE', 'announcements', $id, "Publication du communiqué : '{$title}' (Cible: {$targetAudience})");

            return ['success' => true, 'message' => "Le communiqué a été publié avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    public static function getAnnouncementsFor(int $schoolId, ?string $userRole = null): array {
        $pdo = getDbConnection();

        if ($userRole === 'directeur' || $userRole === 'secretaire' || empty($userRole)) {
            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.role as author_role
                FROM `announcements` a
                JOIN `users` u ON a.author_id = u.id
                WHERE a.school_id = ?
                ORDER BY a.is_pinned DESC, a.published_at DESC
            ");
            $stmt->execute([$schoolId]);
        } else {
            // Mapping du rôle pour la cible
            $target = 'all';
            if ($userRole === 'professeur') $target = 'professeurs';
            elseif ($userRole === 'eleve') $target = 'eleves';
            elseif ($userRole === 'parent') $target = 'parents';

            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.role as author_role
                FROM `announcements` a
                JOIN `users` u ON a.author_id = u.id
                WHERE a.school_id = ? AND (a.target_audience = 'all' OR a.target_audience = ?)
                ORDER BY a.is_pinned DESC, a.published_at DESC
            ");
            $stmt->execute([$schoolId, $target]);
        }

        return $stmt->fetchAll();
    }

    public static function deleteAnnouncement(int $schoolId, int $announcementId): bool {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("DELETE FROM `announcements` WHERE `id` = ? AND `school_id` = ?");
        return $stmt->execute([$announcementId, $schoolId]);
    }

    // --- CONVOCATIONS ET RÉUNIONS DES PROFESSEURS ---

    public static function createMeeting(int $schoolId, int $organizerId, array $data): array {
        $pdo = getDbConnection();

        $title = trim($data['title'] ?? '');
        $meetingType = $data['meeting_type'] ?? 'ordinaire';
        $meetingDate = $data['meeting_date'] ?? '';
        $location = trim($data['location'] ?? 'Salle des Professeurs');
        $agenda = trim($data['agenda'] ?? '');

        if (empty($title) || empty($meetingDate) || empty($agenda)) {
            return ['success' => false, 'message' => "Le titre, la date/heure et l'ordre du jour sont obligatoires."];
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO `meetings` (`school_id`, `organizer_id`, `title`, `meeting_type`, `meeting_date`, `location`, `agenda`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'planifiee')
            ");
            $stmt->execute([$schoolId, $organizerId, $title, $meetingType, $meetingDate, $location, $agenda]);
            $meetingId = (int)$pdo->lastInsertId();

            // Convocation automatique de tous les professeurs de l'établissement
            $stmtTeachers = $pdo->prepare("SELECT id FROM `users` WHERE `school_id` = ? AND `role` = 'professeur' AND `is_active` = 1");
            $stmtTeachers->execute([$schoolId]);
            $teachers = $stmtTeachers->fetchAll(PDO::FETCH_COLUMN);

            $stmtPart = $pdo->prepare("INSERT INTO `meeting_participants` (`meeting_id`, `teacher_id`, `attendance_status`) VALUES (?, ?, 'en_attente')");
            foreach ($teachers as $tId) {
                $stmtPart->execute([$meetingId, $tId]);
            }

            $pdo->commit();

            Audit::log($schoolId, $organizerId, 'CONVOCATION_CREEE', 'meetings', $meetingId, "Convocation de réunion ({$meetingType}) : '{$title}' pour " . count($teachers) . " professeurs");

            return ['success' => true, 'message' => "La convocation a été émise et transmise à l'ensemble du corps professoral."];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    public static function getMeetingsForDirector(int $schoolId): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   COUNT(mp.id) as total_invited,
                   SUM(CASE WHEN mp.attendance_status = 'present' THEN 1 ELSE 0 END) as count_present,
                   SUM(CASE WHEN mp.attendance_status = 'excuse' THEN 1 ELSE 0 END) as count_excused,
                   SUM(CASE WHEN mp.read_receipt = 1 THEN 1 ELSE 0 END) as count_read
            FROM `meetings` m
            LEFT JOIN `meeting_participants` mp ON m.id = mp.meeting_id
            WHERE m.school_id = ?
            GROUP BY m.id
            ORDER BY m.meeting_date DESC
        ");
        $stmt->execute([$schoolId]);
        return $stmt->fetchAll();
    }

    public static function getMeetingDetails(int $schoolId, int $meetingId): ?array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM `meetings` WHERE `id` = ? AND `school_id` = ?");
        $stmt->execute([$meetingId, $schoolId]);
        $meeting = $stmt->fetch();
        if (!$meeting) return null;

        $stmtParts = $pdo->prepare("
            SELECT mp.*, u.first_name, u.last_name, u.phone, u.email
            FROM `meeting_participants` mp
            JOIN `users` u ON mp.teacher_id = u.id
            WHERE mp.meeting_id = ?
            ORDER BY u.last_name ASC
        ");
        $stmtParts->execute([$meetingId]);
        $meeting['participants'] = $stmtParts->fetchAll();

        return $meeting;
    }

    public static function getMeetingsForTeacher(int $schoolId, int $teacherId): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, mp.read_receipt, mp.read_at, mp.attendance_status, mp.response_comment,
                   org.first_name as org_first_name, org.last_name as org_last_name
            FROM `meetings` m
            JOIN `meeting_participants` mp ON m.id = mp.meeting_id
            JOIN `users` org ON m.organizer_id = org.id
            WHERE m.school_id = ? AND mp.teacher_id = ?
            ORDER BY m.meeting_date DESC
        ");
        $stmt->execute([$schoolId, $teacherId]);
        return $stmt->fetchAll();
    }

    public static function respondToMeeting(int $meetingId, int $teacherId, string $status, ?string $comment = null): bool {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            UPDATE `meeting_participants`
            SET `read_receipt` = 1, `read_at` = COALESCE(read_at, NOW()), `attendance_status` = ?, `response_comment` = ?
            WHERE `meeting_id` = ? AND `teacher_id` = ?
        ");
        return $stmt->execute([$status, $comment, $meetingId, $teacherId]);
    }
}

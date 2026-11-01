<?php
/**
 * Service de Notifications Scolaires — MBOOLO
 * Alertes multi-canaux (SMS, WhatsApp, Email, In-App) aux parents d'élèves
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class NotificationService {

    /**
     * Notifier les parents d'un élève suite à un signalement d'assiduité (Absence ou Retard)
     */
    public static function notifyAttendance(
        int $schoolId, 
        int $studentId, 
        string $status, 
        string $date, 
        string $timeSlot, 
        ?string $reason = null,
        bool $sendSms = true,
        bool $sendWhatsapp = true
    ): array {
        $pdo = getDbConnection();

        // Récupérer les informations de l'école et de l'élève
        $stmtSt = $pdo->prepare("
            SELECT u.first_name, u.last_name, sp.student_matricule, c.name as class_name, s.name as school_name, s.phone as school_phone
            FROM `users` u
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            JOIN `schools` s ON u.school_id = s.id
            WHERE u.id = ? AND u.school_id = ?
        ");
        $stmtSt->execute([$studentId, $schoolId]);
        $student = $stmtSt->fetch();

        if (!$student) {
            return ['success' => false, 'message' => "Élève introuvable pour notification.", 'sent_count' => 0];
        }

        // Récupérer les parents associés à cet élève
        $stmtParents = $pdo->prepare("
            SELECT p.id, p.first_name, p.last_name, p.phone, p.email, ps.relationship_type
            FROM `parent_students` ps
            JOIN `users` p ON ps.parent_id = p.id
            WHERE ps.student_id = ? AND ps.school_id = ? AND p.is_active = 1
        ");
        $stmtParents->execute([$studentId, $schoolId]);
        $parents = $stmtParents->fetchAll();

        $formattedDate = date('d/m/Y', strtotime($date));
        $isAbsence = ($status === 'absent');
        $statusLabel = $isAbsence ? 'une Absence' : 'un Retard';
        $icon = $isAbsence ? '❌' : '⏱️';

        $title = "{$icon} Signalement : {$statusLabel} pour {$student['first_name']}";
        
        $sentCount = 0;
        $channelsUsed = [];

        foreach ($parents as $parent) {
            $parentName = trim($parent['first_name'] . ' ' . $parent['last_name']);
            $phone = trim($parent['phone'] ?? '');

            // Modèle de message officiel pour les familles (SMS / WhatsApp)
            $message = "Bonjour M./Mme {$parentName},\n";
            $message .= "L'établissement {$student['school_name']} vous informe que votre enfant {$student['first_name']} {$student['last_name']} ({$student['class_name']}) a fait l'objet d'un signalement pour {$statusLabel} le {$formattedDate}";
            if (!empty($timeSlot)) {
                $message .= " sur le créneau : {$timeSlot}.";
            } else {
                $message .= ".";
            }
            if (!empty($reason)) {
                $message .= "\nMotif consigné : {$reason}.";
            } else {
                $message .= "\nMerci de bien vouloir justifier cette situation auprès de la Vie Scolaire au {$student['school_phone']}.";
            }

            // 1. Notification In-App (Plateforme)
            self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'in_app', $parent['email'] ?? $phone, $status, $title, $message);
            $sentCount++;
            $channelsUsed['in_app'] = true;

            // 2. Notification SMS
            if ($sendSms && !empty($phone)) {
                self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'sms', $phone, $status, $title, $message);
                $sentCount++;
                $channelsUsed['sms'] = true;
            }

            // 3. Notification WhatsApp
            if ($sendWhatsapp && !empty($phone)) {
                self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'whatsapp', $phone, $status, $title, $message);
                $sentCount++;
                $channelsUsed['whatsapp'] = true;
            }
        }

        // Audit de traçabilité
        if ($sentCount > 0) {
            $actorId = Auth::id();
            if (!$actorId) {
                $stmtDir = $pdo->prepare("SELECT id FROM `users` WHERE `school_id` = ? AND `role` = 'directeur' AND `is_active` = 1 LIMIT 1");
                $stmtDir->execute([$schoolId]);
                $actorId = (int)$stmtDir->fetchColumn() ?: $studentId;
            }

            Audit::log(
                $schoolId, 
                $actorId, 
                'NOTIFICATION_PARENT_ENVOYEE', 
                'notifications', 
                $studentId, 
                "Alerte {$status} transmise aux parents de {$student['first_name']} {$student['last_name']} via " . implode(', ', array_keys($channelsUsed))
            );
        }

        return [
            'success' => true,
            'message' => $sentCount > 0 ? "Alerte envoyée aux parents ({$sentCount} messages émis)." : "Pointage enregistré (aucun parent rattaché).",
            'sent_count' => $sentCount,
            'channels' => array_keys($channelsUsed)
        ];
    }

    /**
     * Notifier les parents de la disponibilité d'un nouveau bulletin scolaire
     */
    public static function notifyReportCard(
        int $schoolId, 
        int $studentId, 
        string $semester, 
        float $generalAverage, 
        string $rank
    ): array {
        $pdo = getDbConnection();

        $stmtSt = $pdo->prepare("
            SELECT u.first_name, u.last_name, c.name as class_name, s.name as school_name
            FROM `users` u
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            JOIN `schools` s ON u.school_id = s.id
            WHERE u.id = ? AND u.school_id = ?
        ");
        $stmtSt->execute([$studentId, $schoolId]);
        $student = $stmtSt->fetch();

        if (!$student) return ['success' => false];

        $stmtParents = $pdo->prepare("
            SELECT p.id, p.first_name, p.last_name, p.phone, p.email
            FROM `parent_students` ps
            JOIN `users` p ON ps.parent_id = p.id
            WHERE ps.student_id = ? AND ps.school_id = ? AND p.is_active = 1
        ");
        $stmtParents->execute([$studentId, $schoolId]);
        $parents = $stmtParents->fetchAll();

        $title = "📜 Bulletin disponible : {$semester} — {$student['first_name']}";
        $avgFormatted = number_format($generalAverage, 2);

        $sentCount = 0;
        foreach ($parents as $parent) {
            $message = "Bonjour {$parent['first_name']},\n";
            $message .= "Le bulletin officiel du {$semester} de votre enfant {$student['first_name']} ({$student['class_name']}) est disponible sur MBOOLO.\n";
            $message .= "Moyenne générale : {$avgFormatted}/20 (Rang : {$rank}).\n";
            $message .= "Vous pouvez le consulter et le télécharger au format officiel PDF depuis votre espace parent.";

            self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'in_app', $parent['email'] ?? $parent['phone'], 'bulletin', $title, $message);
            if (!empty($parent['phone'])) {
                self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'whatsapp', $parent['phone'], 'bulletin', $title, $message);
            }
            $sentCount++;
        }

        return ['success' => true, 'sent_count' => $sentCount];
    }

    /**
     * Notifier les parents suite à la réception d'un paiement de scolarité
     */
    public static function notifyPaymentReceived(
        int $schoolId,
        int $studentId,
        float $amount,
        string $method,
        string $receiptNumber
    ): array {
        $pdo = getDbConnection();

        $stmtSt = $pdo->prepare("
            SELECT u.first_name, u.last_name, c.name as class_name, s.name as school_name
            FROM `users` u
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            JOIN `schools` s ON u.school_id = s.id
            WHERE u.id = ? AND u.school_id = ?
        ");
        $stmtSt->execute([$studentId, $schoolId]);
        $student = $stmtSt->fetch();

        if (!$student) return ['success' => false];

        $stmtParents = $pdo->prepare("
            SELECT p.id, p.first_name, p.last_name, p.phone, p.email
            FROM `parent_students` ps
            JOIN `users` p ON ps.parent_id = p.id
            WHERE ps.student_id = ? AND ps.school_id = ? AND p.is_active = 1
        ");
        $stmtParents->execute([$studentId, $schoolId]);
        $parents = $stmtParents->fetchAll();

        $title = "💳 Quittance de Paiement : " . formatMoney($amount) . " — {$student['first_name']}";
        $sentCount = 0;

        foreach ($parents as $parent) {
            $message = "Bonjour {$parent['first_name']},\n";
            $message .= "L'établissement {$student['school_name']} confirme le règlement de " . formatMoney($amount) . " pour la scolarité de {$student['first_name']} {$student['last_name']} ({$student['class_name']}).\n";
            $message .= "Mode : " . ucfirst($method) . " | Reçu officiel n° {$receiptNumber}.\n";
            $message .= "La quittance certifiée est téléchargeable sur votre espace MBOOLO.";

            self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'in_app', $parent['email'] ?? $parent['phone'], 'paiement', $title, $message);
            if (!empty($parent['phone'])) {
                self::saveNotification($schoolId, (int)$parent['id'], $studentId, 'sms', $parent['phone'], 'paiement', $title, $message);
            }
            $sentCount++;
        }

        return ['success' => true, 'sent_count' => $sentCount];
    }

    /**
     * Enregistrer une notification dans la base de données
     */
    private static function saveNotification(
        int $schoolId, 
        int $userId, 
        ?int $studentId, 
        string $channel, 
        ?string $recipientContact, 
        string $type, 
        string $title, 
        string $message
    ): int {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `notifications` (`school_id`, `user_id`, `student_id`, `channel`, `recipient_contact`, `type`, `title`, `message`, `status`, `is_read`, `sent_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'delivre', 0, NOW())
        ");
        $stmt->execute([$schoolId, $userId, $studentId, $channel, $recipientContact, $type, $title, $message]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Récupérer les notifications d'un utilisateur (avec pagination)
     */
    public static function getUserNotifications(int $schoolId, int $userId, int $limit = 40): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT n.*, u.first_name as student_first_name, u.last_name as student_last_name
            FROM `notifications` n
            LEFT JOIN `users` u ON n.student_id = u.id
            WHERE n.school_id = ? AND n.user_id = ?
            ORDER BY n.sent_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $schoolId, PDO::PARAM_INT);
        $stmt->bindValue(2, $userId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Compter le nombre de notifications non lues
     */
    public static function getUnreadCount(int $schoolId, int $userId): int {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM `notifications` 
            WHERE `school_id` = ? AND `user_id` = ? AND `is_read` = 0
        ");
        $stmt->execute([$schoolId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marquer une notification comme lue
     */
    public static function markAsRead(int $schoolId, int $userId, int $notificationId): bool {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = NOW() 
            WHERE `id` = ? AND `school_id` = ? AND `user_id` = ?
        ");
        return $stmt->execute([$notificationId, $schoolId, $userId]);
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public static function markAllAsRead(int $schoolId, int $userId): bool {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = NOW() 
            WHERE `school_id` = ? AND `user_id` = ? AND `is_read` = 0
        ");
        return $stmt->execute([$schoolId, $userId]);
    }

    /**
     * Récupérer les dernières alertes émises au sein de l'école (supervision surveillant / direction)
     */
    public static function getSchoolRecentDispatched(int $schoolId, int $limit = 30): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT n.*, 
                   st.first_name as student_first_name, st.last_name as student_last_name, sp.student_matricule, c.name as class_name,
                   p.first_name as parent_first_name, p.last_name as parent_last_name, p.phone as parent_phone
            FROM `notifications` n
            LEFT JOIN `users` st ON n.student_id = st.id
            LEFT JOIN `student_profiles` sp ON st.id = sp.user_id
            LEFT JOIN `classes` c ON sp.class_id = c.id
            LEFT JOIN `users` p ON n.user_id = p.id
            WHERE n.school_id = ?
            ORDER BY n.sent_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $schoolId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

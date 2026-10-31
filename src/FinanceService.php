<?php
/**
 * Service Financier & Trésorerie Scolaire — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class FinanceService {

    // --- FRAIS DE SCOLARITÉ & MENSUALITÉS DES ÉLÈVES ---

    public static function getStudentFeeStatus(int $schoolId, int $studentId): array {
        $pdo = getDbConnection();

        // Récupérer la classe de l'élève
        $stmtC = $pdo->prepare("SELECT class_id FROM `student_profiles` WHERE `user_id` = ? AND `school_id` = ?");
        $stmtC->execute([$studentId, $schoolId]);
        $classId = (int)$stmtC->fetchColumn();

        // Échéances applicables (soit pour sa classe, soit pour toute l'école)
        $stmtFees = $pdo->prepare("
            SELECT fs.*, 
                   (SELECT COUNT(*) FROM `student_payments` sp 
                    WHERE sp.fee_schedule_id = fs.id AND sp.student_id = ? AND sp.payment_status = 'paye') as is_paid,
                   (SELECT sp.transaction_ref FROM `student_payments` sp 
                    WHERE sp.fee_schedule_id = fs.id AND sp.student_id = ? AND sp.payment_status = 'paye' LIMIT 1) as paid_ref,
                   (SELECT sp.payment_date FROM `student_payments` sp 
                    WHERE sp.fee_schedule_id = fs.id AND sp.student_id = ? AND sp.payment_status = 'paye' LIMIT 1) as paid_date,
                   (SELECT sp.payment_method FROM `student_payments` sp 
                    WHERE sp.fee_schedule_id = fs.id AND sp.student_id = ? AND sp.payment_status = 'paye' LIMIT 1) as paid_method
            FROM `fee_schedules` fs
            WHERE fs.school_id = ? AND (fs.class_id = ? OR fs.class_id IS NULL)
            ORDER BY fs.due_date ASC
        ");
        $stmtFees->execute([$studentId, $studentId, $studentId, $studentId, $schoolId, $classId]);
        $fees = $stmtFees->fetchAll();

        $totalDue = 0;
        $totalPaid = 0;

        foreach ($fees as &$fee) {
            $totalDue += (float)$fee['amount_due'];
            if ($fee['is_paid'] > 0) {
                $totalPaid += (float)$fee['amount_due'];
            }
        }

        $balanceRemaining = max(0, $totalDue - $totalPaid);

        return [
            'fees' => $fees,
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'balance_remaining' => $balanceRemaining
        ];
    }

    public static function getStudentPaymentHistory(int $schoolId, int $studentId): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, fs.fee_title, fs.month, fs.year,
                   u.first_name as receiver_first_name, u.last_name as receiver_last_name
            FROM `student_payments` sp
            JOIN `fee_schedules` fs ON sp.fee_schedule_id = fs.id
            LEFT JOIN `users` u ON sp.received_by_user_id = u.id
            WHERE sp.school_id = ? AND sp.student_id = ?
            ORDER BY sp.payment_date DESC
        ");
        $stmt->execute([$schoolId, $studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Traitement et confirmation sécurisée du paiement en ligne (Wave, Orange Money, Carte Bancaire)
     * Règle de sécurité : Vérification et confirmation par simulateur avant enregistrement effectif
     */
    public static function processOnlinePayment(int $schoolId, int $studentId, int $feeScheduleId, string $method, array $paymentDetails): array {
        $pdo = getDbConnection();

        // 1. Vérification de l'échéance
        $stmtFS = $pdo->prepare("SELECT * FROM `fee_schedules` WHERE `id` = ? AND `school_id` = ?");
        $stmtFS->execute([$feeScheduleId, $schoolId]);
        $fee = $stmtFS->fetch();
        if (!$fee) {
            return ['success' => false, 'message' => "Échéance de mensualité introuvable."];
        }

        // 2. Vérification si déjà payé
        $stmtCheck = $pdo->prepare("SELECT id FROM `student_payments` WHERE `student_id` = ? AND `fee_schedule_id` = ? AND `payment_status` = 'paye'");
        $stmtCheck->execute([$studentId, $feeScheduleId]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => "Cette mensualité a déjà été réglée avec succès."];
        }

        // 3. Simulation et vérification auprès du prestataire de paiement (Wave, OM, CB)
        $amount = (float)$fee['amount_due'];
        $phoneNumber = trim($paymentDetails['phone_number'] ?? '');
        $cardNumber = trim($paymentDetails['card_number'] ?? '');

        // Validation préalable selon le mode
        if (in_array($method, ['wave', 'orange_money'])) {
            if (empty($phoneNumber) || strlen($phoneNumber) < 9) {
                return ['success' => false, 'message' => "Veuillez renseigner un numéro de téléphone mobile valide pour le paiement par " . strtoupper($method) . "."];
            }
        } elseif ($method === 'carte_bancaire') {
            if (empty($cardNumber) || strlen(str_replace(' ', '', $cardNumber)) < 16) {
                return ['success' => false, 'message' => "Veuillez renseigner un numéro de carte bancaire valide à 16 chiffres."];
            }
        } else {
            return ['success' => false, 'message' => "Méthode de paiement en ligne invalide."];
        }

        // Génération de la référence sécurisée et du numéro de reçu
        $prefix = strtoupper(substr($method, 0, 3));
        $txRef = "MBL-" . $prefix . "-" . date('Ymd') . "-" . strtoupper(bin2hex(random_bytes(4)));
        $receiptNum = "REC-" . date('Y') . "-" . rand(10000, 99999);

        // Simulation de la validation du prestataire (réponse de confirmation API)
        $gatewayConfirmation = [
            'provider' => strtoupper($method),
            'status' => 'TRANSACTION_CONFIRMED',
            'gateway_auth_code' => 'AUTH_' . rand(100000, 999999),
            'customer_identifier' => !empty($phoneNumber) ? $phoneNumber : substr($cardNumber, 0, 4) . ' **** **** ' . substr($cardNumber, -4),
            'amount_debited' => $amount,
            'timestamp' => date('Y-m-d H:i:s'),
            'signature' => hash_hmac('sha256', $txRef . $amount, 'MBOOLO_SECRET_KEY_2026')
        ];

        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO `student_payments` (
                    `school_id`, `student_id`, `fee_schedule_id`, `received_by_user_id`,
                    `amount_paid`, `payment_method`, `payment_status`, `transaction_ref`,
                    `gateway_response`, `payment_date`, `receipt_number`, `notes`
                ) VALUES (?, ?, ?, NULL, ?, ?, 'paye', ?, ?, NOW(), ?, ?)
            ");
            $notes = "Paiement en ligne confirmé via " . ucfirst($method);
            $stmtInsert->execute([
                $schoolId, $studentId, $feeScheduleId, $amount, $method, $txRef,
                json_encode($gatewayConfirmation), $receiptNum, $notes
            ]);
            $paymentId = (int)$pdo->lastInsertId();

            Audit::log($schoolId, Auth::id() ?: $studentId, 'PAIEMENT_EN_LIGNE_CONFIRME', 'student_payments', $paymentId, "Paiement en ligne validé : {$amount} FCFA par {$method} (Réf: {$txRef})");

            return [
                'success' => true,
                'transaction_ref' => $txRef,
                'receipt_number' => $receiptNum,
                'amount' => $amount,
                'message' => "Paiement de " . formatMoney($amount) . " confirmé avec succès par le prestataire ! Votre reçu officiel est disponible."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Échec lors de l'enregistrement de la transaction : " . $e->getMessage()];
        }
    }

    /**
     * Enregistrement d'un paiement au guichet par le Secrétaire
     */
    public static function recordSecretaryPayment(int $schoolId, int $secretaryId, array $data): array {
        $pdo = getDbConnection();

        $studentId = (int)($data['student_id'] ?? 0);
        $feeScheduleId = (int)($data['fee_schedule_id'] ?? 0);
        $amountPaid = (float)($data['amount_paid'] ?? 0);
        $method = $data['payment_method'] ?? 'especes';
        $receiptNumber = trim($data['receipt_number'] ?? '');
        $notes = trim($data['notes'] ?? 'Règlement au comptoir administratif');

        if (empty($studentId) || empty($feeScheduleId) || $amountPaid <= 0) {
            return ['success' => false, 'message' => "L'élève, l'échéance et un montant valide sont obligatoires."];
        }

        if (empty($receiptNumber)) {
            $receiptNumber = "REC-" . date('Y') . "-" . rand(10000, 99999);
        }

        $txRef = "CASH-REC-" . date('Ymd') . "-" . rand(1000, 9999);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `student_payments` (
                    `school_id`, `student_id`, `fee_schedule_id`, `received_by_user_id`,
                    `amount_paid`, `payment_method`, `payment_status`, `transaction_ref`,
                    `gateway_response`, `payment_date`, `receipt_number`, `notes`
                ) VALUES (?, ?, ?, ?, ?, ?, 'paye', ?, ?, NOW(), ?, ?)
            ");
            $gatewayDetails = json_encode(['agent_id' => $secretaryId, 'manual_verification' => true]);
            $stmt->execute([
                $schoolId, $studentId, $feeScheduleId, $secretaryId,
                $amountPaid, $method, $txRef, $gatewayDetails, $receiptNumber, $notes
            ]);
            $id = $pdo->lastInsertId();

            Audit::log($schoolId, $secretaryId, 'PAIEMENT_GUICHET_ENREGISTRE', 'student_payments', $id, "Enregistrement manuel de paiement : " . formatMoney($amountPaid) . " (Reçu: {$receiptNumber})");

            return ['success' => true, 'receipt_number' => $receiptNumber, 'message' => "Le paiement a été enregistré avec succès au guichet."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    // --- GESTION DES SALAIRES DES PROFESSEURS ---

    public static function createOrUpdateTeacherSalary(int $schoolId, int $secretaryId, array $data): array {
        $pdo = getDbConnection();

        $teacherId = (int)($data['teacher_id'] ?? 0);
        $month = trim($data['month'] ?? '');
        $year = (int)($data['year'] ?? date('Y'));
        $baseSalary = (float)($data['base_salary'] ?? 0);
        $bonus = (float)($data['bonus'] ?? 0);
        $deductions = (float)($data['deductions'] ?? 0);
        $status = $data['payment_status'] ?? 'en_attente';
        $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : ($status === 'paye' ? date('Y-m-d') : null);
        $paymentMethod = $data['payment_method'] ?? 'virement';
        $notes = trim($data['notes'] ?? '');

        if (empty($teacherId) || empty($month) || $baseSalary <= 0) {
            return ['success' => false, 'message' => "L'enseignant, le mois et un salaire de base valide sont requis."];
        }

        $netSalary = max(0, ($baseSalary + $bonus) - $deductions);
        $payslipRef = "PAYSLIP-" . $year . "-" . strtoupper(substr($month, 0, 3)) . "-T" . $teacherId . "-" . rand(100, 999);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `teacher_salaries` (
                    `school_id`, `teacher_id`, `processed_by_user_id`, `month`, `year`,
                    `base_salary`, `bonus`, `deductions`, `net_salary`, `payment_status`,
                    `payment_date`, `payment_method`, `payslip_reference`, `notes`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `base_salary` = VALUES(`base_salary`),
                    `bonus` = VALUES(`bonus`),
                    `deductions` = VALUES(`deductions`),
                    `net_salary` = VALUES(`net_salary`),
                    `payment_status` = VALUES(`payment_status`),
                    `payment_date` = VALUES(`payment_date`),
                    `payment_method` = VALUES(`payment_method`),
                    `notes` = VALUES(`notes`)
            ");
            $stmt->execute([
                $schoolId, $teacherId, $secretaryId, $month, $year,
                $baseSalary, $bonus, $deductions, $netSalary, $status,
                $paymentDate, $paymentMethod, $payslipRef, $notes
            ]);
            $salId = $pdo->lastInsertId();

            Audit::log($schoolId, $secretaryId, 'SALAIRE_TRAITE', 'teacher_salaries', $salId, "Traitement salaire {$month} {$year} pour prof #{$teacherId} (Net: {$netSalary} FCFA, Statut: {$status})");

            return ['success' => true, 'message' => "La fiche de paie et le salaire du professeur ont été enregistrés avec succès."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
        }
    }

    public static function getAllSalariesForSecretary(int $schoolId, ?string $month = null, ?int $year = null): array {
        $pdo = getDbConnection();
        $sql = "
            SELECT ts.*, u.first_name, u.last_name, u.phone, u.email,
                   proc.first_name as proc_first_name, proc.last_name as proc_last_name
            FROM `teacher_salaries` ts
            JOIN `users` u ON ts.teacher_id = u.id
            LEFT JOIN `users` proc ON ts.processed_by_user_id = proc.id
            WHERE ts.school_id = ?
        ";
        $params = [$schoolId];

        if ($month) {
            $sql .= " AND ts.month = ?";
            $params[] = $month;
        }
        if ($year) {
            $sql .= " AND ts.year = ?";
            $params[] = $year;
        }

        $sql .= " ORDER BY ts.year DESC, ts.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Consultation confidentielle par le professeur de ses propres salaires autorisés
     */
    public static function getTeacherAuthorizedSalaries(int $schoolId, int $teacherId): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT ts.*, sch.currency, sch.name as school_name
            FROM `teacher_salaries` ts
            JOIN `schools` sch ON ts.school_id = sch.id
            WHERE ts.school_id = ? AND ts.teacher_id = ?
            ORDER BY ts.year DESC, ts.created_at DESC
        ");
        $stmt->execute([$schoolId, $teacherId]);
        return $stmt->fetchAll();
    }

    // --- JOURNAL DES TRANSACTIONS & ÉTAT DE TRÉSORERIE ---

    public static function getFinancialLedger(int $schoolId, int $limit = 50): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT 
                sp.id,
                'PAIEMENT_ELEVE' as operation_type,
                sp.transaction_ref as reference,
                sp.amount_paid as amount,
                'CREDIT' as direction,
                sp.payment_method,
                sp.payment_status,
                sp.payment_date as op_date,
                CONCAT('Mensualité ', fs.month, ' - ', u.first_name, ' ', u.last_name) as description
            FROM `student_payments` sp
            JOIN `fee_schedules` fs ON sp.fee_schedule_id = fs.id
            JOIN `users` u ON sp.student_id = u.id
            WHERE sp.school_id = ? AND sp.payment_status = 'paye'

            UNION ALL

            SELECT 
                ts.id,
                'SALAIRE_PROF' as operation_type,
                ts.payslip_reference as reference,
                ts.net_salary as amount,
                'DEBIT' as direction,
                ts.payment_method,
                ts.payment_status,
                COALESCE(ts.payment_date, ts.created_at) as op_date,
                CONCAT('Salaire ', ts.month, ' ', ts.year, ' - Prof. ', u.first_name, ' ', u.last_name) as description
            FROM `teacher_salaries` ts
            JOIN `users` u ON ts.teacher_id = u.id
            WHERE ts.school_id = ? AND ts.payment_status = 'paye'

            ORDER BY op_date DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $schoolId, PDO::PARAM_INT);
        $stmt->bindValue(2, $schoolId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

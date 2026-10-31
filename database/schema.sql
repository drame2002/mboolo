-- =====================================================================
-- MBOOLO — Plateforme de gestion et de communication scolaire
-- Base de données relationnelle Multi-Tenant
-- Jeu de caractères: UTF8MB4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `mboolo_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mboolo_db`;

-- Désactiver temporairement les vérifications de clés étrangères pour création propre
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `disciplinary_records`;
DROP TABLE IF EXISTS `attendances`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `report_card_details`;
DROP TABLE IF EXISTS `report_cards`;
DROP TABLE IF EXISTS `teacher_salaries`;
DROP TABLE IF EXISTS `student_payments`;
DROP TABLE IF EXISTS `fee_schedules`;
DROP TABLE IF EXISTS `parent_students`;
DROP TABLE IF EXISTS `meeting_participants`;
DROP TABLE IF EXISTS `meetings`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `lesson_summaries`;
DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `evaluations`;
DROP TABLE IF EXISTS `timetables`;
DROP TABLE IF EXISTS `class_subjects`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `schools`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABLE DES ÉTABLISSEMENTS SCOLAIRES (Multi-Tenant)
CREATE TABLE `schools` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `slogan` VARCHAR(255) DEFAULT NULL,
    `address` TEXT NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `website` VARCHAR(191) DEFAULT NULL,
    `logo_path` VARCHAR(255) DEFAULT NULL,
    `currency` VARCHAR(10) DEFAULT 'FCFA',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_school_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLE DES UTILISATEURS
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('directeur', 'surveillant', 'professeur', 'secretaire', 'eleve', 'parent') NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `gender` ENUM('M', 'F') DEFAULT 'M',
    `date_of_birth` DATE DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `avatar_path` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_school_username` (`school_id`, `username`),
    UNIQUE KEY `uk_school_email` (`school_id`, `email`),
    INDEX `idx_users_role` (`school_id`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLE DES CLASSES
CREATE TABLE `classes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `level` VARCHAR(50) NOT NULL, -- ex: 'CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2', '6eme', '5eme', '4eme', '3eme', 'Seconde', 'Premiere', 'Terminale'
    `cycle` ENUM('Prescolaire', 'Elementaire', 'College', 'Lycee') NOT NULL,
    `track` VARCHAR(100) DEFAULT NULL, -- 'Scientifique (S1)', 'Scientifique (S2)', 'Scientifique (S3)', 'Littéraire (L1)', 'Littéraire (L2)', 'Littéraire (L\')', 'Économie & Gestion (G)', 'Génie Mécanique (T1)', 'Génie Électrique (T2)'
    `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    `main_room` VARCHAR(50) DEFAULT NULL,
    `capacity` INT UNSIGNED DEFAULT 40,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_classes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    INDEX `idx_classes_school_cycle` (`school_id`, `cycle`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLE DES PROFILS ÉLÈVES
CREATE TABLE `student_profiles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `school_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `student_matricule` VARCHAR(50) NOT NULL,
    `admission_date` DATE DEFAULT NULL,
    `previous_school` VARCHAR(191) DEFAULT NULL,
    `blood_group` VARCHAR(10) DEFAULT NULL,
    `emergency_contact` VARCHAR(100) DEFAULT NULL,
    `emergency_phone` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sp_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sp_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_school_matricule` (`school_id`, `student_matricule`),
    INDEX `idx_sp_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLE DES MATIÈRES
CREATE TABLE `subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `color` VARCHAR(20) DEFAULT '#3b82f6',
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_subjects_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_school_subject_code` (`school_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLE DES AFFECTATIONS CLASSE - MATIÈRE - PROFESSEUR
CREATE TABLE `class_subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `coefficient` DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    `weekly_hours` DECIMAL(4, 1) NOT NULL DEFAULT 2.0,
    `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cs_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_cs_teacher` (`teacher_id`),
    INDEX `idx_cs_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLE DE L'EMPLOI DU TEMPS
CREATE TABLE `timetables` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `class_subject_id` INT UNSIGNED NOT NULL,
    `day_of_week` ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `duration_minutes` INT UNSIGNED NOT NULL, -- Calculé ou saisi (ex: 120 min)
    `room` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tt_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE,
    INDEX `idx_tt_day` (`school_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABLE DES ÉVALUATIONS (Devoirs, Compositions, Interrogations)
CREATE TABLE `evaluations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `class_subject_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `eval_type` ENUM('devoir', 'composition', 'interrogation', 'projet') NOT NULL DEFAULT 'devoir',
    `semester` ENUM('Semestre 1', 'Semestre 2') NOT NULL DEFAULT 'Semestre 1',
    `max_score` DECIMAL(5, 2) NOT NULL DEFAULT 20.00,
    `weight` DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    `eval_date` DATE NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_eval_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_eval_cs` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE,
    INDEX `idx_eval_semestre` (`school_id`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABLE DES NOTES
CREATE TABLE `grades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `evaluation_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL, -- Référence users.id (role 'eleve')
    `entered_by_teacher_id` INT UNSIGNED NOT NULL,
    `score` DECIMAL(5, 2) DEFAULT NULL, -- NULL si absent
    `is_absent` TINYINT(1) DEFAULT 0,
    `appreciation` VARCHAR(255) DEFAULT NULL,
    `entered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_grades_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grades_eval` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grades_teacher` FOREIGN KEY (`entered_by_teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_eval_student` (`evaluation_id`, `student_id`),
    INDEX `idx_grades_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABLE DES RÉSUMÉS DE LEÇONS (Cahier de textes numérique)
CREATE TABLE `lesson_summaries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `class_subject_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `lesson_date` DATE NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `summary_content` MEDIUMTEXT NOT NULL,
    `homework` TEXT DEFAULT NULL,
    `homework_due_date` DATE DEFAULT NULL,
    `attachments` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ls_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ls_cs` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ls_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_ls_date` (`school_id`, `lesson_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TABLE DES COMMUNIQUÉS DU DIRECTEUR
CREATE TABLE `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `author_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `content` TEXT NOT NULL,
    `target_audience` ENUM('all', 'professeurs', 'eleves', 'parents', 'personnel') NOT NULL DEFAULT 'all',
    `urgency_level` ENUM('normal', 'urgent', 'tres_urgent') NOT NULL DEFAULT 'normal',
    `is_pinned` TINYINT(1) DEFAULT 0,
    `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ann_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_ann_audience` (`school_id`, `target_audience`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. TABLE DES CONVOCATIONS DE RÉUNIONS (Professeurs)
CREATE TABLE `meetings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `organizer_id` INT UNSIGNED NOT NULL, -- Directeur
    `title` VARCHAR(191) NOT NULL,
    `meeting_type` ENUM('ordinaire', 'urgente') NOT NULL DEFAULT 'ordinaire',
    `meeting_date` DATETIME NOT NULL,
    `location` VARCHAR(150) NOT NULL DEFAULT 'Salle des professeurs',
    `agenda` TEXT NOT NULL,
    `status` ENUM('planifiee', 'en_cours', 'terminee', 'annulee') NOT NULL DEFAULT 'planifiee',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_meet_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_meet_org` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_meet_date` (`school_id`, `meeting_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. TABLE DES PARTICIPATIONS / ACCUSÉS DES CONVOCATIONS
CREATE TABLE `meeting_participants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `read_receipt` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `attendance_status` ENUM('en_attente', 'present', 'excuse', 'absent') DEFAULT 'en_attente',
    `response_comment` VARCHAR(255) DEFAULT NULL,
    CONSTRAINT `fk_mp_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mp_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_meeting_teacher` (`meeting_id`, `teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. TABLE DE LIAISON PARENTS - ÉLÈVES
CREATE TABLE `parent_students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `relationship_type` ENUM('Pere', 'Mere', 'Tuteur', 'Autre') DEFAULT 'Tuteur',
    `is_primary_guardian` TINYINT(1) DEFAULT 1,
    `linked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ps_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ps_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ps_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_parent_student` (`parent_id`, `student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TABLE DU BARÈME DES MENSUALITÉS / FRAIS SCOLAIRES
CREATE TABLE `fee_schedules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED DEFAULT NULL, -- NULL si applicable à toute l'école
    `fee_title` VARCHAR(100) NOT NULL, -- ex: 'Mensualité Octobre', 'Mensualité Novembre', 'Frais d\'inscription'
    `month` VARCHAR(20) DEFAULT NULL,   -- 'Octobre', 'Novembre', etc.
    `year` INT UNSIGNED NOT NULL DEFAULT 2025,
    `amount_due` DECIMAL(10, 2) NOT NULL,
    `due_date` DATE NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fs_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fs_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    INDEX `idx_fs_academic` (`school_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. TABLE DES PAIEMENTS DES ÉLÈVES (Guichet + En Ligne)
CREATE TABLE `student_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `fee_schedule_id` INT UNSIGNED NOT NULL,
    `received_by_user_id` INT UNSIGNED DEFAULT NULL, -- NULL si paiement en ligne automatique
    `amount_paid` DECIMAL(10, 2) NOT NULL,
    `payment_method` ENUM('wave', 'orange_money', 'carte_bancaire', 'especes', 'cheque', 'virement') NOT NULL,
    `payment_status` ENUM('en_attente', 'paye', 'echoue', 'annule') NOT NULL DEFAULT 'en_attente',
    `transaction_ref` VARCHAR(100) NOT NULL UNIQUE,
    `gateway_response` TEXT DEFAULT NULL,
    `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `receipt_number` VARCHAR(50) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pay_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_fee` FOREIGN KEY (`fee_schedule_id`) REFERENCES `fee_schedules` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pay_receiver` FOREIGN KEY (`received_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_pay_status` (`school_id`, `payment_status`, `payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. TABLE DES SALAIRES DES PROFESSEURS
CREATE TABLE `teacher_salaries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `processed_by_user_id` INT UNSIGNED DEFAULT NULL, -- Secrétaire ou Directeur
    `month` VARCHAR(20) NOT NULL, -- 'Octobre', 'Novembre', etc.
    `year` INT UNSIGNED NOT NULL,
    `base_salary` DECIMAL(10, 2) NOT NULL,
    `bonus` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `deductions` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `net_salary` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('en_attente', 'paye', 'rejete') NOT NULL DEFAULT 'en_attente',
    `payment_date` DATE DEFAULT NULL,
    `payment_method` ENUM('virement', 'cheque', 'especes', 'wave', 'orange_money') DEFAULT 'virement',
    `payslip_reference` VARCHAR(50) NOT NULL UNIQUE,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sal_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sal_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_sal_processor` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_sal_teacher` (`teacher_id`, `year`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. TABLE DES BULLETINS SCOLAIRES SEMESTRIELS
CREATE TABLE `report_cards` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `semester` ENUM('Semestre 1', 'Semestre 2') NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
    `general_average` DECIMAL(4, 2) NOT NULL,
    `class_rank` INT UNSIGNED DEFAULT NULL,
    `class_min_average` DECIMAL(4, 2) DEFAULT NULL,
    `class_max_average` DECIMAL(4, 2) DEFAULT NULL,
    `class_average` DECIMAL(4, 2) DEFAULT NULL,
    `total_coefficients` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `total_points` DECIMAL(7, 2) NOT NULL DEFAULT 0.00,
    `principal_appreciation` TEXT DEFAULT NULL,
    `conduct` VARCHAR(100) DEFAULT 'Très bon comportement',
    `absences_count` INT UNSIGNED DEFAULT 0,
    `status` ENUM('brouillon', 'valide', 'publie') NOT NULL DEFAULT 'valide',
    `generated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rc_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rc_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rc_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_rc_student_semester` (`school_id`, `student_id`, `semester`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. TABLE DES DÉTAILS DU BULLETIN (Moyennes par Matière)
CREATE TABLE `report_card_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_card_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `subject_average` DECIMAL(4, 2) NOT NULL,
    `coefficient` DECIMAL(4, 2) NOT NULL,
    `weighted_average` DECIMAL(6, 2) NOT NULL, -- subject_average * coefficient
    `class_subject_average` DECIMAL(4, 2) DEFAULT NULL,
    `teacher_appreciation` VARCHAR(255) DEFAULT NULL,
    CONSTRAINT `fk_rcd_rc` FOREIGN KEY (`report_card_id`) REFERENCES `report_cards` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rcd_subj` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rcd_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. TABLE DU JOURNAL D'AUDIT & TRAÇABILITÉ DES OPÉRATIONS SENSIBLES
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL, -- ex: 'LOGIN', 'NOTE_SAISIE', 'PAIEMENT_VALIDE', 'SALAIRE_PAYE', 'CONVOCATION_CREEE'
    `target_table` VARCHAR(50) DEFAULT NULL,
    `record_id` INT UNSIGNED DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    INDEX `idx_audit_school_action` (`school_id`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. TABLE DES ABSENCES & RETARDS (Assiduité gérée par le Surveillant)
CREATE TABLE `attendances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `recorded_by_user_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time_slot` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('absent', 'retard', 'present') NOT NULL DEFAULT 'absent',
    `is_justified` TINYINT(1) DEFAULT 0,
    `justification_reason` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_att_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_recorder` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_att_date` (`school_id`, `date`),
    INDEX `idx_att_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. TABLE DE LA VIE SCOLAIRE & DISCIPLINE (Gérée par le Surveillant)
CREATE TABLE `disciplinary_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `recorded_by_user_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `incident_type` ENUM('retard_repete', 'bavardage', 'manque_travail', 'indiscipline', 'degradation', 'absence_injustifiee', 'autre') NOT NULL DEFAULT 'indiscipline',
    `sanction` ENUM('observation', 'avertissement', 'retenue', 'exclusion_temporaire', 'convocation_parent') NOT NULL DEFAULT 'observation',
    `details` TEXT NOT NULL,
    `status` ENUM('ouvert', 'traite', 'clos') NOT NULL DEFAULT 'ouvert',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_disc_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_disc_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_disc_recorder` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    INDEX `idx_disc_school` (`school_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

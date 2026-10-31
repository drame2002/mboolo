<?php
// database/migration_surveillant.php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDbConnection();
    echo "[1/4] Connexion réussie à la base de données.\n";

    // 1. Modifier la colonne role de la table users pour inclure 'surveillant'
    echo "[2/4] Mise à jour du type ENUM de la colonne users.role...\n";
    $pdo->exec("
        ALTER TABLE `users` 
        MODIFY COLUMN `role` ENUM('directeur', 'surveillant', 'professeur', 'secretaire', 'eleve', 'parent') NOT NULL;
    ");
    echo " -> users.role mis à jour avec succès.\n";

    // 2. Création de la table attendances (Absences et retards gérés par le Surveillant)
    echo "[3/4] Création des tables 'attendances' et 'disciplinary_records'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `attendances` (
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
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `disciplinary_records` (
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
    ");
    echo " -> Tables d'assiduité et de discipline créées avec succès.\n";

    // 3. Insertion de comptes Surveillants de test si inexistants
    echo "[4/4] Création des comptes Surveillants de démonstration...\n";
    $defaultPasswordHash = password_hash('pass123', PASSWORD_BCRYPT);

    $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `email` IN ('surveillant@mboolo.sn', 'surveillant@teranga.sn')")->execute([$defaultPasswordHash]);

    $stmtCheck = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ?");
    $stmtCheck->execute(['surveillant@mboolo.sn']);
    if (!$stmtCheck->fetch()) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO `users` 
            (`school_id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `gender`, `date_of_birth`, `address`)
            VALUES (?, ?, ?, ?, 'surveillant', ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            1, 'surveillant_mboolo', 'surveillant@mboolo.sn', $defaultPasswordHash,
            'Ousmane', 'SÈNE', '+221 77 650 44 33', 'M', '1982-07-14', 'Grand Yoff, Dakar'
        ]);
        $surveillantId = $pdo->lastInsertId();
        echo " -> Surveillant École 1 (MBOOLO) créé : surveillant@mboolo.sn / password123 (ID: $surveillantId)\n";

        // Exemple d'absence et de discipline initiales
        $pdo->prepare("
            INSERT INTO `attendances` (`school_id`, `student_id`, `recorded_by_user_id`, `date`, `time_slot`, `status`, `is_justified`, `justification_reason`)
            VALUES (1, 6, ?, CURDATE(), '08:00 - 10:00', 'retard', 1, 'Problème de transport justifié par les parents')
        ")->execute([$surveillantId]);

        $pdo->prepare("
            INSERT INTO `disciplinary_records` (`school_id`, `student_id`, `recorded_by_user_id`, `date`, `incident_type`, `sanction`, `details`, `status`)
            VALUES (1, 7, ?, CURDATE(), 'retard_repete', 'observation', 'Trois retards constatés cette semaine sans justificatif valable.', 'ouvert')
        ")->execute([$surveillantId]);
    } else {
        echo " -> Surveillant École 1 existe déjà.\n";
    }

    $stmtCheck->execute(['surveillant@teranga.sn']);
    if (!$stmtCheck->fetch()) {
        $pdo->prepare("
            INSERT INTO `users` 
            (`school_id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `gender`, `date_of_birth`, `address`)
            VALUES (?, ?, ?, ?, 'surveillant', ?, ?, ?, ?, ?, ?)
        ")->execute([
            2, 'surveillant_teranga', 'surveillant@teranga.sn', $defaultPasswordHash,
            'Pape', 'FALL', '+221 77 340 55 66', 'M', '1984-03-22', 'Balacos, Saint-Louis'
        ]);
        echo " -> Surveillant École 2 (TERANGA) créé : surveillant@teranga.sn / password123\n";
    }

    echo "\n>>> MIGRATION DU RÔLE SURVEILLANT TERMINÉE AVEC SUCCÈS ! <<<\n";

} catch (Exception $e) {
    echo "ERREUR MIGRATION : " . $e->getMessage() . "\n";
    exit(1);
}

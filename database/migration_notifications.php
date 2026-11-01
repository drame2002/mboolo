<?php
/**
 * Migration : Table des Notifications Parents (SMS, WhatsApp, Email & In-App)
 */
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDbConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `school_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `student_id` INT UNSIGNED DEFAULT NULL,
        `channel` ENUM('sms', 'whatsapp', 'email', 'in_app') NOT NULL DEFAULT 'in_app',
        `recipient_contact` VARCHAR(100) DEFAULT NULL,
        `type` VARCHAR(50) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `status` ENUM('envoye', 'delivre', 'en_attente', 'echec') NOT NULL DEFAULT 'envoye',
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `read_at` DATETIME DEFAULT NULL,
        INDEX (`school_id`, `user_id`, `is_read`),
        INDEX (`student_id`, `type`),
        CONSTRAINT `fk_notif_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Table 'notifications' créée ou mise à jour avec succès !\n";

} catch (Exception $e) {
    echo "Erreur lors de la migration notifications : " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * Service de Sauvegarde de la Base de Données — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class BackupService {

    /**
     * Génère une sauvegarde SQL complète ou spécifique à un établissement
     */
    public static function generateSqlBackup(?int $schoolId = null): string {
        $pdo = getDbConnection();
        $dbConfig = getDbConfig();
        
        $backup = "-- =====================================================================\n";
        $backup .= "-- MBOOLO SAAS — Sauvegarde Automatique de Base de Données\n";
        $backup .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        if ($schoolId) {
            $backup .= "-- Établissement ID: {$schoolId}\n";
        }
        $backup .= "-- Serveur: MySQL {$dbConfig['host']}:{$dbConfig['port']}\n";
        $backup .= "-- =====================================================================\n\n";
        $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Structure de la table
            $stmtCreate = $pdo->query("SHOW CREATE TABLE `{$table}`;")->fetch(PDO::FETCH_NUM);
            $backup .= "-- Structure de la table `{$table}`\n";
            $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $backup .= $stmtCreate[1] . ";\n\n";

            // Données
            $query = "SELECT * FROM `{$table}`";
            if ($schoolId && in_array($table, [
                'users', 'classes', 'subjects', 'class_subjects', 'timetables',
                'evaluations', 'grades', 'lesson_summaries', 'announcements',
                'meetings', 'parent_students', 'fee_schedules', 'student_payments',
                'teacher_salaries', 'report_cards', 'audit_logs'
            ])) {
                $query .= " WHERE `school_id` = {$schoolId}";
            }

            $stmtRows = $pdo->query($query);
            $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $backup .= "-- Données de la table `{$table}` (" . count($rows) . " enregistrements)\n";
                foreach ($rows as $row) {
                    $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
                    $values = array_map(function($v) use ($pdo) {
                        return $v === null ? "NULL" : $pdo->quote($v);
                    }, array_values($row));

                    $backup .= "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
        }

        $backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $backup .= "-- FIN DE LA SAUVEGARDE MBOOLO --\n";

        return $backup;
    }
}

<?php
/**
 * Configuration de la Base de Données — MBOOLO
 */

function getDbConfig(): array {
    return [
        'driver'  => 'mysql',
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'mboolo_db',
        'user'    => 'root',
        'pass'    => 'admin123',
        'charset' => 'utf8mb4'
    ];
}

/**
 * Retourne l'instance PDO singleton pour l'application
 */
function getDbConnection(): PDO {
    static $pdoInstance = null;

    if ($pdoInstance === null) {
        $config = getDbConfig();
        $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdoInstance = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            die("Erreur critique de connexion à la base de données MBOOLO : " . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdoInstance;
}

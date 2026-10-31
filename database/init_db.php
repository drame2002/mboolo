<?php
// Initialisateur de la base de données MBOOLO
require_once __DIR__ . '/../config/database.php';

try {
    $dbConfig = getDbConfig();
    
    // Connexion au serveur MySQL sans spécifier la base (pour pouvoir la créer si inexistante)
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[1/3] Connexion au serveur MySQL réussie.\n";

    // Lecture du schéma SQL
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    if (!$schemaSql) {
        throw new Exception("Impossible de lire database/schema.sql");
    }

    // Exécution du script SQL global
    echo "[2/3] Exécution du schéma SQL (création de la base et des 20 tables)...\n";
    $pdo->exec($schemaSql);
    echo " -> Schéma exécuté avec succès.\n";

    // Reconnexion avec la base de données sélectionnée
    $pdo->exec("USE `{$dbConfig['name']}`;");
    $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
    echo " -> Tables créées (" . count($tables) . " tables) :\n";
    foreach ($tables as $t) {
        echo "    * $t\n";
    }

    echo "[3/3] Base de données mboolo_db initialisée avec succès !\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}

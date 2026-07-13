<?php
/**
 * Crée la table php_sessions (stockage sessions PHP en MySQL).
 *
 * Usage : php migrations/run_migrate_php_sessions.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/conn/conn.php';

if (empty($db) || !($db instanceof PDO)) {
    echo "Erreur : connexion BDD indisponible.\n";
    exit(1);
}

echo "Migration php_sessions...\n";

try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `php_sessions` (
            `id` VARCHAR(128) NOT NULL,
            `data` MEDIUMBLOB NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_php_sessions_last_activity` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK : table php_sessions prête.\n";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

$st = $db->query("SHOW TABLE STATUS LIKE 'php_sessions'");
$row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
if ($row) {
    echo "Engine=" . ($row['Engine'] ?? '?') . " Rows=" . ($row['Rows'] ?? '0') . "\n";
}

echo "Done.\n";

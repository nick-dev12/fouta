<?php
/**
 * Handler de sessions PHP en MySQL (procédural).
 * Survit aux redémarrages Webuzo / PHP-FPM / Apache / Nginx.
 */

if (!function_exists('php_session_mysql_db')) {
    /**
     * @return PDO|null
     */
    function php_session_mysql_db()
    {
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            return $GLOBALS['db'];
        }

        $conn = __DIR__ . '/../conn/conn.php';
        if (!is_file($conn)) {
            return null;
        }

        // conn.php définit $db ; en cas d'échec il exit(503) — site déjà inutilisable.
        require_once $conn;

        if (isset($db) && $db instanceof PDO) {
            $GLOBALS['db'] = $db;
            return $db;
        }

        return (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) ? $GLOBALS['db'] : null;
    }
}

if (!function_exists('php_session_mysql_ensure_table')) {
    /**
     * @return bool
     */
    function php_session_mysql_ensure_table(PDO $db)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

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
            $ready = true;
            return true;
        } catch (Throwable $e) {
            error_log('[php_sessions] ensure_table: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('php_session_mysql_lock_name')) {
    function php_session_mysql_lock_name(string $id): string
    {
        // Nom de verrou MySQL max 64 caractères.
        $clean = preg_replace('/[^a-zA-Z0-9,-]/', '', $id);
        if (!is_string($clean)) {
            $clean = '';
        }

        return 'php_sess_' . substr($clean, 0, 54);
    }
}

if (!function_exists('php_session_mysql_open')) {
    function php_session_mysql_open($save_path, $session_name): bool
    {
        $db = php_session_mysql_db();
        if (!$db) {
            return false;
        }

        return php_session_mysql_ensure_table($db);
    }
}

if (!function_exists('php_session_mysql_close')) {
    function php_session_mysql_close(): bool
    {
        $db = php_session_mysql_db();
        if ($db && !empty($GLOBALS['php_session_mysql_lock'])) {
            try {
                $st = $db->prepare('SELECT RELEASE_LOCK(:n)');
                $st->execute(['n' => $GLOBALS['php_session_mysql_lock']]);
            } catch (Throwable $e) {
                // ignore
            }
            unset($GLOBALS['php_session_mysql_lock']);
        }

        return true;
    }
}

if (!function_exists('php_session_mysql_read')) {
    function php_session_mysql_read($id): string
    {
        $db = php_session_mysql_db();
        if (!$db) {
            return '';
        }

        try {
            $lock = php_session_mysql_lock_name((string) $id);
            $stLock = $db->prepare('SELECT GET_LOCK(:n, 5)');
            $stLock->execute(['n' => $lock]);
            $GLOBALS['php_session_mysql_lock'] = $lock;

            $st = $db->prepare('SELECT `data` FROM `php_sessions` WHERE `id` = :id LIMIT 1');
            $st->execute(['id' => (string) $id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if (!$row || !isset($row['data'])) {
                return '';
            }

            return is_string($row['data']) ? $row['data'] : (string) $row['data'];
        } catch (Throwable $e) {
            error_log('[php_sessions] read: ' . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('php_session_mysql_write')) {
    function php_session_mysql_write($id, $data): bool
    {
        $db = php_session_mysql_db();
        if (!$db) {
            return false;
        }

        try {
            $st = $db->prepare(
                'INSERT INTO `php_sessions` (`id`, `data`, `last_activity`)
                 VALUES (:id, :data, :ts)
                 ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `last_activity` = VALUES(`last_activity`)'
            );

            return $st->execute([
                'id' => (string) $id,
                'data' => (string) $data,
                'ts' => time(),
            ]);
        } catch (Throwable $e) {
            error_log('[php_sessions] write: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('php_session_mysql_destroy')) {
    function php_session_mysql_destroy($id): bool
    {
        $db = php_session_mysql_db();
        if (!$db) {
            return false;
        }

        try {
            $st = $db->prepare('DELETE FROM `php_sessions` WHERE `id` = :id');
            return $st->execute(['id' => (string) $id]);
        } catch (Throwable $e) {
            error_log('[php_sessions] destroy: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('php_session_mysql_gc')) {
    function php_session_mysql_gc($maxlifetime): int|false
    {
        $db = php_session_mysql_db();
        if (!$db) {
            return false;
        }

        try {
            $maxlifetime = (int) $maxlifetime;
            if ($maxlifetime < 1) {
                $maxlifetime = 2592000;
            }
            $cutoff = time() - $maxlifetime;
            $st = $db->prepare('DELETE FROM `php_sessions` WHERE `last_activity` < :cutoff');
            $st->execute(['cutoff' => $cutoff]);

            return $st->rowCount();
        } catch (Throwable $e) {
            error_log('[php_sessions] gc: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('php_session_mysql_register')) {
    /**
     * Enregistre le handler MySQL. Retourne true si OK.
     */
    function php_session_mysql_register(): bool
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return false;
        }

        $db = php_session_mysql_db();
        if (!$db || !php_session_mysql_ensure_table($db)) {
            return false;
        }

        $ok = session_set_save_handler(
            'php_session_mysql_open',
            'php_session_mysql_close',
            'php_session_mysql_read',
            'php_session_mysql_write',
            'php_session_mysql_destroy',
            'php_session_mysql_gc'
        );

        if ($ok) {
            // Garantit l'écriture session même en cas de sortie anticipée.
            register_shutdown_function('session_write_close');
            ini_set('session.save_handler', 'user');
        }

        return $ok;
    }
}

if (!function_exists('php_session_files_fallback_path')) {
    /**
     * Dossier fichiers de secours (hors Webuzo) si MySQL indisponible.
     */
    function php_session_files_fallback_path(): string
    {
        $candidates = [];

        // Production VPS (compte site)
        if (is_dir('/home/colobanes')) {
            $candidates[] = '/home/colobanes/tmp/sessions';
        }

        // Projet local / dépôt
        $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'sessions';

        foreach ($candidates as $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0700, true);
            }
            if (is_dir($path) && is_writable($path)) {
                return $path;
            }
        }

        // Dernier recours : save_path PHP actuel
        $current = (string) ini_get('session.save_path');
        return $current !== '' ? $current : sys_get_temp_dir();
    }
}

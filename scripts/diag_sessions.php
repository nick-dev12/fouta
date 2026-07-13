<?php
/**
 * Diagnostic sessions (durée cookie, gc, backend MySQL/fichiers).
 *
 * CLI  : php scripts/diag_sessions.php
 * Web  : /scripts/diag_sessions.php  (à supprimer après usage)
 */


declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/session_user.php';

$is_cli = (PHP_SAPI === 'cli');

if (!$is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
}

session_start_persistent();

// Marqueur pour vérifier la persistance écriture.
if (empty($_SESSION['_diag_started_at'])) {
    $_SESSION['_diag_started_at'] = date('c');
    $_SESSION['_diag_hits'] = 1;
} else {
    $_SESSION['_diag_hits'] = (int) ($_SESSION['_diag_hits'] ?? 0) + 1;
}

$lifetime = session_persistent_lifetime();
$params = session_get_cookie_params();
$backend = session_storage_backend();
$sid = session_id();

$lines = [];
$lines[] = '=== DIAG SESSIONS COLO BANES ===';
$lines[] = 'date_server        : ' . date('c');
$lines[] = 'php_sapi           : ' . PHP_SAPI;
$lines[] = 'php_version        : ' . PHP_VERSION;
$lines[] = '';
$lines[] = '--- Backend ---';
$lines[] = 'save_handler       : ' . ini_get('session.save_handler');
$lines[] = 'storage_backend    : ' . $backend;
$lines[] = 'save_path          : ' . ini_get('session.save_path');
$lines[] = '';
$lines[] = '--- Durées (doivent être ~315360000 = 10 ans) ---';
$lines[] = 'app_lifetime       : ' . $lifetime;
$lines[] = 'gc_maxlifetime     : ' . ini_get('session.gc_maxlifetime');
$lines[] = 'cookie_lifetime    : ' . ini_get('session.cookie_lifetime');
$lines[] = 'gc_probability     : ' . ini_get('session.gc_probability');
$lines[] = 'gc_divisor         : ' . ini_get('session.gc_divisor');
$lines[] = '';
$lines[] = '--- Cookie params ---';
$lines[] = 'cookie.name        : ' . session_name();
$lines[] = 'cookie.lifetime    : ' . ($params['lifetime'] ?? '?');
$lines[] = 'cookie.path        : ' . ($params['path'] ?? '?');
$lines[] = 'cookie.domain      : ' . (($params['domain'] ?? '') !== '' ? $params['domain'] : '(host-only)');
$lines[] = 'cookie.secure      : ' . (!empty($params['secure']) ? '1' : '0');
$lines[] = 'cookie.httponly    : ' . (!empty($params['httponly']) ? '1' : '0');
$lines[] = 'cookie.samesite    : ' . ($params['samesite'] ?? '');
$lines[] = '';
$lines[] = '--- Session courante ---';
$lines[] = 'session_id         : ' . $sid;
$lines[] = 'session_status     : ' . session_status();
$lines[] = 'diag_started_at    : ' . ($_SESSION['_diag_started_at'] ?? '');
$lines[] = 'diag_hits          : ' . ($_SESSION['_diag_hits'] ?? 0);
$lines[] = 'has_user_id        : ' . (!empty($_SESSION['user_id']) ? 'yes' : 'no');
$lines[] = 'has_admin_id       : ' . (!empty($_SESSION['admin_id']) ? 'yes' : 'no');
$lines[] = 'has_super_admin_id : ' . (!empty($_SESSION['super_admin_id']) ? 'yes' : 'no');

/** @var PDO|null $diag_pdo */
$diag_pdo = null;
$row = null;
if ($backend === 'mysql') {
    try {
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $diag_pdo = $GLOBALS['db'];
        } else {
            require_once $root . '/conn/conn.php';
            if (isset($db) && $db instanceof PDO) {
                $GLOBALS['db'] = $db;
                $diag_pdo = $db;
            }
        }

        if ($diag_pdo instanceof PDO) {
            $st = $diag_pdo->prepare('SELECT id, LENGTH(data) AS data_len, last_activity FROM php_sessions WHERE id = :id LIMIT 1');
            if ($st === false) {
                throw new RuntimeException('prepare php_sessions failed');
            }
            $st->execute(['id' => $sid]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;

            $countSt = $diag_pdo->query('SELECT COUNT(*) FROM php_sessions');
            $count = $countSt ? (int) $countSt->fetchColumn() : 0;
            $lines[] = '';
            $lines[] = '--- MySQL php_sessions ---';
            $lines[] = 'table_ok           : yes';
            $lines[] = 'rows_total         : ' . $count;
            if ($row) {
                $lines[] = 'row_found          : yes';
                $lines[] = 'row_data_len       : ' . ($row['data_len'] ?? '?');
                $lines[] = 'row_last_activity  : ' . ($row['last_activity'] ?? '?') . ' (' . date('c', (int) $row['last_activity']) . ')';
            } else {
                $lines[] = 'row_found          : no (sera créée à la fin de requête / session_write_close)';
            }
        }
    } catch (Throwable $e) {
        $diag_pdo = null;
        $lines[] = '';
        $lines[] = '--- MySQL php_sessions ---';
        $lines[] = 'error              : ' . $e->getMessage();
    }
} else {
    $path = (string) ini_get('session.save_path');
    $file = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . $sid;
    $lines[] = '';
    $lines[] = '--- Fichiers sessions ---';
    $lines[] = 'dir_writable        : ' . (is_dir($path) && is_writable($path) ? 'yes' : 'no');
    $lines[] = 'session_file        : ' . $file;
    $lines[] = 'file_exists         : ' . (is_file($file) ? 'yes' : 'no (après fin de requête)');
}

$lines[] = '';
$lines[] = '--- Checks ---';
$gc = (int) ini_get('session.gc_maxlifetime');
$ck = (int) ini_get('session.cookie_lifetime');
$lines[] = 'gc_maxlifetime_ok   : ' . ($gc >= 2592000 ? 'YES' : 'NO');
$lines[] = 'cookie_lifetime_ok  : ' . ($ck >= 2592000 ? 'YES' : 'NO');
$lines[] = 'backend_mysql_ok    : ' . ($backend === 'mysql' ? 'YES' : 'NO (fallback fichiers)');
$lines[] = 'https_detected      : ' . (session_request_is_https() ? 'yes' : 'no');

$lines[] = '';
$lines[] = 'Recharge cette page : diag_hits doit augmenter. Si remis à 1 => session non persistante.';
$lines[] = 'Après test web : supprimer scripts/diag_sessions.php et phpinfo_tmp.php';

echo implode(PHP_EOL, $lines) . PHP_EOL;

// Force write immédiat pour le check MySQL en CLI.
session_write_close();

if ($backend === 'mysql' && $diag_pdo instanceof PDO) {
    try {
        $st = $diag_pdo->prepare('SELECT id, LENGTH(data) AS data_len, last_activity FROM php_sessions WHERE id = :id LIMIT 1');
        if ($st === false) {
            throw new RuntimeException('prepare php_sessions failed');
        }
        $st->execute(['id' => $sid]);
        $row2 = $st->fetch(PDO::FETCH_ASSOC);
        echo PHP_EOL . '--- Après session_write_close ---' . PHP_EOL;
        echo 'row_persisted      : ' . ($row2 ? 'YES' : 'NO') . PHP_EOL;
        if ($row2) {
            echo 'row_data_len       : ' . ($row2['data_len'] ?? '?') . PHP_EOL;
            echo 'row_last_activity  : ' . date('c', (int) $row2['last_activity']) . PHP_EOL;
        }
    } catch (Throwable $e) {
        echo 'persist_check_error: ' . $e->getMessage() . PHP_EOL;
    }
}
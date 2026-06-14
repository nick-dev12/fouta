<?php
/**
 * Limitation des tentatives de connexion (session + IP en base).
 * - 1er cycle : 7 échecs → blocage 15 min ; avertissement à partir du 4e échec
 * - Après déblocage : 2 échecs → blocage 30 min, puis 60 min, etc. (durée ×2)
 */

if (!defined('LOGIN_RATE_MAX_ATTEMPTS')) {
    define('LOGIN_RATE_MAX_ATTEMPTS', 7);
}
if (!defined('LOGIN_RATE_WARN_AFTER')) {
    define('LOGIN_RATE_WARN_AFTER', 4);
}
if (!defined('LOGIN_RATE_SUBSEQUENT_MAX_ATTEMPTS')) {
    define('LOGIN_RATE_SUBSEQUENT_MAX_ATTEMPTS', 2);
}
if (!defined('LOGIN_RATE_LOCK_SECONDS')) {
    define('LOGIN_RATE_LOCK_SECONDS', 900);
}
if (!defined('LOGIN_RATE_MAX_LOCKOUT_LEVEL')) {
    define('LOGIN_RATE_MAX_LOCKOUT_LEVEL', 10);
}

/**
 * Clé client (empreinte IP) — REMOTE_ADDR uniquement (pas de X-Forwarded-For non fiable).
 */
function login_attempt_client_key()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '0.0.0.0';
    $salt = 'colobanes_login_rate_v1';
    $secret_file = __DIR__ . '/../config/login_rate_secret.php';
    if (is_file($secret_file)) {
        $cfg = require $secret_file;
        if (is_array($cfg) && !empty($cfg['ip_salt'])) {
            $salt = (string) $cfg['ip_salt'];
        }
    }
    return hash('sha256', $ip . '|' . $salt);
}

/**
 * Crée la table de persistance IP si absente.
 */
function login_attempt_db_ensure_table()
{
    static $ok = null;
    if ($ok === true) {
        return true;
    }
    global $db;
    if (!isset($db) || !($db instanceof PDO)) {
        if (file_exists(__DIR__ . '/../conn/conn.php')) {
            require_once __DIR__ . '/../conn/conn.php';
        }
    }
    if (!isset($db) || !($db instanceof PDO)) {
        $ok = false;
        return false;
    }
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `login_rate_limit_ip` (
            `client_key` char(64) NOT NULL,
            `fail_count` int(10) unsigned NOT NULL DEFAULT 0,
            `lockout_level` int(10) unsigned NOT NULL DEFAULT 0,
            `lock_until` int(10) unsigned NOT NULL DEFAULT 0,
            `lock_duration` int(10) unsigned NOT NULL DEFAULT 0,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`client_key`),
            KEY `idx_lock_until` (`lock_until`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ok = true;
        return true;
    } catch (PDOException $e) {
        $ok = false;
        return false;
    }
}

/**
 * Charge l'état IP depuis la base.
 *
 * @return array{fail_count:int,lockout_level:int,lock_until:int,lock_duration:int}|null
 */
function login_attempt_db_load()
{
    if (!login_attempt_db_ensure_table()) {
        return null;
    }
    global $db;
    try {
        $stmt = $db->prepare('
            SELECT fail_count, lockout_level, lock_until, lock_duration
            FROM login_rate_limit_ip
            WHERE client_key = :k
            LIMIT 1
        ');
        $stmt->execute(['k' => login_attempt_client_key()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'fail_count' => (int) ($row['fail_count'] ?? 0),
            'lockout_level' => (int) ($row['lockout_level'] ?? 0),
            'lock_until' => (int) ($row['lock_until'] ?? 0),
            'lock_duration' => (int) ($row['lock_duration'] ?? 0),
        ];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Persiste l'état session vers la base (anti-contournement nouvelle session).
 */
function login_attempt_db_save()
{
    if (!login_attempt_db_ensure_table()) {
        return false;
    }
    global $db;
    try {
        $stmt = $db->prepare('
            INSERT INTO login_rate_limit_ip (client_key, fail_count, lockout_level, lock_until, lock_duration, updated_at)
            VALUES (:k, :fc, :lv, :lu, :ld, NOW())
            ON DUPLICATE KEY UPDATE
                fail_count = VALUES(fail_count),
                lockout_level = VALUES(lockout_level),
                lock_until = VALUES(lock_until),
                lock_duration = VALUES(lock_duration),
                updated_at = NOW()
        ');
        return $stmt->execute([
            'k' => login_attempt_client_key(),
            'fc' => login_attempt_fail_count_raw(),
            'lv' => (int) ($_SESSION['login_lockout_level'] ?? 0),
            'lu' => (int) ($_SESSION['login_lock_until'] ?? 0),
            'ld' => (int) ($_SESSION['login_lock_duration'] ?? 0),
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime l'état IP après connexion réussie.
 */
function login_attempt_db_clear()
{
    if (!login_attempt_db_ensure_table()) {
        return false;
    }
    global $db;
    try {
        $stmt = $db->prepare('DELETE FROM login_rate_limit_ip WHERE client_key = :k');
        return $stmt->execute(['k' => login_attempt_client_key()]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Fusionne l'état IP (plus restrictif) dans la session.
 */
function login_attempt_sync_from_db()
{
    $row = login_attempt_db_load();
    if ($row === null) {
        return;
    }
    $now = time();
    $db_lock = (int) $row['lock_until'];
    $sess_lock = (int) ($_SESSION['login_lock_until'] ?? 0);

    if ($db_lock > $now && $db_lock >= $sess_lock) {
        $_SESSION['login_lock_until'] = $db_lock;
        $_SESSION['login_lock_duration'] = (int) $row['lock_duration'];
        $_SESSION['login_lockout_level'] = max((int) ($_SESSION['login_lockout_level'] ?? 0), (int) $row['lockout_level']);
        $_SESSION['login_fail_count'] = 0;
        return;
    }

    if ($db_lock <= $now) {
        $_SESSION['login_fail_count'] = max(login_attempt_fail_count_raw(), (int) $row['fail_count']);
        $_SESSION['login_lockout_level'] = max((int) ($_SESSION['login_lockout_level'] ?? 0), (int) $row['lockout_level']);
    }
}

/**
 * Compteur session brut (sans sync).
 */
function login_attempt_fail_count_raw()
{
    return (int) ($_SESSION['login_fail_count'] ?? 0);
}

/**
 * Durée de blocage (secondes) selon le niveau d'escalade (0 = 15 min, 1 = 30 min, …).
 */
function login_attempt_lock_duration_for_level($level)
{
    $level = max(0, min((int) LOGIN_RATE_MAX_LOCKOUT_LEVEL, (int) $level));
    $mult = 1 << $level;
    $duration = (int) LOGIN_RATE_LOCK_SECONDS * $mult;
    return min($duration, 86400 * 7);
}

/**
 * Nombre max d'échecs avant blocage (7 au premier cycle, puis 2).
 */
function login_attempt_max_before_lock()
{
    $level = (int) ($_SESSION['login_lockout_level'] ?? 0);
    return $level === 0 ? (int) LOGIN_RATE_MAX_ATTEMPTS : (int) LOGIN_RATE_SUBSEQUENT_MAX_ATTEMPTS;
}

/**
 * Niveau d'escalade actuel (0 = jamais bloqué, 1 = après 1er blocage, …).
 */
function login_attempt_lockout_level()
{
    login_attempt_unlock_if_expired();
    return (int) ($_SESSION['login_lockout_level'] ?? 0);
}

/**
 * Réinitialise le compteur si la période de blocage est expirée.
 */
function login_attempt_unlock_if_expired()
{
    login_attempt_sync_from_db();

    if (empty($_SESSION['login_lock_until'])) {
        login_attempt_db_save();
        return;
    }
    if (time() >= (int) $_SESSION['login_lock_until']) {
        unset(
            $_SESSION['login_lock_until'],
            $_SESSION['login_fail_count'],
            $_SESSION['login_lock_duration']
        );
        login_attempt_db_save();
    }
}

/**
 * Indique si la connexion est temporairement bloquée.
 */
function login_attempt_is_locked()
{
    login_attempt_unlock_if_expired();
    if (empty($_SESSION['login_lock_until'])) {
        return false;
    }
    return time() < (int) $_SESSION['login_lock_until'];
}

/**
 * Secondes restantes avant déblocage (0 si non bloqué).
 */
function login_attempt_remaining_seconds()
{
    login_attempt_unlock_if_expired();
    if (empty($_SESSION['login_lock_until'])) {
        return 0;
    }
    return max(0, (int) $_SESSION['login_lock_until'] - time());
}

/**
 * Nombre d'échecs enregistrés dans le cycle en cours.
 */
function login_attempt_fail_count()
{
    login_attempt_unlock_if_expired();
    return login_attempt_fail_count_raw();
}

/**
 * Tentatives restantes avant le prochain blocage.
 */
function login_attempt_remaining_before_lock()
{
    if (login_attempt_is_locked()) {
        return 0;
    }
    return max(0, login_attempt_max_before_lock() - login_attempt_fail_count());
}

/**
 * Afficher l'avertissement (à partir de 4 échecs, hors période de blocage).
 */
function login_attempt_show_warning()
{
    if (login_attempt_is_locked()) {
        return false;
    }
    return login_attempt_fail_count() >= (int) LOGIN_RATE_WARN_AFTER;
}

/**
 * Durée du blocage en cours (secondes), pour affichage.
 */
function login_attempt_active_lock_duration_seconds()
{
    if (!login_attempt_is_locked()) {
        return 0;
    }
    $stored = (int) ($_SESSION['login_lock_duration'] ?? 0);
    if ($stored > 0) {
        return $stored;
    }
    $level = max(0, login_attempt_lockout_level() - 1);
    return login_attempt_lock_duration_for_level($level);
}

/**
 * Après une connexion réussie.
 */
function login_attempt_clear()
{
    unset(
        $_SESSION['login_fail_count'],
        $_SESSION['login_lock_until'],
        $_SESSION['login_lockout_level'],
        $_SESSION['login_lock_duration']
    );
    login_attempt_db_clear();
}

/**
 * Compte un échec d'authentification (identifiants incorrects, compte inactif, etc.).
 */
function login_attempt_register_failure()
{
    login_attempt_unlock_if_expired();
    if (login_attempt_is_locked()) {
        return;
    }
    $n = login_attempt_fail_count_raw() + 1;
    $_SESSION['login_fail_count'] = $n;
    $max = login_attempt_max_before_lock();
    if ($n >= $max) {
        $level = (int) ($_SESSION['login_lockout_level'] ?? 0);
        $duration = login_attempt_lock_duration_for_level($level);
        $_SESSION['login_lock_until'] = time() + $duration;
        $_SESSION['login_lock_duration'] = $duration;
        $_SESSION['login_lockout_level'] = $level + 1;
        $_SESSION['login_fail_count'] = 0;
    }
    login_attempt_db_save();
}

/**
 * Message lisible pour le temps restant.
 */
function login_attempt_format_remaining($seconds)
{
    $seconds = max(0, (int) $seconds);
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    if ($m <= 0) {
        return $s . ' s';
    }
    if ($s === 0) {
        return $m . ' min';
    }
    return $m . ' min ' . str_pad((string) $s, 2, '0', STR_PAD_LEFT) . ' s';
}

/**
 * Message d'avertissement tentatives restantes.
 */
function login_attempt_warning_message()
{
    if (!login_attempt_show_warning()) {
        return '';
    }
    $rem = login_attempt_remaining_before_lock();
    if ($rem <= 0) {
        return '';
    }
    $label = $rem > 1 ? 'tentatives' : 'tentative';
    return 'Attention : il vous reste ' . $rem . ' ' . $label . ' avant un blocage temporaire de la connexion.';
}

/**
 * Affichage HTML sûr des messages serveur (autorise uniquement les sauts <br>).
 */
function login_safe_html_message($message)
{
    $message = trim((string) $message);
    if ($message === '') {
        return '';
    }
    $parts = preg_split('/<br\s*\/?>/i', $message) ?: [];
    $safe = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        $safe[] = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
    }
    return implode('<br>', $safe);
}

/**
 * Réponse standard en cas de blocage actif.
 */
function login_attempt_locked_result_array()
{
    $rem = login_attempt_remaining_seconds();
    return [
        'success' => false,
        'message' => 'Trop de tentatives de connexion incorrectes. Réessayez dans ' . login_attempt_format_remaining($rem) . '.',
        'type' => null,
        'admin' => null,
        'user' => null,
        'vendeur_collaborateur' => null,
        'rate_limited' => true,
        'remaining_seconds' => $rem,
    ];
}

/**
 * Enregistre un échec et retourne le tableau « échec connexion » (avec blocage si seuil atteint).
 *
 * @param string $message Message affiché si le compte n'est pas encore bloqué
 * @return array Même forme que process_unified_login()
 */
function login_failure_result_array($message)
{
    login_attempt_register_failure();
    if (login_attempt_is_locked()) {
        return login_attempt_locked_result_array();
    }
    return [
        'success' => false,
        'message' => $message,
        'type' => null,
        'admin' => null,
        'user' => null,
        'vendeur_collaborateur' => null,
    ];
}

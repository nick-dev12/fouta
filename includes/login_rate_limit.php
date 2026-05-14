<?php
/**
 * Limitation des tentatives de connexion (session) : 5 échecs → blocage 10 minutes.
 * Utilisation : session déjà démarrée.
 */

if (!defined('LOGIN_RATE_MAX_ATTEMPTS')) {
    define('LOGIN_RATE_MAX_ATTEMPTS', 5);
}
if (!defined('LOGIN_RATE_LOCK_SECONDS')) {
    define('LOGIN_RATE_LOCK_SECONDS', 600);
}

/**
 * Réinitialise le compteur si la période de blocage est expirée.
 */
function login_attempt_unlock_if_expired() {
    if (empty($_SESSION['login_lock_until'])) {
        return;
    }
    if (time() >= (int) $_SESSION['login_lock_until']) {
        unset($_SESSION['login_lock_until'], $_SESSION['login_fail_count']);
    }
}

/**
 * Indique si la connexion est temporairement bloquée.
 */
function login_attempt_is_locked() {
    login_attempt_unlock_if_expired();
    if (empty($_SESSION['login_lock_until'])) {
        return false;
    }
    return time() < (int) $_SESSION['login_lock_until'];
}

/**
 * Secondes restantes avant déblocage (0 si non bloqué).
 */
function login_attempt_remaining_seconds() {
    login_attempt_unlock_if_expired();
    if (empty($_SESSION['login_lock_until'])) {
        return 0;
    }
    return max(0, (int) $_SESSION['login_lock_until'] - time());
}

/**
 * Après une connexion réussie.
 */
function login_attempt_clear() {
    unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
}

/**
 * Compte un échec d’authentification (identifiants incorrects, compte inactif côté identifiant fourni, etc.).
 */
function login_attempt_register_failure() {
    login_attempt_unlock_if_expired();
    if (login_attempt_is_locked()) {
        return;
    }
    $n = (int) ($_SESSION['login_fail_count'] ?? 0) + 1;
    $_SESSION['login_fail_count'] = $n;
    if ($n >= LOGIN_RATE_MAX_ATTEMPTS) {
        $_SESSION['login_lock_until'] = time() + LOGIN_RATE_LOCK_SECONDS;
        $_SESSION['login_fail_count'] = 0;
    }
}

/**
 * Message lisible pour le temps restant.
 */
function login_attempt_format_remaining($seconds) {
    $seconds = max(0, (int) $seconds);
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    if ($m <= 0) {
        return $s . ' s';
    }
    return $m . ' min ' . str_pad((string) $s, 2, '0', STR_PAD_LEFT) . ' s';
}

/**
 * Enregistre un échec et retourne le tableau « échec connexion » (avec blocage si seuil atteint).
 *
 * @param string $message Message affiché si le compte n’est pas encore bloqué
 * @return array Même forme que process_unified_login()
 */
function login_failure_result_array($message) {
    login_attempt_register_failure();
    if (login_attempt_is_locked()) {
        $rem = login_attempt_remaining_seconds();
        return [
            'success' => false,
            'message' => 'Trop de tentatives de connexion. Réessayez dans ' . login_attempt_format_remaining($rem) . '.',
            'type' => null,
            'admin' => null,
            'user' => null,
            'vendeur_collaborateur' => null,
            'rate_limited' => true,
            'remaining_seconds' => $rem,
        ];
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

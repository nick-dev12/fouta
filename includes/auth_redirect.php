<?php
/**
 * Redirections de connexion et cookie « portail » (vendeur / admin / client).
 * Programmation procédurale uniquement
 */

if (!function_exists('auth_portal_cookie_lifetime')) {
    function auth_portal_cookie_lifetime()
    {
        return 30 * 24 * 3600;
    }
}

if (!function_exists('auth_set_portal_cookie')) {
    /**
     * @param string $portal vendeur|admin|client|choix
     */
    function auth_set_portal_cookie($portal)
    {
        $portal = trim((string) $portal);
        if ($portal === '') {
            return;
        }

        $lifetime = auth_portal_cookie_lifetime();
        setcookie('colobane_auth_portal', $portal, [
            'expires' => time() + $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('auth_clear_portal_cookie')) {
    function auth_clear_portal_cookie()
    {
        setcookie('colobane_auth_portal', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('auth_redirect_after_login')) {
    /**
     * Redirection HTTP sûre après connexion (évite écran blanc / re-soumission formulaire).
     *
     * @param string $url URL absolue ou relative commençant par /
     */
    function auth_redirect_after_login($url)
    {
        $url = trim((string) $url);
        if ($url === '' || strpos($url, '//') !== false) {
            $url = '/index.php';
        }
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: ' . $url, true, 303);
        exit;
    }
}

if (!function_exists('auth_user_is_logged_in')) {
    function auth_user_is_logged_in()
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}

if (!function_exists('admin_login_redirect_url')) {
    /**
     * URL de connexion unifiée (choix-connexion.php).
     *
     * @param string|null $redirect_after
     */
    function admin_login_redirect_url($redirect_after = null)
    {
        if ($redirect_after === null || $redirect_after === '') {
            $redirect_after = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
        }

        $redirect_after = (string) $redirect_after;
        if ($redirect_after !== '' && $redirect_after[0] !== '/') {
            $redirect_after = '/' . ltrim($redirect_after, '/');
        }

        $qs = $redirect_after !== '' ? ('?redirect=' . urlencode($redirect_after)) : '';

        return '/choix-connexion.php' . $qs;
    }
}

if (!function_exists('admin_redirect_to_login')) {
    function admin_redirect_to_login()
    {
        header('Location: ' . admin_login_redirect_url());
        exit;
    }
}

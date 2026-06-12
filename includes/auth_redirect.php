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

if (!function_exists('auth_session_is_vendeur')) {
    /**
     * Session vendeur titulaire ou collaborateur boutique.
     */
    function auth_session_is_vendeur()
    {
        if (empty($_SESSION['admin_id']) || (int) $_SESSION['admin_id'] <= 0) {
            return false;
        }

        if (!empty($_SESSION['vendeur_collaborateur_id'])) {
            return true;
        }

        if (!function_exists('normalize_admin_role')) {
            $model_admin = __DIR__ . '/../models/model_admin.php';
            if (is_file($model_admin)) {
                require_once $model_admin;
            }
        }

        if (!function_exists('normalize_admin_role')) {
            return (string) ($_SESSION['admin_role'] ?? '') === 'vendeur';
        }

        return normalize_admin_role($_SESSION['admin_role'] ?? '') === 'vendeur';
    }
}

if (!function_exists('auth_vendeur_dashboard_url')) {
    function auth_vendeur_dashboard_url()
    {
        return '/admin/dashboard.php';
    }
}

if (!function_exists('auth_vendeur_may_browse_marketplace')) {
    /**
     * Le vendeur a choisi de visiter la marketplace publique (session explicite).
     */
    function auth_vendeur_may_browse_marketplace()
    {
        return !empty($_SESSION['vendeur_visite_marketplace']);
    }
}

if (!function_exists('auth_grant_vendeur_marketplace_visit')) {
    function auth_grant_vendeur_marketplace_visit()
    {
        $_SESSION['vendeur_visite_marketplace'] = true;
    }
}

if (!function_exists('auth_revoke_vendeur_marketplace_visit')) {
    function auth_revoke_vendeur_marketplace_visit()
    {
        unset($_SESSION['vendeur_visite_marketplace']);
    }
}

if (!function_exists('auth_marketplace_visit_url')) {
    /**
     * URL d’entrée marketplace pour un vendeur connecté (active la session de visite).
     */
    function auth_marketplace_visit_url($path = '/index.php')
    {
        $path = trim((string) $path);
        if ($path === '' || strpos($path, '//') !== false) {
            $path = '/index.php';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $sep = (strpos($path, '?') !== false) ? '&' : '?';

        return $path . $sep . 'visite_marketplace=1';
    }
}

if (!function_exists('auth_redirect_vendeur_to_dashboard')) {
    /**
     * Redirige un vendeur connecté vers son tableau de bord.
     */
    function auth_redirect_vendeur_to_dashboard()
    {
        if (!auth_session_is_vendeur()) {
            return;
        }

        if (auth_vendeur_may_browse_marketplace()) {
            return;
        }

        auth_redirect_after_login(auth_vendeur_dashboard_url());
    }
}

if (!function_exists('auth_login_redirect_url_for_admin')) {
    /**
     * URL de redirection après connexion admin (vendeur → tableau de bord).
     *
     * @param string|null $fallback
     */
    function auth_login_redirect_url_for_admin($fallback = null)
    {
        if (auth_session_is_vendeur()) {
            return auth_vendeur_dashboard_url();
        }

        $fallback = trim((string) ($fallback ?? '/admin/dashboard.php'));
        if ($fallback === '' || strpos($fallback, '//') !== false) {
            $fallback = '/admin/dashboard.php';
        }

        return $fallback[0] === '/' ? $fallback : '/' . $fallback;
    }
}

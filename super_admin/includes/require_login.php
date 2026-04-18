<?php
/**
 * Session super admin + garde d'accès + jeton CSRF
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/paths.php';
require_once dirname(__DIR__, 2) . '/controllers/controller_super_admin.php';

if (empty($_SESSION['super_admin_id'])) {
    header('Location: ' . super_admin_href('login.php'));
    exit;
}

super_admin_csrf_token();

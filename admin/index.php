<?php
/**
 * Page d'accueil du dossier admin
 * Redirige vers choix-connexion.php ou dashboard.php selon la session
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_admin.php';
require_once __DIR__ . '/../includes/auth_redirect.php';
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

header('Location: ' . admin_login_redirect_url('/admin/dashboard.php'));
exit;

?>
<?php
/**
 * Endpoint AJAX : connexion/inscription Google ou Apple via Firebase Auth.
 */
if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/includes/session_user.php';
session_start();

require_once __DIR__ . '/includes/firebase_auth_flow.php';

try {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw !== false ? $raw : '', true);
    firebase_auth_process_callback(is_array($payload) ? $payload : []);
} catch (Throwable $e) {
    error_log('[auth-firebase-callback] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    firebase_auth_json_response(
        false,
        'Erreur serveur lors de la connexion. Réessayez dans un instant.'
    );
}

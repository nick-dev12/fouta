<?php
/**
 * Endpoint AJAX : connexion/inscription Google ou Apple via Firebase Auth.
 */
session_start();
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/includes/firebase_auth_flow.php';

$payload = json_decode(file_get_contents('php://input'), true);
firebase_auth_process_callback(is_array($payload) ? $payload : []);

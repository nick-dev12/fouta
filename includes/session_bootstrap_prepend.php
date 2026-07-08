<?php
/**
 * Chargé automatiquement via .user.ini (auto_prepend_file).
 * Configure le cookie de session AVANT tout session_start() du site.
 */
if (session_status() !== PHP_SESSION_NONE) {
    return;
}

require_once __DIR__ . '/session_user.php';
session_configure_persistent();

<?php
/**
 * Point d'entrée vendeur → marketplace publique (évite page blanche / historique navigateur).
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/includes/session_user.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth_redirect.php';

if (!auth_session_is_vendeur()) {
    header('Location: /choix-connexion.php?redirect=' . rawurlencode('/visiter-marketplace.php'), true, 302);
    exit;
}

auth_grant_vendeur_marketplace_visit();

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Location: /index.php', true, 303);
exit;

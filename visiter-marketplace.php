<?php
/**
 * Point d'entrée vendeur → marketplace publique (rétrocompatibilité).
 * Préférer /index.php?vendeur_visite=1 (évite page blanche WebView / redirect sans corps).
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/includes/session_user.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth_redirect.php';

$target = '/index.php?vendeur_visite=1';

if (!auth_session_is_vendeur()) {
    header('Location: /choix-connexion.php?redirect=' . rawurlencode($target), true, 302);
    exit;
}

auth_grant_vendeur_marketplace_visit();

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

if (!headers_sent()) {
    header('Location: ' . $target, true, 303);
    exit;
}

$target_esc = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?php echo $target_esc; ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirection — COLObanes</title>
    <script>window.location.replace(<?php echo json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);</script>
</head>
<body>
    <p>Redirection vers la marketplace… <a href="<?php echo $target_esc; ?>">Continuer</a></p>
</body>
</html>

<?php
/**
 * Callback Apple Sign-In (Android) — page légère pour le retour OAuth.
 * URL : https://colobanes.com/auth/apple-callback
 *
 * Le plugin sign_in_with_apple intercepte cette URL via Android App Links.
 * Cette page confirme le retour si l'app ne s'ouvre pas automatiquement.
 */
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion Apple — COLObanes</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 2rem; color: #3564a6; }
    </style>
</head>
<body>
    <p>Retour connexion Apple…</p>
    <p><small>Vous pouvez fermer cette page si l'application COLObanes s'est ouverte.</small></p>
</body>
</html>

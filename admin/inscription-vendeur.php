<?php
/**
 * Inscription vendeur — création boutique (sans session admin requise).
 */
session_start();

require_once __DIR__ . '/../controllers/controller_admin.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$result = process_inscription_vendeur();
if (!empty($result['success'])) {
    $_SESSION['inscription_success'] = $result['message'];
    header('Location: login.php');
    exit;
}

$err = (!empty($result['message']) && empty($result['success'])) ? $result['message'] : '';

require_once __DIR__ . '/../includes/asset_version.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ouvrir ma boutique — inscription vendeur</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <style>
        body { font-family: var(--font-corps); background: var(--fond-page); margin: 0; min-height: 100vh; padding: 24px; }
        .wrap { max-width: 480px; margin: 0 auto; background: var(--glass-bg, #fff); border: 1px solid var(--glass-border, #e0dcd0); border-radius: 16px; padding: 28px; box-shadow: var(--glass-shadow, 0 4px 24px rgba(0,0,0,.06)); }
        h1 { color: var(--titres); font-size: 1.4rem; margin: 0 0 8px; }
        .sub { color: var(--texte-fonce); font-size: .9rem; margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin: 14px 0 6px; color: var(--titres); font-size: .9rem; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; }
        .err { background: #fdecea; border-left: 4px solid #c26638; padding: 12px; border-radius: 8px; margin-bottom: 16px; color: #333; font-size: .9rem; }
        button { width: 100%; margin-top: 22px; padding: 14px; background: #918a44; color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { opacity: .95; }
        .links { margin-top: 18px; text-align: center; font-size: .9rem; }
        .links a { color: #c26638; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Créer ma boutique</h1>
        <p class="sub">Identité, téléphone obligatoire, code PIN à 6 chiffres, nom de la boutique. L’URL de partage sera générée automatiquement.</p>
        <?php if ($err): ?>
            <div class="err"><?php echo $err; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <label for="identite">Identité (nom affiché) *</label>
            <input type="text" id="identite" name="identite" required maxlength="200" value="<?php echo isset($_POST['identite']) ? htmlspecialchars($_POST['identite']) : ''; ?>">

            <label for="boutique_nom">Nom de la boutique *</label>
            <input type="text" id="boutique_nom" name="boutique_nom" required maxlength="255" value="<?php echo isset($_POST['boutique_nom']) ? htmlspecialchars($_POST['boutique_nom']) : ''; ?>">

            <label for="telephone">Téléphone (connexion) *</label>
            <input type="text" id="telephone" name="telephone" required autocomplete="tel" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">

            <label for="email">Email (optionnel)</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

            <label for="pin">Code PIN (6 chiffres) *</label>
            <input type="password" id="pin" name="pin" inputmode="numeric" pattern="[0-9]{6}" required maxlength="6" autocomplete="new-password">

            <label for="pin_confirm">Confirmer le PIN *</label>
            <input type="password" id="pin_confirm" name="pin_confirm" inputmode="numeric" pattern="[0-9]{6}" required maxlength="6" autocomplete="new-password">

            <button type="submit">Créer mon compte vendeur</button>
        </form>
        <p class="links"><a href="login.php">Déjà inscrit ? Connexion</a> · <a href="/choix-connexion.php">Autres connexions</a></p>
    </div>
</body>
</html>

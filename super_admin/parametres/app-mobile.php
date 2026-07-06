<?php
/**
 * Mise à jour obligatoire — apps mobiles Android / iOS.
 */
require_once __DIR__ . '/../includes/require_login.php';

if (ob_get_level() === 0) {
    ob_start();
}
require_once dirname(__DIR__, 2) . '/includes/flash_toast.php';
require_once dirname(__DIR__, 2) . '/includes/app_mobile_version.php';
require_once dirname(__DIR__, 2) . '/models/model_super_admin.php';
require_once dirname(__DIR__, 2) . '/controllers/controller_super_admin.php';

$sa_id = (int) ($_SESSION['super_admin_id'] ?? 0);
$flash_ok = '';
$flash_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf_token'] ?? '';
    if (!super_admin_csrf_valid($tok)) {
        $flash_err = 'Jeton de sécurité invalide.';
    } elseif (!isset($_POST['save_app_mobile'])) {
        $flash_err = 'Action non reconnue.';
    } elseif (app_mobile_version_save($_POST)) {
        super_admin_log_action($sa_id, 'app_mobile_version_modifie', 'app_mobile_version', 0, 'config');
        http_redirect_safe('/super_admin/parametres/app-mobile.php?ok=1');
    } else {
        $flash_err = 'Enregistrement impossible. Vérifiez les champs obligatoires.';
    }
}

if (isset($_GET['ok'])) {
    $flash_ok = 'Configuration enregistrée.';
}

$cfg = app_mobile_version_load();
$csrf = super_admin_csrf_token();
$config_path = app_mobile_version_config_path();
$config_writable = is_writable($config_path) || (!is_file($config_path) && is_writable(dirname($config_path)));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include dirname(__DIR__, 2) . '/includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App mobile — Super Admin</title>
    <?php require_once dirname(__DIR__, 2) . '/includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-clients.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-parametres.css<?php echo asset_version_query(); ?>">
</head>

<body class="page-users admin-clients-page sa-users-page sa-param-hub-page sa-cat-page">
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <div class="sa-users-shell sa-param-shell sa-cat-shell">
        <a class="sa-cat-back" href="index.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Paramètres</a>

        <header class="sa-param-hero" aria-labelledby="sa-app-mobile-title">
            <div class="sa-param-hero__grid">
                <div>
                    <nav class="sa-param-breadcrumb" aria-label="Fil d’Ariane">
                        <ol>
                            <li><a href="../dashboard.php">Tableau de bord</a></li>
                            <li class="sa-param-breadcrumb__sep" aria-hidden="true"><i class="fas fa-chevron-right"></i></li>
                            <li><a href="index.php">Paramètres</a></li>
                            <li class="sa-param-breadcrumb__sep" aria-hidden="true"><i class="fas fa-chevron-right"></i></li>
                            <li aria-current="page">App mobile</li>
                        </ol>
                    </nav>
                    <h1 id="sa-app-mobile-title" class="sa-param-hero__title">Mise à jour app mobile</h1>
                    <p class="sa-param-hero__lead">
                        Définissez la version minimale requise sur Android et iOS. Si l’application installée est
                        inférieure au <strong>build minimum</strong>, un écran bloquant s’affiche avec le lien vers le store.
                    </p>
                </div>
            </div>
        </header>

        <?php if ($flash_ok !== ''): ?>
            <div class="sa-flash sa-flash--ok" role="status"><?php echo htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($flash_err !== ''): ?>
            <div class="sa-flash sa-flash--err" role="alert"><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!$config_writable): ?>
            <div class="sa-flash sa-flash--err" role="alert">
                Le fichier de configuration n’est pas accessible en écriture :
                <code><?php echo htmlspecialchars($config_path, ENT_QUOTES, 'UTF-8'); ?></code>
            </div>
        <?php endif; ?>

        <section class="sa-cat-panel" aria-labelledby="sa-app-mobile-form-title">
            <h2 id="sa-app-mobile-form-title" class="sa-cat-panel__title">Paramètres de version</h2>

            <form method="post" class="sa-cat-form" action="app-mobile.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="save_app_mobile" value="1">

                <div class="sa-cat-form__row">
                    <label class="sa-cat-form__check">
                        <input type="checkbox" name="force_update" value="1" <?php echo !empty($cfg['force_update']) ? 'checked' : ''; ?>>
                        Activer la mise à jour obligatoire (popup bloquante)
                    </label>
                    <p class="sa-cat-form__hint">
                        Si désactivé, aucun blocage même si le build installé est inférieur au minimum.
                    </p>
                </div>

                <div class="sa-cat-form__grid sa-cat-form__grid--2">
                    <fieldset class="sa-cat-form__fieldset">
                        <legend><i class="fab fa-android" aria-hidden="true"></i> Android</legend>
                        <div class="sa-cat-form__row">
                            <label for="android_min_build">Build minimum (versionCode)</label>
                            <input type="number" min="0" step="1" id="android_min_build" name="android_min_build"
                                value="<?php echo (int) ($cfg['android']['min_build'] ?? 0); ?>" required>
                            <p class="sa-cat-form__hint">Ex. : 12 pour la version publiée 1.3.2+12</p>
                        </div>
                        <div class="sa-cat-form__row">
                            <label for="android_min_version">Version affichée (informatif)</label>
                            <input type="text" id="android_min_version" name="android_min_version"
                                value="<?php echo htmlspecialchars((string) ($cfg['android']['min_version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </fieldset>

                    <fieldset class="sa-cat-form__fieldset">
                        <legend><i class="fab fa-apple" aria-hidden="true"></i> iOS</legend>
                        <div class="sa-cat-form__row">
                            <label for="ios_min_build">Build minimum (CFBundleVersion)</label>
                            <input type="number" min="0" step="1" id="ios_min_build" name="ios_min_build"
                                value="<?php echo (int) ($cfg['ios']['min_build'] ?? 0); ?>" required>
                        </div>
                        <div class="sa-cat-form__row">
                            <label for="ios_min_version">Version affichée (informatif)</label>
                            <input type="text" id="ios_min_version" name="ios_min_version"
                                value="<?php echo htmlspecialchars((string) ($cfg['ios']['min_version'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </fieldset>
                </div>

                <div class="sa-cat-form__row">
                    <label for="store_android">Lien Google Play</label>
                    <input type="url" id="store_android" name="store_android" required
                        value="<?php echo htmlspecialchars((string) ($cfg['store_android'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="sa-cat-form__row">
                    <label for="store_ios">Lien App Store</label>
                    <input type="url" id="store_ios" name="store_ios" required
                        value="<?php echo htmlspecialchars((string) ($cfg['store_ios'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="sa-cat-form__row">
                    <label for="title">Titre du popup</label>
                    <input type="text" id="title" name="title" required maxlength="120"
                        value="<?php echo htmlspecialchars((string) ($cfg['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="sa-cat-form__row">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="4" required maxlength="500"><?php echo htmlspecialchars((string) ($cfg['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="sa-cat-form__actions">
                    <button type="submit" class="sa-btn sa-btn--primary" <?php echo $config_writable ? '' : 'disabled'; ?>>
                        <i class="fas fa-save" aria-hidden="true"></i> Enregistrer
                    </button>
                </div>
            </form>
        </section>

        <section class="sa-cat-panel" aria-labelledby="sa-app-mobile-api-title">
            <h2 id="sa-app-mobile-api-title" class="sa-cat-panel__title">API &amp; procédure</h2>
            <p>Endpoint public : <code>/api/app_version.php?platform=android&amp;build=12</code></p>
            <p class="sa-cat-form__hint">
                Après chaque publication sur les stores, augmentez le build minimum correspondant
                (identique au numéro après le <code>+</code> dans <code>pubspec.yaml</code>, ex. <code>1.3.2+13</code> → build 13).
            </p>
        </section>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>

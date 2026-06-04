<?php
/**
 * Complément des informations manquantes après authentification Google/Apple.
 */
session_start();

if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/includes/asset_version.php';
require_once __DIR__ . '/includes/senegal_regions.php';
require_once __DIR__ . '/includes/marketplace_helpers.php';
require_once __DIR__ . '/includes/firebase_auth_flow.php';
require_once __DIR__ . '/includes/auth_redirect.php';
require_once __DIR__ . '/includes/flash_toast.php';
require_once __DIR__ . '/models/model_users.php';
require_once __DIR__ . '/models/model_admin.php';

$pending = firebase_auth_get_pending();

if (!$pending || empty($pending['uid']) || empty($pending['email'])) {
    firebase_auth_redirect_safe('/choix-connexion.php');
}

$auth_provider = (isset($pending['provider']) && trim((string) $pending['provider']) === 'apple') ? 'apple' : 'google';
$provider_label = firebase_auth_pending_provider_label($pending);

$type_get = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
$type_pending = isset($pending['type']) ? trim((string) $pending['type']) : '';
$type = ($type_get === 'vendor' || $type_pending === 'vendor') ? 'vendor' : (($type_get === 'client' || $type_pending === 'client') ? 'client' : '');

if ($type !== 'client' && $type !== 'vendor') {
    firebase_auth_redirect_safe('/auth-google-choose-type.php');
}

$_SESSION['firebase_auth_pending']['type'] = $type;
$_SESSION['google_auth_pending']['type'] = $type;
$errors = [];

function google_complete_safe_redirect($redirect)
{
    $redirect = trim((string) $redirect);
    if ($redirect === '' || strpos($redirect, '//') !== false) {
        return '/index.php';
    }
    return $redirect[0] === '/' ? $redirect : '/' . $redirect;
}

function google_complete_set_user_session(array $user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_prenom'] = $user['prenom'];
    $_SESSION['user_email'] = (string) ($user['email'] ?? '');
    $_SESSION['user_telephone'] = $user['telephone'];
    $_SESSION['user_statut'] = $user['statut'];
    auth_set_portal_cookie('client');
    if (file_exists(__DIR__ . '/includes/panier_invite.php')) {
        try {
            require_once __DIR__ . '/includes/panier_invite.php';
            panier_fusionner_invite_apres_connexion((int) $user['id']);
        } catch (Throwable $e) {
            error_log('[auth-google-complete] fusion panier : ' . $e->getMessage());
        }
    }
}

function google_complete_set_admin_session(array $admin)
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_nom'] = $admin['nom'];
    $_SESSION['admin_prenom'] = $admin['prenom'];
    $_SESSION['admin_email'] = $admin['email'] ?? '';
    $_SESSION['admin_statut'] = $admin['statut'];
    $_SESSION['admin_role'] = normalize_admin_role($admin['role'] ?? 'admin');
    $_SESSION['admin_boutique_nom'] = trim((string) ($admin['boutique_nom'] ?? ''));
    $_SESSION['admin_boutique_slug'] = trim((string) ($admin['boutique_slug'] ?? ''));
    unset($_SESSION['vendeur_collaborateur_id'], $_SESSION['vendeur_collaborateur_nom']);
    $role = normalize_admin_role($admin['role'] ?? 'admin');
    auth_set_portal_cookie($role === 'vendeur' ? 'vendeur' : 'admin');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telephone = isset($_POST['telephone']) ? trim((string) $_POST['telephone']) : '';
    $telephone_digits = users_normalize_phone_digits($telephone);

    if ($telephone_digits === '' || strlen($telephone_digits) < 8) {
        $errors[] = 'Le numéro de téléphone est obligatoire.';
    }

    if ($type === 'vendor') {
        $identite = isset($_POST['identite']) ? trim((string) $_POST['identite']) : '';
        $boutique_nom = isset($_POST['boutique_nom']) ? trim((string) $_POST['boutique_nom']) : '';
        $boutique_region = isset($_POST['boutique_region']) ? trim((string) $_POST['boutique_region']) : '';

        if (mb_strlen($identite) < 2) {
            $errors[] = 'L’identité est obligatoire.';
        }
        if (mb_strlen($boutique_nom) < 2) {
            $errors[] = 'Le nom de la boutique est obligatoire.';
        }
        if ($boutique_region === '' || !senegal_region_is_valid($boutique_region)) {
            $errors[] = 'Veuillez sélectionner la région de votre boutique.';
        }
        if (admin_telephone_exists($telephone_digits)) {
            $errors[] = 'Ce numéro de téléphone est déjà enregistré.';
        }
        if (admin_email_exists($pending['email'])) {
            $errors[] = 'Cet email est déjà utilisé par une boutique.';
        }

        $slug = marketplace_slugify($boutique_nom);
        $base_slug = $slug;
        $n = 0;
        while ($slug !== '' && admin_boutique_slug_exists($slug)) {
            $n++;
            $slug = $base_slug . '-' . $n;
            if ($n > 200) {
                $errors[] = 'Impossible de générer une URL boutique unique. Modifiez le nom.';
                break;
            }
        }

        if (empty($errors)) {
            $admin_id = create_google_vendeur_boutique(
                $identite,
                $pending['email'],
                $telephone_digits,
                $boutique_nom,
                $slug,
                $boutique_region,
                $pending['uid'],
                $auth_provider
            );
            if ($admin_id) {
                $admin = get_admin_by_id((int) $admin_id);
                google_complete_set_admin_session($admin);
                unset($_SESSION['firebase_auth_pending'], $_SESSION['google_auth_pending']);
                firebase_auth_redirect_safe('/admin/dashboard.php');
            }
            $errors[] = 'Erreur lors de la création de la boutique. Réessayez.';
        }
    } else {
        $nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
        $accepte_conditions = isset($_POST['accepte_conditions']) && $_POST['accepte_conditions'] === '1';

        if (mb_strlen($nom) < 2) {
            $errors[] = 'Le nom est obligatoire.';
        }
        if (!$accepte_conditions) {
            $errors[] = 'Vous devez accepter les conditions d’utilisation.';
        }
        if (get_user_by_telephone($telephone_digits)) {
            $errors[] = 'Ce numéro de téléphone est déjà enregistré.';
        }
        if (user_email_exists($pending['email'])) {
            $errors[] = 'Cet email est déjà utilisé.';
        }

        if (empty($errors)) {
            $creation_error = null;
            $user_id = create_google_user($nom, '', $pending['email'], $telephone_digits, $pending['uid'], $creation_error, $auth_provider);
            if ($user_id) {
                update_user_accepte_conditions((int) $user_id, true);
                $user = get_user_by_id((int) $user_id);
                google_complete_set_user_session($user);
                $redirect = google_complete_safe_redirect($pending['redirect'] ?? '/index.php');
                unset($_SESSION['firebase_auth_pending'], $_SESSION['google_auth_pending']);
                firebase_auth_redirect_safe($redirect);
            }
            $errors[] = $creation_error ?: 'Erreur lors de la création du compte. Réessayez.';
        }
    }
}

$default_name = trim((string) ($pending['name'] ?? ''));
$page_title = $type === 'vendor' ? 'Compléter ma boutique' : 'Compléter mon compte';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - COLObanes</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/auth-connexion.css<?php echo asset_version_query(); ?>">
    <?php include __DIR__ . '/includes/auth_intl_tel_head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page <?php echo $type === 'vendor' ? 'auth-page--vendor' : 'auth-page--email'; ?>">
    <header class="auth-header">
        <a class="logo" href="/index.php">
            <img src="/image/logo_market.png" alt="COLObanes">
        </a>
    </header>

    <div class="auth-layout">
        <main class="auth-main">
            <div class="auth-card">
                <div class="auth-card__inner">
                    <div class="auth-card__head">
                        <div class="auth-card__icon" aria-hidden="true">
                            <i class="fas <?php echo $type === 'vendor' ? 'fa-store' : 'fa-user-check'; ?>"></i>
                        </div>
                        <h1><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="auth-card__lead">
                            <?php echo htmlspecialchars($provider_label, ENT_QUOTES, 'UTF-8'); ?> a confirmé votre email : <strong><?php echo htmlspecialchars($pending['email'], ENT_QUOTES, 'UTF-8'); ?></strong>.
                            Renseignez seulement les informations manquantes.
                        </p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="auth-google-complete.php?type=<?php echo urlencode($type); ?>" class="auth-inscription-form">
                        <?php if ($type === 'vendor'): ?>
                            <div class="form-group">
                                <label for="identite"><i class="fas fa-id-card"></i> Identité (nom affiché) *</label>
                                <div class="input-wrapper">
                                    <input type="text" id="identite" name="identite" required maxlength="200"
                                        value="<?php echo htmlspecialchars($_POST['identite'] ?? $default_name, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="boutique_nom"><i class="fas fa-shop"></i> Nom de la boutique *</label>
                                <div class="input-wrapper">
                                    <input type="text" id="boutique_nom" name="boutique_nom" required maxlength="255"
                                        value="<?php echo htmlspecialchars($_POST['boutique_nom'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-store" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="boutique_region"><i class="fas fa-map-marker-alt"></i> Région de la boutique *</label>
                                <div class="input-wrapper">
                                    <select id="boutique_region" name="boutique_region" required class="auth-select">
                                        <?php echo senegal_regions_options_html($_POST['boutique_region'] ?? '', true, 'Sélectionnez une région'); ?>
                                    </select>
                                    <i class="fas fa-location-dot" aria-hidden="true"></i>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label for="nom"><i class="fas fa-user"></i> Nom complet *</label>
                                <div class="input-wrapper">
                                    <input type="text" id="nom" name="nom" required autocomplete="name"
                                        value="<?php echo htmlspecialchars($_POST['nom'] ?? $default_name, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="telephone"><i class="fas fa-phone"></i> Téléphone *</label>
                            <div class="input-wrapper input-wrapper--intl-tel">
                                <input type="tel" id="telephone" name="telephone" required autocomplete="tel"
                                    value="<?php echo htmlspecialchars($_POST['telephone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <?php if ($type !== 'vendor'): ?>
                            <div class="checkbox-group">
                                <input type="checkbox" id="accepte_conditions" name="accepte_conditions" value="1"
                                    <?php echo isset($_POST['accepte_conditions']) ? 'checked' : ''; ?>>
                                <label for="accepte_conditions">
                                    J'accepte les <a href="/conditions-utilisation.php" target="_blank" rel="noopener noreferrer">conditions d'utilisation</a>
                                </label>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Terminer
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/includes/auth_intl_tel_scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initAuthIntlTel === 'function') {
                window.initAuthIntlTel('telephone');
            }
        });
    </script>
</body>
</html>

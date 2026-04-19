<?php
/**
 * Paramètres vitrine vendeur : logo, couleurs, adresse (contact / pied de page)
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/require_access.php';
require_once __DIR__ . '/../models/model_admin.php';
require_once __DIR__ . '/../includes/boutique_vendeur_display.php';
require_once __DIR__ . '/../includes/upload_image_limits.php';

$role = admin_normalize_role_for_route($_SESSION['admin_role'] ?? '');
if ($role !== 'vendeur') {
    header('Location: parametres.php');
    exit;
}

$admin_id = (int) $_SESSION['admin_id'];
$admin = get_admin_by_id($admin_id);
if (!$admin || ($admin['role'] ?? '') !== 'vendeur') {
    header('Location: parametres.php');
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = (string) $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['boutique_branding_save'])) {
    $tok = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['admin_csrf'] ?? ''), $tok)) {
        $error_message = 'Session expirée. Veuillez recharger la page.';
    } else {
        $raw_c1 = trim((string) ($_POST['couleur_principale'] ?? ''));
        $raw_c2 = trim((string) ($_POST['couleur_accent'] ?? ''));
        if ($raw_c1 !== '' && boutique_normalize_hex_color($raw_c1) === '') {
            $error_message = 'Couleur principale invalide (format #RRGGBB).';
        } elseif ($raw_c2 !== '' && boutique_normalize_hex_color($raw_c2) === '') {
            $error_message = 'Couleur d’accent invalide (format #RRGGBB).';
        } else {
            $c1 = $raw_c1 !== '' ? boutique_normalize_hex_color($raw_c1) : '';
            $c2 = $raw_c2 !== '' ? boutique_normalize_hex_color($raw_c2) : '';
            $adresse = isset($_POST['boutique_adresse']) ? trim((string) $_POST['boutique_adresse']) : '';

            $current_logo = trim((string) ($admin['boutique_logo'] ?? ''));
            $logo_final = $current_logo;
            $remove_logo = !empty($_POST['retirer_logo']);

            if ($remove_logo) {
                $logo_final = '';
                if ($current_logo !== '') {
                    $old = __DIR__ . '/../upload/' . str_replace('\\', '/', $current_logo);
                    if (is_file($old)) {
                        @unlink($old);
                    }
                }
            } elseif (isset($_FILES['boutique_logo']) && (int) ($_FILES['boutique_logo']['error'] ?? 0) === UPLOAD_ERR_OK) {
                $f = $_FILES['boutique_logo'];
                $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $mime = (string) ($f['type'] ?? '');
                if (!in_array($mime, $allowed, true)) {
                    $error_message = 'Logo : formats acceptés JPEG, PNG, GIF, WebP.';
                } elseif ((int) ($f['size'] ?? 0) > UPLOAD_MAX_IMAGE_BYTES) {
                    $error_message = 'Logo trop volumineux (maximum 20 Mo).';
                } else {
                    $ext = strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                        $ext = 'jpg';
                    }
                    $dir = __DIR__ . '/../upload/boutique_branding/';
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    $fname = 'v_' . $admin_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest = $dir . $fname;
                    if (move_uploaded_file($f['tmp_name'], $dest)) {
                        if ($current_logo !== '') {
                            $old = __DIR__ . '/../upload/' . str_replace('\\', '/', $current_logo);
                            if (is_file($old)) {
                                @unlink($old);
                            }
                        }
                        $logo_final = 'boutique_branding/' . $fname;
                    } else {
                        $error_message = 'Impossible d’enregistrer le fichier logo.';
                    }
                }
            }

            if ($error_message === '') {
                $ok = update_admin_boutique_branding($admin_id, [
                    'boutique_logo' => $logo_final !== '' ? $logo_final : null,
                    'boutique_couleur_principale' => $c1 !== '' ? $c1 : null,
                    'boutique_couleur_accent' => $c2 !== '' ? $c2 : null,
                    'boutique_adresse' => $adresse !== '' ? $adresse : null,
                ]);
                if ($ok) {
                    $_SESSION['success_message'] = 'L’apparence de votre boutique a été enregistrée.';
                    header('Location: parametres-boutique-vendeur.php');
                    exit;
                }
                $error_message = 'Enregistrement impossible. Vérifiez que la base de données contient les colonnes vitrine (migration alter_admin_boutique_branding.sql).';
            }
        }
    }
    if ($error_message !== '') {
        $admin = get_admin_by_id($admin_id);
    }
}

$logo_url = '';
$clogo = trim((string) ($admin['boutique_logo'] ?? ''));
if ($clogo !== '') {
    $logo_url = '/upload/' . htmlspecialchars(str_replace('\\', '/', $clogo), ENT_QUOTES, 'UTF-8');
}
$c1_val = htmlspecialchars(boutique_normalize_hex_color($admin['boutique_couleur_principale'] ?? '') ?: '#3564a6', ENT_QUOTES, 'UTF-8');
$c2_val = htmlspecialchars(boutique_normalize_hex_color($admin['boutique_couleur_accent'] ?? '') ?: '#ff6b35', ENT_QUOTES, 'UTF-8');
$adresse_val = htmlspecialchars((string) ($admin['boutique_adresse'] ?? ''), ENT_QUOTES, 'UTF-8');
$logo_url_attr = $logo_url !== '' ? htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') : '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apparence de ma boutique - Administration</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        /* Page branding vitrine — scope BRV */
        .brv-page {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 24px 56px;
            box-sizing: border-box;
        }
        .brv-alerts {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }
        .brv-alerts .message {
            border-radius: 12px;
            padding: 14px 18px;
            font-weight: 500;
        }
        .brv-intro-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 28px;
            padding: 18px 20px;
            background: linear-gradient(135deg, rgba(53, 100, 166, 0.07), rgba(255, 107, 53, 0.06));
            border: 1px solid rgba(53, 100, 166, 0.14);
            border-radius: 16px;
            align-items: center;
        }
        .brv-intro-strip__icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--couleur-dominante), var(--bleu-fonce));
            color: var(--texte-clair);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(53, 100, 166, 0.28);
        }
        .brv-intro-strip__text {
            flex: 1 1 220px;
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.55;
            color: var(--gris-fonce);
        }
        .brv-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            align-items: start;
        }
        @media (max-width: 860px) {
            .brv-form-grid {
                grid-template-columns: 1fr;
            }
        }
        .brv-card {
            background: var(--blanc, #fff);
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 16px;
            padding: 22px 24px 24px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s ease;
        }
        .brv-card:hover {
            box-shadow: 0 8px 32px rgba(53, 100, 166, 0.08);
        }
        .brv-card--wide {
            grid-column: 1 / -1;
        }
        .brv-card__head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--blanc-neige, #f0f0f0);
        }
        .brv-card__head i {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(53, 100, 166, 0.1);
            color: var(--couleur-dominante);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .brv-card__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--titres, #1a1a2e);
            letter-spacing: -0.02em;
        }
        .brv-card__hint {
            margin: 4px 0 0;
            font-size: 0.85rem;
            color: var(--gris-moyen, #737373);
            line-height: 1.45;
        }
        .brv-logo-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .brv-logo-frame {
            width: 100%;
            max-width: 280px;
            min-height: 120px;
            padding: 20px;
            border-radius: 14px;
            background: linear-gradient(145deg, #fafbfc, #f0f3f8);
            border: 2px dashed rgba(53, 100, 166, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-sizing: border-box;
        }
        .brv-logo-frame.is-preview-new {
            border-style: solid;
            border-color: rgba(53, 100, 166, 0.45);
            background: linear-gradient(145deg, rgba(53, 100, 166, 0.06), #fff);
        }
        .brv-logo-frame.is-remove-pending {
            opacity: 0.55;
            filter: grayscale(0.3);
        }
        .brv-logo-frame img {
            max-width: 100%;
            max-height: 100px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .brv-logo-placeholder {
            text-align: center;
            color: var(--gris-moyen);
            font-size: 0.88rem;
            padding: 8px;
        }
        .brv-logo-placeholder i {
            font-size: 2rem;
            opacity: 0.35;
            display: block;
            margin-bottom: 8px;
        }
        .brv-logo-caption {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--gris-moyen);
            margin-top: 8px;
            text-align: center;
        }
        .brv-logo-caption--preview {
            color: var(--couleur-dominante);
        }
        .brv-file-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }
        .brv-file-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            background: var(--blanc);
            border: 2px solid rgba(53, 100, 166, 0.28);
            color: var(--couleur-dominante);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, transform 0.15s;
        }
        .brv-file-label:hover {
            background: rgba(53, 100, 166, 0.08);
            border-color: var(--couleur-dominante);
            transform: translateY(-1px);
        }
        .brv-remove-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 14px;
            background: rgba(255, 107, 53, 0.08);
            border-radius: 12px;
            border: 1px solid rgba(255, 107, 53, 0.2);
            font-size: 0.88rem;
            color: var(--gris-fonce);
            cursor: pointer;
        }
        .brv-remove-logo input {
            width: 18px;
            height: 18px;
            accent-color: var(--orange, #ff6b35);
        }
        .brv-color-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 480px) {
            .brv-color-grid {
                grid-template-columns: 1fr;
            }
        }
        .brv-color-field {
            padding: 14px;
            border-radius: 12px;
            background: var(--blanc-casse, #fafafa);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        .brv-color-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gris-fonce);
            margin-bottom: 10px;
        }
        .brv-color-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brv-color-row input[type="color"] {
            width: 52px;
            height: 52px;
            padding: 0;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }
        .brv-color-hex {
            font-family: ui-monospace, monospace;
            font-size: 0.88rem;
            color: var(--gris-fonce);
            font-weight: 600;
        }
        .brv-textarea {
            width: 100%;
            min-height: 110px;
            padding: 14px 16px;
            border: 2px solid rgba(53, 100, 166, 0.15);
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            line-height: 1.5;
            resize: vertical;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .brv-textarea:focus {
            outline: none;
            border-color: var(--couleur-dominante);
            box-shadow: 0 0 0 3px rgba(53, 100, 166, 0.12);
        }
        .brv-actions {
            margin-top: 28px;
            padding: 20px 24px;
            border-radius: 16px;
            background: var(--blanc);
            border: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.03);
        }
        .brv-actions__hint {
            margin: 0;
            font-size: 0.88rem;
            color: var(--gris-moyen);
            max-width: 420px;
            line-height: 1.45;
        }
        .brv-actions__btn {
            min-width: 200px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<header class="dashboard-page-header">
    <div class="dashboard-page-header__intro">
        <p class="dashboard-page-header__eyebrow">Vitrine publique</p>
        <h1 class="dashboard-page-header__title">
            <i class="fas fa-palette" aria-hidden="true"></i>
            <span>Apparence de ma boutique</span>
        </h1>
        <p class="dashboard-page-header__lead">
            Personnalisez l’identité visuelle de votre vitrine : le logo apparaît dans la navigation, les couleurs sur les boutons et accents, l’adresse sur la page contact et le pied de page.
        </p>
    </div>
    <div class="dashboard-page-header__toolbar" role="group">
        <a href="parametres.php" class="dash-tool-btn dash-tool-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span>Retour aux paramètres</span>
        </a>
    </div>
</header>

<div class="brv-page">
    <?php if ($success_message !== '' || $error_message !== ''): ?>
    <div class="brv-alerts">
        <?php if ($success_message !== ''): ?>
            <div class="message success" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message !== ''): ?>
            <div class="message error" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="brv-intro-strip">
        <div class="brv-intro-strip__icon" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></div>
        <p class="brv-intro-strip__text">
            Les visiteurs voient ces réglages sur <strong>votre URL boutique</strong> uniquement. Formats logo&nbsp;: JPEG, PNG, GIF ou WebP — taille max. <strong>20&nbsp;Mo</strong>. Pensez à un logo horizontal ou carré lisible en petit.
        </p>
    </div>

    <form method="post" enctype="multipart/form-data" action="" id="brv-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf']); ?>">
        <input type="hidden" name="boutique_branding_save" value="1">

        <div class="brv-form-grid">
            <section class="brv-card" aria-labelledby="brv-logo-title">
                <div class="brv-card__head">
                    <i class="fas fa-image" aria-hidden="true"></i>
                    <div>
                        <h2 id="brv-logo-title" class="brv-card__title">Logo de la boutique</h2>
                        <p class="brv-card__hint">Aperçu en direct du fichier choisi avant enregistrement.</p>
                    </div>
                </div>

                <div class="brv-logo-stage">
                    <div class="brv-logo-frame" id="brv-logo-frame" data-current-src="<?php echo $logo_url_attr; ?>">
                        <?php if ($logo_url !== ''): ?>
                            <img src="<?php echo $logo_url; ?>" alt="Logo de la boutique" id="brv-preview-img">
                        <?php else: ?>
                            <div class="brv-logo-placeholder" id="brv-logo-placeholder">
                                <i class="fas fa-image" aria-hidden="true"></i>
                                Aucun logo — le logo marketplace sera affiché.
                            </div>
                            <img src="" alt="" id="brv-preview-img" hidden>
                        <?php endif; ?>
                    </div>
                    <p class="brv-logo-caption" id="brv-logo-caption"><?php echo $logo_url !== '' ? 'Logo actuel' : 'Aperçu'; ?></p>
                </div>

                <div class="brv-file-row">
                    <input type="file" id="boutique_logo" name="boutique_logo" class="visually-hidden" accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="brv-logo-title">
                    <label for="boutique_logo" class="brv-file-label">
                        <i class="fas fa-upload" aria-hidden="true"></i>
                        Choisir une image
                    </label>
                </div>

                <?php if ($logo_url !== ''): ?>
                <label class="brv-remove-logo">
                    <input type="checkbox" name="retirer_logo" value="1" id="retirer_logo">
                    <span>Retirer mon logo et utiliser le logo par défaut du site</span>
                </label>
                <?php endif; ?>
            </section>

            <section class="brv-card" aria-labelledby="brv-colors-title">
                <div class="brv-card__head">
                    <i class="fas fa-droplet" aria-hidden="true"></i>
                    <div>
                        <h2 id="brv-colors-title" class="brv-card__title">Couleurs du thème</h2>
                        <p class="brv-card__hint">Principale pour les boutons et liens, accent pour les mises en avant.</p>
                    </div>
                </div>
                <div class="brv-color-grid">
                    <div class="brv-color-field">
                        <label for="couleur_principale">Principale</label>
                        <div class="brv-color-row">
                            <input type="color" id="couleur_principale" name="couleur_principale" value="<?php echo $c1_val; ?>">
                            <span class="brv-color-hex" id="hex-principale"><?php echo $c1_val; ?></span>
                        </div>
                    </div>
                    <div class="brv-color-field">
                        <label for="couleur_accent">Accent</label>
                        <div class="brv-color-row">
                            <input type="color" id="couleur_accent" name="couleur_accent" value="<?php echo $c2_val; ?>">
                            <span class="brv-color-hex" id="hex-accent"><?php echo $c2_val; ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="brv-card brv-card--wide" aria-labelledby="brv-addr-title">
                <div class="brv-card__head">
                    <i class="fas fa-location-dot" aria-hidden="true"></i>
                    <div>
                        <h2 id="brv-addr-title" class="brv-card__title">Adresse affichée</h2>
                        <p class="brv-card__hint">Visible sur la page Contact et dans le pied de page. Téléphone et e-mail sont ceux de votre fiche administrateur (<a href="profil.php">profil</a>).</p>
                    </div>
                </div>
                <label for="boutique_adresse" class="visually-hidden">Adresse</label>
                <textarea id="boutique_adresse" class="brv-textarea" name="boutique_adresse" rows="4" placeholder="Ex. : 12 rue …, ville, pays"><?php echo $adresse_val; ?></textarea>
            </section>
        </div>

        <div class="brv-actions">
            <p class="brv-actions__hint">Enregistrez pour appliquer les changements sur votre vitrine publique. Le logo n’est remplacé qu’après validation du formulaire.</p>
            <button type="submit" class="dash-tool-btn dash-tool-btn--primary brv-actions__btn">
                <i class="fas fa-save" aria-hidden="true"></i>
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var fileInput = document.getElementById('boutique_logo');
    var frame = document.getElementById('brv-logo-frame');
    var img = document.getElementById('brv-preview-img');
    var caption = document.getElementById('brv-logo-caption');
    var placeholder = document.getElementById('brv-logo-placeholder');
    var removeCb = document.getElementById('retirer_logo');
    var c1 = document.getElementById('couleur_principale');
    var c2 = document.getElementById('couleur_accent');
    var h1 = document.getElementById('hex-principale');
    var h2 = document.getElementById('hex-accent');
    var currentSrc = frame ? frame.getAttribute('data-current-src') || '' : '';
    var objectUrl = null;

    function revokeObjectUrl() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    function showPlaceholder(show) {
        if (!placeholder) return;
        placeholder.style.display = show ? 'block' : 'none';
    }

    function applyRemovePending() {
        if (!frame || !removeCb) return;
        frame.classList.toggle('is-remove-pending', removeCb.checked);
    }

    function syncHex() {
        if (c1 && h1) h1.textContent = c1.value;
        if (c2 && h2) h2.textContent = c2.value;
    }

    if (fileInput && img && frame && caption) {
        fileInput.addEventListener('change', function () {
            revokeObjectUrl();
            if (removeCb) removeCb.checked = false;
            applyRemovePending();
            var f = fileInput.files && fileInput.files[0];
            if (!f) {
                if (currentSrc) {
                    img.removeAttribute('hidden');
                    img.src = currentSrc;
                    img.alt = 'Logo de la boutique';
                    caption.textContent = 'Logo actuel';
                    caption.classList.remove('brv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                } else {
                    img.removeAttribute('src');
                    img.setAttribute('hidden', 'hidden');
                    showPlaceholder(true);
                    caption.textContent = 'Aperçu';
                    caption.classList.remove('brv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                }
                return;
            }
            objectUrl = URL.createObjectURL(f);
            img.removeAttribute('hidden');
            img.src = objectUrl;
            img.alt = 'Aperçu du nouveau logo';
            showPlaceholder(false);
            caption.textContent = 'Aperçu — enregistrez pour appliquer';
            caption.classList.add('brv-logo-caption--preview');
            frame.classList.add('is-preview-new');
        });
    }

    if (removeCb && frame && img && caption) {
        removeCb.addEventListener('change', function () {
            applyRemovePending();
            if (removeCb.checked) {
                revokeObjectUrl();
                if (fileInput) fileInput.value = '';
                if (currentSrc) {
                    img.removeAttribute('hidden');
                    img.src = currentSrc;
                    img.alt = 'Logo actuel (sera retiré après enregistrement)';
                    showPlaceholder(false);
                    caption.textContent = 'Suppression prévue au prochain enregistrement';
                    caption.classList.add('brv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                } else {
                    img.removeAttribute('src');
                    img.setAttribute('hidden', 'hidden');
                    showPlaceholder(true);
                    caption.textContent = 'Aucun logo après enregistrement';
                    caption.classList.add('brv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                }
            } else {
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    fileInput.dispatchEvent(new Event('change'));
                } else if (currentSrc) {
                    img.removeAttribute('hidden');
                    img.src = currentSrc;
                    img.alt = 'Logo de la boutique';
                    showPlaceholder(false);
                    caption.textContent = 'Logo actuel';
                    caption.classList.remove('brv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                }
            }
        });
    }

    if (c1) c1.addEventListener('input', syncHex);
    if (c2) c2.addEventListener('input', syncHex);
    syncHex();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

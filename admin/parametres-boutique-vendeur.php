<?php
/**
 * Paramètres vitrine vendeur : logo, couleurs, adresse — redesign v2
 */
require_once __DIR__ . '/includes/require_admin_session.php';



require_once __DIR__ . '/includes/require_access.php';
require_once __DIR__ . '/../models/model_admin.php';
require_once __DIR__ . '/../includes/boutique_vendeur_display.php';
require_once __DIR__ . '/../includes/upload_image_limits.php';
require_once __DIR__ . '/../includes/senegal_regions.php';

$role = admin_normalize_role_for_route($_SESSION['admin_role'] ?? '');
if ($role !== 'vendeur') {
    header('Location: parametres.php');
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];
$admin    = get_admin_by_id($admin_id);
if (!$admin || ($admin['role'] ?? '') !== 'vendeur') {
    header('Location: parametres.php');
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$error_message   = '';

require_once __DIR__ . '/../includes/flash_toast.php';
if (isset($_SESSION['success_message'])) {
    // géré par flash_toast_collect() dans le footer
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['boutique_branding_save'])) {
    $tok = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)($_SESSION['admin_csrf'] ?? ''), $tok)) {
        $error_message = 'Session expirée. Veuillez recharger la page.';
    } else {
        $raw_c1 = trim((string)($_POST['couleur_principale'] ?? ''));
        $raw_c2 = trim((string)($_POST['couleur_accent'] ?? ''));
        if ($raw_c1 !== '' && boutique_normalize_hex_color($raw_c1) === '') {
            $error_message = "Couleur principale invalide (format #RRGGBB).";
        } elseif ($raw_c2 !== '' && boutique_normalize_hex_color($raw_c2) === '') {
            $error_message = "Couleur d'accent invalide (format #RRGGBB).";
        } else {
            $c1 = $raw_c1 !== '' ? boutique_normalize_hex_color($raw_c1) : '';
            $c2 = $raw_c2 !== '' ? boutique_normalize_hex_color($raw_c2) : '';
            $adresse         = isset($_POST['boutique_adresse']) ? trim((string)$_POST['boutique_adresse']) : '';
            $boutique_region = isset($_POST['boutique_region']) ? trim((string)$_POST['boutique_region']) : '';

            if (admin_has_boutique_region_column() && ($boutique_region === '' || !senegal_region_is_valid($boutique_region))) {
                $error_message = 'Veuillez sélectionner une région valide pour votre boutique.';
            }

            $current_logo = trim((string)($admin['boutique_logo'] ?? ''));
            $logo_final   = $current_logo;
            $remove_logo  = !empty($_POST['retirer_logo']);

            if ($remove_logo) {
                $logo_final = '';
                if ($current_logo !== '') {
                    $old = __DIR__ . '/../upload/' . str_replace('\\', '/', $current_logo);
                    if (is_file($old)) { @unlink($old); }
                }
            } elseif (isset($_FILES['boutique_logo']) && (int)($_FILES['boutique_logo']['error'] ?? 0) === UPLOAD_ERR_OK) {
                $f       = $_FILES['boutique_logo'];
                $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $mime    = (string)($f['type'] ?? '');
                if (!in_array($mime, $allowed, true)) {
                    $error_message = 'Logo : formats acceptés JPEG, PNG, GIF, WebP.';
                } elseif ((int)($f['size'] ?? 0) > UPLOAD_MAX_IMAGE_BYTES) {
                    $error_message = 'Logo trop volumineux (maximum 20 Mo).';
                } else {
                    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) { $ext = 'jpg'; }
                    $dir  = __DIR__ . '/../upload/boutique_branding/';
                    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                    $fname = 'v_' . $admin_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest  = $dir . $fname;
                    if (move_uploaded_file($f['tmp_name'], $dest)) {
                        if ($current_logo !== '') {
                            $old = __DIR__ . '/../upload/' . str_replace('\\', '/', $current_logo);
                            if (is_file($old)) { @unlink($old); }
                        }
                        $logo_final = 'boutique_branding/' . $fname;
                    } else {
                        $error_message = "Impossible d'enregistrer le fichier logo.";
                    }
                }
            }

            if ($error_message === '') {
                $branding_data = [
                    'boutique_logo'               => $logo_final !== '' ? $logo_final : null,
                    'boutique_couleur_principale' => $c1 !== '' ? $c1 : null,
                    'boutique_couleur_accent'     => $c2 !== '' ? $c2 : null,
                    'boutique_adresse'            => $adresse !== '' ? $adresse : null,
                ];
                if (admin_has_boutique_region_column()) {
                    $branding_data['boutique_region'] = $boutique_region;
                }
                $ok = update_admin_boutique_branding($admin_id, $branding_data);
                if ($ok) {
                    $_SESSION['success_message'] = "L'apparence de votre boutique a été enregistrée.";
                    header('Location: parametres-boutique-vendeur.php');
                    exit;
                }
                $error_message = 'Enregistrement impossible. Vérifiez que la base de données contient les colonnes vitrine (migration alter_admin_boutique_branding.sql).';
            }
        }
    }
    if ($error_message !== '') {
        flash_toast_queue_page('error', $error_message);
        $admin = get_admin_by_id($admin_id);
    }
}

$logo_url    = '';
$clogo       = trim((string)($admin['boutique_logo'] ?? ''));
if ($clogo !== '') {
    $logo_url = '/upload/' . htmlspecialchars(str_replace('\\', '/', $clogo), ENT_QUOTES, 'UTF-8');
}
$c1_val        = htmlspecialchars(boutique_normalize_hex_color($admin['boutique_couleur_principale'] ?? '') ?: '#3564a6', ENT_QUOTES, 'UTF-8');
$c2_val        = htmlspecialchars(boutique_normalize_hex_color($admin['boutique_couleur_accent'] ?? '') ?: '#ff6b35', ENT_QUOTES, 'UTF-8');
$adresse_val   = htmlspecialchars((string)($admin['boutique_adresse'] ?? ''), ENT_QUOTES, 'UTF-8');
$region_val    = htmlspecialchars((string)($admin['boutique_region'] ?? ''), ENT_QUOTES, 'UTF-8');
$logo_url_attr = $logo_url !== '' ? htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') : '';

$boutique_nom = trim((string)($_SESSION['admin_boutique_nom'] ?? ''));
if ($boutique_nom === '') { $boutique_nom = 'Ma boutique'; }
$admin_prenom  = trim((string)($_SESSION['admin_prenom'] ?? ''));
$admin_initial = $admin_prenom !== '' ? mb_strtoupper(mb_substr($admin_prenom, 0, 1, 'UTF-8'), 'UTF-8') : 'V';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apparence de ma boutique &mdash; Administration</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        /* ===== PARAMÈTRES BOUTIQUE VENDEUR v2 ===== */

        .pbv-page {
            max-width: 900px;
            margin: 0 auto;
            padding: clamp(16px, 4vw, 36px) clamp(14px, 4vw, 24px) 90px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            font-family: var(--font-corps, 'Poppins', sans-serif);
        }

        /* ---- Header ---- */
        .pbv-page-header {
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }

        .pbv-page-header__left { display: flex; flex-direction: column; gap: 3px; }

        .pbv-page-header__eyebrow {
            font-size: 0.73rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.12em;
            color: var(--orange, #FF6B35);
            display: flex; align-items: center; gap: 5px;
        }

        .pbv-page-header__title {
            font-size: clamp(1.3rem, 3vw, 1.75rem);
            font-weight: 800; color: var(--titres, #0d0d0d);
            font-family: var(--font-titres, 'Poppins', sans-serif);
            line-height: 1.15; letter-spacing: -0.025em;
        }

        /* ---- Boutons ---- */
        .pbv-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 11px;
            font-size: 0.81rem; font-weight: 700;
            cursor: pointer; border: none;
            text-decoration: none; font-family: var(--font-corps, 'Poppins', sans-serif);
            transition: all 0.2s; white-space: nowrap;
        }

        .pbv-btn--primary { background: var(--orange, #FF6B35); color: #fff; box-shadow: 0 4px 14px rgba(255,107,53,0.25); }
        .pbv-btn--primary:hover { background: var(--orange-fonce, #E85A2A); transform: translateY(-1px); }
        .pbv-btn--outline { background: #fff; color: var(--couleur-dominante, #3564a6); border: 1.5px solid rgba(53,100,166,0.22); }
        .pbv-btn--outline:hover { background: rgba(53,100,166,0.05); }

        /* ---- Hero boutique ---- */
        .pbv-hero {
            background: linear-gradient(135deg, #7c2d12 0%, var(--orange-fonce, #E85A2A) 50%, var(--orange, #FF6B35) 100%);
            border-radius: 20px;
            padding: clamp(20px, 3.5vw, 34px);
            position: relative; overflow: hidden;
            box-shadow: 0 16px 44px rgba(255,107,53,0.28);
        }

        .pbv-hero::before {
            content: ''; position: absolute; top: -60px; right: -40px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%; pointer-events: none;
        }

        .pbv-hero::after {
            content: ''; position: absolute; bottom: -70px; right: 80px;
            width: 170px; height: 170px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%; pointer-events: none;
        }

        .pbv-hero__inner {
            display: flex; align-items: center; gap: 18px;
            flex-wrap: wrap; position: relative;
        }

        .pbv-hero__logo-wrap {
            width: 68px; height: 68px; border-radius: 16px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.3);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .pbv-hero__logo-img { width: 100%; height: 100%; object-fit: contain; }
        .pbv-hero__logo-fallback { font-size: 1.8rem; font-weight: 900; color: #fff; font-family: var(--font-titres, 'Poppins', sans-serif); }

        .pbv-hero__body { flex: 1; min-width: 0; }
        .pbv-hero__name { font-size: clamp(1.1rem, 2.5vw, 1.45rem); font-weight: 900; color: #fff; font-family: var(--font-titres, 'Poppins', sans-serif); line-height: 1.1; }
        .pbv-hero__sub  { font-size: 0.79rem; color: rgba(255,255,255,0.65); margin-top: 4px; display: flex; align-items: center; gap: 6px; }

        .pbv-hero__colors { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
        .pbv-hero__color-dot {
            width: 24px; height: 24px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.4);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .pbv-hero__color-dot:hover { transform: scale(1.15); }
        .pbv-hero__color-label { font-size: 0.73rem; color: rgba(255,255,255,0.55); }

        .pbv-hero__link {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 17px;
            background: rgba(255,255,255,0.14);
            border: 1.5px solid rgba(255,255,255,0.22);
            border-radius: 10px; color: #fff;
            font-size: 0.78rem; font-weight: 700;
            text-decoration: none; transition: background 0.2s;
            margin-left: auto; white-space: nowrap;
        }
        .pbv-hero__link:hover { background: rgba(255,255,255,0.24); }

        /* ---- Alertes ---- */
        .pbv-alert {
            display: flex; align-items: flex-start; gap: 11px;
            padding: 14px 18px; border-radius: 14px;
            font-size: 0.84rem; font-weight: 500;
            border: 1px solid transparent;
        }
        .pbv-alert--success { background: rgba(34,197,94,0.09); border-color: rgba(34,197,94,0.22); color: #15803d; }
        .pbv-alert--error   { background: rgba(239,68,68,0.07); border-color: rgba(239,68,68,0.2); color: #b91c1c; }
        .pbv-alert i { margin-top: 2px; font-size: 1rem; flex-shrink: 0; }

        /* ---- Grille principale ---- */
        .pbv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 720px) { .pbv-grid { grid-template-columns: 1fr; } }

        .pbv-grid--wide { grid-column: 1 / -1; }

        /* ---- Cards ---- */
        .pbv-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(53,100,166,0.08);
            box-shadow: 0 2px 14px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .pbv-card:hover { box-shadow: 0 8px 28px rgba(53,100,166,0.1); }

        .pbv-card--orange { border-color: rgba(255,107,53,0.12); }
        .pbv-card--orange:hover { box-shadow: 0 8px 28px rgba(255,107,53,0.1); }

        .pbv-card__head {
            padding: 16px 20px 13px;
            border-bottom: 1px solid rgba(53,100,166,0.07);
            display: flex; align-items: center; gap: 12px;
        }

        .pbv-card--orange .pbv-card__head { border-bottom-color: rgba(255,107,53,0.08); }

        .pbv-card__head-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem; flex-shrink: 0;
        }

        .pbv-card__head-icon--blue   { background: rgba(53,100,166,0.1); color: var(--couleur-dominante, #3564a6); }
        .pbv-card__head-icon--orange { background: rgba(255,107,53,0.1); color: var(--orange, #FF6B35); }
        .pbv-card__head-icon--green  { background: rgba(34,197,94,0.1); color: #15803d; }
        .pbv-card__head-icon--violet { background: rgba(139,92,246,0.1); color: #7c3aed; }
        .pbv-card__head-icon--map    { background: rgba(234,179,8,0.1); color: #a16207; }

        .pbv-card__head-text h3 { font-size: 0.93rem; font-weight: 800; color: var(--titres, #0d0d0d); margin: 0; font-family: var(--font-titres, 'Poppins', sans-serif); }
        .pbv-card__head-text p  { font-size: 0.72rem; color: var(--gris-moyen, #737373); margin: 2px 0 0; }

        .pbv-card__body { padding: 18px 20px 20px; display: flex; flex-direction: column; gap: 14px; }

        /* ---- Logo upload ---- */
        .pbv-logo-stage {
            display: flex; flex-direction: column; align-items: center; gap: 12px;
        }

        .pbv-logo-frame {
            width: 100%; max-width: 240px;
            min-height: 110px; padding: 16px;
            border-radius: 14px;
            background: linear-gradient(145deg, #f8fafc, #f0f3f8);
            border: 2px dashed rgba(53,100,166,0.22);
            display: flex; align-items: center; justify-content: center;
            position: relative; box-sizing: border-box;
            transition: border-color 0.2s, background 0.2s;
        }

        .pbv-logo-frame.is-preview-new {
            border-style: solid;
            border-color: rgba(255,107,53,0.5);
            background: linear-gradient(145deg, rgba(255,107,53,0.05), #fff);
        }

        .pbv-logo-frame.is-remove-pending { opacity: 0.5; filter: grayscale(0.4); }

        .pbv-logo-frame img { max-width: 100%; max-height: 90px; object-fit: contain; }

        .pbv-logo-placeholder {
            text-align: center; color: var(--gris-moyen, #737373);
            font-size: 0.82rem;
        }

        .pbv-logo-placeholder i { font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 8px; }

        .pbv-logo-caption {
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--gris-moyen, #737373); text-align: center;
        }

        .pbv-logo-caption--preview { color: var(--orange, #FF6B35); }

        .pbv-file-row { display: flex; justify-content: center; }

        .pbv-file-label {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 11px;
            background: #fff;
            border: 1.5px solid rgba(255,107,53,0.3);
            color: var(--orange, #FF6B35);
            font-weight: 700; font-size: 0.84rem;
            cursor: pointer; transition: all 0.2s;
            font-family: var(--font-corps, 'Poppins', sans-serif);
        }

        .pbv-file-label:hover { background: rgba(255,107,53,0.07); border-color: var(--orange, #FF6B35); transform: translateY(-1px); }

        .pbv-remove-logo {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px;
            background: rgba(239,68,68,0.06);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 11px; font-size: 0.82rem;
            color: var(--gris-fonce, #4a4a4a); cursor: pointer;
        }

        .pbv-remove-logo input { width: 16px; height: 16px; accent-color: #ef4444; }

        /* ---- Couleurs — nouvelle section full-width ---- */
        .pbv-card--colors { }

        .pbv-colors-layout {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            align-items: start;
        }

        @media (max-width: 820px) {
            .pbv-colors-layout { grid-template-columns: 1fr 1fr; }
            .pbv-color-preview-card { grid-column: 1 / -1; }
        }

        @media (max-width: 500px) {
            .pbv-colors-layout { grid-template-columns: 1fr; }
        }

        .pbv-color-slot {
            padding: 16px;
            border-radius: 14px;
            border: 1.5px solid rgba(53,100,166,0.1);
            background: rgba(53,100,166,0.025);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .pbv-color-slot--accent {
            background: rgba(255,107,53,0.025);
            border-color: rgba(255,107,53,0.12);
        }

        /* Swatch (grand carré cliquable) */
        .pbv-color-swatch-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .pbv-color-swatch {
            width: 52px;
            height: 52px;
            border-radius: 13px;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
        }

        .pbv-color-swatch:hover { transform: scale(1.06); box-shadow: 0 6px 20px rgba(0,0,0,0.25); }

        .pbv-color-swatch input[type="color"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            border: none;
            padding: 0;
        }

        .pbv-color-slot__info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .pbv-color-slot__title {
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--titres, #0d0d0d);
            font-family: var(--font-titres, 'Poppins', sans-serif);
        }

        .pbv-color-hex {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--gris-fonce, #4a4a4a);
            letter-spacing: 0.04em;
        }

        /* Liste des usages */
        .pbv-color-usages {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pbv-color-usages li {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.74rem;
            color: var(--gris-fonce, #4a4a4a);
            font-weight: 500;
        }

        .pbv-color-usages li i {
            font-size: 0.68rem;
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .pbv-color-slot--main  .pbv-color-usages li i { color: var(--couleur-dominante, #3564a6); }
        .pbv-color-slot--accent .pbv-color-usages li i { color: var(--orange, #FF6B35); }

        /* Aperçu carte produit */
        .pbv-color-preview-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pbv-color-preview-card__label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--gris-moyen, #737373);
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
        }

        .pbv-mock-card {
            background: #fff;
            border-radius: 14px;
            border: 1.5px solid rgba(53,100,166,0.1);
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
        }

        .pbv-mock-card__img {
            height: 110px;
            background: linear-gradient(135deg, #f0f4f8, #e8ecf1);
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding: 10px;
        }

        .pbv-mock-badge-promo {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.66rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            transition: background 0.25s;
        }

        .pbv-mock-card__body {
            padding: 12px 14px 14px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .pbv-mock-card__name {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--titres, #0d0d0d);
            margin: 0;
        }

        .pbv-mock-card__price {
            font-size: 0.95rem;
            font-weight: 900;
            margin: 0;
            transition: color 0.25s;
            font-family: var(--font-titres, 'Poppins', sans-serif);
        }

        .pbv-mock-card__btn {
            width: 100%;
            padding: 8px 12px;
            border: none;
            border-radius: 9px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #fff;
            cursor: default;
            transition: background 0.25s;
            font-family: var(--font-corps, 'Poppins', sans-serif);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* ---- Select et Textarea ---- */
        .pbv-form-group { display: flex; flex-direction: column; gap: 6px; }

        .pbv-form-label {
            font-size: 0.73rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--gris-fonce, #4a4a4a);
        }

        .pbv-select,
        .pbv-textarea {
            width: 100%; padding: 11px 15px;
            border: 1.5px solid rgba(53,100,166,0.18);
            border-radius: 11px; background: #f9fbff;
            font-size: 0.87rem; color: var(--titres, #0d0d0d);
            font-family: var(--font-corps, 'Poppins', sans-serif);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none; box-sizing: border-box;
        }

        .pbv-select:focus,
        .pbv-textarea:focus {
            border-color: var(--couleur-dominante, #3564a6);
            box-shadow: 0 0 0 3px rgba(53,100,166,0.1);
            background: #fff;
        }

        .pbv-textarea { min-height: 100px; resize: vertical; line-height: 1.5; }

        /* ---- Bouton enregistrer par section ---- */
        .pbv-section-save {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 22px; border-radius: 11px;
            font-size: 0.82rem; font-weight: 700;
            border: none; cursor: pointer;
            font-family: var(--font-corps, 'Poppins', sans-serif);
            transition: all 0.2s; width: 100%;
            justify-content: center;
        }

        .pbv-section-save--orange {
            background: var(--orange, #FF6B35); color: #fff;
            box-shadow: 0 4px 14px rgba(255,107,53,0.25);
        }
        .pbv-section-save--orange:hover { background: var(--orange-fonce, #E85A2A); transform: translateY(-1px); box-shadow: 0 7px 18px rgba(255,107,53,0.32); }

        .pbv-section-save--blue {
            background: var(--couleur-dominante, #3564a6); color: #fff;
            box-shadow: 0 4px 14px rgba(53,100,166,0.25);
        }
        .pbv-section-save--blue:hover { background: var(--bleu-fonce, #2d5690); transform: translateY(-1px); box-shadow: 0 7px 18px rgba(53,100,166,0.32); }

        .pbv-section-save--green {
            background: #16a34a; color: #fff;
            box-shadow: 0 4px 14px rgba(22,163,74,0.22);
        }
        .pbv-section-save--green:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 7px 18px rgba(22,163,74,0.3); }

        .pbv-section-save--gold {
            background: #a16207; color: #fff;
            box-shadow: 0 4px 14px rgba(161,98,7,0.22);
        }
        .pbv-section-save--gold:hover { background: #854d0e; transform: translateY(-1px); box-shadow: 0 7px 18px rgba(161,98,7,0.3); }

        .pbv-section-save i { font-size: 0.82em; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="pbv-page">

    <!-- ===== HEADER ===== -->
    <header class="pbv-page-header">
        <div class="pbv-page-header__left">
            <p class="pbv-page-header__eyebrow">
                <i class="fas fa-store"></i> Vitrine publique
            </p>
            <h1 class="pbv-page-header__title">Apparence de ma boutique</h1>
        </div>
        <div style="display:flex;gap:9px;align-items:center;flex-wrap:wrap;">
            <a href="parametres.php" class="pbv-btn pbv-btn--outline">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </header>

    <!-- ===== HERO BOUTIQUE ===== -->
    <div class="pbv-hero">
        <div class="pbv-hero__inner">
            <div class="pbv-hero__logo-wrap">
                <?php if ($logo_url !== ''): ?>
                    <img src="<?php echo $logo_url; ?>" alt="Logo de la boutique" class="pbv-hero__logo-img">
                <?php else: ?>
                    <span class="pbv-hero__logo-fallback"><?php echo htmlspecialchars($admin_initial); ?></span>
                <?php endif; ?>
            </div>
            <div class="pbv-hero__body">
                <div class="pbv-hero__name"><?php echo htmlspecialchars($boutique_nom); ?></div>
                <div class="pbv-hero__sub">
                    <i class="fas fa-palette" style="font-size:.7rem;"></i>
                    Personnalisez l&rsquo;apparence de votre vitrine
                </div>
                <div class="pbv-hero__colors">
                    <span class="pbv-hero__color-dot" id="hero-c1-dot" style="background:<?php echo $c1_val; ?>;"></span>
                    <span class="pbv-hero__color-dot" id="hero-c2-dot" style="background:<?php echo $c2_val; ?>;"></span>
                    <span class="pbv-hero__color-label">Couleurs actives</span>
                </div>
            </div>
            <a href="parametres.php" class="pbv-hero__link">
                <i class="fas fa-sliders-h"></i> Param&egrave;tres
            </a>
        </div>
    </div>

    <!-- ===== ALERTES via flash toast ===== -->

    <!-- ===== FORMULAIRE ===== -->
    <form method="post" enctype="multipart/form-data" action="" id="brv-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf']); ?>">
        <input type="hidden" name="boutique_branding_save" value="1">

        <div class="pbv-grid">

            <!-- LOGO -->
            <section class="pbv-card pbv-card--orange" aria-labelledby="pbv-logo-title">
                <div class="pbv-card__head">
                    <div class="pbv-card__head-icon pbv-card__head-icon--orange"><i class="fas fa-image"></i></div>
                    <div class="pbv-card__head-text">
                        <h3 id="pbv-logo-title">Logo de la boutique</h3>
                        <p>Aper&ccedil;u en direct avant enregistrement</p>
                    </div>
                </div>
                <div class="pbv-card__body">
                    <div class="pbv-logo-stage">
                        <div class="pbv-logo-frame" id="brv-logo-frame" data-current-src="<?php echo $logo_url_attr; ?>">
                            <?php if ($logo_url !== ''): ?>
                                <img src="<?php echo $logo_url; ?>" alt="Logo de la boutique" id="brv-preview-img">
                            <?php else: ?>
                                <div class="pbv-logo-placeholder" id="brv-logo-placeholder">
                                    <i class="fas fa-store"></i>
                                    Aucun logo — le logo marketplace sera affich&eacute;.
                                </div>
                                <img src="" alt="" id="brv-preview-img" hidden>
                            <?php endif; ?>
                        </div>
                        <p class="pbv-logo-caption" id="brv-logo-caption">
                            <?php echo $logo_url !== '' ? 'Logo actuel' : 'Aper&ccedil;u'; ?>
                        </p>
                    </div>

                    <div class="pbv-file-row">
                        <input type="file" id="boutique_logo" name="boutique_logo"
                            class="visually-hidden"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            aria-describedby="pbv-logo-title">
                        <label for="boutique_logo" class="pbv-file-label">
                            <i class="fas fa-upload"></i> Choisir une image
                        </label>
                    </div>

                    <?php if ($logo_url !== ''): ?>
                        <label class="pbv-remove-logo">
                            <input type="checkbox" name="retirer_logo" value="1" id="retirer_logo">
                            <span>Retirer mon logo et utiliser le logo par d&eacute;faut du site</span>
                        </label>
                    <?php endif; ?>

                    <button type="submit" class="pbv-section-save pbv-section-save--orange">
                        <i class="fas fa-floppy-disk"></i> Enregistrer le logo
                    </button>
                </div>
            </section>

            <!-- COULEURS -->
            <section class="pbv-card pbv-grid--wide pbv-card--colors" aria-labelledby="pbv-colors-title">
                <div class="pbv-card__head">
                    <div class="pbv-card__head-icon pbv-card__head-icon--violet"><i class="fas fa-palette"></i></div>
                    <div class="pbv-card__head-text">
                        <h3 id="pbv-colors-title">Couleurs de votre boutique</h3>
                        <p>Ces couleurs s&rsquo;appliquent automatiquement &agrave; toutes les pages de votre vitrine</p>
                    </div>
                </div>
                <div class="pbv-card__body">

                    <!-- Pickers + explications côte à côte -->
                    <div class="pbv-colors-layout">

                        <!-- Couleur principale -->
                        <div class="pbv-color-slot pbv-color-slot--main">
                            <div class="pbv-color-slot__picker-row">
                                <label for="couleur_principale" class="pbv-color-swatch-label">
                                    <span class="pbv-color-swatch" id="swatch-main" style="background:<?php echo $c1_val; ?>;">
                                        <input type="color" id="couleur_principale" name="couleur_principale" value="<?php echo $c1_val; ?>" title="Choisir la couleur principale">
                                    </span>
                                    <span class="pbv-color-slot__info">
                                        <span class="pbv-color-slot__title">Couleur principale</span>
                                        <span class="pbv-color-hex" id="hex-principale"><?php echo $c1_val; ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Couleur accent -->
                        <div class="pbv-color-slot pbv-color-slot--accent">
                            <div class="pbv-color-slot__picker-row">
                                <label for="couleur_accent" class="pbv-color-swatch-label">
                                    <span class="pbv-color-swatch" id="swatch-accent" style="background:<?php echo $c2_val; ?>;">
                                        <input type="color" id="couleur_accent" name="couleur_accent" value="<?php echo $c2_val; ?>" title="Choisir la couleur d'accent">
                                    </span>
                                    <span class="pbv-color-slot__info">
                                        <span class="pbv-color-slot__title">Couleur d&rsquo;accent</span>
                                        <span class="pbv-color-hex" id="hex-accent"><?php echo $c2_val; ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Aperçu carte produit live -->
                        <div class="pbv-color-preview-card">
                            <p class="pbv-color-preview-card__label"><i class="fas fa-eye"></i> Aper&ccedil;u en direct</p>
                            <div class="pbv-mock-card">
                                <div class="pbv-mock-card__img">
                                    <span class="pbv-mock-badge-promo" id="preview-badge-accent">Promo</span>
                                </div>
                                <div class="pbv-mock-card__body">
                                    <p class="pbv-mock-card__name">Nom du produit</p>
                                    <p class="pbv-mock-card__price" id="preview-price-main">12 000 FCFA</p>
                                    <button type="button" class="pbv-mock-card__btn" id="preview-btn-main">
                                        <i class="fas fa-cart-plus"></i> Ajouter
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="pbv-section-save pbv-section-save--blue">
                        <i class="fas fa-floppy-disk"></i> Enregistrer les couleurs
                    </button>

                </div>
            </section>

            <!-- RÉGION -->
            <section class="pbv-card pbv-grid--wide" aria-labelledby="pbv-region-title">
                <div class="pbv-card__head">
                    <div class="pbv-card__head-icon pbv-card__head-icon--map"><i class="fas fa-map-location-dot"></i></div>
                    <div class="pbv-card__head-text">
                        <h3 id="pbv-region-title">R&eacute;gion de la boutique</h3>
                        <p>Zone g&eacute;ographique affich&eacute;e sur votre vitrine</p>
                    </div>
                </div>
                <div class="pbv-card__body">
                    <div class="pbv-form-group">
                        <label class="pbv-form-label" for="boutique_region">S&eacute;lectionner une r&eacute;gion <span style="color:var(--orange,#FF6B35)">*</span></label>
                        <select id="boutique_region" class="pbv-select" name="boutique_region" required>
                            <?php echo senegal_regions_options_html($region_val, true, 'Sélectionnez une région'); ?>
                        </select>
                    </div>
                    <button type="submit" class="pbv-section-save pbv-section-save--gold">
                        <i class="fas fa-floppy-disk"></i> Enregistrer la r&eacute;gion
                    </button>
                </div>
            </section>

            <!-- ADRESSE -->
            <section class="pbv-card pbv-grid--wide" aria-labelledby="pbv-addr-title">
                <div class="pbv-card__head">
                    <div class="pbv-card__head-icon pbv-card__head-icon--green"><i class="fas fa-location-dot"></i></div>
                    <div class="pbv-card__head-text">
                        <h3 id="pbv-addr-title">Adresse affich&eacute;e</h3>
                        <p>Adresse physique ou contact visible sur votre boutique et le pied de page</p>
                    </div>
                </div>
                <div class="pbv-card__body">
                    <div class="pbv-form-group">
                        <label class="pbv-form-label" for="boutique_adresse">Adresse <span style="color:var(--gris-clair,#a3a3a3);font-weight:500;text-transform:none;">(optionnel)</span></label>
                        <textarea id="boutique_adresse" class="pbv-textarea" name="boutique_adresse"
                            rows="3" placeholder="Ex. : 12 rue Sandaga, Dakar, S&eacute;n&eacute;gal"><?php echo $adresse_val; ?></textarea>
                    </div>
                    <button type="submit" class="pbv-section-save pbv-section-save--green">
                        <i class="fas fa-floppy-disk"></i> Enregistrer l&rsquo;adresse
                    </button>
                </div>
            </section>

        </div><!-- /.pbv-grid -->

    </form>

</div><!-- /.pbv-page -->

<script>
(function () {
    var fileInput   = document.getElementById('boutique_logo');
    var frame       = document.getElementById('brv-logo-frame');
    var img         = document.getElementById('brv-preview-img');
    var caption     = document.getElementById('brv-logo-caption');
    var placeholder = document.getElementById('brv-logo-placeholder');
    var removeCb    = document.getElementById('retirer_logo');
    var c1          = document.getElementById('couleur_principale');
    var c2          = document.getElementById('couleur_accent');
    var h1          = document.getElementById('hex-principale');
    var h2          = document.getElementById('hex-accent');
    var heroC1      = document.getElementById('hero-c1-dot');
    var heroC2      = document.getElementById('hero-c2-dot');
    var previewMain = document.getElementById('preview-btn-main');
    var previewAcc  = document.getElementById('preview-badge-accent');
    var previewPrice= document.getElementById('preview-price-main');
    var swatchMain  = document.getElementById('swatch-main');
    var swatchAccent= document.getElementById('swatch-accent');
    var currentSrc  = frame ? frame.getAttribute('data-current-src') || '' : '';
    var objectUrl   = null;

    function revokeObjectUrl() { if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; } }

    function showPlaceholder(show) { if (placeholder) placeholder.style.display = show ? 'block' : 'none'; }

    function applyRemovePending() {
        if (!frame || !removeCb) return;
        frame.classList.toggle('is-remove-pending', removeCb.checked);
    }

    function syncHex() {
        if (c1 && h1) h1.textContent = c1.value;
        if (c2 && h2) h2.textContent = c2.value;
        if (c1 && heroC1)      heroC1.style.background    = c1.value;
        if (c2 && heroC2)      heroC2.style.background    = c2.value;
        if (c1 && swatchMain)  swatchMain.style.background  = c1.value;
        if (c2 && swatchAccent)swatchAccent.style.background = c2.value;
        if (c1 && previewMain) { previewMain.style.background = c1.value; previewMain.style.color = '#fff'; }
        if (c1 && previewPrice){ previewPrice.style.color = c1.value; }
        if (c2 && previewAcc)  { previewAcc.style.background = c2.value; previewAcc.style.color = '#fff'; }
    }

    if (fileInput && img && frame && caption) {
        fileInput.addEventListener('change', function () {
            revokeObjectUrl();
            if (removeCb) removeCb.checked = false;
            applyRemovePending();
            var f = fileInput.files && fileInput.files[0];
            if (!f) {
                if (currentSrc) {
                    img.removeAttribute('hidden'); img.src = currentSrc; img.alt = 'Logo de la boutique';
                    caption.textContent = 'Logo actuel';
                    caption.classList.remove('pbv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                } else {
                    img.removeAttribute('src'); img.setAttribute('hidden','hidden');
                    showPlaceholder(true);
                    caption.textContent = 'Aperçu';
                    caption.classList.remove('pbv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                }
                return;
            }
            objectUrl = URL.createObjectURL(f);
            img.removeAttribute('hidden'); img.src = objectUrl; img.alt = 'Aperçu du nouveau logo';
            showPlaceholder(false);
            caption.textContent = 'Aperçu — enregistrez pour appliquer';
            caption.classList.add('pbv-logo-caption--preview');
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
                    img.removeAttribute('hidden'); img.src = currentSrc;
                    img.alt = 'Logo actuel (sera retiré après enregistrement)';
                    showPlaceholder(false);
                    caption.textContent = 'Suppression prévue au prochain enregistrement';
                    caption.classList.add('pbv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                } else {
                    img.removeAttribute('src'); img.setAttribute('hidden','hidden');
                    showPlaceholder(true);
                    caption.textContent = 'Aucun logo après enregistrement';
                    caption.classList.add('pbv-logo-caption--preview');
                    frame.classList.remove('is-preview-new');
                }
            } else {
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    fileInput.dispatchEvent(new Event('change'));
                } else if (currentSrc) {
                    img.removeAttribute('hidden'); img.src = currentSrc; img.alt = 'Logo de la boutique';
                    showPlaceholder(false);
                    caption.textContent = 'Logo actuel';
                    caption.classList.remove('pbv-logo-caption--preview');
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

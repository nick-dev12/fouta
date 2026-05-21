<?php
if (!defined('SITE_BRAND_TAGLINE')) {
    require_once __DIR__ . '/includes/site_brand.php';
}
if (!function_exists('get_asset_version')) {
    require_once __DIR__ . '/includes/asset_version.php';
}
$asset_version = isset($asset_version) ? $asset_version : get_asset_version();
$u_home = $GLOBALS['nav_home'] ?? '/index.php';
$u_produits = $GLOBALS['nav_produits'] ?? '/produits.php';
$u_panier = $GLOBALS['nav_panier'] ?? '/panier.php';
$u_nouveautes = $GLOBALS['nav_nouveautes'] ?? '/nouveautes.php';
$u_promo = $GLOBALS['nav_promo'] ?? '/promo.php';
$u_contact = $GLOBALS['nav_contact'] ?? '/contact.php';
if (!function_exists('nav_categorie_href')) {
    require_once __DIR__ . '/includes/marketplace_helpers.php';
}
$categories_menu = [];
$nav_megamenu = [];
if (file_exists(__DIR__ . '/models/model_categories.php')) {
    require_once __DIR__ . '/models/model_categories.php';
    $boutique_mid = defined('BOUTIQUE_ADMIN_ID') ? (int) BOUTIQUE_ADMIN_ID : null;
    if ($boutique_mid > 0 && function_exists('get_all_categories_for_vendeur')) {
        // Vitrine : uniquement les catégories ayant des produits de ce vendeur (pas tout le méga-menu plateforme)
        $categories_menu = get_all_categories_for_vendeur($boutique_mid);
        $nav_megamenu = [];
    } elseif (function_exists('categories_hierarchy_enabled') && categories_hierarchy_enabled() && function_exists('get_megamenu_categories')) {
        $nav_megamenu = get_megamenu_categories(null);
        foreach ($nav_megamenu as $bl) {
            $categories_menu[] = $bl['general'];
        }
    } else {
        $categories_menu = get_all_categories();
    }
}
// Compter les articles du panier si l'utilisateur est connecté
$panier_count = 0;
if (isset($_SESSION['user_id'])) {
    $conn_path = file_exists(__DIR__ . '/conn/conn.php') ? __DIR__ . '/conn/conn.php' : dirname(__DIR__) . '/conn/conn.php';
    if (file_exists($conn_path)) {
        require_once $conn_path;
    }
    $model_path = file_exists(__DIR__ . '/models/model_panier.php')
        ? __DIR__ . '/models/model_panier.php'
        : dirname(__DIR__) . '/models/model_panier.php';

    if (file_exists($model_path)) {
        require_once $model_path;
        $panier_vid = null;
        if (defined('BOUTIQUE_ADMIN_ID') && (int) BOUTIQUE_ADMIN_ID > 0) {
            $panier_vid = (int) BOUTIQUE_ADMIN_ID;
        } elseif (!empty($GLOBALS['nav_panier_count_for_vendeur_id'])) {
            $panier_vid = (int) $GLOBALS['nav_panier_count_for_vendeur_id'];
        }
        if ($panier_vid > 0 && function_exists('count_panier_items_for_vendeur')) {
            $panier_count = count_panier_items_for_vendeur($_SESSION['user_id'], $panier_vid);
        } else {
            $panier_count = count_panier_items($_SESSION['user_id']);
        }
    }
}
$nav_panier_connect_redirect = $GLOBALS['nav_panier_login_redirect'] ?? '/panier.php';
$nav_compte_href = '/choix-connexion.php';
$nav_compte_btn_class = 'nav-compte-btn';
$nav_compte_title = 'Mon compte';
$nav_compte_subtitle = 'Identifiez-vous';
$nav_compte_logged = false;

if (isset($_SESSION['commercant_id'])) {
    $nav_compte_logged = true;
    $nav_compte_href = '/view/profil_commercent.php';
    if (isset($commercant) && !empty($commercant['nom'])) {
        $explode_nom = explode(' ', $commercant['nom']);
        $nav_compte_subtitle = htmlspecialchars($explode_nom[0] ?? $commercant['nom'], ENT_QUOTES, 'UTF-8');
    }
} elseif (isset($_SESSION['admin_id'])) {
    $nav_compte_logged = true;
    $nav_compte_href = '/admin/dashboard.php';
    $nav_compte_btn_class .= ' nav-compte-btn--admin';
    $__role_nav = (string) ($_SESSION['admin_role'] ?? 'admin');
    if ($__role_nav === 'vendeur') {
        $nav_compte_btn_class .= ' nav-compte-btn--boutique';
        $__bn = trim((string) ($_SESSION['admin_boutique_nom'] ?? ''));
        if ($__bn === '') {
            $mid_nav = (int) ($_SESSION['admin_id'] ?? 0);
            if ($mid_nav > 0 && file_exists(__DIR__ . '/models/model_admin.php')) {
                require_once __DIR__ . '/models/model_admin.php';
                if (function_exists('get_admin_by_id')) {
                    $__adm_nav = get_admin_by_id($mid_nav);
                    if ($__adm_nav) {
                        $__bn = trim((string) ($__adm_nav['boutique_nom'] ?? ''));
                        if ($__bn !== '') {
                            $_SESSION['admin_boutique_nom'] = $__bn;
                        }
                        $__bs = trim((string) ($__adm_nav['boutique_slug'] ?? ''));
                        if ($__bs !== '') {
                            $_SESSION['admin_boutique_slug'] = $__bs;
                        }
                    }
                }
            }
        }
        if ($__bn !== '') {
            $nav_compte_title = htmlspecialchars($__bn, ENT_QUOTES, 'UTF-8');
            $nav_compte_subtitle = htmlspecialchars('Ma boutique', ENT_QUOTES, 'UTF-8');
        } else {
            $nav_compte_title = htmlspecialchars('Ma boutique', ENT_QUOTES, 'UTF-8');
            $__nom_ad = trim((string) ($_SESSION['admin_nom'] ?? ''));
            $nav_compte_subtitle = htmlspecialchars($__nom_ad !== '' ? $__nom_ad : 'Espace vendeur', ENT_QUOTES, 'UTF-8');
        }
    } else {
        $nav_compte_title = htmlspecialchars('Espace équipe', ENT_QUOTES, 'UTF-8');
        $__nom_ad = trim((string) ($_SESSION['admin_nom'] ?? ''));
        $nav_compte_subtitle = htmlspecialchars($__nom_ad !== '' ? $__nom_ad : 'Administration', ENT_QUOTES, 'UTF-8');
    }
} elseif (isset($_SESSION['user_id'])) {
    $nav_compte_logged = true;
    $nav_compte_href = '/user/mon-compte.php';
    $__nom_cli = trim((string) ($_SESSION['user_nom'] ?? ''));
    if ($__nom_cli !== '') {
        $nav_compte_title = htmlspecialchars($__nom_cli, ENT_QUOTES, 'UTF-8');
        $nav_compte_subtitle = htmlspecialchars('Mon compte', ENT_QUOTES, 'UTF-8');
    } elseif (!empty($_SESSION['user_prenom'])) {
        $nav_compte_title = htmlspecialchars(trim((string) $_SESSION['user_prenom']), ENT_QUOTES, 'UTF-8');
        $nav_compte_subtitle = htmlspecialchars('Mon compte', ENT_QUOTES, 'UTF-8');
    }
}

if ($nav_compte_logged) {
    $nav_compte_btn_class .= ' nav-compte-btn--logged';
}
$nav_panier_href = isset($_SESSION['user_id'])
    ? $u_panier
    : '/choix-connexion.php?redirect=' . rawurlencode($nav_panier_connect_redirect);

$shop_nav_script = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ($_SERVER['PHP_SELF'] ?? ''));
$shop_dock_home_act = ($shop_nav_script === 'index.php');
$shop_dock_panier_act = in_array($shop_nav_script, ['panier.php', 'commande.php'], true);
$shop_dock_compte_act = (strpos($_SERVER['REQUEST_URI'] ?? '', '/user/') !== false);
?>
<link rel="stylesheet" href="/css/variables.css<?php echo $asset_version ? '?v=' . $asset_version : ''; ?>">
<link rel="stylesheet" href="/css/nabare.css<?php echo $asset_version ? '?v=' . $asset_version : ''; ?>">
<link rel="stylesheet" href="/css/shop-bottom-nav.css<?php echo $asset_version ? '?v=' . $asset_version : ''; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<style>
    /* Nav style Planète Gâteau - fond dégradé, barre recherche, Mon compte, panier */
    .nav-planete-gateau {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 12px 30px;
        background: var(--blanc);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
    }

    .section1 {
        z-index: 100;
    }

    .nav-planete-gateau .logo {
        flex-shrink: 0;
    }

    .nav-planete-gateau .logo img {
        height: 70px;
        width: auto;
        max-width: 160px;
        object-fit: contain;

        transform: scale(2);
        margin-left: 20px;
    }

    .nav-top-row {
        display: contents;
    }

    .nav-top-row .logo {
        order: 1;
    }

    .nav-search-wrapper {
        order: 2;
    }

    .nav-top-row .nav-panier-link {
        order: 3;
    }

    .nav-top-row .nav-compte-btn {
        order: 4;
    }

    /* Barre de recherche avec filtres */
    .nav-search-wrapper {
        display: flex;
        flex: 1;
        max-width: 500px;
        margin: 0 20px;
        position: relative;
        z-index: 9999;
    }

    .nav-search-form {
        display: flex;
        align-items: stretch;
        flex: 1;
        border-radius: 25px;
        overflow: hidden;
        box-shadow: var(--ombre-douce);
    }

    .nav-language-switcher {
        margin-left: 8px;
        width: 90px;
        height: 36px;
        padding: 0 8px;
        background: var(--blanc);
        border: 1px solid rgba(13, 13, 13, 0.08);
        border-radius: 10px;
        color: var(--couleur-dominante);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(53, 100, 166, 0.06);
        position: relative;
        overflow: hidden;
    }

    .nav-language-switcher:hover {
        background: var(--blanc);
        border-color: var(--couleur-dominante);
    }

    /* Ne pas utiliser display:none : le script GTranslate n’initialise pas le <select> correctement. */
    .nav-language-switcher .gtranslate_wrapper {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .nav-lang-flag {
        display: block;
        width: 24px;
        height: 18px;
        border-radius: 2px;
        background-image: url('https://flagcdn.com/w40/fr.png');
        background-size: cover;
        background-position: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.28);
        flex-shrink: 0;
    }

    .nav-lang-code {
        font: 800 12px/1 var(--font-corps);
        color: var(--texte-fonce);
        letter-spacing: 0.02em;
        min-width: 20px;
        text-transform: uppercase;
    }

    .nav-lang-chevron {
        color: var(--gris-fonce);
        font-size: 12px;
        margin-left: auto;
        pointer-events: none;
    }

    .nav-lang-select {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        border: 0;
        outline: none;
        background: transparent;
        font: 800 12px/1 var(--font-corps);
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
    }

    .nav-search-btn {
        padding: 12px 20px;
        background: var(--couleur-dominante);
        border: none;
        color: var(--texte-clair);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s;
    }

    .nav-search-btn:hover {
        background: var(--couleur-dominante-hover);
    }

    .nav-search-btn i {
        font-size: 18px;
    }

    .nav-search-input {
        flex: 1;
        padding: 12px 20px;
        border: 2px solid var(--border-input);
        border-left: none;
        background: var(--blanc);
        font-size: 15px;
        outline: none;
        border-radius: 0 25px 25px 0;
    }

    .nav-search-input::placeholder {
        color: var(--gris-clair);
    }

    .nav-search-input:focus {
        border-color: var(--couleur-dominante);
    }

    /* Bouton Mon compte */
    .nav-compte-btn {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 10px 20px;
        background: var(--couleur-dominante);
        color: var(--texte-clair);
        text-decoration: none;
        border-radius: 25px;
        transition: all 0.3s;
        position: relative;
        min-width: 140px;
    }

    .nav-compte-btn:hover {
        background: var(--couleur-dominante-hover);
        color: var(--texte-clair);
        transform: translateY(-1px);
    }

    .nav-compte-title {
        font-size: 14px;
        font-weight: 700;
        display: block;
        line-height: 1.2;
    }

    .nav-compte-subtitle {
        font-size: 12px;
        opacity: 0.95;
        font-weight: 400;
    }

    .nav-compte-chevron {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        opacity: 0.9;
    }

    /* Panier */
    .nav-panier-link {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        color: var(--texte-fonce);
        text-decoration: none;
        transition: color 0.3s;
    }

    .nav-panier-link:hover {
        color: var(--couleur-dominante);
    }

    .nav-panier-link i {
        font-size: 26px;
    }

    .nav-panier-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: var(--orange);
        color: var(--texte-clair);
        border-radius: 50%;
        min-width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        border: 2px solid var(--blanc);
        padding: 0 4px;
        box-shadow: var(--ombre-promo);
    }

    @media (max-width: 992px) {
        .nav-planete-gateau {
            padding: 10px 20px;
            gap: 12px;
        }

        .nav-planete-gateau .logo img {
            height: 55px;
        }

        .nav-search-wrapper {
            max-width: 320px;
        }

        .nav-language-switcher {
            width: 88px;
            height: 35px;
            min-width: 0;
            padding: 0 7px;
        }

        .nav-search-input {
            font-size: 14px;
            padding: 10px 16px;
        }

        .nav-compte-btn {
            min-width: 130px;
            padding: 8px 14px;
        }

        .nav-compte-title {
            font-size: 12px;
        }

        .nav-compte-subtitle {
            font-size: 11px;
        }
    }

    @media (max-width: 768px) {
        .nav-planete-gateau {
            flex-wrap: wrap;
            padding: 10px 12px;
            gap: 10px;
        }

        .nav-top-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            order: 1;
            flex-shrink: 0;
        }

        .nav-planete-gateau .logo {
            flex-shrink: 0;
        }

        .nav-planete-gateau .logo img {
            height: 45px;
            max-width: 120px;
        }

        .nav-panier-link {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
        }

        .nav-panier-link i {
            font-size: 22px;
        }

        .nav-panier-badge {
            min-width: 18px;
            height: 18px;
            font-size: 10px;
        }

        .nav-compte-btn {
            min-width: auto;
            padding: 8px 12px;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            flex-shrink: 0;
            max-width: 140px;
        }

        .nav-compte-btn:not(.nav-compte-btn--boutique):not(.nav-compte-btn--logged) .nav-compte-title {
            display: none;
        }

        .nav-compte-btn--boutique .nav-compte-title,
        .nav-compte-btn--logged:not(.nav-compte-btn--boutique) .nav-compte-title {
            display: block;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.15;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nav-compte-btn--boutique .nav-compte-subtitle {
            font-size: 10px;
            font-weight: 500;
            opacity: 0.92;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nav-compte-btn:not(.nav-compte-btn--boutique) .nav-compte-subtitle {
            font-size: 12px;
            font-weight: 600;
        }

        .nav-compte-chevron {
            display: none;
        }

        .nav-search-wrapper {
            order: 2;
            width: 100%;
            max-width: 100%;
            margin: 0;
            flex-direction: row;
        }

        .nav-search-form {
            flex: 1;
        }

        .nav-search-btn {
            padding: 10px 14px;
        }

        .nav-search-input {
            padding: 10px 14px;
            font-size: 14px;
        }

        .nav-language-switcher {
            width: 84px;
            height: 34px;
            padding: 0 7px;
            gap: 5px;
        }
    }

    @media (max-width: 480px) {
        .nav-planete-gateau {
            padding: 8px 10px;
            gap: 8px;
        }

        .nav-planete-gateau .logo img {
            height: 40px;
            max-width: 100px;
            object-fit: contain;
        }

        .nav-compte-btn {
            padding: 6px 10px;
        }

        .nav-compte-subtitle {
            font-size: 11px;
        }

        .nav-panier-link {
            width: 38px;
            height: 38px;
        }

        .nav-panier-link i {
            font-size: 20px;
        }

        .nav-search-btn {
            padding: 8px 12px;
        }

        .nav-search-input {
            padding: 8px 12px;
            font-size: 13px;
        }

        .nav-language-switcher {
            width: 78px;
            height: 32px;
            padding: 0 6px;
            gap: 5px;
        }

        .nav-lang-flag {
            width: 22px;
            height: 16px;
        }

        .nav-lang-code {
            font-size: 11px;
            min-width: 18px;
        }

        .nav-lang-chevron {
            font-size: 11px;
        }

        .nav-lang-select {
            font-size: 11px;
        }
    }
</style>

<div class="info">

</div>
<nav class="nav-planete-gateau">
    <div class="nav-top-row">
        <a class="logo" href="<?php echo htmlspecialchars($u_home); ?>">
            <?php
            $__nav_logo_src = '/image/logo_market.png';
            $__nav_logo_alt = SITE_BRAND_NAME;
            if (!empty($GLOBALS['BOUTIQUE_VENDEUR_DISPLAY']['boutique_logo'])) {
                $__nav_logo_src = '/upload/' . str_replace('\\', '/', $GLOBALS['BOUTIQUE_VENDEUR_DISPLAY']['boutique_logo']);
                $__bn = trim((string) ($GLOBALS['BOUTIQUE_VENDEUR_DISPLAY']['boutique_nom'] ?? ''));
                $__nav_logo_alt = $__bn !== '' ? $__bn : 'Boutique';
            }
            ?>
            <img src="<?php echo htmlspecialchars($__nav_logo_src, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($__nav_logo_alt, ENT_QUOTES, 'UTF-8'); ?>">
        </a>
        <a href="<?php echo htmlspecialchars($nav_panier_href, ENT_QUOTES, 'UTF-8'); ?>"
            class="nav-panier-link"
            title="<?php echo isset($_SESSION['user_id']) ? 'Voir mon panier (' . $panier_count . ' article' . ($panier_count > 1 ? 's' : '') . ')' : 'Se connecter pour voir le panier'; ?>">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php if (isset($_SESSION['user_id']) && $panier_count > 0): ?>
                <span class="nav-panier-badge"><?php echo $panier_count > 99 ? '99+' : $panier_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo htmlspecialchars($nav_compte_href, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($nav_compte_btn_class, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="nav-compte-title"><?php echo $nav_compte_title; ?></span>
            <span class="nav-compte-subtitle"><?php echo $nav_compte_subtitle; ?></span>
            <i class="fa-solid fa-chevron-down nav-compte-chevron"></i>
        </a>
    </div>

    <div class="nav-search-wrapper">
        <form class="nav-search-form" action="<?php echo htmlspecialchars($u_produits); ?>" method="get"
            id="nav-search-form">
            <button type="submit" class="nav-search-btn" aria-label="Rechercher">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <input type="text" name="recherche" id="nav-search" class="nav-search-input"
                placeholder="Que recherchez-vous ?"
                value="<?php echo !empty($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : ''; ?>">
            <input type="hidden" name="prix_min" id="nav-prix-min"
                value="<?php echo isset($_GET['prix_min']) ? htmlspecialchars($_GET['prix_min']) : ''; ?>">
            <input type="hidden" name="prix_max" id="nav-prix-max"
                value="<?php echo isset($_GET['prix_max']) ? htmlspecialchars($_GET['prix_max']) : ''; ?>">
            <input type="hidden" name="categorie" id="nav-categorie"
                value="<?php echo isset($_GET['categorie']) ? htmlspecialchars($_GET['categorie']) : ''; ?>">
            <input type="hidden" name="tri" id="nav-tri"
                value="<?php echo isset($_GET['tri']) ? htmlspecialchars($_GET['tri']) : ''; ?>">
        </form>
        <div class="nav-language-switcher" aria-label="Sélection de la langue" title="Changer la langue">
            <span class="nav-lang-flag" id="navLangFlag" aria-hidden="true"></span>
            <span class="nav-lang-code" id="navLangCode" aria-hidden="true">FR</span>
            <i class="fa-solid fa-chevron-up nav-lang-chevron" aria-hidden="true"></i>
            <select class="nav-lang-select" id="navLangSelect" aria-label="Changer la langue">
                <option value="fr" data-flag-src="https://flagcdn.com/w40/fr.png">FR</option>
                <option value="en" data-flag-src="https://flagcdn.com/w40/gb.png">EN</option>
                <option value="es" data-flag-src="https://flagcdn.com/w40/es.png">ES</option>
                <option value="pt" data-flag-src="https://flagcdn.com/w40/pt.png">PT</option>
                <option value="ar" data-flag-src="https://flagcdn.com/w40/sa.png">AR</option>
            </select>
            <div class="gtranslate_wrapper"></div>
        </div>
    </div>
    <script>
        window.gtranslateSettings = {
            default_language: 'fr',
            native_language_names: true,
            detect_browser_language: false,
            languages: ['fr', 'en', 'es', 'pt', 'ar'],
            wrapper_selector: '.gtranslate_wrapper'
        };

        (function () {
            var select = document.getElementById('navLangSelect');
            var flag = document.getElementById('navLangFlag');
            var code = document.getElementById('navLangCode');
            if (!select || !flag || !code) {
                return;
            }

            function getCookie(name) {
                var parts = ('; ' + document.cookie).split('; ' + name + '=');
                if (parts.length === 2) {
                    return parts.pop().split(';').shift();
                }
                return '';
            }

            function setCookie(name, value) {
                var expires = '; expires=' + new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
                document.cookie = name + '=' + value + expires + '; path=/';
                if (location.hostname.indexOf('.') !== -1) {
                    document.cookie = name + '=' + value + expires + '; path=/; domain=.' + location.hostname.replace(/^www\./, '');
                }
            }

            function clearCookie(name) {
                var past = '; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = name + '=' + past + '; path=/';
                if (location.hostname.indexOf('.') !== -1) {
                    document.cookie = name + '=' + past + '; path=/; domain=.' + location.hostname.replace(/^www\./, '');
                }
            }

            function currentLangFromCookie() {
                var raw = decodeURIComponent(getCookie('googtrans') || '');
                var match = raw.match(/\/fr\/([a-z-]+)/i) || raw.match(/\/auto\/([a-z-]+)/i);
                return match ? match[1].toLowerCase() : 'fr';
            }

            function syncFlag() {
                var opt = select.options[select.selectedIndex];
                flag.style.backgroundImage = 'url("' + (opt ? (opt.getAttribute('data-flag-src') || 'https://flagcdn.com/w40/fr.png') : 'https://flagcdn.com/w40/fr.png') + '")';
                code.textContent = opt ? opt.textContent.trim().toUpperCase() : 'FR';
            }

            function applyLanguage(lang) {
                if (lang === 'fr') {
                    clearCookie('googtrans');
                } else {
                    setCookie('googtrans', '/fr/' + lang);
                }
                window.location.reload();
            }

            var initial = currentLangFromCookie();
            if (select.querySelector('option[value="' + initial + '"]')) {
                select.value = initial;
            }
            syncFlag();

            select.addEventListener('change', function () {
                applyLanguage(select.value);
            });
        })();
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/dropdown.js" defer></script>
</nav>

<!-- Overlay et sidebar menu latéral (apparaît au clic sur MENU) -->
<div class="nav-sidebar-overlay" id="navSidebarOverlay"></div>
<aside class="nav-sidebar" id="navSidebar">
    <div class="nav-sidebar-header">
        <a href="<?php echo htmlspecialchars($u_home); ?>" class="nav-sidebar-logo">
            <img src="/image/logo_market.png"
                alt="<?php echo htmlspecialchars(SITE_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?>">
        </a>
        <p class="nav-sidebar-slogan"><?php echo htmlspecialchars(SITE_BRAND_TAGLINE, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="nav-sidebar-content">
        <a href="<?php echo htmlspecialchars($u_nouveautes); ?>" class="nav-sidebar-item nav-sidebar-nouveautes">
            <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
            <span>NOUVEAUTÉS</span>
        </a>
        <a href="<?php echo htmlspecialchars($u_promo); ?>" class="nav-sidebar-item nav-sidebar-promo">
            <i class="fa-solid fa-percent"></i>
            <span>PROMO</span>
        </a>
        <div class="nav-sidebar-categories nav-sidebar-categories--glass">
            <?php if (!empty($nav_megamenu)): ?>
                <p class="nav-sidebar-section-label">Nos rayons</p>
                <ul class="nav-glass-rayons-list" role="list">
                    <?php foreach ($nav_megamenu as $mega): ?>
                        <?php
                        $g = $mega['general'];
                        $gid = (int) $g['id'];
                        $ic_class = function_exists('categorie_fa_icon_class') ? categorie_fa_icon_class($g) : 'fa-solid fa-layer-group';
                        $rayon_href = function_exists('nav_categorie_generale_href')
                            ? nav_categorie_generale_href($gid)
                            : nav_categorie_href($gid);
                        ?>
                        <li>
                            <a class="nav-glass-rayon-card" href="<?php echo htmlspecialchars($rayon_href); ?>">
                                <span class="nav-glass-rayon-icon" aria-hidden="true"><i
                                        class="<?php echo htmlspecialchars($ic_class); ?>"></i></span>
                                <span class="nav-glass-rayon-label"><?php echo htmlspecialchars($g['nom']); ?></span>
                                <span class="nav-glass-rayon-arrow" aria-hidden="true"><i
                                        class="fa-solid fa-arrow-right"></i></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif (!empty($categories_menu)): ?>
                <p class="nav-sidebar-section-label">Catégories</p>
                <?php foreach ($categories_menu as $categorie): ?>
                    <a href="<?php echo htmlspecialchars(nav_categorie_href($categorie['id'])); ?>"
                        class="nav-sidebar-category">
                        <span><?php echo htmlspecialchars($categorie['nom']); ?></span>
                        <span class="nav-sidebar-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="nav-sidebar-section-label">Catalogue</p>
                <a href="<?php echo htmlspecialchars($u_produits); ?>" class="nav-sidebar-category">
                    <span>Tous les produits</span>
                    <span class="nav-sidebar-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="nav-sidebar-footer">
        <a href="<?php echo htmlspecialchars($u_contact); ?>" class="nav-sidebar-footer-btn">
            <i class="fa-solid fa-phone"></i>
            <span>CONTACTEZ<br>NOUS</span>
        </a>
        <a href="<?php echo htmlspecialchars($u_contact); ?>#livraison" class="nav-sidebar-footer-btn">
            <i class="fa-solid fa-truck"></i>
            <span>PORTS ET<br>EXPÉDITION</span>
        </a>
        <a href="<?php echo isset($_SESSION['user_id']) ? '/user/mon-compte.php' : '/choix-connexion.php'; ?>"
            class="nav-sidebar-footer-btn">
            <i class="fa-solid fa-briefcase"></i>
            <span>COMPTE<br>PRO</span>
        </a>
    </div>
</aside>

<!-- Dock bas boutique (tablet/mobile) — même structure que le dock vendeur -->
<div class="shop-bottom-dock" id="shopBottomDock" aria-label="Navigation boutique rapide">
    <div class="shop-dock-bar" id="shopDockBar" aria-label="Navigation boutique réduite">
        <nav class="shop-dock-primary" aria-label="Raccourcis">
            <a href="<?php echo htmlspecialchars($u_home, ENT_QUOTES, 'UTF-8'); ?>"
                class="menu-item menu-item--dock-mini<?php echo $shop_dock_home_act ? ' active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fa-solid fa-house"></i></span>
                <span class="menu-item__text">Accueil</span>
            </a>
            <button type="button"
                id="shopDockSidebarBtn"
                class="menu-item menu-item--dock-mini menu-item--dock-mini-btn"
                aria-label="Ouvrir le menu catalogue">
                <span class="menu-item__icon" aria-hidden="true"><i class="fa-solid fa-bars"></i></span>
                <span class="menu-item__text">Menu</span>
            </button>
            <a href="<?php echo htmlspecialchars($nav_panier_href, ENT_QUOTES, 'UTF-8'); ?>"
                class="menu-item menu-item--dock-mini menu-item--has-badge<?php echo $shop_dock_panier_act ? ' active' : ''; ?>"
                title="<?php echo isset($_SESSION['user_id']) ? 'Panier' : 'Se connecter — panier'; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fa-solid fa-cart-shopping"></i></span>
                <span class="menu-item__text">Panier</span>
                <?php if (isset($_SESSION['user_id']) && $panier_count > 0): ?>
                <span class="menu-item__badge menu-item__badge--dock" aria-label="<?php echo (int) $panier_count; ?> article<?php echo $panier_count > 1 ? 's' : ''; ?> dans le panier"><?php echo $panier_count > 99 ? '99+' : (int) $panier_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo htmlspecialchars($nav_compte_href, ENT_QUOTES, 'UTF-8'); ?>"
                class="menu-item menu-item--dock-mini<?php echo $shop_dock_compte_act ? ' active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                <span class="menu-item__text">Compte</span>
            </a>
        </nav>
    </div>
</div>

<section class="section1">
    <div class="section1-left">
        <button type="button" class="toggle-categories-btn" id="navMenuToggle" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-bars"></i>
            <span>MENU</span>
        </button>
    </div>
    <?php if (isset($section1_mp_page_title) && trim((string) $section1_mp_page_title) !== ''): ?>
        <div class="section1-center section1-center--mp-title">
            <h1 class="section1-page-title">
                <?php echo htmlspecialchars(trim((string) $section1_mp_page_title), ENT_QUOTES, 'UTF-8'); ?>
            </h1>
        </div>
    <?php endif; ?>
    <div class="section1-right">
        <a href="<?php echo htmlspecialchars($u_nouveautes); ?>" class="nav-action-btn nav-btn-nouveautes">
            <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
            <span>NOUVEAUTÉS</span>
        </a>
        <a href="<?php echo htmlspecialchars($u_promo); ?>" class="nav-action-btn nav-btn-promo">
            <i class="fa-solid fa-percent"></i>
            <span>PROMO</span>
        </a>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('navMenuToggle');
        var sidebar = document.getElementById('navSidebar');
        var overlay = document.getElementById('navSidebarOverlay');

        function openSidebarMenu() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('show');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            var icon = toggle ? toggle.querySelector('i') : null;
            if (icon) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        }

        function closeSidebarMenu() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            var icon = toggle ? toggle.querySelector('i') : null;
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }

        window.openBoutiqueNavSidebar = openSidebarMenu;
        window.closeBoutiqueNavSidebar = closeSidebarMenu;

        if (toggle) toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('open')) closeSidebarMenu();
            else openSidebarMenu();
        });
        if (overlay) overlay.addEventListener('click', closeSidebarMenu);

        var dockSidebarBtn = document.getElementById('shopDockSidebarBtn');
        if (dockSidebarBtn) {
            dockSidebarBtn.addEventListener('click', function () {
                if (sidebar && sidebar.classList.contains('open')) closeSidebarMenu();
                else openSidebarMenu();
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth <= 1024) return;
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebarMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            closeSidebarMenu();
        });
    });
</script>
<?php
if (!defined('SITE_BRAND_NAME')) {
    require_once __DIR__ . '/includes/site_brand.php';
}
if (!function_exists('asset_version_query')) {
    require_once __DIR__ . '/includes/asset_version.php';
}
if (!function_exists('boutique_url')) {
    require_once __DIR__ . '/includes/marketplace_helpers.php';
}
$__footer_vd = isset($GLOBALS['BOUTIQUE_VENDEUR_DISPLAY']) && is_array($GLOBALS['BOUTIQUE_VENDEUR_DISPLAY'])
    ? $GLOBALS['BOUTIQUE_VENDEUR_DISPLAY']
    : null;
$__footer_slug = defined('BOUTIQUE_SLUG') ? (string) BOUTIQUE_SLUG : '';
$__footer_is_boutique = ($__footer_slug !== '' && $__footer_vd !== null);
$__footer_nom = $__footer_is_boutique
    ? (trim((string) ($__footer_vd['boutique_nom'] ?? '')) !== '' ? trim((string) $__footer_vd['boutique_nom']) : (defined('BOUTIQUE_NOM') ? BOUTIQUE_NOM : 'Boutique'))
    : '';
$__footer_logo_src = '/image/logo_market.png';
$__footer_logo_alt = SITE_BRAND_NAME;
if ($__footer_is_boutique && !empty($__footer_vd['boutique_logo'])) {
    $__footer_logo_src = '/upload/' . str_replace('\\', '/', $__footer_vd['boutique_logo']);
    $__footer_logo_alt = $__footer_nom;
}
$__footer_home = $__footer_is_boutique ? boutique_url('index.php', $__footer_slug) : '/index.php';
$__footer_panier = $__footer_is_boutique ? boutique_url('panier.php', $__footer_slug) : '/panier.php';
$__footer_produits = $__footer_is_boutique ? boutique_url('produits.php', $__footer_slug) : '/produits.php';
$__footer_contact = $__footer_is_boutique ? boutique_url('contact.php', $__footer_slug) : '/contact.php';
$__footer_tel = '';
$__footer_mail = '';
$__footer_addr = '';
if ($__footer_is_boutique) {
    $__footer_tel = trim((string) ($__footer_vd['telephone'] ?? ''));
    $__footer_mail = trim((string) ($__footer_vd['email'] ?? ''));
    $__footer_addr = trim((string) ($__footer_vd['boutique_adresse'] ?? ''));
    if ($__footer_tel === '') {
        $__footer_tel = '+221 33 870 00 70';
    }
    if ($__footer_mail === '') {
        $__footer_mail = 'contact@colobanes.sn';
    }
    if ($__footer_addr === '') {
        $__footer_addr = 'Adresse non renseignée — contactez la boutique par téléphone ou email.';
    }
} else {
    $__footer_tel = '+221 33 870 00 70';
    $__footer_mail = 'contact@colobanes.sn';
    $__footer_addr = 'Rond point ZAC MBAO, Dakar';
}

$__footer_social_cfg = file_exists(__DIR__ . '/config/social.php') ? require __DIR__ . '/config/social.php' : [];
$__footer_social_cfg = is_array($__footer_social_cfg) ? $__footer_social_cfg : [];
$__wa_t = trim((string) ($__footer_social_cfg['whatsapp'] ?? ''));
$__ig_t = trim((string) ($__footer_social_cfg['instagram'] ?? ''));
$__fb_t = trim((string) ($__footer_social_cfg['facebook'] ?? ''));
$__footer_show_social = ($__wa_t !== '' && preg_replace('/[^0-9]/', '', $__wa_t) !== '') || $__ig_t !== '' || $__fb_t !== '';
?>
<?php if ($__footer_show_social): ?>
<link rel="stylesheet" href="/css/site-social-links.css<?php echo asset_version_query(); ?>">
<?php endif; ?>
<footer class="footer">
    <div class="container footer_container">
        <div class="footer_item">
            <a href="<?php echo htmlspecialchars($__footer_home, ENT_QUOTES, 'UTF-8'); ?>" class="footer_logo">
                <img src="<?php echo htmlspecialchars($__footer_logo_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($__footer_logo_alt, ENT_QUOTES, 'UTF-8'); ?>" class="footer_logo_img" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                <span class="footer_logo_fallback"><i class="fas fa-store"></i> <?php echo htmlspecialchars($__footer_is_boutique ? $__footer_nom : SITE_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <div class="footer_p">
                <?php echo $__footer_is_boutique
                    ? htmlspecialchars($__footer_nom . ' — boutique partenaire', ENT_QUOTES, 'UTF-8')
                    : (SITE_BRAND_NAME . ' — marketplace Sénégal'); ?>
            </div>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl"><?php echo $__footer_is_boutique ? 'La boutique' : 'Contact'; ?></h3>
            <ul class="footer_list">
                <li class="li footer_list_item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $__footer_tel), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($__footer_tel, ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
                <li class="li footer_list_item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo nl2br(htmlspecialchars($__footer_addr, ENT_QUOTES, 'UTF-8')); ?></span>
                </li>
                <li class="li footer_list_item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo htmlspecialchars($__footer_mail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($__footer_mail, ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
            </ul>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl">Liens rapides</h3>
            <ul class="footer_list">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])): ?>
                    <li class="li footer_list_item">
                        <a href="/user/mon-compte.php">Mon compte</a>
                    </li>
                    <li class="li footer_list_item">
                        <a href="/user/deconnexion.php">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="li footer_list_item">
                        <a href="/choix-connexion.php">Connexion</a>
                    </li>
                    <li class="li footer_list_item">
                        <a href="/user/inscription.php">Inscription</a>
                    </li>
                <?php endif; ?>
                <li class="li footer_list_item">
                    <a href="<?php echo htmlspecialchars($__footer_panier, ENT_QUOTES, 'UTF-8'); ?>">Panier</a>
                </li>
                <li class="li footer_list_item">
                    <a href="<?php echo htmlspecialchars($__footer_produits, ENT_QUOTES, 'UTF-8'); ?>">Produits</a>
                </li>
                <li class="li footer_list_item">
                    <a href="<?php echo htmlspecialchars($__footer_contact, ENT_QUOTES, 'UTF-8'); ?>">Contact</a>
                </li>
            </ul>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl">Informations légales</h3>
            <ul class="footer_list">
                <li class="li footer_list_item">
                    <a href="/politique-confidentialite.php">Politique de confidentialité</a>
                </li>
                <li class="li footer_list_item">
                    <a href="/conditions-utilisation.php">Conditions d'utilisation</a>
                </li>
            </ul>
        </div>
    </div>
    <?php if ($__footer_show_social): ?>
    <div class="footer_social_wrap">
        <div class="container footer_social_inner">
            <p class="footer_social_title">Suivez-nous</p>
            <?php
            $social_config = $__footer_social_cfg;
            include __DIR__ . '/includes/social_links_block.php';
            ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="footer_bottom">
        <div class="container footer_bottom_container">
            <p class="footer_copy">
                <?php echo $__footer_is_boutique
                    ? '2026 ' . htmlspecialchars($__footer_nom, ENT_QUOTES, 'UTF-8') . ' | Tous droits réservés'
                    : '2026 COLObanes | Tous droits réservés'; ?>
            </p>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/includes/social_floating.php'; ?>

<?php
/**
 * Liens réseaux sociaux (WhatsApp, Instagram, Facebook) — pour footer, contact, etc.
 * Nécessite variables.css / Font Awesome (fab) si icônes brands.
 */
if (!isset($social_config) || !is_array($social_config)) {
    $social_config = [];
    if (file_exists(__DIR__ . '/../config/social.php')) {
        $social_config = require __DIR__ . '/../config/social.php';
    }
}
$wa_raw = $social_config['whatsapp'] ?? '';
$instagram = isset($social_config['instagram']) ? trim((string) $social_config['instagram']) : '';
$facebook = isset($social_config['facebook']) ? trim((string) $social_config['facebook']) : '';
$whatsapp_url = '';
if ($wa_raw !== '') {
    $whatsapp_clean = preg_replace('/[^0-9]/', '', (string) $wa_raw);
    if ($whatsapp_clean !== '') {
        $whatsapp_url = 'https://wa.me/' . $whatsapp_clean;
    }
}
$has_social = ($whatsapp_url !== '' || $instagram !== '' || $facebook !== '');
if (!$has_social) {
    return;
}
?>
<div class="site-social-links" role="list" aria-label="Nos réseaux sociaux">
    <?php if ($whatsapp_url !== ''): ?>
    <a class="site-social-links__a site-social-links__a--wa" href="<?php echo htmlspecialchars($whatsapp_url, ENT_QUOTES, 'UTF-8'); ?>"
        target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp" role="listitem">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <?php if ($instagram !== ''): ?>
    <a class="site-social-links__a site-social-links__a--ig" href="<?php echo htmlspecialchars($instagram, ENT_QUOTES, 'UTF-8'); ?>"
        target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram" role="listitem">
        <i class="fab fa-instagram" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <?php if ($facebook !== ''): ?>
    <a class="site-social-links__a site-social-links__a--fb" href="<?php echo htmlspecialchars($facebook, ENT_QUOTES, 'UTF-8'); ?>"
        target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook" role="listitem">
        <i class="fab fa-facebook-f" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
</div>

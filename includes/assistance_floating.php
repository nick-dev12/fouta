<?php
/**
 * Assistant plateforme (Jotform Agent) — widget injecté par le script CDN (aucun bouton HTML personnalisé).
 */
if (defined('ASSISTANCE_FLOATING_LOADED')) {
    return;
}
define('ASSISTANCE_FLOATING_LOADED', true);

$assistance_config = file_exists(__DIR__ . '/../config/assistance.php')
    ? require __DIR__ . '/../config/assistance.php'
    : [];
$assistance_config = is_array($assistance_config) ? $assistance_config : [];

$jotform_embed = isset($assistance_config['jotform_embed_js']) ? trim((string) $assistance_config['jotform_embed_js']) : '';

if ($jotform_embed === '') {
    return;
}
?>
<script src="<?php echo htmlspecialchars($jotform_embed, ENT_QUOTES, 'UTF-8'); ?>" defer></script>

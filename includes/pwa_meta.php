<?php
/**
 * Meta tags et liens pour l'installation PWA (Progressive Web App)
 * À inclure dans le <head> des pages client
 */
if (!function_exists('get_asset_version')) {
    require_once __DIR__ . '/asset_version.php';
}
$asset_version = get_asset_version();
?>
<?php include __DIR__ . '/favicon.php'; ?>
<meta name="theme-color" content="#3564a6">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<?php if (!defined('SITE_BRAND_NAME')) { require_once __DIR__ . '/site_brand.php'; } ?>
<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(SITE_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="application-name" content="<?php echo htmlspecialchars(SITE_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="manifest" href="/manifest.json">
<script>
(function() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function() {});
        });
    }
})();
</script>

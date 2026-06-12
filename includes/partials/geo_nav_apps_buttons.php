<?php
/**
 * Boutons navigation livraison (Google Maps, Yango, Yassir, WhatsApp).
 * Variables requises : $geo_nav_lat, $geo_nav_lng
 * Optionnel : $geo_nav_label (texte WhatsApp), $geo_nav_wrap_class
 */
require_once __DIR__ . '/../geo_location_service.php';

if (!isset($geo_nav_lat, $geo_nav_lng) || !geo_coords_valid((float) $geo_nav_lat, (float) $geo_nav_lng)) {
    return;
}
$geo_nav_lat = (float) $geo_nav_lat;
$geo_nav_lng = (float) $geo_nav_lng;
$geo_nav_label = isset($geo_nav_label) ? (string) $geo_nav_label : 'Position client';
$geo_nav_wrap_class = isset($geo_nav_wrap_class) ? (string) $geo_nav_wrap_class : 'geo-nav-apps';
?>
<div class="<?php echo htmlspecialchars($geo_nav_wrap_class, ENT_QUOTES, 'UTF-8'); ?>">
    <a href="<?php echo htmlspecialchars(geo_nav_google_maps_dir($geo_nav_lat, $geo_nav_lng), ENT_QUOTES, 'UTF-8'); ?>"
        class="geo-nav-app geo-nav-app--gmaps" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-google"></i> Google Maps
    </a>
    <a href="<?php echo htmlspecialchars(geo_nav_yango($geo_nav_lat, $geo_nav_lng), ENT_QUOTES, 'UTF-8'); ?>"
        class="geo-nav-app geo-nav-app--yango" target="_blank" rel="noopener noreferrer">
        <i class="fas fa-car"></i> Yango
    </a>
    <a href="<?php echo htmlspecialchars(geo_nav_yassir($geo_nav_lat, $geo_nav_lng), ENT_QUOTES, 'UTF-8'); ?>"
        class="geo-nav-app geo-nav-app--yassir" target="_blank" rel="noopener noreferrer">
        <i class="fas fa-taxi"></i> Yassir
    </a>
    <a href="<?php echo htmlspecialchars(geo_share_whatsapp($geo_nav_lat, $geo_nav_lng, $geo_nav_label), ENT_QUOTES, 'UTF-8'); ?>"
        class="geo-nav-app geo-nav-app--whatsapp" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-whatsapp"></i> WhatsApp
    </a>
</div>

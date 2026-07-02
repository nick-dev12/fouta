<?php
/**
 * Carte boutique style « sac shopping » (icône marketplace).
 * Variable : $boutique (array admin) ou $bt_card (array préparé)
 */
if (!function_exists('marketplace_boutique_prepare_card')) {
    require_once dirname(__DIR__) . '/marketplace_boutique_card_helpers.php';
}

$card = null;
if (isset($bt_card) && is_array($bt_card) && !empty($bt_card['nom'])) {
    $card = $bt_card;
} elseif (!empty($boutique) && is_array($boutique)) {
    $card = marketplace_boutique_prepare_card($boutique);
} else {
    return;
}

$theme = $card['theme'];
$style_vars = '--bt-main:' . htmlspecialchars($theme['main'], ENT_QUOTES, 'UTF-8')
    . ';--bt-dark:' . htmlspecialchars($theme['dark'], ENT_QUOTES, 'UTF-8')
    . ';--bt-accent:' . htmlspecialchars($theme['accent'], ENT_QUOTES, 'UTF-8')
    . ';--bt-band:' . htmlspecialchars($theme['band'], ENT_QUOTES, 'UTF-8');
?>
<article class="mp-bt-card<?php echo !empty($theme['has_custom']) ? ' mp-bt-card--themed' : ''; ?>"
    style="<?php echo $style_vars; ?>" role="listitem">
    <div class="mp-bt-bag" aria-hidden="true">
        <div class="mp-bt-bag__handle"></div>
        <div class="mp-bt-bag__shell">
            <div class="mp-bt-bag__logo">
                <?php if ($card['logo_url'] !== ''): ?>
                    <img src="<?php echo htmlspecialchars($card['logo_url'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        onerror="this.remove();">
                <?php endif; ?>
                <svg class="mp-bt-bag__fallback" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M18 24c0-7.7 6.3-14 14-14s14 6.3 14 14" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                    <rect x="10" y="24" width="44" height="32" rx="8" fill="currentColor" opacity="0.18"/>
                </svg>
            </div>
            <div class="mp-bt-bag__band"></div>
        </div>
    </div>

    <div class="mp-bt-card__body">
        <h3 class="mp-bt-card__title"><?php echo htmlspecialchars($card['nom'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php if ($card['distance_km'] !== null && function_exists('geo_format_distance')): ?>
            <p class="mp-bt-card__dist">
                <i class="fas fa-route" aria-hidden="true"></i>
                <?php echo htmlspecialchars(geo_format_distance((float) $card['distance_km']), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <div class="mp-bt-card__actions">
            <a href="<?php echo htmlspecialchars($card['vitrine_href'], ENT_QUOTES, 'UTF-8'); ?>"
                class="mp-bt-card__btn mp-bt-card__btn--primary">
                <i class="fas fa-store" aria-hidden="true"></i> Voir la boutique
            </a>
            <?php if ($card['maps_url'] !== '' || ($card['lat'] !== null && $card['lng'] !== null)): ?>
                <?php
                $bt_geo_label = 'Boutique — ' . $card['nom'];
                $bt_maps_url = $card['maps_url'];
                if ($bt_maps_url === '' && $card['lat'] !== null && $card['lng'] !== null) {
                    $bt_maps_url = 'https://www.google.com/maps/dir/?api=1&destination='
                        . rawurlencode((string) $card['lat'] . ',' . (string) $card['lng'])
                        . '&travelmode=driving';
                }
                ?>
                <button type="button"
                    class="mp-bt-card__btn mp-bt-card__btn--ghost js-geo-open-maps"
                    title="Ouvrir avec une application de navigation"
                    data-lat="<?php echo $card['lat'] !== null ? htmlspecialchars((string) $card['lat'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    data-lng="<?php echo $card['lng'] !== null ? htmlspecialchars((string) $card['lng'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    data-label="<?php echo htmlspecialchars($bt_geo_label, ENT_QUOTES, 'UTF-8'); ?>"
                    data-maps-url="<?php echo htmlspecialchars($bt_maps_url, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-location-dot" aria-hidden="true"></i> Localisation
                </button>
            <?php endif; ?>
        </div>
    </div>
</article>

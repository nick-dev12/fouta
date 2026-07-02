<?php
/**
 * Catalogue des boutiques partenaires — recherche + carte de proximité.
 */

session_start();

require_once __DIR__ . '/conn/conn.php';
require_once __DIR__ . '/models/model_boutiques_marketplace.php';
require_once __DIR__ . '/includes/geo_location_service.php';
require_once __DIR__ . '/includes/marketplace_country_filter.php';
require_once __DIR__ . '/includes/marketplace_helpers.php';
require_once __DIR__ . '/includes/marketplace_boutique_card_helpers.php';
require_once __DIR__ . '/includes/asset_version.php';
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/site_brand.php';

$base = get_site_base_url();
$seo_title = 'Boutiques partenaires — ' . SITE_BRAND_NAME . ' | Marketplace Sénégal';
$seo_description = 'Découvrez les boutiques vendeurs partenaires sur ' . SITE_BRAND_NAME . '. Recherchez par nom et explorez les commerces proches de vous sur la carte.';
$seo_keywords = site_brand_seo_keywords_default() . ', boutiques partenaires, vendeurs, marketplace, géolocalisation';
$seo_canonical = $base . '/boutiques.php';

$search = trim((string) ($_GET['q'] ?? ''));
$geo_loc = geo_session_get_location();
$geo_error = !empty($_GET['geo_error']);
$country = marketplace_get_selected_country_code();

$rayon_proche = 4;
$lat_proche = $geo_loc !== null ? (float) $geo_loc['lat'] : null;
$lng_proche = $geo_loc !== null ? (float) $geo_loc['lng'] : null;

$boutiques = marketplace_list_boutiques($search, 100, 0, $country, null, null, 0, false);
$nb_boutiques = marketplace_count_boutiques($search, $country, null, null, 0, false);

$boutiques_proches = [];
if ($geo_loc !== null) {
    $boutiques_proches = marketplace_list_boutiques(
        '',
        50,
        0,
        $country,
        $lat_proche,
        $lng_proche,
        (float) $rayon_proche,
        true
    );
}
$map_payload = marketplace_boutiques_map_payload($boutiques_proches);
$nb_proches = count($boutiques_proches);

$redirect_map = '/boutiques.php';
if ($search !== '') {
    $redirect_map .= '?q=' . rawurlencode($search);
}
$redirect_map .= (strpos($redirect_map, '?') !== false ? '&' : '?') . 'open_map=1';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/platform-share-modal.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/boutiques-marketplace.css<?php echo asset_version_query(); ?>">
</head>

<body class="mp-bt-body">
    <?php include 'nav_bar.php'; ?>

    <main class="mp-bt-page">
        <header class="mp-bt-page__hero">
            <div class="mp-bt-page__hero-inner">
                <p class="mp-bt-page__eyebrow"><i class="fas fa-store" aria-hidden="true"></i> Marketplace</p>
                <h1 class="mp-bt-page__title">Boutiques partenaires</h1>
            </div>
        </header>

        <div class="mp-bt-page__container">
            <?php if ($geo_error): ?>
                <div class="mp-bt-alert mp-bt-alert--error" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    Impossible d'utiliser votre position. Réessayez dans un instant.
                </div>
            <?php endif; ?>

            <form class="mp-bt-toolbar" method="get" action="/boutiques.php" role="search">
                <label class="mp-bt-search-v2" for="mpBtSearch">
                    <span class="mp-bt-search-v2__label">Rechercher une boutique</span>
                    <span class="mp-bt-search-v2__wrap">
                        <i class="fas fa-search mp-bt-search-v2__ico" aria-hidden="true"></i>
                        <input type="search"
                            id="mpBtSearch"
                            name="q"
                            class="mp-bt-search-v2__input"
                            value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Nom, région, adresse…"
                            autocomplete="off">
                        <button type="submit" class="mp-bt-search-v2__btn">
                            <span>Rechercher</span>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </span>
                </label>
            </form>

            <section class="mp-bt-nearby" aria-labelledby="mpBtNearbyTitle">
                <div class="mp-bt-nearby__glow" aria-hidden="true"></div>
                <div class="mp-bt-nearby__inner">
                    <div class="mp-bt-nearby__copy">
                        <p class="mp-bt-nearby__eyebrow"><i class="fas fa-location-dot" aria-hidden="true"></i> Autour de vous</p>
                        <h2 id="mpBtNearbyTitle">Boutiques proches de chez moi</h2>
                        <?php if ($geo_loc !== null && $nb_proches === 0): ?>
                            <p>Aucune boutique géolocalisée trouvée dans un rayon de <?php echo (int) $rayon_proche; ?> km pour le moment.</p>
                        <?php elseif ($geo_loc !== null && $nb_proches > 0): ?>
                            <p>
                                <strong><?php echo (int) $nb_proches; ?></strong>
                                boutique<?php echo $nb_proches > 1 ? 's' : ''; ?> à moins de <?php echo (int) $rayon_proche; ?> km.
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($geo_loc === null): ?>
                        <div class="mp-bt-nearby__actions">
                            <form method="POST" action="/set-location.php" id="geo-locate-form">
                                <input type="hidden" name="geo_lat" id="geo_lat" value="">
                                <input type="hidden" name="geo_lng" id="geo_lng" value="">
                                <input type="hidden" name="geo_precision" id="geo_precision" value="">
                                <input type="hidden" name="geo_source" id="geo_source" value="gps">
                                <input type="hidden" name="redirect"
                                    value="<?php echo htmlspecialchars($redirect_map, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="button" class="mp-bt-nearby__btn" id="btn-geo-locate">
                                    <i class="fas fa-location-crosshairs" aria-hidden="true"></i>
                                    Voir les boutiques proches de chez moi
                                </button>
                            </form>
                            <div id="geo-status" class="mp-bt-geo-status" hidden></div>
                        </div>
                    <?php else: ?>
                        <div class="mp-bt-nearby__actions">
                            <button type="button"
                                class="mp-bt-nearby__btn mp-bt-nearby__btn--map"
                                id="mpBtOpenMap"
                                <?php echo empty($map_payload) ? 'disabled' : ''; ?>>
                                <i class="fas fa-map-location-dot" aria-hidden="true"></i>
                                Voir les boutiques proches de chez moi
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <p class="mp-bt-results-count">
                <strong><?php echo (int) $nb_boutiques; ?></strong>
                boutique<?php echo $nb_boutiques > 1 ? 's' : ''; ?> partenaire<?php echo $nb_boutiques > 1 ? 's' : ''; ?>
                <?php if ($search !== ''): ?>
                    pour « <?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?> »
                <?php endif; ?>
            </p>

            <?php if (empty($boutiques)): ?>
                <div class="mp-bt-empty">
                    <i class="fas fa-store-slash" aria-hidden="true"></i>
                    <h2>Aucune boutique trouvée</h2>
                    <p>
                        <?php if ($search !== ''): ?>
                            Essayez un autre mot-clé ou consultez toutes les boutiques.
                        <?php else: ?>
                            Les boutiques vendeurs apparaîtront ici dès leur inscription.
                        <?php endif; ?>
                    </p>
                    <?php if ($search !== ''): ?>
                        <a href="/boutiques.php" class="mp-bt-empty__link">Voir toutes les boutiques</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mp-bt-grid mp-bt-grid--cards" role="list">
                    <?php foreach ($boutiques as $boutique):
                        include __DIR__ . '/includes/partials/boutique_marketplace_card.php';
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="mp-bt-map-modal" id="mpBtMapModal" hidden aria-hidden="true">
        <div class="mp-bt-map-modal__backdrop" data-map-close></div>
        <div class="mp-bt-map-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mpBtMapTitle">
            <header class="mp-bt-map-modal__head">
                <div>
                    <p class="mp-bt-map-modal__eyebrow">Proximité</p>
                    <h2 id="mpBtMapTitle">Boutiques proches de vous</h2>
                </div>
                <button type="button" class="mp-bt-map-modal__close" data-map-close aria-label="Fermer">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>
            <div class="mp-bt-map-modal__layout">
                <div class="mp-bt-map-modal__map-wrap">
                    <button type="button"
                        class="mp-bt-map-list-toggle"
                        id="mpBtMapListToggle"
                        aria-expanded="false"
                        aria-controls="mpBtMapList">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>Boutiques trouvées</span>
                        <span class="mp-bt-map-list-toggle__count" id="mpBtMapListCount">0</span>
                    </button>

                    <aside class="mp-bt-map-list" id="mpBtMapList" aria-label="Liste des boutiques proches">
                        <header class="mp-bt-map-list__head">
                            <h3>Boutiques trouvées</h3>
                            <button type="button" class="mp-bt-map-list__close" id="mpBtMapListClose" aria-label="Fermer la liste">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </header>
                        <ul class="mp-bt-map-list__items" id="mpBtMapListItems" role="list"></ul>
                    </aside>

                    <div id="mpBtMapCanvas" class="mp-bt-map-modal__map" aria-label="Carte des boutiques"></div>

                    <aside class="mp-bt-map-detail" id="mpBtMapSide" hidden aria-label="Détail boutique">
                        <header class="mp-bt-map-detail__head">
                            <button type="button" class="mp-bt-map-detail__close" data-map-detail-close aria-label="Fermer">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </header>
                        <div class="mp-bt-map-side__detail" id="mpBtMapSideDetail">
                            <div class="mp-bt-map-side__logo" id="mpBtMapSideLogo"></div>
                            <h3 id="mpBtMapSideName"></h3>
                            <p class="mp-bt-map-side__dist" id="mpBtMapSideDist"></p>
                            <p class="mp-bt-map-side__addr" id="mpBtMapSideAddr"></p>
                            <div class="mp-bt-map-side__actions">
                                <a href="#" class="mp-bt-map-side__btn mp-bt-map-side__btn--primary" id="mpBtMapSideVisit" target="_blank" rel="noopener">
                                    <i class="fas fa-store" aria-hidden="true"></i> Voir la boutique
                                </a>
                                <a href="#" class="mp-bt-map-side__btn mp-bt-map-side__btn--ghost" id="mpBtMapSideMaps" target="_blank" rel="noopener">
                                    <i class="fab fa-google" aria-hidden="true"></i> Google Maps
                                </a>
                                <button type="button" class="mp-bt-map-side__btn mp-bt-map-side__btn--share" id="mpBtMapShareShop">
                                    <i class="fas fa-share-nodes" aria-hidden="true"></i> Partager la boutique
                                </button>
                                <button type="button" class="mp-bt-map-side__btn mp-bt-map-side__btn--share" id="mpBtMapShareGeo">
                                    <i class="fas fa-location-arrow" aria-hidden="true"></i> Partager la localisation
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="mpBtMapData"><?php
        echo json_encode([
            'user' => $geo_loc ? ['lat' => (float) $geo_loc['lat'], 'lng' => (float) $geo_loc['lng']] : null,
            'boutiques' => $map_payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?></script>

    <?php include 'footer.php'; ?>
    <?php require __DIR__ . '/includes/partials/platform_share_modal.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="/js/geo-location.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/platform-share-modal.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/geo-nav-apps.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/boutiques-proches-map.js<?php echo asset_version_query(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.GeoLocationCapture) {
                return;
            }
            var locateForm = document.getElementById('geo-locate-form');
            if (locateForm) {
                window.GeoLocationCapture.init({
                    latInput: 'geo_lat',
                    lngInput: 'geo_lng',
                    precisionInput: 'geo_precision',
                    sourceInput: 'geo_source',
                    statusEl: 'geo-status',
                    button: 'btn-geo-locate',
                    auto: false,
                    onSuccess: function () { locateForm.submit(); }
                });
            }

            var urlParams = new URLSearchParams(window.location.search || '');
            var shouldOpenMap = urlParams.get('open_map') === '1';
            if (shouldOpenMap && typeof window.openBoutiquesMapModal === 'function') {
                var mapBtn = document.getElementById('mpBtOpenMap');
                if (!mapBtn || !mapBtn.disabled) {
                    window.openBoutiquesMapModal();
                }
            }
        });
    </script>
</body>

</html>

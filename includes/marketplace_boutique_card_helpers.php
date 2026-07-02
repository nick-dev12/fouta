<?php
/**
 * Helpers affichage cartes boutique marketplace.
 */

if (!function_exists('marketplace_boutique_public_url')) {
    function marketplace_boutique_public_url(string $slug, string $nom = ''): string
    {
        if (!function_exists('get_site_base_url')) {
            require_once __DIR__ . '/site_url.php';
        }
        if (!function_exists('boutique_url')) {
            require_once __DIR__ . '/marketplace_helpers.php';
        }
        $slug = trim($slug);
        if ($slug === '') {
            return '/boutiques.php';
        }
        $url = rtrim(get_site_base_url(), '/') . boutique_url('index.php', $slug);
        $nom = trim($nom);
        if ($nom !== '') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'nom=' . rawurlencode($nom);
        }
        return $url;
    }
}

if (!function_exists('marketplace_boutique_card_theme')) {
    /**
     * @return array{main:string,dark:string,accent:string,has_custom:bool}
     */
    function marketplace_boutique_card_theme(array $boutique): array
    {
        if (!function_exists('boutique_normalize_hex_color')) {
            require_once __DIR__ . '/boutique_vendeur_display.php';
        }
        $main = boutique_normalize_hex_color($boutique['boutique_couleur_principale'] ?? '');
        $accent = boutique_normalize_hex_color($boutique['boutique_couleur_accent'] ?? '');
        $main_hex = $main !== '' ? $main : '#3564a6';
        $accent_hex = $accent !== '' ? $accent : '#2d5690';
        return [
            'main' => $main_hex,
            'dark' => '#0d0d0d',
            'accent' => $accent !== '' ? $accent : '#ff6b35',
            'band' => $accent !== '' ? $accent_hex : '#2d5690',
            'has_custom' => ($main !== '' || $accent !== ''),
        ];
    }
}

if (!function_exists('marketplace_boutique_logo_url')) {
    function marketplace_boutique_logo_url(array $boutique): string
    {
        $logo_rel = trim((string) ($boutique['boutique_logo'] ?? ''));
        if ($logo_rel === '') {
            return '';
        }
        return '/upload/' . str_replace('\\', '/', $logo_rel);
    }
}

if (!function_exists('marketplace_boutique_prepare_card')) {
    /**
     * @return array<string, mixed>|null
     */
    function marketplace_boutique_prepare_card(array $boutique): ?array
    {
        if (!function_exists('boutique_vitrine_entry_href')) {
            require_once __DIR__ . '/marketplace_helpers.php';
        }
        if (!function_exists('boutique_pickup_info_from_admin')) {
            require_once __DIR__ . '/boutique_vendeur_display.php';
        }

        $slug = trim((string) ($boutique['boutique_slug'] ?? ''));
        if ($slug === '') {
            return null;
        }

        $nom = trim((string) ($boutique['boutique_nom'] ?? ''));
        if ($nom === '') {
            $nom = 'Boutique';
        }

        $theme = marketplace_boutique_card_theme($boutique);
        $pickup = boutique_pickup_info_from_admin($boutique, $nom);
        $share_url = marketplace_boutique_public_url($slug, $nom);
        $share_title = 'Découvrez « ' . $nom . ' » sur COLObanes';
        $share_text = $share_title;

        if (!function_exists('geo_coords_valid')) {
            require_once __DIR__ . '/geo_location_service.php';
        }

        $has_geo = $pickup['lat'] !== null
            && $pickup['lng'] !== null
            && geo_coords_valid((float) $pickup['lat'], (float) $pickup['lng']);

        $maps_dir_url = '';
        $geo_share_url = '';
        if ($has_geo) {
            $maps_dir_url = geo_nav_google_maps_dir((float) $pickup['lat'], (float) $pickup['lng']);
            $geo_share_url = 'https://maps.google.com/?q=' . $pickup['lat'] . ',' . $pickup['lng'];
        }

        $nb_produits = (int) ($boutique['nb_produits'] ?? 0);

        return [
            'id' => (int) ($boutique['id'] ?? 0),
            'nom' => $nom,
            'slug' => $slug,
            'region' => trim((string) ($boutique['boutique_region'] ?? '')),
            'adresse' => $pickup['adresse_ligne'],
            'telephone' => trim((string) ($boutique['telephone'] ?? '')),
            'logo_url' => marketplace_boutique_logo_url($boutique),
            'vitrine_href' => boutique_vitrine_entry_href($slug),
            'maps_url' => $maps_dir_url,
            'share_url' => $share_url,
            'share_title' => $share_title,
            'share_text' => $share_text,
            'geo_share_url' => $geo_share_url,
            'geo_share_title' => 'Point de retrait — ' . $nom,
            'lat' => $has_geo ? (float) $pickup['lat'] : null,
            'lng' => $has_geo ? (float) $pickup['lng'] : null,
            'has_geo' => $has_geo,
            'nb_produits' => $nb_produits,
            'distance_km' => isset($boutique['distance_km']) ? (float) $boutique['distance_km'] : null,
            'theme' => $theme,
        ];
    }
}

if (!function_exists('marketplace_boutiques_map_payload')) {
    /**
     * @param array<int, array<string, mixed>> $boutiques
     * @return array<int, array<string, mixed>>
     */
    function marketplace_boutiques_map_payload(array $boutiques): array
    {
        if (!function_exists('geo_coords_valid')) {
            require_once __DIR__ . '/geo_location_service.php';
        }

        $out = [];
        foreach ($boutiques as $boutique) {
            if (!is_array($boutique)) {
                continue;
            }
            $card = marketplace_boutique_prepare_card($boutique);
            if ($card === null || $card['lat'] === null || $card['lng'] === null) {
                continue;
            }
            if (!geo_coords_valid((float) $card['lat'], (float) $card['lng'])) {
                continue;
            }
            $out[] = [
                'id' => $card['id'],
                'nom' => $card['nom'],
                'slug' => $card['slug'],
                'logo' => $card['logo_url'],
                'lat' => (float) $card['lat'],
                'lng' => (float) $card['lng'],
                'distance_km' => $card['distance_km'],
                'adresse' => $card['adresse'],
                'region' => $card['region'],
                'share_url' => $card['share_url'],
                'share_title' => $card['share_title'],
                'share_text' => $card['share_text'],
                'geo_share_url' => $card['geo_share_url'],
                'geo_share_title' => $card['geo_share_title'],
                'vitrine_href' => $card['vitrine_href'],
                'maps_url' => $card['maps_url'],
                'theme_main' => $card['theme']['main'],
            ];
        }
        return $out;
    }
}

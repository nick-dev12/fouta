<?php
/**
 * Boutiques marketplace — liste publique, recherche, filtre proximité.
 * Programmation procédurale uniquement.
 */

if (!function_exists('marketplace_boutiques_search_where')) {
    /**
     * @param array<string, mixed> $params
     */
    function marketplace_boutiques_search_where(string $search, ?string $country, array &$params): string
    {
        $parts = [
            "a.role = 'vendeur'",
            "a.statut = 'actif'",
            "a.boutique_slug IS NOT NULL",
            "TRIM(a.boutique_slug) <> ''",
            "TRIM(COALESCE(a.boutique_nom, '')) <> ''",
        ];

        if ($country !== null && $country !== '') {
            if (!function_exists('geo_column_exists')) {
                require_once dirname(__DIR__) . '/includes/geo_location_service.php';
            }
            if (geo_column_exists('admin', 'boutique_country')) {
                $parts[] = 'a.boutique_country = :mp_country';
                $params['mp_country'] = strtoupper(trim($country));
            }
        }

        if (!function_exists('marketplace_region_filter_applies')) {
            require_once dirname(__DIR__) . '/includes/marketplace_region_filter.php';
        }
        if (marketplace_region_filter_applies()) {
            $region_code = marketplace_get_selected_region_code();
            if ($region_code !== null && $region_code !== '') {
                $parts[] = 'a.boutique_region = :mp_region_filter';
                $params['mp_region_filter'] = $region_code;
            }
        }

        $search = trim($search);
        if ($search !== '') {
            $parts[] = '(
                a.boutique_nom LIKE :mp_q1
                OR a.boutique_slug LIKE :mp_q2
                OR COALESCE(a.boutique_region, \'\') LIKE :mp_q3
                OR COALESCE(a.boutique_adresse, \'\') LIKE :mp_q4
            )';
            $like = '%' . $search . '%';
            $params['mp_q1'] = $like;
            $params['mp_q2'] = $like;
            $params['mp_q3'] = $like;
            $params['mp_q4'] = $like;
        }

        return implode(' AND ', $parts);
    }
}

if (!function_exists('marketplace_count_boutiques')) {
    function marketplace_count_boutiques(
        string $search = '',
        ?string $country = null,
        ?float $lat = null,
        ?float $lng = null,
        float $rayon_km = 0.0,
        bool $proche_only = false
    ): int {
        global $db;

        if (!isset($db) || !($db instanceof PDO)) {
            return 0;
        }

        if (!function_exists('geo_boutiques_ready')) {
            require_once dirname(__DIR__) . '/includes/geo_location_service.php';
        }

        $params = [];
        $where = marketplace_boutiques_search_where($search, $country, $params);

        $geo_sql = '';
        $having = '';
        if ($proche_only || ($lat !== null && $lng !== null && geo_coords_valid($lat, $lng))) {
            if (!geo_boutiques_ready() || $lat === null || $lng === null || !geo_coords_valid($lat, $lng)) {
                return $proche_only ? 0 : marketplace_count_boutiques($search, $country, null, null, 0, false);
            }

            $distance_expr = geo_sql_distance_expr('a.boutique_latitude', 'a.boutique_longitude');
            $geo_sql = ", $distance_expr AS distance_km";
            $where .= '
                AND a.boutique_latitude IS NOT NULL
                AND a.boutique_longitude IS NOT NULL
            ';

            if ($rayon_km > 0) {
                $having = ' HAVING distance_km <= :mp_rayon ';
                $params['mp_rayon'] = $rayon_km;
            }

            $params['geo_lat'] = $lat;
            $params['geo_lat2'] = $lat;
            $params['geo_lng'] = $lng;
        }

        if ($proche_only && $geo_sql === '') {
            return 0;
        }

        try {
            if ($geo_sql !== '') {
                $sql = "
                    SELECT COUNT(*) FROM (
                        SELECT a.id $geo_sql
                        FROM admin a
                        WHERE $where
                        $having
                    ) AS mp_b_cnt
                ";
            } else {
                $sql = "SELECT COUNT(*) FROM admin a WHERE $where";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[marketplace_count_boutiques] ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('marketplace_list_boutiques')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function marketplace_list_boutiques(
        string $search = '',
        int $limit = 24,
        int $offset = 0,
        ?string $country = null,
        ?float $lat = null,
        ?float $lng = null,
        float $rayon_km = 0.0,
        bool $proche_only = false
    ): array {
        global $db;

        if (!isset($db) || !($db instanceof PDO)) {
            return [];
        }

        if (!function_exists('geo_boutiques_ready')) {
            require_once dirname(__DIR__) . '/includes/geo_location_service.php';
        }

        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $params = [];
        $where = marketplace_boutiques_search_where($search, $country, $params);

        $distance_select = 'NULL AS distance_km';
        $having = '';
        $order = 'a.boutique_nom ASC, a.id ASC';

        if ($proche_only || ($lat !== null && $lng !== null && geo_coords_valid($lat, $lng))) {
            if (!geo_boutiques_ready() || $lat === null || $lng === null || !geo_coords_valid($lat, $lng)) {
                return $proche_only
                    ? []
                    : marketplace_list_boutiques($search, $limit, $offset, $country, null, null, 0, false);
            }

            $distance_expr = geo_sql_distance_expr('a.boutique_latitude', 'a.boutique_longitude');
            $distance_select = "$distance_expr AS distance_km";
            $where .= '
                AND a.boutique_latitude IS NOT NULL
                AND a.boutique_longitude IS NOT NULL
            ';
            $order = 'distance_km ASC, a.boutique_nom ASC';

            if ($rayon_km > 0) {
                $having = ' HAVING distance_km <= :mp_rayon ';
                $params['mp_rayon'] = $rayon_km;
            }

            $params['geo_lat'] = $lat;
            $params['geo_lat2'] = $lat;
            $params['geo_lng'] = $lng;
        }

        if ($proche_only && $distance_select === 'NULL AS distance_km') {
            return [];
        }

        try {
            $sql = "
                SELECT
                    a.id,
                    a.boutique_slug,
                    a.boutique_nom,
                    a.boutique_logo,
                    a.boutique_adresse,
                    a.boutique_region,
                    a.boutique_latitude,
                    a.boutique_longitude,
                    a.boutique_couleur_principale,
                    a.boutique_couleur_accent,
                    a.telephone,
                    $distance_select
                FROM admin a
                WHERE $where
                $having
                ORDER BY $order
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (PDOException $e) {
            error_log('[marketplace_list_boutiques] ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('marketplace_boutiques_featured')) {
    /**
     * Boutiques mises en avant sur l'accueil (ordre aléatoire stable sur la journée).
     *
     * @return array<int, array<string, mixed>>
     */
    function marketplace_boutiques_featured(int $limit = 6, ?string $country = null): array
    {
        $limit = max(1, min($limit, 12));
        $all = marketplace_list_boutiques('', max($limit, 12), 0, $country, null, null, 0, false);
        if (count($all) <= $limit) {
            return $all;
        }
        $seed = (int) date('Ymd');
        usort($all, function ($a, $b) use ($seed) {
            $ha = crc32($seed . '|' . (int) ($a['id'] ?? 0));
            $hb = crc32($seed . '|' . (int) ($b['id'] ?? 0));
            return $ha <=> $hb;
        });
        return array_slice($all, 0, $limit);
    }
}

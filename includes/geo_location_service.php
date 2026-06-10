<?php
/**
 * Service de localisation exacte (GPS).
 * Programmation procédurale uniquement.
 *
 * - Validation et parsing de coordonnées
 * - Distance Haversine (PHP et SQL)
 * - Sauvegarde position : commande, utilisateur, boutique
 * - Position visiteur en session (consentement requis côté client)
 * - Recherche boutiques / produits à proximité
 */

/* Durée de validité de la position visiteur en session (secondes) */
define('GEO_SESSION_MAX_AGE', 1800); // 30 minutes

/* Rayon terrestre moyen en km */
define('GEO_EARTH_RADIUS_KM', 6371.0);

/* Sources autorisées */
define('GEO_SOURCES_COMMANDE', ['gps', 'map_pin', 'adresse', 'ip']);
define('GEO_SOURCES_BOUTIQUE', ['gps', 'map_pin', 'adresse', 'manuel']);

/* =========================================================================
 * Validation / parsing
 * ========================================================================= */

/**
 * Convertit une valeur brute (POST, BDD…) en float coordonnée, ou null.
 */
function geo_parse_coord($value): ?float
{
    if ($value === null || $value === '' || is_array($value)) {
        return null;
    }
    $value = str_replace(',', '.', trim((string) $value));
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

/**
 * Vérifie qu'un couple lat/lng est plausible.
 */
function geo_coords_valid(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) {
        return false;
    }
    if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
        return false;
    }
    // (0,0) = golfe de Guinée en pleine mer : quasi toujours une erreur de capteur
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
        return false;
    }
    return true;
}

/**
 * Précision GPS en mètres (bornée, null si invalide).
 */
function geo_parse_precision($value): ?float
{
    $p = geo_parse_coord($value);
    if ($p === null || $p < 0) {
        return null;
    }
    return min($p, 999999.0);
}

/* =========================================================================
 * Distance
 * ========================================================================= */

/**
 * Distance Haversine en kilomètres entre deux points.
 */
function geo_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return GEO_EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * Distance lisible : "350 m" ou "12,4 km".
 */
function geo_format_distance(float $km): string
{
    if ($km < 1.0) {
        return number_format(round($km * 1000), 0, ',', ' ') . ' m';
    }
    if ($km < 10.0) {
        return number_format($km, 1, ',', ' ') . ' km';
    }
    return number_format(round($km), 0, ',', ' ') . ' km';
}

/**
 * Expression SQL Haversine (km) pour deux colonnes lat/lng.
 * Les placeholders :geo_lat / :geo_lng doivent être bindés.
 */
function geo_sql_distance_expr(string $lat_col, string $lng_col): string
{
    return "(" . GEO_EARTH_RADIUS_KM . " * 2 * ASIN(SQRT(
        POWER(SIN(RADIANS(($lat_col - :geo_lat) / 2)), 2)
        + COS(RADIANS(:geo_lat2)) * COS(RADIANS($lat_col))
        * POWER(SIN(RADIANS(($lng_col - :geo_lng) / 2)), 2)
    )))";
}

/* =========================================================================
 * Détection colonnes (déploiement progressif sans casser l'existant)
 * ========================================================================= */

function geo_column_exists(string $table, string $col): bool
{
    global $db;
    static $cache = [];

    $key = $table . '.' . $col;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (empty($db) || !($db instanceof PDO)) {
        return false;
    }
    try {
        $q = $db->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
        ");
        $q->execute(['t' => $table, 'c' => $col]);
        $cache[$key] = (int) $q->fetchColumn() > 0;
    } catch (PDOException $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function geo_commandes_ready(): bool
{
    return geo_column_exists('commandes', 'delivery_latitude');
}

function geo_boutiques_ready(): bool
{
    return geo_column_exists('admin', 'boutique_latitude');
}

function geo_users_ready(): bool
{
    return geo_column_exists('users', 'last_latitude');
}

/* =========================================================================
 * Sauvegarde positions
 * ========================================================================= */

/**
 * Attache la position exacte du client à une commande déjà créée.
 */
function geo_save_commande_location(int $commande_id, ?float $lat, ?float $lng, ?float $precision = null, string $source = 'gps'): bool
{
    global $db;

    if ($commande_id <= 0 || !geo_coords_valid($lat, $lng) || !geo_commandes_ready()) {
        return false;
    }
    if (!in_array($source, GEO_SOURCES_COMMANDE, true)) {
        $source = 'gps';
    }
    try {
        $stmt = $db->prepare("
            UPDATE commandes SET
                delivery_latitude = :lat,
                delivery_longitude = :lng,
                delivery_geo_precision = :precision,
                delivery_geo_source = :source,
                delivery_geo_date = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $commande_id,
            'lat' => $lat,
            'lng' => $lng,
            'precision' => $precision,
            'source' => $source,
        ]);
    } catch (PDOException $e) {
        error_log('[geo_save_commande_location] ' . $e->getMessage());
        return false;
    }
}

/**
 * Mémorise la dernière position connue d'un utilisateur connecté.
 */
function geo_save_user_last_location(int $user_id, ?float $lat, ?float $lng, ?float $precision = null): bool
{
    global $db;

    if ($user_id <= 0 || !geo_coords_valid($lat, $lng) || !geo_users_ready()) {
        return false;
    }
    try {
        $stmt = $db->prepare("
            UPDATE users SET
                last_latitude = :lat,
                last_longitude = :lng,
                last_geo_precision = :precision,
                last_geo_date = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $user_id,
            'lat' => $lat,
            'lng' => $lng,
            'precision' => $precision,
        ]);
    } catch (PDOException $e) {
        error_log('[geo_save_user_last_location] ' . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre la position d'une boutique (vendeur).
 */
function geo_save_boutique_location(int $admin_id, ?float $lat, ?float $lng, string $source = 'manuel'): bool
{
    global $db;

    if ($admin_id <= 0 || !geo_boutiques_ready()) {
        return false;
    }
    if (!in_array($source, GEO_SOURCES_BOUTIQUE, true)) {
        $source = 'manuel';
    }

    // lat/lng null = effacement volontaire de la position
    $clear = ($lat === null && $lng === null);
    if (!$clear && !geo_coords_valid($lat, $lng)) {
        return false;
    }
    try {
        $stmt = $db->prepare("
            UPDATE admin SET
                boutique_latitude = :lat,
                boutique_longitude = :lng,
                boutique_geo_source = :source,
                boutique_geo_maj = NOW()
            WHERE id = :id AND role = 'vendeur'
        ");
        return $stmt->execute([
            'id' => $admin_id,
            'lat' => $clear ? null : $lat,
            'lng' => $clear ? null : $lng,
            'source' => $clear ? null : $source,
        ]);
    } catch (PDOException $e) {
        error_log('[geo_save_boutique_location] ' . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre la position de la boutique uniquement si elle n'en a pas encore.
 * (Connexion / création de compte vendeur : ne jamais écraser une position
 * définie volontairement dans les paramètres.)
 */
function geo_save_boutique_location_if_missing(int $admin_id, ?float $lat, ?float $lng, string $source = 'gps'): bool
{
    global $db;

    if ($admin_id <= 0 || !geo_coords_valid($lat, $lng) || !geo_boutiques_ready()) {
        return false;
    }
    try {
        $stmt = $db->prepare("SELECT boutique_latitude FROM admin WHERE id = :id AND role = 'vendeur'");
        $stmt->execute(['id' => $admin_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if ($row['boutique_latitude'] !== null) {
            return false; // position déjà définie : ne pas écraser
        }
    } catch (PDOException $e) {
        return false;
    }
    return geo_save_boutique_location($admin_id, $lat, $lng, $source);
}

/* =========================================================================
 * Position visiteur en session
 * ========================================================================= */

function geo_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    if (!isset($_SESSION)) {
        $_SESSION = [];
    }
}

function geo_session_set_location(float $lat, float $lng, ?float $precision = null): void
{
    geo_ensure_session();
    $_SESSION['geo_location'] = [
        'lat' => $lat,
        'lng' => $lng,
        'precision' => $precision,
        'time' => time(),
    ];
}

/**
 * Position visiteur en session si encore valide, sinon null.
 *
 * @return array{lat: float, lng: float, precision: ?float, time: int}|null
 */
function geo_session_get_location(): ?array
{
    geo_ensure_session();
    $loc = $_SESSION['geo_location'] ?? null;
    if (!is_array($loc) || !isset($loc['lat'], $loc['lng'], $loc['time'])) {
        return null;
    }
    if ((time() - (int) $loc['time']) > GEO_SESSION_MAX_AGE) {
        unset($_SESSION['geo_location']);
        return null;
    }
    $lat = geo_parse_coord($loc['lat']);
    $lng = geo_parse_coord($loc['lng']);
    if (!geo_coords_valid($lat, $lng)) {
        return null;
    }
    return [
        'lat' => $lat,
        'lng' => $lng,
        'precision' => isset($loc['precision']) ? geo_parse_precision($loc['precision']) : null,
        'time' => (int) $loc['time'],
    ];
}

function geo_session_clear_location(): void
{
    geo_ensure_session();
    unset($_SESSION['geo_location']);
}

/* =========================================================================
 * Liens cartographiques (OpenStreetMap / Google Maps)
 * ========================================================================= */

function geo_osm_link(float $lat, float $lng, int $zoom = 17): string
{
    return 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat)
        . '&mlon=' . rawurlencode((string) $lng)
        . '#map=' . $zoom . '/' . rawurlencode((string) $lat) . '/' . rawurlencode((string) $lng);
}

function geo_gmaps_link(float $lat, float $lng): string
{
    return 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng);
}

/* =========================================================================
 * Recherche de proximité
 * ========================================================================= */

/**
 * Boutiques actives triées par distance croissante depuis un point.
 *
 * @param float $lat Latitude du visiteur
 * @param float $lng Longitude du visiteur
 * @param float $rayon_km Rayon maximal (0 = illimité)
 * @param int $limit Nombre max de résultats
 * @param string|null $country Restreindre au pays boutique (ex: 'SN')
 * @return array Lignes admin + clé distance_km
 */
function geo_boutiques_proches(float $lat, float $lng, float $rayon_km = 30.0, int $limit = 20, ?string $country = null): array
{
    global $db;

    if (!geo_boutiques_ready() || !geo_coords_valid($lat, $lng)) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $distance_expr = geo_sql_distance_expr('a.boutique_latitude', 'a.boutique_longitude');

    // Pré-filtre boîte englobante (utilise l'index) avant le calcul exact
    $bbox = '';
    $params = [
        'geo_lat' => $lat,
        'geo_lat2' => $lat,
        'geo_lng' => $lng,
    ];
    if ($rayon_km > 0) {
        $delta_lat = $rayon_km / 111.0;
        $cos_lat = max(0.087, cos(deg2rad($lat))); // borne anti division près des pôles
        $delta_lng = $rayon_km / (111.0 * $cos_lat);
        $bbox = "
            AND a.boutique_latitude BETWEEN :bb_lat_min AND :bb_lat_max
            AND a.boutique_longitude BETWEEN :bb_lng_min AND :bb_lng_max
        ";
        $params['bb_lat_min'] = $lat - $delta_lat;
        $params['bb_lat_max'] = $lat + $delta_lat;
        $params['bb_lng_min'] = $lng - $delta_lng;
        $params['bb_lng_max'] = $lng + $delta_lng;
    }

    $country_sql = '';
    if ($country !== null && geo_column_exists('admin', 'boutique_country')) {
        $country_sql = " AND a.boutique_country = :country ";
        $params['country'] = strtoupper(trim($country));
    }

    $having = '';
    if ($rayon_km > 0) {
        $having = " HAVING distance_km <= :rayon ";
        $params['rayon'] = $rayon_km;
    }

    try {
        $stmt = $db->prepare("
            SELECT
                a.id, a.boutique_slug, a.boutique_nom, a.boutique_logo,
                a.boutique_adresse, a.boutique_region,
                a.boutique_latitude, a.boutique_longitude,
                $distance_expr AS distance_km
            FROM admin a
            WHERE a.role = 'vendeur'
              AND a.statut = 'actif'
              AND a.boutique_latitude IS NOT NULL
              AND a.boutique_longitude IS NOT NULL
              $bbox
              $country_sql
            $having
            ORDER BY distance_km ASC
            LIMIT $limit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[geo_boutiques_proches] ' . $e->getMessage());
        return [];
    }
}

/**
 * Produits actifs des boutiques proches, triés par distance puis date.
 *
 * @return array Lignes produits + boutique + distance_km
 */
function geo_produits_proches(float $lat, float $lng, float $rayon_km = 30.0, int $limit = 24, ?string $country = null): array
{
    global $db;

    if (!geo_boutiques_ready() || !geo_coords_valid($lat, $lng)) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $distance_expr = geo_sql_distance_expr('a.boutique_latitude', 'a.boutique_longitude');

    $params = [
        'geo_lat' => $lat,
        'geo_lat2' => $lat,
        'geo_lng' => $lng,
    ];

    $bbox = '';
    if ($rayon_km > 0) {
        $delta_lat = $rayon_km / 111.0;
        $cos_lat = max(0.087, cos(deg2rad($lat)));
        $delta_lng = $rayon_km / (111.0 * $cos_lat);
        $bbox = "
            AND a.boutique_latitude BETWEEN :bb_lat_min AND :bb_lat_max
            AND a.boutique_longitude BETWEEN :bb_lng_min AND :bb_lng_max
        ";
        $params['bb_lat_min'] = $lat - $delta_lat;
        $params['bb_lat_max'] = $lat + $delta_lat;
        $params['bb_lng_min'] = $lng - $delta_lng;
        $params['bb_lng_max'] = $lng + $delta_lng;
    }

    $country_sql = '';
    if ($country !== null && geo_column_exists('admin', 'boutique_country')) {
        $country_sql = " AND a.boutique_country = :country ";
        $params['country'] = strtoupper(trim($country));
    }

    $having = '';
    if ($rayon_km > 0) {
        $having = " HAVING distance_km <= :rayon ";
        $params['rayon'] = $rayon_km;
    }

    try {
        $stmt = $db->prepare("
            SELECT
                p.id, p.nom, p.prix, p.prix_promotion, p.stock, p.image_principale,
                p.admin_id, p.date_creation,
                a.boutique_slug, a.boutique_nom,
                $distance_expr AS distance_km
            FROM produits p
            INNER JOIN admin a ON a.id = p.admin_id
            WHERE p.statut = 'actif'
              AND a.role = 'vendeur'
              AND a.statut = 'actif'
              AND a.boutique_latitude IS NOT NULL
              AND a.boutique_longitude IS NOT NULL
              $bbox
              $country_sql
            $having
            ORDER BY distance_km ASC, p.date_creation DESC
            LIMIT $limit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[geo_produits_proches] ' . $e->getMessage());
        return [];
    }
}

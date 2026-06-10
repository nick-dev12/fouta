<?php
/**
 * Géocodage via Nominatim (OpenStreetMap) — open source, gratuit.
 * Programmation procédurale uniquement.
 *
 * Règles d'utilisation Nominatim (https://operations.osmfoundation.org/policies/nominatim/) :
 * - Maximum 1 requête par seconde
 * - User-Agent identifiant l'application obligatoire
 * - Pas d'usage massif : pour du volume, héberger sa propre instance Nominatim/Photon
 */

require_once __DIR__ . '/geo_location_service.php';

define('GEO_NOMINATIM_BASE', 'https://nominatim.openstreetmap.org');
define('GEO_NOMINATIM_USER_AGENT', 'COLObanes-Marketplace/1.0 (contact@colobanes.local)');
define('GEO_NOMINATIM_TIMEOUT', 6);

/**
 * Respecte la limite de 1 requête/seconde (verrou fichier partagé entre processus).
 */
function geo_nominatim_throttle(): void
{
    $lock_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colobanes_nominatim.lock';
    $fp = @fopen($lock_file, 'c+');
    if ($fp === false) {
        usleep(1100000);
        return;
    }
    flock($fp, LOCK_EX);
    $last = (float) trim((string) @stream_get_contents($fp));
    $elapsed = microtime(true) - $last;
    if ($elapsed < 1.1) {
        usleep((int) ((1.1 - $elapsed) * 1000000));
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) microtime(true));
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * Requête HTTP GET vers Nominatim, JSON décodé ou null.
 */
function geo_nominatim_request(string $path, array $query): ?array
{
    geo_nominatim_throttle();

    $url = GEO_NOMINATIM_BASE . $path . '?' . http_build_query($query);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => GEO_NOMINATIM_TIMEOUT,
            'ignore_errors' => true,
            'header' => "User-Agent: " . GEO_NOMINATIM_USER_AGENT . "\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        // Fallback local WAMP : certificats SSL parfois absents en CLI Windows
        $ctx_no_ssl = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => GEO_NOMINATIM_TIMEOUT,
                'ignore_errors' => true,
                'header' => "User-Agent: " . GEO_NOMINATIM_USER_AGENT . "\r\nAccept: application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx_no_ssl);
    }
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

/**
 * Géocode une adresse texte en coordonnées GPS.
 *
 * @param string $address Adresse libre (ex: "Marché Colobane, Dakar")
 * @param string|null $country Code pays ISO-2 pour restreindre (ex: 'SN')
 * @return array{lat: float, lng: float, display_name: string}|null
 */
function geo_geocode_address(string $address, ?string $country = null): ?array
{
    $address = trim($address);
    if ($address === '') {
        return null;
    }
    $query = [
        'q' => $address,
        'format' => 'jsonv2',
        'limit' => 1,
        'addressdetails' => 0,
    ];
    if ($country !== null && preg_match('/^[A-Za-z]{2}$/', $country)) {
        $query['countrycodes'] = strtolower($country);
    }
    $data = geo_nominatim_request('/search', $query);
    if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
        return null;
    }
    $lat = geo_parse_coord($data[0]['lat']);
    $lng = geo_parse_coord($data[0]['lon']);
    if (!geo_coords_valid($lat, $lng)) {
        return null;
    }
    return [
        'lat' => $lat,
        'lng' => $lng,
        'display_name' => isset($data[0]['display_name']) ? (string) $data[0]['display_name'] : '',
    ];
}

/**
 * Géocodage inverse : coordonnées GPS -> adresse lisible.
 */
function geo_reverse_geocode(float $lat, float $lng): ?string
{
    if (!geo_coords_valid($lat, $lng)) {
        return null;
    }
    $data = geo_nominatim_request('/reverse', [
        'lat' => $lat,
        'lon' => $lng,
        'format' => 'jsonv2',
        'addressdetails' => 0,
        'zoom' => 17,
    ]);
    if (empty($data) || empty($data['display_name'])) {
        return null;
    }
    return (string) $data['display_name'];
}

/**
 * Géocode l'adresse d'une boutique et enregistre ses coordonnées.
 * Utilise boutique_adresse + région + pays pour maximiser la précision.
 */
function geo_geocode_boutique(int $admin_id): bool
{
    global $db;

    if ($admin_id <= 0 || !geo_boutiques_ready()) {
        return false;
    }
    try {
        $stmt = $db->prepare("
            SELECT boutique_adresse, boutique_region,
                   " . (geo_column_exists('admin', 'boutique_country') ? 'boutique_country' : "'SN' AS boutique_country") . "
            FROM admin WHERE id = :id AND role = 'vendeur'
        ");
        $stmt->execute(['id' => $admin_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
    if (!$row) {
        return false;
    }

    $parts = [];
    if (!empty($row['boutique_adresse'])) {
        $parts[] = trim((string) $row['boutique_adresse']);
    }
    if (!empty($row['boutique_region'])) {
        $parts[] = str_replace('-', ' ', trim((string) $row['boutique_region']));
    }
    if (empty($parts)) {
        return false;
    }
    $country = !empty($row['boutique_country']) ? strtoupper(trim((string) $row['boutique_country'])) : null;

    $result = geo_geocode_address(implode(', ', $parts), $country);
    if ($result === null) {
        return false;
    }
    return geo_save_boutique_location($admin_id, $result['lat'], $result['lng'], 'adresse');
}

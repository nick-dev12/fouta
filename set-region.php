<?php
/**
 * Enregistre la région marketplace en session et redirige.
 */
session_start();

require_once __DIR__ . '/includes/marketplace_region_filter.php';
require_once __DIR__ . '/includes/geo_regions.php';

$redirect = isset($_POST['redirect']) ? (string) $_POST['redirect'] : (isset($_GET['redirect']) ? (string) $_GET['redirect'] : '/index.php');
$region = isset($_POST['region']) ? (string) $_POST['region'] : (isset($_GET['region']) ? (string) $_GET['region'] : '');

if ($redirect === '' || strpos($redirect, '/') !== 0 || strpos($redirect, '//') === 0) {
    $redirect = '/index.php';
}

if ($region === '' || $region === 'all') {
    marketplace_clear_selected_region();
} else {
    $country = marketplace_get_selected_country_code();
    if ($country !== null && geo_region_is_valid($country, $region)) {
        marketplace_set_selected_region($region);
    }
}

header('Location: ' . $redirect, true, 303);
exit;

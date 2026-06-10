<?php
/**
 * Filtre marketplace par région (session visiteur).
 */

require_once __DIR__ . '/geo_regions.php';
require_once __DIR__ . '/marketplace_country_filter.php';

function marketplace_region_filter_applies(): bool
{
    if (defined('BOUTIQUE_ADMIN_ID') && (int) BOUTIQUE_ADMIN_ID > 0) {
        return false;
    }
    return true;
}

function marketplace_get_selected_region_code(): ?string
{
    if (!marketplace_region_filter_applies()) {
        return null;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $country = marketplace_get_selected_country_code();
    if ($country === null || !marketplace_country_supports_regions($country)) {
        return null;
    }
    $code = $_SESSION['marketplace_region_code'] ?? null;
    if ($code === null || $code === '' || $code === 'all') {
        return null;
    }
    $region = (string) $code;
    return geo_region_is_valid($country, $region) ? $region : null;
}

function marketplace_set_selected_region(string $code): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $country = marketplace_get_selected_country_code();
    if ($country === null || !geo_region_is_valid($country, $code)) {
        return false;
    }
    $_SESSION['marketplace_region_code'] = $code;
    return true;
}

function marketplace_clear_selected_region(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['marketplace_region_code']);
}

function marketplace_get_selected_region_label(): string
{
    $code = marketplace_get_selected_region_code();
    if ($code === null) {
        return '';
    }
    $country = marketplace_get_selected_country_code();
    if ($country === null) {
        return '';
    }
    return geo_region_label($country, $code);
}

function produit_visible_in_marketplace_region(array $produit): bool
{
    if (!produit_visible_in_marketplace_country($produit)) {
        return false;
    }
    if (!marketplace_region_filter_applies()) {
        return true;
    }
    $code = marketplace_get_selected_region_code();
    if ($code === null) {
        return true;
    }
    $admin_id = (int) ($produit['admin_id'] ?? 0);
    if ($admin_id <= 0) {
        return false;
    }
    if (!function_exists('admin_has_boutique_region_column')) {
        require_once __DIR__ . '/../models/model_admin.php';
    }
    if (!admin_has_boutique_region_column()) {
        return true;
    }
    if (!function_exists('get_admin_by_id')) {
        require_once __DIR__ . '/../models/model_admin.php';
    }
    $admin = get_admin_by_id($admin_id);
    if (!$admin || ($admin['role'] ?? '') !== 'vendeur') {
        return false;
    }
    $admin_country = strtoupper(trim((string) ($admin['boutique_country'] ?? 'SN')));
    $visitor_country = marketplace_get_selected_country_code();
    if ($visitor_country !== null && $admin_country !== $visitor_country) {
        return false;
    }
    return (string) ($admin['boutique_region'] ?? '') === $code;
}

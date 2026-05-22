<?php
/**
 * Filtre marketplace par région (session visiteur).
 */

require_once __DIR__ . '/senegal_regions.php';

function marketplace_region_filter_applies()
{
    if (defined('BOUTIQUE_ADMIN_ID') && (int) BOUTIQUE_ADMIN_ID > 0) {
        return false;
    }
    return true;
}

function marketplace_get_selected_region_code()
{
    if (!marketplace_region_filter_applies()) {
        return null;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $code = $_SESSION['marketplace_region_code'] ?? null;
    if ($code === null || $code === '' || $code === 'all') {
        return null;
    }
    return senegal_region_is_valid($code) ? $code : null;
}

function marketplace_set_selected_region($code)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!senegal_region_is_valid($code)) {
        return false;
    }
    $_SESSION['marketplace_region_code'] = $code;
    return true;
}

function marketplace_clear_selected_region()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['marketplace_region_code']);
}

function marketplace_get_selected_region_label()
{
    $code = marketplace_get_selected_region_code();
    if ($code === null) {
        return '';
    }
    return senegal_region_label($code);
}

function produit_visible_in_marketplace_region($produit)
{
    if (!marketplace_region_filter_applies()) {
        return true;
    }
    $code = marketplace_get_selected_region_code();
    if ($code === null) {
        return true;
    }
    if (!is_array($produit)) {
        return false;
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
    return (string) ($admin['boutique_region'] ?? '') === $code;
}

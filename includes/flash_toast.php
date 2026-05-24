<?php
/**
 * Notifications toast (succès / erreur) — site entier
 * Programmation procédurale uniquement
 */

if (!function_exists('flash_toast_plain')) {
    function flash_toast_plain($message)
    {
        $text = html_entity_decode(strip_tags((string) $message), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}

if (!function_exists('flash_toast_push')) {
    function flash_toast_push($type, $message)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $type = in_array($type, ['success', 'error', 'info', 'warning'], true) ? $type : 'info';
        $message = flash_toast_plain($message);
        if ($message === '') {
            return;
        }
        if (!isset($_SESSION['flash_toasts']) || !is_array($_SESSION['flash_toasts'])) {
            $_SESSION['flash_toasts'] = [];
        }
        $_SESSION['flash_toasts'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_toast_queue_page')) {
    function flash_toast_queue_page($type, $message)
    {
        if (!isset($GLOBALS['flash_toast_queue']) || !is_array($GLOBALS['flash_toast_queue'])) {
            $GLOBALS['flash_toast_queue'] = [];
        }
        $message = flash_toast_plain($message);
        if ($message === '') {
            return;
        }
        $type = in_array($type, ['success', 'error', 'info', 'warning'], true) ? $type : 'info';
        $GLOBALS['flash_toast_queue'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_toast_from_query')) {
    function flash_toast_from_query()
    {
        $items = [];

        if (isset($_GET['added']) && in_array((string) $_GET['added'], ['1', 'success'], true)) {
            $items[] = ['type' => 'success', 'message' => 'Produit ajouté au panier avec succès.'];
        }

        if (!empty($_GET['recommande']) && (string) $_GET['recommande'] === '1') {
            $count = max(1, (int) ($_GET['count'] ?? 1));
            $items[] = [
                'type' => 'success',
                'message' => $count > 1
                    ? $count . ' produits de votre commande annulée ont été ajoutés au panier avec succès !'
                    : 'Les produits ont été ajoutés au panier avec succès !',
            ];
        }

        if (!empty($_GET['receive_ok'])) {
            $items[] = ['type' => 'success', 'message' => 'Réception du colis confirmée avec succès !'];
        }

        if (!empty($_GET['commande_annulee'])) {
            $items[] = ['type' => 'success', 'message' => 'Commande annulée avec succès.'];
        }

        if (!empty($_GET['livraison_confirmee'])) {
            $items[] = ['type' => 'success', 'message' => 'Colis reçu confirmé avec succès !'];
        }

        if (isset($_GET['error']) && (string) $_GET['error'] !== '') {
            $items[] = ['type' => 'error', 'message' => flash_toast_plain((string) $_GET['error'])];
        }

        return $items;
    }
}

if (!function_exists('flash_toast_collect')) {
    function flash_toast_collect()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $items = [];

        if (!empty($_SESSION['flash_toasts']) && is_array($_SESSION['flash_toasts'])) {
            $items = array_merge($items, $_SESSION['flash_toasts']);
            unset($_SESSION['flash_toasts']);
        }

        if (!empty($_SESSION['success_message'])) {
            $items[] = ['type' => 'success', 'message' => flash_toast_plain($_SESSION['success_message'])];
            unset($_SESSION['success_message']);
        }

        if (!empty($_SESSION['error_message'])) {
            $items[] = ['type' => 'error', 'message' => flash_toast_plain($_SESSION['error_message'])];
            unset($_SESSION['error_message']);
        }

        if (!empty($GLOBALS['flash_toast_queue']) && is_array($GLOBALS['flash_toast_queue'])) {
            $items = array_merge($items, $GLOBALS['flash_toast_queue']);
            $GLOBALS['flash_toast_queue'] = [];
        }

        $items = array_merge($items, flash_toast_from_query());

        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['message'])) {
                continue;
            }
            $key = ($item['type'] ?? 'info') . '|' . $item['message'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = [
                'type' => in_array($item['type'] ?? '', ['success', 'error', 'info', 'warning'], true)
                    ? $item['type']
                    : 'info',
                'message' => flash_toast_plain($item['message']),
            ];
        }

        return $unique;
    }
}

if (!function_exists('flash_toast_render')) {
    function flash_toast_render()
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;

        if (!function_exists('asset_version_query')) {
            require_once __DIR__ . '/asset_version.php';
        }

        $items = flash_toast_collect();

        echo '<link rel="stylesheet" href="/css/flash-toast.css' . asset_version_query() . '">' . "\n";
        echo '<div id="flashToastHost" class="flash-toast-host" aria-live="polite" aria-atomic="true"></div>' . "\n";
        echo '<script>window.__FLASH_TOASTS__=' . json_encode(
            $items,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) . ';</script>' . "\n";
        echo '<script src="/js/flash-toast.js' . asset_version_query() . '"></script>' . "\n";
    }
}

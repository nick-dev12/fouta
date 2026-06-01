<?php
/**
 * Traitement de l'ajout direct au panier depuis les cartes produits
 * Redirige vers la page d'origine ou le panier avec un message
 */
session_start();

require_once __DIR__ . '/controllers/controller_panier.php';
require_once __DIR__ . '/includes/marketplace_helpers.php';
require_once __DIR__ . '/includes/flash_toast.php';
require_once __DIR__ . '/models/model_admin.php';

$boutique_slug_redirect = isset($_POST['boutique_slug']) ? trim((string) $_POST['boutique_slug']) : '';

// Méthode POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['produit_id'])) {
    header('Location: /index.php');
    exit;
}

$result = process_add_to_panier();

if ($result['success']) {
    flash_toast_push('success', 'Produit ajouté au panier avec succès.');
    if ($boutique_slug_redirect !== '') {
        $adm_bs = get_admin_by_boutique_slug($boutique_slug_redirect);
        if ($adm_bs) {
            header('Location: ' . boutique_url('panier.php', $boutique_slug_redirect));
            exit;
        }
    }
    header('Location: /panier.php');
} else {
    flash_toast_push('error', $result['message'] ?? 'Impossible d\'ajouter ce produit au panier.');
    $return_url = isset($_POST['return_url']) && $_POST['return_url'] !== '' ? $_POST['return_url'] : '/panier.php';
    header('Location: ' . $return_url);
}
exit;

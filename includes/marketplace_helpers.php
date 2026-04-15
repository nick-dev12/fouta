<?php
/**
 * Helpers marketplace : URL boutique, slug
 */

if (!function_exists('marketplace_slugify')) {
    function marketplace_slugify($text) {
        $text = strtolower(trim((string) $text));
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        }
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'boutique';
    }
}

if (!function_exists('marketplace_reserved_public_slugs')) {
    /**
     * Segments d’URL racine réservés (pas des slugs boutique).
     */
    function marketplace_reserved_public_slugs() {
        return [
            'index', 'produits', 'produit', 'categorie', 'panier', 'commande',
            'choix-connexion', 'choix-inscription', 'contact', 'promo', 'nouveautes',
            'conditions-utilisation', 'politique-confidentialite', 'sign-up', 'add-to-panier',
            'commande-personnalisee', 'stock-info', 'facture', 'facture-cp', 'facture-devis',
            'robots', 'sitemap', 'debug_cp', 'check_php_config', 'generate_sitemap',
            'generate_pwa_icons', 'run_migrate_fcm', 'run_migration_image_cp', 'fix_ssl_certificates',
            'autoload', 'footer', 'nav_bar', 'admin', 'user', 'boutique', 'api', 'css', 'js',
            'image', 'upload', 'config', 'conn', 'vendor', 'page', 'models', 'controllers',
            'includes', 'fonts', 'mot-de-passe', 'pwa',
        ];
    }
}

if (!function_exists('marketplace_is_reserved_public_slug')) {
    function marketplace_is_reserved_public_slug($segment) {
        $s = strtolower(trim((string) $segment));
        return in_array($s, marketplace_reserved_public_slugs(), true);
    }
}

if (!function_exists('produit_public_boutique_label')) {
    function produit_public_boutique_label($produit) {
        $n = trim((string) ($produit['vendeur_boutique_nom'] ?? ''));
        return $n !== '' ? $n : 'FOUTA POIDS LOURDS';
    }
}

if (!function_exists('boutique_url')) {
    /**
     * URL vitrine : /boutique/page.php?boutique={slug} (fonctionne sans mod_rewrite).
     * @param string $page ex. 'index.php', 'produits.php', 'categorie.php?id=5'
     * @param string $slug slug boutique
     */
    function boutique_url($page, $slug) {
        $s = trim((string) $slug, '/');
        if ($s === '') {
            return '/boutique/';
        }
        $bq = 'boutique=' . rawurlencode($s);
        $page = ltrim((string) $page, '/');
        if ($page === '' || $page === 'index.php') {
            return '/boutique/index.php?' . $bq;
        }
        if (strpos($page, '?') !== false) {
            $parts = explode('?', $page, 2);
            return '/boutique/' . $parts[0] . '?' . $parts[1] . '&' . $bq;
        }
        return '/boutique/' . $page . '?' . $bq;
    }
}

if (!function_exists('boutique_vitrine_entry_href')) {
    /**
     * Accueil vitrine (alias de boutique_url pour index).
     */
    function boutique_vitrine_entry_href($slug) {
        return boutique_url('index.php', $slug);
    }
}

if (!function_exists('marketplace_public_base_product_url')) {
    /** URL fiche produit côté public (racine). */
    function marketplace_public_base_product_url($produit_id) {
        return '/produit.php?id=' . (int) $produit_id;
    }
}

if (!function_exists('nav_categorie_href')) {
    /**
     * Lien catégorie selon contexte marketplace ou boutique.
     */
    function nav_categorie_href($categorie_id) {
        $id = (int) $categorie_id;
        if (defined('BOUTIQUE_SLUG')) {
            return boutique_url('categorie.php?id=' . $id, BOUTIQUE_SLUG);
        }
        return '/categorie.php?id=' . $id;
    }
}

if (!function_exists('nav_categorie_generale_href')) {
    /**
     * Lien « rayon » plateforme (categories_generales.id) — liste tous les produits du rayon.
     */
    function nav_categorie_generale_href($generale_id) {
        $id = (int) $generale_id;
        if ($id <= 0) {
            return defined('BOUTIQUE_SLUG')
                ? boutique_url('categorie.php', BOUTIQUE_SLUG)
                : '/categorie.php';
        }
        if (defined('BOUTIQUE_SLUG')) {
            return boutique_url('categorie.php?generale=' . $id, BOUTIQUE_SLUG);
        }
        return '/categorie.php?generale=' . $id;
    }
}

if (!function_exists('boutique_add_to_panier_hidden_fields')) {
    /**
     * Champs cachés pour add-to-panier.php (redirection vers le panier de la vitrine).
     */
    function boutique_add_to_panier_hidden_fields() {
        if (!defined('BOUTIQUE_SLUG') || BOUTIQUE_SLUG === '') {
            return;
        }
        echo '<input type="hidden" name="boutique_slug" value="' . htmlspecialchars((string) BOUTIQUE_SLUG, ENT_QUOTES, 'UTF-8') . '">';
    }
}

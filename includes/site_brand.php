<?php
/**
 * Marque et textes SEO par défaut — COLObanes (marketplace Sénégal)
 * Inclure avant seo_meta.php ou pour les expéditeurs email / factures.
 */
if (!defined('SITE_BRAND_NAME')) {
    define('SITE_BRAND_NAME', 'COLObanes');
}
if (!defined('SITE_BRAND_TAGLINE')) {
    define('SITE_BRAND_TAGLINE', 'Marketplace Sénégal — toutes les boutiques, tous les produits');
}
if (!defined('SITE_BRAND_CONTACT_EMAIL')) {
    define('SITE_BRAND_CONTACT_EMAIL', 'contact@colobanes.sn');
}

if (!function_exists('site_brand_seo_title_default')) {
    function site_brand_seo_title_default() {
        return SITE_BRAND_NAME . ' — Marketplace Sénégal | Boutiques en ligne & produits de toutes catégories';
    }
}
if (!function_exists('site_brand_seo_description_default')) {
    function site_brand_seo_description_default() {
        return SITE_BRAND_NAME . ' est le marketplace qui rassemble les boutiques du Sénégal au même endroit. '
            . 'Achetez en ligne : mode, alimentaire, high-tech, artisanat, beauté et bien plus. '
            . 'Vendeurs locaux, catalogue multi-boutiques, livraison et shopping facile à Dakar et partout au Sénégal.';
    }
}
if (!function_exists('site_brand_seo_keywords_default')) {
    function site_brand_seo_keywords_default() {
        return 'COLObanes, marketplace Sénégal, achat en ligne Sénégal, e-commerce Dakar, boutiques en ligne Sénégal, '
            . 'marketplace Afrique de l\'Ouest, shopping en ligne, multi-vendeurs, vente en ligne produits, '
            . 'boutique en ligne Dakar, plateforme marketplace, commerce digital Sénégal';
    }
}

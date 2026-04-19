<?php
/**
 * Sitemap XML dynamique — COLObanes (marketplace Sénégal)
 * Accessible via /sitemap.xml (réécriture .htaccess → ce script)
 */
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/site_brand.php';
require_once __DIR__ . '/models/model_produits.php';
require_once __DIR__ . '/models/model_categories.php';

$base = get_site_base_url();
$logo_url = $base . '/image/logo_market.png';
$home_image_title = SITE_BRAND_NAME . ' — marketplace Sénégal, boutiques en ligne, tous produits';

$static_pages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily', 'image' => $logo_url, 'image_title' => $home_image_title],
    ['loc' => '/contact.php', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/produits.php', 'priority' => '0.95', 'changefreq' => 'daily'],
    ['loc' => '/nouveautes.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => '/promo.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => '/commande-personnalisee.php', 'priority' => '0.65', 'changefreq' => 'monthly'],
    ['loc' => '/politique-confidentialite.php', 'priority' => '0.35', 'changefreq' => 'yearly'],
    ['loc' => '/conditions-utilisation.php', 'priority' => '0.35', 'changefreq' => 'yearly'],
    ['loc' => '/choix-inscription.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
];

$category_pages = [];
if (function_exists('get_all_categories')) {
    foreach (get_all_categories() as $cat) {
        if (empty($cat['nom'])) {
            continue;
        }
        $category_pages[] = [
            'loc' => '/categorie.php?id=' . (int) $cat['id'],
            'priority' => '0.85',
            'changefreq' => 'weekly',
            'lastmod' => $cat['date_modification'] ?? $cat['date_creation'] ?? null,
        ];
    }
}

$produits = get_all_produits('actif');
$product_pages = [];
foreach ($produits as $p) {
    $product_pages[] = [
        'loc' => '/produit.php?id=' . (int) $p['id'],
        'priority' => '0.8',
        'changefreq' => 'weekly',
        'lastmod' => isset($p['date_modification']) ? $p['date_modification'] : ($p['date_creation'] ?? null),
    ];
}

$urls = array_merge($static_pages, $category_pages, $product_pages);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as $u):
    $loc = $base . $u['loc'];
    $lastmod = '';
    if (!empty($u['lastmod'])) {
        $lastmod = date('Y-m-d', strtotime($u['lastmod']));
    } else {
        $lastmod = date('Y-m-d');
    }
?>
    <url>
        <loc><?php echo htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></loc>
        <lastmod><?php echo htmlspecialchars($lastmod, ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></lastmod>
        <changefreq><?php echo htmlspecialchars($u['changefreq'] ?? 'weekly', ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></changefreq>
        <priority><?php echo htmlspecialchars($u['priority'] ?? '0.5', ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></priority>
        <?php if (!empty($u['image'])): ?>
        <image:image>
            <image:loc><?php echo htmlspecialchars($u['image'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></image:loc>
            <?php if (!empty($u['image_title'])): ?>
            <image:title><?php echo htmlspecialchars($u['image_title'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></image:title>
            <?php endif; ?>
        </image:image>
        <?php endif; ?>
    </url>
<?php endforeach; ?>
</urlset>

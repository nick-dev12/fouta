<?php
session_start();

// Inclusion des modèles
require_once __DIR__ . '/models/model_categories.php';
require_once __DIR__ . '/models/model_produits.php';
require_once __DIR__ . '/includes/produit_boutique_line.php';

// Rayon plateforme (categories_generales) ou catégorie feuille
$generale_id = isset($_GET['generale']) ? (int) $_GET['generale'] : 0;
$categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

unset($categorie);
$categorie = null;
$generale_row = null;
$categorie_nom = 'Catégorie';
$produits = [];

if ($generale_id > 0) {
    $generale_row = get_categorie_generale_by_id($generale_id);
    if (!$generale_row || empty($generale_row['nom'])) {
        header('Location: index.php');
        exit;
    }
    $categorie_nom = (string) $generale_row['nom'];
    $produits = get_produits_by_categorie_generale($generale_id, null);
} elseif ($categorie_id > 0) {
    $categorie = get_categorie_by_id($categorie_id);
    if (!$categorie || !is_array($categorie) || empty($categorie['nom'])) {
        header('Location: index.php');
        exit;
    }
    $categorie_nom = (string) $categorie['nom'];
    $produits = get_produits_by_categorie($categorie_id);
    if ($produits === false) {
        $produits = [];
    }
} else {
    header('Location: index.php');
    exit;
}

// Inclusion du fichier de connexion à la BDD (pour les autres fonctionnalités si nécessaire)
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

require_once __DIR__ . '/includes/marketplace_helpers.php';
require_once __DIR__ . '/includes/asset_version.php';

$sous_categories_rayon = [];
if ($generale_id > 0) {
    $sous_categories_rayon = get_subcategories_with_active_products_for_general($generale_id, null);
}

$return_url_cat = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/categorie.php';
$card_partial = __DIR__ . '/includes/partials/home_mp_product_card.php';

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/site_brand.php';
$base = get_site_base_url();
$seo_title = $categorie_nom . ' — catalogue ' . SITE_BRAND_NAME . ' | Marketplace Sénégal';
if ($generale_row) {
    $desc_cat = !empty($generale_row['description'])
        ? strip_tags((string) $generale_row['description'])
        : 'Rayon « ' . $categorie_nom . ' » sur ' . SITE_BRAND_NAME . ' : produits de boutiques sénégalaises, achat en ligne.';
    $seo_canonical = $base . '/categorie.php?generale=' . (int) $generale_id;
} else {
    $desc_cat = !empty($categorie['description'])
        ? strip_tags((string) $categorie['description'])
        : 'Catégorie « ' . $categorie_nom . ' » sur ' . SITE_BRAND_NAME . ', marketplace multi-boutiques au Sénégal.';
    $seo_canonical = $base . '/categorie.php?id=' . (int) $categorie_id;
}
$seo_description = mb_substr($desc_cat, 0, 160);
$seo_keywords = site_brand_seo_keywords_default() . ', ' . $categorie_nom . ', catalogue ' . $categorie_nom;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/mp-category-page.css<?php echo asset_version_query(); ?>">
</head>

<body>

    <?php include __DIR__ . '/nav_bar.php'; ?>

    <?php if (isset($_GET['added']) && $_GET['added'] === '1'): ?>
    <div class="cat-page-alert cat-page-alert--ok mp-shell">
        <i class="fas fa-check-circle" aria-hidden="true"></i> Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="cat-page-alert cat-page-alert--err mp-shell">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $_GET['error']); ?>
    </div>
    <?php endif; ?>

    <main class="mp-main">
        <div class="mp-shell">
            <header class="cat-rayon-hero">
                <p class="cat-rayon-kicker">
                    <i class="fas <?php echo $generale_row ? 'fa-layer-group' : 'fa-folder-open'; ?>" aria-hidden="true"></i>
                    <?php echo $generale_row ? 'Rayon marketplace' : 'Catégorie'; ?>
                </p>
                <h1><?php echo htmlspecialchars($categorie_nom); ?></h1>
                <?php if ($generale_row && !empty($generale_row['description'])): ?>
                <p class="cat-rayon-desc"><?php echo htmlspecialchars(strip_tags((string) $generale_row['description'])); ?></p>
                <?php elseif (!$generale_row && !empty($categorie['description'])): ?>
                <p class="cat-rayon-desc"><?php echo htmlspecialchars(strip_tags((string) $categorie['description'])); ?></p>
                <?php endif; ?>
            </header>

            <?php if ($generale_id > 0 && !empty($sous_categories_rayon)): ?>
            <section class="cat-subs" aria-labelledby="cat-subs-heading">
                <div class="cat-subs-inner">
                    <div class="cat-subs-head">
                        <div class="cat-subs-head-text">
                            <span class="cat-subs-kicker" id="cat-subs-heading">Affiner</span>
                            <h2 class="cat-subs-title">Sous-catégories</h2>
                        </div>
                        <p class="cat-subs-hint">Choisissez une rubrique pour voir uniquement les produits associés.</p>
                    </div>
                    <div class="cat-subs-scroller">
                        <a class="cat-sub-chip cat-sub-chip--all is-active" href="<?php echo htmlspecialchars(nav_categorie_generale_href($generale_id)); ?>">
                            <i class="fas fa-border-all" aria-hidden="true"></i> Tout le rayon
                        </a>
                        <?php foreach ($sous_categories_rayon as $sc): ?>
                            <?php
                            $sid = (int) ($sc['id'] ?? 0);
                            if ($sid <= 0) {
                                continue;
                            }
                            ?>
                        <a class="cat-sub-chip" href="<?php echo htmlspecialchars(nav_categorie_href($sid)); ?>">
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <?php echo htmlspecialchars((string) ($sc['nom'] ?? '')); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section class="mp-block" aria-labelledby="cat-products-heading">
                <header class="mp-block-head">
                    <h2 id="cat-products-heading">Produits</h2>
                    <span style="font-size:14px;color:var(--texte-mute);"><?php echo (int) count($produits); ?> article(s)</span>
                </header>
                <div class="mp-grid" id="produits-container">
                    <?php if (empty($produits)): ?>
                    <div class="mp-empty">
                        <p style="margin:0 0 12px;"><i class="fas fa-box-open" style="font-size:40px;opacity:.45;" aria-hidden="true"></i></p>
                        <p style="margin:0 0 20px;">Aucun produit publié pour le moment.</p>
                        <a href="index.php" class="cat-page-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à l’accueil</a>
                    </div>
                    <?php else: ?>
                    <?php
                    foreach ($produits as $produit) {
                        $return_url = $return_url_cat;
                        require $card_partial;
                    }
                    ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>
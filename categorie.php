<?php
session_start();

// Inclusion des modèles
require_once __DIR__ . '/models/model_categories.php';
require_once __DIR__ . '/models/model_produits.php';
require_once __DIR__ . '/includes/produit_boutique_line.php';

// Rayon plateforme (categories_generales) ou catégorie feuille
$generale_id = isset($_GET['generale']) ? (int) $_GET['generale'] : 0;
$categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$filter_genre_id = isset($_GET['genre']) ? (int) $_GET['genre'] : 0;
$filter_sous_categorie_id = 0;

unset($categorie);
$categorie = null;
$generale_row = null;
$categorie_nom = 'Catégorie';
$produits = [];
$genres_pour_filtre_rayon = [];

if ($generale_id > 0) {
    $filter_sous_categorie_id = isset($_GET['sous_categorie']) ? (int) $_GET['sous_categorie'] : 0;
    $generale_row = get_categorie_generale_by_id($generale_id);
    if (!$generale_row || empty($generale_row['nom'])) {
        header('Location: index.php');
        exit;
    }
    $categorie_nom = (string) $generale_row['nom'];

    require_once __DIR__ . '/models/model_genres.php';
    if (function_exists('count_genres_linked_to_categorie_generale')
        && count_genres_linked_to_categorie_generale($generale_id) > 0
        && function_exists('get_genres_linked_to_categorie_generale')) {
        $genres_pour_filtre_rayon = get_genres_linked_to_categorie_generale($generale_id);
        if ($filter_genre_id > 0) {
            $genre_ok = false;
            foreach ($genres_pour_filtre_rayon as $grow) {
                if ((int) ($grow['id'] ?? 0) === $filter_genre_id) {
                    $genre_ok = true;
                    break;
                }
            }
            if (!$genre_ok) {
                $filter_genre_id = 0;
            }
        }
    } else {
        $filter_genre_id = 0;
    }

    if ($filter_sous_categorie_id > 0) {
        if (!function_exists('categorie_plateforme_liee_au_rayon')
            || !categorie_plateforme_liee_au_rayon($filter_sous_categorie_id, $generale_id)) {
            $filter_sous_categorie_id = 0;
        }
    }

    $produits = get_produits_by_categorie_generale(
        $generale_id,
        null,
        $filter_genre_id > 0 ? $filter_genre_id : null,
        $filter_sous_categorie_id > 0 ? $filter_sous_categorie_id : null
    );
} elseif ($categorie_id > 0) {
    $filter_genre_id = 0;
    $filter_sous_categorie_id = 0;
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

if (!empty($produits) && is_array($produits)) {
    if (function_exists('random_int')) {
        mt_srand((int) (microtime(true) * 1000000) + random_int(0, 9999));
    } else {
        mt_srand((int) (microtime(true) * 1000000));
    }
    shuffle($produits);
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
    if (!empty($filter_genre_id)) {
        $seo_canonical .= '&genre=' . (int) $filter_genre_id;
    }
    if (!empty($filter_sous_categorie_id)) {
        $seo_canonical .= '&sous_categorie=' . (int) $filter_sous_categorie_id;
    }
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

            <?php if ($generale_id > 0 && (!empty($sous_categories_rayon) || !empty($genres_pour_filtre_rayon))): ?>
            <section class="cat-filters" aria-labelledby="cat-filters-heading">
                <div class="cat-filters-inner">
                    <div class="cat-filters-head">
                        <span class="cat-subs-kicker">Affiner</span>
                        <h2 class="cat-subs-title" id="cat-filters-heading">Filtres</h2>
                    </div>

                    <div class="cat-filters-controls">
                        <?php if (!empty($sous_categories_rayon)): ?>
                        <label class="cat-filter-select-wrap" for="cat-filter-sous-categorie">
                            <span class="cat-filter-select-label">
                                <i class="fas fa-layer-group" aria-hidden="true"></i>
                                Sous-catégorie
                            </span>
                            <span class="cat-filter-select-shell">
                                <select id="cat-filter-sous-categorie" class="cat-filter-select" aria-label="Filtrer par sous-catégorie"
                                    onchange="if (this.value) window.location.href = this.value;">
                                    <?php
                                    $h_ref_tout = function_exists('nav_categorie_generale_filtre_href')
                                        ? nav_categorie_generale_filtre_href($generale_id, $filter_genre_id > 0 ? $filter_genre_id : 0, 0)
                                        : nav_categorie_generale_href($generale_id);
                                    ?>
                                    <option value="<?php echo htmlspecialchars($h_ref_tout); ?>" <?php echo $filter_sous_categorie_id === 0 ? 'selected' : ''; ?>>
                                        Toutes les sous-catégories
                                    </option>
                                    <?php foreach ($sous_categories_rayon as $sc): ?>
                                        <?php
                                        $sid = (int) ($sc['id'] ?? 0);
                                        if ($sid <= 0) {
                                            continue;
                                        }
                                        $h_sub = function_exists('nav_categorie_generale_filtre_href')
                                            ? nav_categorie_generale_filtre_href(
                                                $generale_id,
                                                $filter_genre_id > 0 ? $filter_genre_id : 0,
                                                $sid
                                            )
                                            : nav_categorie_href($sid);
                                        ?>
                                    <option value="<?php echo htmlspecialchars($h_sub); ?>" <?php echo $filter_sous_categorie_id === $sid ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) ($sc['nom'] ?? '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </span>
                        </label>
                        <?php endif; ?>

                        <?php if (!empty($genres_pour_filtre_rayon) && function_exists('nav_categorie_generale_genre_href')): ?>
                        <label class="cat-filter-select-wrap" for="cat-filter-genre">
                            <span class="cat-filter-select-label">
                                <i class="fas fa-tags" aria-hidden="true"></i>
                                Genre
                            </span>
                            <span class="cat-filter-select-shell">
                                <select id="cat-filter-genre" class="cat-filter-select" aria-label="Filtrer par genre"
                                    onchange="if (this.value) window.location.href = this.value;">
                                    <?php
                                    $g_href0 = function_exists('nav_categorie_generale_filtre_href')
                                        ? nav_categorie_generale_filtre_href($generale_id, 0, $filter_sous_categorie_id > 0 ? $filter_sous_categorie_id : 0)
                                        : nav_categorie_generale_genre_href($generale_id, 0);
                                    ?>
                                    <option value="<?php echo htmlspecialchars($g_href0); ?>" <?php echo $filter_genre_id === 0 ? 'selected' : ''; ?>>
                                        Tous les genres
                                    </option>
                                    <?php foreach ($genres_pour_filtre_rayon as $grow): ?>
                                    <?php
                                    $gpid = (int) ($grow['id'] ?? 0);
                                    if ($gpid <= 0) {
                                        continue;
                                    }
                                    $gn = trim((string) ($grow['nom'] ?? ''));
                                    $g_href = function_exists('nav_categorie_generale_filtre_href')
                                        ? nav_categorie_generale_filtre_href(
                                            $generale_id,
                                            $gpid,
                                            $filter_sous_categorie_id > 0 ? $filter_sous_categorie_id : 0
                                        )
                                        : nav_categorie_generale_genre_href($generale_id, $gpid);
                                    ?>
                                    <option value="<?php echo htmlspecialchars($g_href); ?>" <?php echo $filter_genre_id === $gpid ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gn !== '' ? $gn : 'Genre'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </span>
                        </label>
                        <?php endif; ?>
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
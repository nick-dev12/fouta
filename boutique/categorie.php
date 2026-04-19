<?php
session_start();
require_once __DIR__ . '/_init.php';

// Inclusion des modèles
require_once __DIR__ . '/../models/model_categories.php';
require_once __DIR__ . '/../models/model_produits.php';

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
        header('Location: ' . boutique_url('index.php', BOUTIQUE_SLUG));
        exit;
    }
    $categorie_nom = (string) $generale_row['nom'];
    $produits = get_produits_by_categorie_generale($generale_id, BOUTIQUE_ADMIN_ID);
} elseif ($categorie_id > 0) {
    $categorie = get_categorie_by_id($categorie_id);
    if (!$categorie || !is_array($categorie) || empty($categorie['nom'])) {
        header('Location: ' . boutique_url('index.php', BOUTIQUE_SLUG));
        exit;
    }
    $categorie_nom = (string) $categorie['nom'];
    $produits = get_produits_by_categorie($categorie_id, BOUTIQUE_ADMIN_ID);
    if ($produits === false) {
        $produits = [];
    }
} else {
    header('Location: ' . boutique_url('index.php', BOUTIQUE_SLUG));
    exit;
}

// Inclusion du fichier de connexion à la BDD (pour les autres fonctionnalités si nécessaire)
if (file_exists(__DIR__ . '/../controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/../controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/../includes/site_url.php';
$base = get_site_base_url();
$seo_title = $categorie_nom . ' - ' . BOUTIQUE_NOM;
if ($generale_row) {
    $desc_cat = !empty($generale_row['description']) ? strip_tags((string) $generale_row['description']) : 'Catalogue « ' . $categorie_nom . ' » — ' . BOUTIQUE_NOM . '.';
    $seo_canonical = $base . boutique_url('categorie.php?generale=' . (int) $generale_id, BOUTIQUE_SLUG);
} else {
    $desc_cat = !empty($categorie['description'])
        ? strip_tags((string) $categorie['description'])
        : 'Catégorie « ' . $categorie_nom . ' » chez ' . BOUTIQUE_NOM . ' sur COLObanes, marketplace des boutiques du Sénégal.';
    $seo_canonical = $base . boutique_url('categorie.php?id=' . (int) $categorie_id, BOUTIQUE_SLUG);
}
$seo_description = mb_substr($desc_cat, 0, 160);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/../includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="/css/owl.carousel.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/boutique-vitrine-products.css<?php echo asset_version_query(); ?>">
    <style>
        /* Styles personnalisés pour les cartes produits */
    </style>
</head>

<body class="boutique-vitrine">

    <?php include __DIR__ . '/../nav_bar.php'; ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: var(--success-bg); border-left: 4px solid var(--bleu); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: var(--error-bg); border-left: 4px solid var(--error-border); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <?php endif; ?>
    <section class="section00">
        <section class="produit_vedetes">
            <div class="box1">
                <h1><?php echo htmlspecialchars($categorie_nom); ?></h1>
            </div>

            <?php if (empty($produits)): ?>
                <div style="text-align: center; padding: 40px; color: var(--gris-moyen);">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p style="font-size: 16px;">Aucun produit publié pour le moment.</p>
                    <a href="<?php echo htmlspecialchars(boutique_url('index.php', BOUTIQUE_SLUG)); ?>"
                        style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: var(--couleur-dominante); color: var(--texte-clair); text-decoration: none; border-radius: 5px; transition: background 0.3s ease;">
                        <i class="fas fa-arrow-left"></i> Retour à l'accueil
                    </a>
                </div>
            <?php else: ?>
                <article data-aos="fade-up" data-aos-delay="0" data-aos-duration="1000" data-aos-easing="ease-in-out"
                    data-aos-mirror="true" data-aos-once="false" data-aos-anchor-placement="top-bottom"
                    class="articles  carousel11">
                    <?php foreach ($produits as $produit): ?>
                        <?php
                        // Vérifier si promotion disponible
                        $has_promo = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
                        $prix_principal = number_format($produit['prix'], 0, ',', ' ');
                        $prix_promo_value = $has_promo ? number_format($produit['prix_promotion'], 0, ',', ' ') : '';
                        $pourcentage_reduction = 0;
                        if ($has_promo) {
                            $pourcentage_reduction = round((($produit['prix'] - $produit['prix_promotion']) / $produit['prix']) * 100);
                        }
                        ?>
                        <div class="carousel">
                            <a href="/produit.php?id=<?php echo (int) $produit['id']; ?>" class="product-card-link">
                                <div class="image-wrapper">
                                    <img src="/upload/<?php echo htmlspecialchars($produit['image_principale']); ?>"
                                        alt="<?php echo htmlspecialchars($produit['nom']); ?>"
                                        onerror="this.src='/image/produit1.jpg'">
                                </div>
                                <div class="produit-content">
                                    <p id="nom"><?php echo htmlspecialchars($produit['nom']); ?></p>

                                    <p class="prix">
                                        <?php if ($has_promo): ?>
                                            <span class="span2"><?php echo $prix_principal; ?> FCFA</span>
                                            <span class="prix-promo"><?php echo $prix_promo_value; ?> FCFA</span>
                                            <span class="span3">-<?php echo $pourcentage_reduction; ?>%</span>
                                        <?php else: ?>
                                            <?php echo $prix_principal; ?><span class="span1"> FCFA</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($produit['stock'])): ?>
                                        <p class="produit-card-stock-info">
                                            <strong>Stock:</strong> <?php echo $produit['stock']; ?>

                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <form method="POST" action="/add-to-panier.php" class="add-to-cart-form">
                                <?php boutique_add_to_panier_hidden_fields(); ?>
                                <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                <input type="hidden" name="quantite" value="1">
                                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/categorie.php'); ?>">
                                <button type="submit" class="btn-add-cart">
                                    <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </article>
            <?php endif; ?>
        </section>
    </section>

    <?php include __DIR__ . '/../footer.php'; ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/owl.carousel.js"></script>
    <script src="/js/owl.animate.js"></script>
    <script src="/js/owl.autoplay.js"></script>

    <script>
        $(document).ready(function () {
            AOS.init();
        });
    </script>

    <script>
        // ..
        AOS.init();
    </script>

</body>

</html>
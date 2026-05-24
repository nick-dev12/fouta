<?php

/**
 * Commandes groupées par catégorie, ou détail / suivi d'une commande (commande_id)
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_commandes.php';
require_once __DIR__ . '/../includes/format_commande_options.php';

$user_id = (int) $_SESSION['user_id'];
$commande_id = isset($_GET['commande_id']) ? (int) $_GET['commande_id'] : null;
$categorie_id = isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : null;

$suivi_confirm_success = isset($_GET['receive_ok']);
$suivi_confirm_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_livraison'])) {
    $post_cmd_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;
    if ($post_cmd_id > 0 && $commande_id === $post_cmd_id) {
        $cmd_post = get_commande_by_id($post_cmd_id, $user_id);
        if ($cmd_post && (($cmd_post['statut'] ?? '') === 'livraison_en_cours')) {
            require_once __DIR__ . '/../models/model_commandes_admin.php';
            if (update_commande_statut($post_cmd_id, 'paye')) {
                require_once __DIR__ . '/../services/send_commande_notification.php';
                send_commande_status_notification(
                    $user_id,
                    $cmd_post['numero_commande'],
                    'paye',
                    trim($_SESSION['user_email'] ?? '')
                );
                $vendeur_id = (int) ($cmd_post['vendeur_id'] ?? 0);
                if ($vendeur_id > 0) {
                    send_commande_vendeur_action_notification(
                        $vendeur_id,
                        $post_cmd_id,
                        $cmd_post['numero_commande'],
                        'Réception confirmée par le client (payée)'
                    );
                }
                header('Location: commande-categorie.php?commande_id=' . $post_cmd_id . '&receive_ok=1');
                exit;
            }
        }
    }
    $suivi_confirm_error = true;
}

$commande = null;
$produits_commande = [];

if ($commande_id) {
    $commande = get_commande_by_id($commande_id, $user_id);
    if (!$commande) {
        header('Location: mes-commandes.php');
        exit;
    }
    $produits_commande = get_commande_produits($commande_id);
}

if ($commande_id && $commande) {
    $commandes_by_categorie = [];
} else {
    $commandes_by_categorie = get_commandes_by_categorie($user_id, $categorie_id);
}

$page_is_suivi = (bool) ($commande_id && $commande);
$nb_prod = $page_is_suivi ? count($produits_commande) : 0;
$page_title = $page_is_suivi ? 'Suivi de commande - COLObanes' : 'Mes commandes par catégorie - COLObanes';

require_once __DIR__ . '/../includes/flash_toast.php';
if ($suivi_confirm_error) {
    flash_toast_queue_page('error', 'Impossible d\'enregistrer la réception pour cette commande.');
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <?php if ($page_is_suivi): ?>
    <link rel="stylesheet" href="/css/mp-category-page.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/commande-suivi-page.css<?php echo asset_version_query(); ?>">
    <?php else: ?>
    <style>
        .categorie-section {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .categorie-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(229, 72, 138, 0.2);
            flex-wrap: wrap;
            gap: 15px;
        }
        .categorie-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--titres);
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-titres);
        }
        .categorie-badge {
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .liste-categorie-page .produits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .liste-categorie-page .produits-grid {
                grid-template-columns: 1fr;
            }
        }
        .produit-card-commande {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 15px;
            border: 1px solid var(--glass-border);
            transition: box-shadow 0.3s ease;
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .produit-card-commande:hover {
            box-shadow: var(--ombre-gourmande);
        }
        .produit-card-header {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 15px;
        }
        .produit-card-commande .produit-card-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            flex-shrink: 0;
        }
        .produit-card-info {
            flex: 1;
            min-width: 0;
        }
        .produit-card-commande .produit-card-nom {
            font-size: 16px;
            font-weight: 700;
            color: var(--titres);
            margin-bottom: 5px;
        }
        .produit-card-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 15px;
            border-top: 1px solid rgba(229, 72, 138, 0.2);
        }
        .produit-card-details div {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .detail-label {
            font-size: 11px;
            color: var(--texte-fonce);
            text-transform: uppercase;
            font-weight: 600;
        }
        .detail-value {
            font-size: 14px;
            color: var(--titres);
            font-weight: 600;
        }
        .detail-value.prix-total {
            color: var(--accent-promo);
        }
        .commande-info-badge {
            display: inline-block;
            background: var(--accent-promo);
            color: var(--texte-clair);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
        .commande-date-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(229, 72, 138, 0.2);
            font-size: 11px;
            color: var(--texte-fonce);
        }
        .couleur-display { display: flex; align-items: center; gap: 8px; }
        .couleur-swatch-large {
            width: 24px; height: 24px;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }
        .options-lignes .option-ligne { margin-bottom: 4px; }
    </style>
    <?php endif; ?>
    <style>
        .content-header-link-retour {
            color: var(--couleur-dominante);
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }
        .content-header-link-retour:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body <?php echo $page_is_suivi ? 'class="commande-suivi-shell"' : ''; ?>>
    <?php include 'includes/user_nav.php'; ?>

    <div class="content-header<?php echo $page_is_suivi ? ' content-header--cc-suivi' : ''; ?>">
        <div class="content-header__intro">
            <h1>
                <i class="fas <?php echo $page_is_suivi ? 'fa-route' : 'fa-layer-group'; ?>"></i>
                <?php echo $page_is_suivi ? htmlspecialchars('Suivi de commande') : htmlspecialchars('Mes commandes par catégorie'); ?>
            </h1>
            <a href="mes-commandes.php" class="content-header-link-retour">
                <i class="fas fa-arrow-left"></i> Retour aux commandes
            </a>
        </div>
        <?php if ($page_is_suivi): ?>
        <div class="content-header__suivi-actions">
            <button type="button" class="cc-header-btn-products" id="ccOpenProducts"
                <?php echo $nb_prod > 0 ? '' : ' disabled'; ?>>
                <i class="fas fa-box-open" aria-hidden="true"></i>
                <span>Voir les produits<?php echo $nb_prod > 0 ? ' (' . $nb_prod . ')' : ''; ?></span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <section class="content-section <?php echo $page_is_suivi ? 'commande-categorie-suivi liste-categorie-page' : 'commande-categorie-page liste-categorie-page'; ?>">
        <?php if ($page_is_suivi):
            require_once dirname(__DIR__) . '/includes/commande_suivi_ui.php';
            commande_suivi_render_dashboard($commande, [
                'commande_id' => (int) $commande_id,
                'suivi_confirm_success' => $suivi_confirm_success,
                'suivi_confirm_error' => $suivi_confirm_error,
                'show_client_actions_bar' => true,
                'admin_hint' => false,
                'wrap_class' => 'cc-products-anchor commande-suivi-detail',
            ]);
            ?>

        <?php else: ?>

        <?php if (empty($commandes_by_categorie)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Aucune commande trouvée.</p>
                <a href="mes-commandes.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Mes commandes</a>
            </div>
        <?php else: ?>
            <?php foreach ($commandes_by_categorie as $categorie_data): ?>
                <div class="categorie-section">
                    <div class="categorie-header">
                        <div class="categorie-title">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($categorie_data['categorie_nom']); ?>
                            <span class="categorie-badge">
                                <?php echo count($categorie_data['produits']); ?>
                                produit<?php echo count($categorie_data['produits']) > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>

                    <div class="produits-grid">
                        <?php foreach ($categorie_data['produits'] as $produit): ?>
                            <?php
                            $produit_nom = $produit['nom'] ?? $produit['produit_nom'] ?? 'Produit sans nom';
                            $produit_nom_affichage = !empty($produit['variante_nom']) ?
                                $produit_nom . ' → ' . $produit['variante_nom'] :
                                $produit_nom;
                            $produit_image =
                                !empty($produit['image_afficher']) ?
                                    $produit['image_afficher'] :
                                    ($produit['image_principale'] ?? '');
                            ?>
                            <div class="produit-card-commande">
                                <div class="produit-card-header">
                                    <div class="produit-cmd-img">
                                        <img src="<?php echo htmlspecialchars('/upload/' . $produit_image); ?>"
                                            alt="<?php echo htmlspecialchars($produit_nom_affichage); ?>"
                                            class="produit-card-image"
                                            onerror="this.src='/image/produit1.jpg'">
                                    </div>
                                    <div class="produit-card-info">
                                        <h4 class="produit-card-nom"><?php echo htmlspecialchars($produit_nom_affichage); ?></h4>
                                        <?php $numero_commande = $produit['numero_commande'] ?? null; ?>
                                        <?php if ($numero_commande): ?>
                                            <span class="commande-info-badge">Commande #
                                                <?php echo htmlspecialchars($numero_commande); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="produit-card-details">
                                    <div>
                                        <span class="detail-label">Quantité</span>
                                        <span class="detail-value"><?php echo (int) ($produit['quantite'] ?? 0); ?></span>
                                    </div>
                                    <div>
                                        <span class="detail-label">Prix unitaire</span>
                                        <span class="detail-value"><?php echo number_format(
                                            (float) ($produit['prix_unitaire'] ?? 0),
                                            0,
                                            ',',
                                            ' '
                                        ); ?> FCFA</span>
                                    </div>
                                    <div>
                                        <span class="detail-label">Prix total</span>
                                        <span class="detail-value prix-total"><?php echo number_format(
                                            (float) ($produit['prix_total'] ?? 0),
                                            0,
                                            ',',
                                            ' '
                                        ); ?> FCFA</span>
                                    </div>
                                </div>
                                <?php
                                $date_commande = $produit['date_commande'] ?? null;
                                if ($date_commande): ?>
                                    <div class="commande-date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo 'Date : ' .
                                            htmlspecialchars(date('d/m/Y à H:i', strtotime($date_commande))); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>

    </section>

    <?php if ($page_is_suivi && $nb_prod > 0): ?>
    <div class="cc-products-layer" id="ccProductsOverlay" aria-hidden="true">
        <div class="cc-products-backdrop" id="ccBackdrop" role="presentation"></div>
        <div class="cc-products-sheet" role="dialog" aria-modal="true" aria-labelledby="ccProductsSheetTitle">
            <header class="cc-products-sheet-hd">
                <strong id="ccProductsSheetTitle">Articles de votre commande</strong>
                <button type="button" class="cc-products-close" id="ccCloseProducts" aria-label="Fermer la liste"><i class="fas fa-times"></i></button>
            </header>
            <div class="cc-products-scroll">
                <div class="mp-grid cc-overlay-mp-grid" id="ccOverlayProduits">
                    <?php foreach ($produits_commande as $produit): ?>
                        <?php
                        $produit_nom = $produit['nom'] ?? $produit['produit_nom'] ?? 'Produit';
                        $produit_nom_affichage =
                            (!empty($produit['variante_nom']))
                            ? ($produit_nom . ' · ' . $produit['variante_nom'])
                            : $produit_nom;
                        $produit_image = !empty($produit['image_afficher'])
                            ? $produit['image_afficher']
                            : ($produit['image_principale'] ?? '');
                        $cat_nom = trim((string) ($produit['categorie_nom'] ?? ''));
                        $qte = (int) ($produit['quantite'] ?? 0);
                        $pu = (float) ($produit['prix_unitaire'] ?? 0);
                        $pt = (float) ($produit['prix_total'] ?? 0);
                        ?>
                        <article class="mp-card">
                            <div class="mp-card-link">
                                <div class="mp-card-img">
                                    <img src="<?php echo htmlspecialchars('/upload/' . $produit_image); ?>"
                                        alt="<?php echo htmlspecialchars($produit_nom_affichage); ?>"
                                        onerror="this.src='/image/produit1.jpg'"
                                        loading="lazy">
                                </div>
                                <div class="mp-card-body">
                                    <p class="mp-card-title"><?php echo htmlspecialchars($produit_nom_affichage); ?></p>
                                    <?php if ($cat_nom !== '') { ?>
                                    <p class="produit-card-boutique"><?php echo htmlspecialchars($cat_nom); ?></p>
                                    <?php } ?>
                                    <div class="mp-card-meta"><?php
                                        if ($qte > 1) {
                                            echo htmlspecialchars(
                                                $qte .
                                                ' × ' .
                                                number_format($pu, 0, ',', ' ') .
                                                ' FCFA'
                                            );
                                        } else {
                                            echo htmlspecialchars(
                                                'Unitaire · ' .
                                                number_format($pu, 0, ',', ' ') .
                                                ' FCFA · Qté ' .
                                                $qte
                                            );
                                        }
?></div>
                                    <div class="mp-card-price-row">
                                        <span class="mp-card-price"><?php echo number_format($pt, 0, ',', ' '); ?> FCFA</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mp-card-cart">
                                <span class="cc-mp-ordered-chip" role="presentation">
                                    <i class="fas fa-receipt"></i>
                                    Dans cette commande
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var o = document.getElementById('ccProductsOverlay');
        var btn = document.getElementById('ccOpenProducts');
        var bk = document.getElementById('ccBackdrop');
        var cl = document.getElementById('ccCloseProducts');
        function openLay(){ if(!o) return; o.classList.add('is-visible'); o.setAttribute('aria-hidden','false'); document.documentElement.style.overflow='hidden'; }
        function shut(){ if(!o) return; o.classList.remove('is-visible'); o.setAttribute('aria-hidden','true'); document.documentElement.style.overflow=''; }
        if(btn) btn.addEventListener('click', openLay);
        if(bk) bk.addEventListener('click', shut);
        if(cl) cl.addEventListener('click', shut);
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') shut(); });
    })();
    </script>
    <?php endif; ?>

    <?php include 'includes/user_footer.php'; ?>

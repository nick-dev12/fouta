<?php
/**
 * Gestion du stock - Catégories et produits
 * Contenu déplacé depuis categories/index.php
 * Utilise la table produits et la colonne stock (plus de table stock_articles)
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/require_access.php';
if (file_exists(__DIR__ . '/../includes/admin_route_access.php')) {
    require_once __DIR__ . '/../includes/admin_route_access.php';
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$cat_modal_error = '';
$cat_modal_open = false;
$cat_modal_nom = '';
$cat_modal_description = '';

require_once __DIR__ . '/../../models/model_categories.php';
require_once __DIR__ . '/../../models/model_produits.php';
$__stock_role_idx = function_exists('admin_normalize_role_for_route')
    ? admin_normalize_role_for_route($_SESSION['admin_role'] ?? 'admin')
    : 'admin';
$stock_catalogue_vendeur_seul = ($__stock_role_idx === 'vendeur' && function_exists('get_categories_platform_for_vendeur_stock'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_add_categorie']) && !$stock_catalogue_vendeur_seul) {
    $tok = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string) ($_SESSION['admin_csrf'] ?? ''), (string) $tok)) {
        $cat_modal_error = 'Session expirée ou formulaire invalide. Veuillez réessayer.';
        $cat_modal_open = true;
    } else {
        require_once __DIR__ . '/../../controllers/controller_categories.php';
        $cat_modal_result = process_add_categorie();
        if (!empty($cat_modal_result['success'])) {
            $_SESSION['success_message'] = $cat_modal_result['message'];
            header('Location: index.php');
            exit;
        }
        $cat_modal_error = $cat_modal_result['message'] ?? 'Une erreur est survenue.';
        $cat_modal_open = true;
        $cat_modal_nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
        $cat_modal_description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    }
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($stock_catalogue_vendeur_seul) {
    $categories = get_categories_platform_for_vendeur_stock((int) ($_SESSION['admin_id'] ?? 0));
} else {
    $categories = get_all_categories();
}
$nb_cat = count($categories);

$rayons_avec_produits = [];
if (function_exists('get_categories_generales_avec_produits_actifs')) {
    $rayon_boutique_id = !empty($stock_catalogue_vendeur_seul) ? (int) ($_SESSION['admin_id'] ?? 0) : null;
    $rayons_avec_produits = get_categories_generales_avec_produits_actifs(
        ($rayon_boutique_id !== null && $rayon_boutique_id > 0) ? $rayon_boutique_id : null
    );
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du stock — Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        /* Page stock — cohérent avec variables.css (importé via admin-dashboard) */
        .stock-page {
            --stock-radius: 18px;
            --stock-radius-sm: 12px;
        }

        .stock-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 1rem 1.25rem 3rem;
        }

        .stock-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.25rem 1.5rem;
            padding: 1.6rem 1.75rem;
            margin-bottom: 1.35rem;
            background: var(--fond-secondaire);
            border: 1px solid var(--border-input);
            border-radius: var(--stock-radius);
            box-shadow: var(--ombre-douce);
            border-left: 5px solid var(--couleur-dominante);
            position: relative;
            overflow: hidden;
        }

        .stock-hero::after {
            content: "";
            position: absolute;
            top: -40%;
            right: -15%;
            width: 45%;
            height: 140%;
            background: radial-gradient(ellipse, var(--bleu-pale) 0%, transparent 70%);
            pointer-events: none;
        }

        .stock-hero__title-wrap {
            position: relative;
            z-index: 1;
        }

        .stock-hero h1 {
            margin: 0 0 0.35rem;
            font-family: var(--font-titres);
            font-size: clamp(1.45rem, 2.5vw, 1.85rem);
            font-weight: 700;
            color: var(--titres);
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .stock-hero h1 i {
            color: var(--couleur-dominante);
            font-size: 1.1em;
        }

        .stock-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            background: var(--fond-principal);
            color: var(--couleur-dominante);
            border: 1px solid var(--border-input);
            box-shadow: 0 1px 4px rgba(53, 100, 166, 0.08);
        }

        .stock-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            position: relative;
            z-index: 1;
        }

        .stock-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.72rem 1.15rem;
            border-radius: var(--stock-radius-sm);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            border: 2px solid transparent;
        }

        .stock-btn--ghost {
            background: var(--fond-principal);
            color: var(--couleur-dominante);
            border-color: var(--border-input);
            box-shadow: 0 2px 8px rgba(53, 100, 166, 0.08);
        }

        .stock-btn--ghost:hover {
            border-color: var(--couleur-dominante);
            box-shadow: var(--ombre-douce);
            transform: translateY(-2px);
        }

        .stock-btn--accent {
            background: linear-gradient(135deg, var(--couleur-dominante) 0%, var(--bleu-fonce) 100%);
            color: var(--texte-clair);
            box-shadow: var(--ombre-promo);
        }

        .stock-btn--accent:hover {
            background: linear-gradient(135deg, var(--couleur-dominante-hover) 0%, var(--bleu-fonce) 100%);
            transform: translateY(-2px);
        }

        .stock-banner-ok {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.25rem;
            border-radius: var(--stock-radius-sm);
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--titres);
            font-weight: 500;
        }

        .stock-banner-ok i {
            color: var(--couleur-dominante);
        }

        .stock-section {
            background: var(--fond-principal);
            border: 1px solid var(--glass-border);
            border-radius: var(--stock-radius);
            padding: 1.35rem 1.35rem 1.6rem;
            box-shadow: var(--glass-shadow);
        }

        .stock-section__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.35rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-input);
        }

        .stock-section__head h2 {
            margin: 0;
            font-family: var(--font-titres);
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--titres);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stock-section__head h2 i {
            color: var(--accent-promo);
        }

        .stock-cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(288px, 1fr));
            gap: 1.15rem;
        }

        .stock-cat-card {
            display: flex;
            flex-direction: column;
            background: var(--fond-principal);
            border-radius: var(--stock-radius-sm);
            overflow: hidden;
            box-shadow: 0 2px 14px rgba(53, 100, 166, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            border: 1px solid var(--border-input);
        }

        .stock-cat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--ombre-gourmande);
            border-color: rgba(53, 100, 166, 0.35);
        }

        .stock-cat-card__media {
            position: relative;
            aspect-ratio: 16 / 10;
            background: linear-gradient(180deg, var(--fond-secondaire) 0%, var(--blanc-neige) 100%);
            overflow: hidden;
        }

        .stock-cat-card__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .stock-cat-card__placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--couleur-dominante);
            font-size: 2.25rem;
            opacity: 0.55;
        }

        .stock-cat-card__body {
            padding: 1.1rem 1.15rem 1.15rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 0.65rem;
        }

        .stock-cat-card__body h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--titres);
            line-height: 1.3;
        }

        .stock-cat-card__desc {
            margin: 0;
            font-size: 0.86rem;
            line-height: 1.45;
            color: var(--texte-mute);
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stock-cat-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.25rem;
        }

        .stock-cat-card__actions a {
            flex: 1 1 auto;
            min-width: 6.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 0.65rem;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s, color 0.15s, transform 0.15s;
        }

        .stock-act-view {
            background: var(--bleu-pale);
            color: var(--couleur-dominante);
            border: 1px solid var(--border-input);
        }

        .stock-act-view:hover {
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            transform: translateY(-1px);
        }

        .stock-act-edit {
            background: var(--fond-secondaire);
            color: var(--gris-fonce);
            border: 1px solid var(--border-input);
        }

        .stock-act-edit:hover {
            border-color: var(--couleur-dominante);
            color: var(--couleur-dominante);
        }

        .stock-act-del {
            background: var(--error-bg);
            color: var(--orange-fonce);
            border: 1px solid var(--error-border);
        }

        .stock-act-del:hover {
            background: var(--orange);
            color: var(--texte-clair);
            border-color: var(--orange);
        }

        .stock-empty {
            text-align: center;
            padding: 2.75rem 1.5rem;
            background: var(--fond-secondaire);
            border-radius: var(--stock-radius-sm);
            border: 1px dashed var(--border-input);
        }

        .stock-empty i {
            font-size: 2.5rem;
            color: var(--couleur-dominante);
            opacity: 0.45;
            margin-bottom: 1rem;
        }

        .stock-empty h3 {
            margin: 0 0 0.5rem;
            font-family: var(--font-titres);
            color: var(--titres);
        }

        .stock-empty p {
            margin: 0 0 1.25rem;
            color: var(--texte-mute);
            font-size: 0.95rem;
        }

        @media (max-width: 640px) {
            .stock-hero {
                padding: 1.25rem;
            }

            .stock-hero__actions {
                width: 100%;
            }

            .stock-btn {
                flex: 1 1 auto;
                justify-content: center;
            }
        }

        button.stock-btn {
            cursor: pointer;
            font-family: inherit;
            border: none;
        }

        /* Modal plein écran — nouvelle catégorie */
        .stock-cat-modal {
            position: fixed;
            inset: 0;
            z-index: 10050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.75rem, 3vw, 1.5rem);
            background: rgba(13, 13, 13, 0.55);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.28s ease, visibility 0.28s ease;
        }

        .stock-cat-modal.stock-cat-modal--open {
            opacity: 1;
            visibility: visible;
        }

        .stock-cat-modal__panel {
            width: 100%;
            max-width: 32rem;
            max-height: min(92vh, 46rem);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: var(--fond-principal);
            border-radius: 22px;
            box-shadow:
                0 25px 60px rgba(53, 100, 166, 0.22),
                0 0 0 1px rgba(53, 100, 166, 0.12);
            transform: translateY(12px) scale(0.98);
            transition: transform 0.32s cubic-bezier(0.34, 1.2, 0.64, 1);
        }

        .stock-cat-modal.stock-cat-modal--open .stock-cat-modal__panel {
            transform: translateY(0) scale(1);
        }

        .stock-cat-modal__head {
            flex-shrink: 0;
            padding: 1.35rem 1.35rem 1rem;
            background: linear-gradient(135deg, var(--couleur-dominante) 0%, var(--bleu-fonce) 100%);
            color: var(--texte-clair);
            position: relative;
        }

        .stock-cat-modal__head-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .stock-cat-modal__head h2 {
            margin: 0;
            font-family: var(--font-titres);
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .stock-cat-modal__head p {
            margin: 0.4rem 0 0;
            font-size: 0.88rem;
            opacity: 0.92;
            line-height: 1.45;
        }

        .stock-cat-modal__close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            color: var(--texte-clair);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .stock-cat-modal__close:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: scale(1.05);
        }

        .stock-cat-modal__body {
            flex: 1;
            overflow-y: auto;
            padding: 1.35rem 1.35rem 1.5rem;
            -webkit-overflow-scrolling: touch;
        }

        .stock-cat-modal__err {
            display: flex;
            gap: 0.65rem;
            padding: 0.85rem 1rem;
            margin-bottom: 1.15rem;
            border-radius: 12px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--titres);
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .stock-cat-modal__err i {
            flex-shrink: 0;
            margin-top: 0.1rem;
            color: var(--orange);
        }

        .stock-cat-field {
            margin-bottom: 1.15rem;
        }

        .stock-cat-field label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--titres);
            margin-bottom: 0.45rem;
        }

        .stock-cat-field label .hint {
            font-weight: 400;
            color: var(--texte-mute);
        }

        .stock-cat-field input[type="text"],
        .stock-cat-field textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid var(--border-input);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: var(--font-corps);
            color: var(--texte-fonce);
            background: var(--fond-principal);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .stock-cat-field textarea {
            min-height: 6.5rem;
            resize: vertical;
        }

        .stock-cat-field input:focus,
        .stock-cat-field textarea:focus {
            outline: none;
            border-color: var(--couleur-dominante);
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        .stock-cat-file {
            position: relative;
            border: 2px dashed var(--border-input);
            border-radius: 14px;
            padding: 1.1rem 1rem;
            text-align: center;
            background: var(--fond-secondaire);
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .stock-cat-file:hover {
            border-color: var(--couleur-dominante);
            background: var(--bleu-pale);
        }

        .stock-cat-file input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .stock-cat-file__hint {
            font-size: 0.8rem;
            color: var(--texte-mute);
            margin-top: 0.35rem;
        }

        .stock-cat-modal__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1.25rem;
            padding-top: 1.1rem;
            border-top: 1px solid var(--border-input);
        }

        .stock-cat-modal__btn {
            flex: 1 1 auto;
            min-width: 8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.85rem 1.1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            font-family: inherit;
            border: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .stock-cat-modal__btn--ghost {
            background: var(--fond-secondaire);
            color: var(--gris-fonce);
            border: 2px solid var(--border-input);
        }

        .stock-cat-modal__btn--ghost:hover {
            border-color: var(--couleur-dominante);
            color: var(--couleur-dominante);
        }

        .stock-cat-modal__btn--primary {
            background: linear-gradient(135deg, var(--couleur-dominante) 0%, var(--bleu-fonce) 100%);
            color: var(--texte-clair);
            box-shadow: var(--ombre-promo);
        }

        .stock-cat-modal__btn--primary:hover {
            transform: translateY(-2px);
        }

        body.stock-cat-modal-active {
            overflow: hidden;
        }

        .stock-section--rayons {
            margin-bottom: 1.35rem;
        }

        .stock-rayon-count {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--couleur-dominante);
        }
    </style>
</head>

<body class="stock-page<?php echo ($cat_modal_open && empty($stock_catalogue_vendeur_seul)) ? ' stock-cat-modal-active' : ''; ?>">
    <?php include '../includes/nav.php'; ?>

    <div class="stock-shell">
        <header class="stock-hero">
            <div class="stock-hero__title-wrap">
                <h1><i class="fas fa-boxes-stacked" aria-hidden="true"></i> Gestion du stock</h1>
                <span class="stock-hero__badge" aria-live="polite">
                    <i class="fas fa-tags" aria-hidden="true"></i>
                    <?php echo (int) $nb_cat; ?>
                    <?php if (!empty($stock_catalogue_vendeur_seul)): ?>
                        rayon<?php echo $nb_cat > 1 ? 's' : ''; ?> (sous-cat. plateforme) avec produits
                    <?php else: ?>
                        catégorie<?php echo $nb_cat > 1 ? 's' : ''; ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="stock-hero__actions">
                <a href="mouvements.php" class="stock-btn stock-btn--ghost">
                    <i class="fas fa-history" aria-hidden="true"></i> Historique des mouvements
                </a>
                <?php if (empty($stock_catalogue_vendeur_seul)): ?>
                <button type="button" class="stock-btn stock-btn--accent js-open-stock-cat-modal">
                    <i class="fas fa-plus" aria-hidden="true"></i> Nouvelle catégorie
                </button>
                <?php endif; ?>
        </div>
        </header>

    <?php if (!empty($success_message)): ?>
        <div class="stock-banner-ok" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

        <?php if (!empty($rayons_avec_produits)): ?>
        <section class="stock-section stock-section--rayons" aria-labelledby="stock-rayons-heading">
            <div class="stock-section__head">
                <h2 id="stock-rayons-heading"><i class="fas fa-store" aria-hidden="true"></i>
                    Rayons — produits publiés
                </h2>
            </div>
            <div class="stock-cat-grid">
                <?php foreach ($rayons_avec_produits as $rayon): ?>
                <article class="stock-cat-card">
                    <div class="stock-cat-card__media">
                        <div class="stock-cat-card__placeholder" aria-hidden="true">
                            <i class="<?php echo htmlspecialchars(categorie_fa_icon_class($rayon)); ?>"></i>
                        </div>
                    </div>
                    <div class="stock-cat-card__body">
                        <h3><?php echo htmlspecialchars($rayon['nom'] ?? ''); ?></h3>
                        <p class="stock-cat-card__desc">
                            <?php
                            $rdesc = trim((string) ($rayon['description'] ?? ''));
                            echo $rdesc !== '' ? htmlspecialchars($rdesc) : 'Produits actifs listés pour ce rayon.';
                            ?>
                        </p>
                        <p class="stock-rayon-count">
                            <i class="fas fa-box-open" aria-hidden="true"></i>
                            <?php echo (int) ($rayon['nb_produits_actifs'] ?? 0); ?>
                            produit<?php echo ((int) ($rayon['nb_produits_actifs'] ?? 0)) > 1 ? 's' : ''; ?> publié<?php echo ((int) ($rayon['nb_produits_actifs'] ?? 0)) > 1 ? 's' : ''; ?>
                        </p>
                        <div class="stock-cat-card__actions">
                            <a href="../produits/index.php?categorie_generale_id=<?php echo (int) ($rayon['id'] ?? 0); ?>"
                                class="stock-act-view">
                                <i class="fas fa-list" aria-hidden="true"></i> Voir les produits publiés
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="stock-section" aria-labelledby="stock-cat-heading">
            <div class="stock-section__head">
                <h2 id="stock-cat-heading"><i class="fas fa-layer-group" aria-hidden="true"></i>
                    <?php echo !empty($stock_catalogue_vendeur_seul) ? 'Sous-catégories plateforme (vos produits)' : 'Catalogue par catégorie'; ?>
                </h2>
        </div>

        <?php if (empty($categories)): ?>
            <div class="stock-empty">
                <i class="fas fa-tags" aria-hidden="true"></i>
                <h3>Aucune catégorie</h3>
                <p><?php echo !empty($stock_catalogue_vendeur_seul)
                    ? 'Les rayons et sous-catégories sont gérés par la plateforme. Les catégories apparaîtront ici dès que vous aurez classé des produits dans une sous-catégorie lors de l’ajout d’article.'
                    : 'Créez une première catégorie pour organiser vos produits et le stock.'; ?></p>
                <?php if (empty($stock_catalogue_vendeur_seul)): ?>
                <button type="button" class="stock-btn stock-btn--accent js-open-stock-cat-modal">
                    <i class="fas fa-plus" aria-hidden="true"></i> Ajouter une catégorie
                </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="stock-cat-grid">
                <?php foreach ($categories as $categorie): ?>
                <article class="stock-cat-card">
                    <div class="stock-cat-card__media">
                        <?php if (!empty($categorie['image'])): ?>
                                <img src="/upload/<?php echo htmlspecialchars($categorie['image']); ?>"
                            alt=""
                            onerror="this.onerror=null;this.src='/image/produit1.jpg'">
                            <?php else: ?>
                        <div class="stock-cat-card__placeholder" aria-hidden="true">
                                    <i class="fas fa-tag"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <div class="stock-cat-card__body">
                        <h3><?php echo htmlspecialchars($categorie['nom']); ?></h3>
                        <p class="stock-cat-card__desc">
                                <?php echo htmlspecialchars($categorie['description'] ?? 'Aucune description'); ?>
                            </p>
                        <div class="stock-cat-card__actions">
                            <a href="../categories/produits.php?id=<?php echo (int) $categorie['id']; ?>"
                                class="stock-act-view">
                                <i class="fas fa-box" aria-hidden="true"></i> Produits
                            </a>
                            <?php if (empty($stock_catalogue_vendeur_seul)): ?>
                            <a href="../categories/modifier.php?id=<?php echo (int) $categorie['id']; ?>"
                                class="stock-act-edit">
                                <i class="fas fa-edit" aria-hidden="true"></i> Modifier
                            </a>
                            <a href="../categories/supprimer.php?id=<?php echo (int) $categorie['id']; ?>"
                                class="stock-act-del"
                                onclick="return confirm('Supprimer cette catégorie ?');">
                                <i class="fas fa-trash" aria-hidden="true"></i> Supprimer
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    </div>

    <?php if (empty($stock_catalogue_vendeur_seul)): ?>
    <div class="stock-cat-modal<?php echo $cat_modal_open ? ' stock-cat-modal--open' : ''; ?>" id="stockCatModal"
        role="dialog" aria-modal="true" aria-labelledby="stockCatModalTitle"<?php echo $cat_modal_open ? '' : ' hidden'; ?>>
        <div class="stock-cat-modal__panel" role="document">
            <div class="stock-cat-modal__head">
                <button type="button" class="stock-cat-modal__close js-close-stock-cat-modal" aria-label="Fermer">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
                <div class="stock-cat-modal__head-icon" aria-hidden="true">
                    <i class="fas fa-folder-plus"></i>
                </div>
                <h2 id="stockCatModalTitle">Nouvelle catégorie</h2>
                <p>Renseignez le nom de votre rayon. L’image et la description sont optionnelles pour mettre en valeur le
                    catalogue.</p>
            </div>
            <div class="stock-cat-modal__body">
                <?php if ($cat_modal_error !== ''): ?>
                <div class="stock-cat-modal__err" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <span><?php echo $cat_modal_error; ?></span>
                </div>
                <?php endif; ?>

                <form method="post" action="" enctype="multipart/form-data" id="stockCatModalForm">
                    <input type="hidden" name="stock_add_categorie" value="1">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="stock-cat-field">
                        <label for="stock_cat_nom">Nom <span class="hint">(obligatoire)</span></label>
                        <input type="text" id="stock_cat_nom" name="nom" required maxlength="255" autocomplete="off"
                            placeholder="Ex. Freinage, Huiles moteur…"
                            value="<?php echo htmlspecialchars($cat_modal_nom, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="stock-cat-field">
                        <label for="stock_cat_desc">Description <span class="hint">(optionnel)</span></label>
                        <textarea id="stock_cat_desc" name="description"
                            placeholder="Quelques mots pour décrire ce rayon aux clients."><?php echo htmlspecialchars($cat_modal_description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="stock-cat-field">
                        <label>Visuel <span class="hint">(optionnel)</span></label>
                        <div class="stock-cat-file">
                            <i class="fas fa-cloud-arrow-up" style="font-size:1.35rem;color:var(--couleur-dominante);margin-bottom:0.35rem;"></i>
                            <div><strong>Glissez une image</strong> ou cliquez pour parcourir</div>
                            <p class="stock-cat-file__hint">JPG, PNG, GIF ou WEBP — max. 20 Mo</p>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                    </div>

                    <div class="stock-cat-modal__actions">
                        <button type="button" class="stock-cat-modal__btn stock-cat-modal__btn--ghost js-close-stock-cat-modal">
                            Annuler
                        </button>
                        <button type="submit" class="stock-cat-modal__btn stock-cat-modal__btn--primary">
                            <i class="fas fa-check" aria-hidden="true"></i> Enregistrer la catégorie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        (function () {
            var modal = document.getElementById('stockCatModal');
            if (!modal) return;

            function openModal() {
                modal.classList.add('stock-cat-modal--open');
                modal.removeAttribute('hidden');
                document.body.classList.add('stock-cat-modal-active');
            }

            function closeModal() {
                modal.classList.remove('stock-cat-modal--open');
                modal.setAttribute('hidden', 'hidden');
                document.body.classList.remove('stock-cat-modal-active');
            }

            document.querySelectorAll('.js-open-stock-cat-modal').forEach(function (btn) {
                btn.addEventListener('click', openModal);
            });
            document.querySelectorAll('.js-close-stock-cat-modal').forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('stock-cat-modal--open')) closeModal();
            });

        })();
    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>

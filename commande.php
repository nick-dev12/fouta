<?php
/**
 * Page de commande
 * Programmation procédurale uniquement
 */

ob_start();

session_start();

require_once __DIR__ . '/includes/marketplace_helpers.php';
require_once __DIR__ . '/models/model_admin.php';

$commande_boutique_slug = trim((string) ($_GET['boutique'] ?? ''));
$commande_boutique_admin = null;
if ($commande_boutique_slug !== '') {
    $commande_boutique_admin = get_admin_by_boutique_slug($commande_boutique_slug);
    if (!$commande_boutique_admin) {
        header('Location: /panier.php');
        exit;
    }
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $redir_cmd = '/commande.php';
    if ($commande_boutique_slug !== '' && $commande_boutique_admin) {
        $redir_cmd .= '?boutique=' . rawurlencode($commande_boutique_slug);
    }
    header('Location: /user/connexion.php?redirect=' . rawurlencode($redir_cmd));
    exit;
}

// Inclusion des modèles et contrôleurs
require_once __DIR__ . '/models/model_panier.php';
require_once __DIR__ . '/models/model_users.php';
require_once __DIR__ . '/controllers/controller_commandes.php';

// Traitement du formulaire
$message = '';
$message_type = '';
$commande_id = null;
$numero_commande = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_commande') {
    $result = process_create_commande();

    if ($result['success']) {
        // Push immédiat ; emails mis en file d'attente (arrière-plan)
        if (file_exists(__DIR__ . '/services/send_new_commande_to_admin.php')) {
            require_once __DIR__ . '/services/send_new_commande_to_admin.php';
            require_once __DIR__ . '/services/send_commande_notification.php';
            try {
                if (!empty($result['notifications']) && is_array($result['notifications'])) {
                    foreach ($result['notifications'] as $n) {
                        if (empty($n['vendeur_id']) || empty($n['numero_commande'])) {
                            continue;
                        }
                        send_new_commande_to_vendeur(
                            (int) $n['vendeur_id'],
                            isset($n['commande_id']) ? (int) $n['commande_id'] : 0,
                            (string) $n['numero_commande'],
                            (float) ($n['montant_total'] ?? 0),
                            (int) ($n['nombre_articles'] ?? 0),
                            (string) ($n['telephone_livraison'] ?? ''),
                            (string) ($n['adresse_livraison'] ?? ''),
                            is_array($n['produits'] ?? null) ? $n['produits'] : []
                        );
                    }
                } elseif (!empty($result['email_data'])) {
                    $d = $result['email_data'];
                    send_new_commande_to_admin(
                        $d['numero_commande'],
                        $d['montant_total'],
                        $d['nombre_articles'],
                        $d['telephone_livraison'] ?? '',
                        $d['adresse_livraison'] ?? '',
                        $d['produits'] ?? []
                    );
                }

                $nums_client = !empty($result['numeros_commandes']) ? $result['numeros_commandes'] : [$result['numero_commande']];
                $montant_client = isset($result['email_data']['montant_total']) ? (float) $result['email_data']['montant_total'] : 0;
                send_new_commande_confirmation_to_client(
                    (int) $_SESSION['user_id'],
                    $nums_client,
                    $montant_client,
                    trim($_SESSION['user_email'] ?? '')
                );
            } catch (Throwable $e) {
                error_log('[commande.php] notifications : ' . $e->getMessage());
            }
        }

        $nums = !empty($result['numeros_commandes']) ? $result['numeros_commandes'] : [$result['numero_commande']];
        $redirect_url = '/user/mes-commandes.php?success=1&numeros=' . urlencode(implode(',', $nums));
        header('Location: ' . $redirect_url);
        exit;
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}

// Récupérer les informations de l'utilisateur
$user = get_user_by_id($_SESSION['user_id']);

// Position déjà connue : session (fraîche) en priorité, sinon dernière position BDD
require_once __DIR__ . '/includes/geo_location_service.php';
$geo_saved = geo_session_get_location();
if ($geo_saved === null && $user) {
    $u_lat = geo_parse_coord($user['last_latitude'] ?? null);
    $u_lng = geo_parse_coord($user['last_longitude'] ?? null);
    if (geo_coords_valid($u_lat, $u_lng)) {
        $geo_saved = [
            'lat' => $u_lat,
            'lng' => $u_lng,
            'precision' => geo_parse_precision($user['last_geo_precision'] ?? null),
        ];
    }
}

// Pays marketplace pour filtrer la recherche d'adresse (Sénégal, CI, Gabon…)
$geo_search_country_iso = geo_search_country_nominatim($commande_boutique_admin);
$geo_search_country_label = geo_search_country_label($commande_boutique_admin);

// Récupérer les produits du panier (éventuellement limités à une boutique)
$panier_items = get_panier_by_user($_SESSION['user_id']);
if ($commande_boutique_admin) {
    $panier_items = filter_panier_items_by_vendeur($panier_items, (int) $commande_boutique_admin['id']);
}

// Vérifier que le panier n'est pas vide
if (empty($panier_items)) {
    if ($commande_boutique_admin && $commande_boutique_slug !== '') {
        header('Location: ' . boutique_url('panier.php', $commande_boutique_slug));
    } else {
        header('Location: /panier.php');
    }
    exit;
}

// Calculer le total (sous-ensemble affiché)
$panier_total = panier_items_sous_total($panier_items);
$nombre_total_articles = panier_items_count_quantites($panier_items);

require_once __DIR__ . '/includes/commande_mode_helpers.php';
$commande_pickup_boutiques = commande_pickup_boutiques_from_panier($panier_items);
$commande_mode_selected = commande_mode_livraison_normalize($_POST['mode_livraison'] ?? 'livraison');

// Contexte badge panier (commande limitée à une boutique)
if ($commande_boutique_admin) {
    $GLOBALS['nav_panier_count_for_vendeur_id'] = (int) $commande_boutique_admin['id'];
}

// Inclusion de la barre de navigation
include 'nav_bar.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <title>Passer la commande - COLObanes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php require_once __DIR__ . '/includes/auth_intl_tel_head.php'; ?>
    <style>
    .commande-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .commande-wrapper {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        margin-top: 30px;
        max-width: 827px;
        margin-left: auto;
        margin-right: auto;
    }

    .commande-form-section {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 30px;
        box-shadow: var(--glass-shadow);
    }

    .commande-summary-section {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 30px;
        box-shadow: var(--glass-shadow);
        height: fit-content;
    }

    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--titres);
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-input);
        font-family: var(--font-titres);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--titres);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--border-input);
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        font-family: inherit;
        background: var(--blanc);
        cursor: pointer;
    }

    .form-group select:focus {
        outline: none;
        border-color: var(--border-input-focus);
        box-shadow: 0 0 0 3px var(--focus-ring);
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--border-input);
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        font-family: inherit;
        background: var(--blanc);
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--border-input-focus);
        box-shadow: 0 0 0 3px var(--focus-ring);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-group .iti {
        width: 100%;
        display: block;
    }

    .form-group .iti input[type="tel"] {
        width: 100%;
        padding-left: 52px;
    }

    .form-group small {
        display: block;
        color: var(--gris-moyen);
        font-size: 12px;
        margin-top: 5px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-input);
    }

    .summary-item:last-child {
        border-bottom: none;
    }

    .summary-item-label {
        color: var(--texte-fonce);
        font-size: 14px;
    }

    .summary-item-value {
        color: var(--titres);
        font-weight: 600;
        font-size: 14px;
    }

    .summary-total {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid var(--couleur-dominante);
    }

    .summary-total .summary-item-label {
        font-size: 18px;
        font-weight: 700;
        color: var(--titres);
    }

    .summary-total .summary-item-value {
        font-size: 20px;
        color: var(--accent-promo);
    }

    .btn-submit-commande {
        width: 100%;
        padding: 15px;
        background: var(--couleur-dominante);
        color: var(--texte-clair);
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-submit-commande:hover {
        background: var(--couleur-dominante-hover);
        transform: translateY(-2px);
        box-shadow: var(--ombre-promo);
        color: var(--texte-clair);
    }

    .btn-submit-commande:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
        transform: none;
    }

    .message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .message.error {
        background: var(--error-bg);
        color: var(--titres);
        border: 1px solid var(--error-border);
    }

    .panier-item-summary {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-input);
    }

    .panier-item-summary:last-child {
        border-bottom: none;
    }

    .panier-item-summary img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }

    .panier-item-summary-info {
        flex: 1;
    }

    .panier-item-summary-info h4 {
        font-size: 14px;
        color: var(--titres);
        margin-bottom: 5px;
        font-weight: 600;
    }

    .panier-item-boutique {
        font-size: 12px;
        color: var(--orange, #c26638);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .panier-item-summary-info p {
        font-size: 12px;
        color: var(--gris-moyen);
        margin: 0;
    }

    .panier-item-summary-price {
        font-size: 14px;
        font-weight: 600;
        color: var(--titres);
    }

    .commande-page-title {
        font-size: 28px;
        color: var(--titres);
        margin-bottom: 10px;
        font-family: var(--font-titres);
    }

    .commande-page-subtitle {
        color: var(--gris-moyen);
        margin-bottom: 30px;
    }

    .summary-livraison {
        color: var(--gris-moyen);
    }

    .commande-link-retour {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: var(--couleur-dominante);
        text-decoration: none;
        font-weight: 500;
    }

    .commande-link-retour:hover {
        color: var(--orange);
        text-decoration: underline;
    }

    /* Bloc consentement géolocalisation */
    .geo-consent-box {
        background: var(--bleu-pale);
        border: 1px solid var(--border-input);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .geo-consent-box .geo-consent-text {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: var(--texte-fonce);
        margin-bottom: 12px;
    }

    .geo-consent-box .geo-consent-text i {
        color: var(--couleur-dominante);
        font-size: 18px;
        margin-top: 2px;
    }

    .btn-geo-capture {
        background: var(--couleur-dominante);
        color: var(--texte-clair);
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-geo-capture:hover {
        background: var(--couleur-dominante-hover);
    }

    .geo-status {
        margin-top: 12px;
        font-size: 13px;
        padding: 10px 12px;
        border-radius: 6px;
    }

    .geo-status[data-geo-state="pending"] {
        background: var(--blanc-neige);
        color: var(--gris-fonce);
    }

    .geo-status[data-geo-state="ok"] {
        background: var(--success-bg);
        border: 1px solid var(--success-border);
        color: var(--texte-fonce);
    }

    .geo-status[data-geo-state="error"] {
        background: var(--error-bg);
        border: 1px solid var(--error-border);
        color: var(--texte-fonce);
    }

    .geo-map {
        margin-top: 14px;
        height: 280px;
        border-radius: 8px;
        border: 1px solid var(--border-input);
        overflow: hidden;
        z-index: 0;
    }

    .geo-search-wrap {
        position: relative;
        margin-bottom: 14px;
    }

    .geo-search-label {
        display: block;
        font-weight: 600;
        color: var(--titres);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .geo-search-input-wrap {
        position: relative;
    }

    .geo-search-input-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gris-moyen);
        pointer-events: none;
    }

    .geo-search-input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 2px solid var(--border-input);
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: var(--blanc);
    }

    .geo-search-input:focus {
        outline: none;
        border-color: var(--border-input-focus);
        box-shadow: 0 0 0 3px var(--focus-ring);
    }

    .geo-search-suggestions {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 500;
        background: var(--blanc);
        border: 1px solid var(--border-input);
        border-radius: 8px;
        box-shadow: var(--ombre-douce);
        max-height: 240px;
        overflow-y: auto;
    }

    .geo-search-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
        padding: 11px 14px;
        border: none;
        border-bottom: 1px solid rgba(53, 100, 166, 0.08);
        background: transparent;
        text-align: left;
        cursor: pointer;
        font-size: 13px;
        color: var(--texte-fonce);
        font-family: inherit;
        line-height: 1.35;
    }

    .geo-search-item:last-child { border-bottom: none; }

    .geo-search-item:hover {
        background: var(--bleu-pale);
    }

    .geo-search-item i {
        color: var(--couleur-dominante);
        margin-top: 2px;
        flex-shrink: 0;
    }

    .geo-search-empty {
        padding: 12px 14px;
        font-size: 13px;
        color: var(--gris-moyen);
    }

    .geo-search-hint {
        font-size: 12px;
        color: var(--gris-moyen);
        margin-top: 6px;
    }

    .cmd-mode-chooser {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 20px;
    }

    .cmd-mode-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px 12px;
        border: 2px solid var(--border-input);
        border-radius: 12px;
        background: var(--blanc);
        color: var(--texte-fonce);
        font-family: inherit;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        text-align: center;
        min-height: 88px;
    }

    .cmd-mode-btn i {
        font-size: 1.35rem;
        color: var(--couleur-dominante);
    }

    .cmd-mode-btn.is-active {
        border-color: var(--couleur-dominante);
        background: var(--bleu-pale);
        box-shadow: var(--ombre-douce);
    }

    .cmd-mode-panel { display: none; }
    .cmd-mode-panel.is-visible { display: block; }

    .cmd-pickup-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 8px;
    }

    .cmd-pickup-card {
        border: 1px solid var(--border-input);
        border-radius: 10px;
        padding: 14px 16px;
        background: var(--blanc-neige);
    }

    .cmd-pickup-card__name {
        font-weight: 800;
        color: var(--titres);
        margin: 0 0 6px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .cmd-pickup-card__name i { color: var(--couleur-dominante); }

    .cmd-pickup-card__addr {
        margin: 0;
        font-size: 0.85rem;
        color: var(--gris-fonce);
        line-height: 1.45;
    }

    .cmd-pickup-card__tel {
        margin: 8px 0 0;
        font-size: 0.82rem;
        color: var(--gris-moyen);
    }

    .cmd-pickup-hint {
        font-size: 0.82rem;
        color: var(--gris-moyen);
        margin: 0 0 16px;
        line-height: 1.45;
    }

    .cmd-pickup-card__maps {
        margin: 10px 0 0;
        font-size: 0.82rem;
    }

    .cmd-pickup-card__maps a {
        color: var(--couleur-dominante);
        font-weight: 700;
        text-decoration: none;
    }

    .cmd-pickup-card__maps a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
        .cmd-mode-chooser { grid-template-columns: 1fr; }
        .cmd-mode-btn {
            min-height: 72px;
            flex-direction: row;
            justify-content: flex-start;
            padding: 14px 16px;
        }
    }

    /* Styles pour éviter que le footer s'incruste */
    .commande-container {
        margin-bottom: 100px;
        min-height: calc(100vh - 200px);
    }

    /* Footer - hérite du style global a_style.css */
    </style>
</head>

<body>

    <div class="commande-container">
        <h1 class="commande-page-title">
            <i class="fas fa-shopping-bag"></i> Passer la commande
        </h1>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="commande-wrapper">
            <!-- Résumé en premier (au-dessus du formulaire) -->
            <div class="commande-summary-section">
                <h2 class="section-title">
                    <i class="fas fa-shopping-cart"></i> Résumé
                </h2>

                <div style="margin-bottom: 20px;">
                    <?php foreach ($panier_items as $item): ?>
                    <?php
                        $prix_unitaire = (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0)
                            ? (float) $item['panier_prix_unitaire']
                            : (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix'] ? $item['prix_promotion'] : $item['prix']);
                        $prix_total_item = $prix_unitaire * $item['quantite'];
                        $item_img = !empty($item['panier_variante_image']) ? $item['panier_variante_image'] : $item['image_principale'];
                        ?>
                    <div class="panier-item-summary">
                        <div class="panier-item-img">
                            <img src="/upload/<?php echo htmlspecialchars($item_img); ?>"
                                alt="<?php echo htmlspecialchars($item['nom']); ?>"
                                onerror="this.src='/image/produit1.jpg'">
                        </div>
                        <div class="panier-item-summary-info">
                            <?php if (!empty($item['vendeur_boutique_nom'])): ?>
                            <div class="panier-item-boutique"><i class="fas fa-store"></i> <?php echo htmlspecialchars($item['vendeur_boutique_nom']); ?></div>
                            <?php endif; ?>
                            <h4><?php echo htmlspecialchars(!empty($item['panier_variante_nom']) ? $item['nom'] . ' - ' . $item['panier_variante_nom'] : $item['nom']); ?></h4>
                            <?php
                            $opts = [];
                            if (!empty($item['panier_couleur'])) $opts[] = 'Couleur: ' . htmlspecialchars($item['panier_couleur']);
                            if (!empty($item['panier_poids'])) $opts[] = 'Poids: ' . htmlspecialchars($item['panier_poids']) . (!empty($item['panier_surcout_poids']) && $item['panier_surcout_poids'] > 0 ? ' (+' . number_format($item['panier_surcout_poids'], 0, ',', ' ') . ' FCFA)' : '');
                            if (!empty($item['panier_taille'])) $opts[] = 'Taille: ' . htmlspecialchars($item['panier_taille']) . (!empty($item['panier_surcout_taille']) && $item['panier_surcout_taille'] > 0 ? ' (+' . number_format($item['panier_surcout_taille'], 0, ',', ' ') . ' FCFA)' : '');
                            ?>
                            <?php if (!empty($opts)): ?>
                            <p style="font-size: 11px; color: var(--gris-moyen); margin-bottom: 4px;"><?php echo implode(' • ', $opts); ?></p>
                            <?php endif; ?>
                            <p>Quantité: <?php echo $item['quantite']; ?> × <?php echo number_format($prix_unitaire, 0, ',', ' '); ?> FCFA</p>
                        </div>
                        <div class="panier-item-summary-price">
                            <?php echo number_format($prix_total_item, 0, ',', ' '); ?> FCFA
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-item">
                    <span class="summary-item-label">Nombre d'articles</span>
                    <span class="summary-item-value"><?php echo $nombre_total_articles; ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-item-label">Nombre de produits</span>
                    <span class="summary-item-value"><?php echo count($panier_items); ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-item-label">Sous-total</span>
                    <span class="summary-item-value"><?php echo number_format($panier_total, 0, ',', ' '); ?>
                        FCFA</span>
                </div>

                <div class="summary-item">
                    <span class="summary-item-label">Livraison</span>
                    <span class="summary-item-value" id="summary-livraison">0 FCFA</span>
                </div>

                <div class="summary-total">
                    <div class="summary-item">
                        <span class="summary-item-label">Total général</span>
                        <span class="summary-item-value"
                            id="summary-total"><?php echo number_format($panier_total, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>

                <a href="<?php echo $commande_boutique_admin && $commande_boutique_slug !== '' ? htmlspecialchars(boutique_url('panier.php', $commande_boutique_slug), ENT_QUOTES, 'UTF-8') : '/panier.php'; ?>" class="commande-link-retour">
                    <i class="fas fa-arrow-left"></i> Retour au panier
                </a>
            </div>

            <div class="commande-form-section">
                <h2 class="section-title">
                    <i class="fas fa-truck-fast"></i> Mode de réception
                </h2>

                <form method="POST" action="" id="form-commande">
                    <input type="hidden" name="action" value="create_commande">
                    <input type="hidden" name="mode_livraison" id="mode_livraison"
                        value="<?php echo htmlspecialchars($commande_mode_selected, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($commande_boutique_admin && $commande_boutique_slug !== ''): ?>
                    <input type="hidden" name="boutique_slug" value="<?php echo htmlspecialchars($commande_boutique_slug, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>

                    <div class="cmd-mode-chooser" role="group" aria-label="Choisir le mode de réception">
                        <button type="button" class="cmd-mode-btn<?php echo $commande_mode_selected === 'retrait' ? ' is-active' : ''; ?>"
                            data-mode="retrait" id="cmd-mode-btn-retrait">
                            <i class="fas fa-store"></i>
                            <span>Récupérer sur site</span>
                        </button>
                        <button type="button" class="cmd-mode-btn<?php echo $commande_mode_selected === 'livraison' ? ' is-active' : ''; ?>"
                            data-mode="livraison" id="cmd-mode-btn-livraison">
                            <i class="fas fa-motorcycle"></i>
                            <span>Livraison</span>
                        </button>
                    </div>

                    <div id="panel-retrait" class="cmd-mode-panel<?php echo $commande_mode_selected === 'retrait' ? ' is-visible' : ''; ?>">
                        <p class="cmd-pickup-hint">
                            Vous récupérez votre commande directement en boutique. Voici les points de retrait concernés :
                        </p>
                        <div class="cmd-pickup-list">
                            <?php if (empty($commande_pickup_boutiques)): ?>
                            <p class="cmd-pickup-hint">Informations boutique indisponibles.</p>
                            <?php else: ?>
                            <?php foreach ($commande_pickup_boutiques as $boutique_pickup): ?>
                            <div class="cmd-pickup-card">
                                <p class="cmd-pickup-card__name">
                                    <i class="fas fa-store" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($boutique_pickup['nom'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php if (!empty($boutique_pickup['adresse_ligne'])): ?>
                                <p class="cmd-pickup-card__addr">
                                    <i class="fas fa-location-dot" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($boutique_pickup['adresse_ligne'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php else: ?>
                                <p class="cmd-pickup-card__addr" style="color:var(--gris-moyen);font-style:italic;">
                                    Adresse non renseignée — le vendeur peut la compléter dans ses paramètres.
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($boutique_pickup['maps_url'])): ?>
                                <p class="cmd-pickup-card__maps">
                                    <a href="<?php echo htmlspecialchars($boutique_pickup['maps_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                        target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-map"></i> Voir sur Google Maps
                                    </a>
                                </p>
                                <?php endif; ?>
                                <?php if ($boutique_pickup['telephone'] !== ''): ?>
                                <p class="cmd-pickup-card__tel">
                                    <i class="fas fa-phone" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($boutique_pickup['telephone'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="panel-livraison" class="cmd-mode-panel<?php echo $commande_mode_selected === 'livraison' ? ' is-visible' : ''; ?>">
                    <!-- Position exacte du client (remplie par le navigateur après consentement) -->
                    <input type="hidden" name="geo_lat" id="geo_lat"
                        value="<?php echo $geo_saved ? htmlspecialchars(number_format($geo_saved['lat'], 8, '.', ''), ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <input type="hidden" name="geo_lng" id="geo_lng"
                        value="<?php echo $geo_saved ? htmlspecialchars(number_format($geo_saved['lng'], 8, '.', ''), ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <input type="hidden" name="geo_precision" id="geo_precision"
                        value="<?php echo $geo_saved && $geo_saved['precision'] !== null ? (int) $geo_saved['precision'] : ''; ?>">
                    <input type="hidden" name="geo_source" id="geo_source" value="<?php echo $geo_saved ? 'gps' : ''; ?>">

                    <div class="geo-consent-box">
                        <div class="geo-consent-text">
                            <i class="fas fa-map-location-dot"></i>
                            <span>Indiquez où livrer la commande : votre position, celle d'un proche, ou une adresse recherchée.
                            Elle est transmise uniquement au vendeur.</span>
                        </div>

                        <div class="geo-search-wrap">
                            <label class="geo-search-label" for="geo_address_search">
                                <i class="fas fa-magnifying-glass"></i> Rechercher une adresse de livraison
                            </label>
                            <div class="geo-search-input-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" id="geo_address_search" class="geo-search-input"
                                    autocomplete="off"
                                    placeholder="Quartier, rue, marché, ville… ou collez une adresse">
                            </div>
                            <div id="geo_search_suggestions" class="geo-search-suggestions" aria-hidden="true"></div>
                            <p class="geo-search-hint">
                                Suggestions limitées au <?php echo htmlspecialchars($geo_search_country_label, ENT_QUOTES, 'UTF-8'); ?>.
                                Tapez un quartier, une rue ou une ville, ou collez une adresse.
                            </p>
                        </div>

                        <div id="geo-map" class="geo-map" style="display:none;"></div>

                        <button type="button" id="btn-geo-capture" class="btn-geo-capture">
                            <i class="fas fa-location-crosshairs"></i>
                            <?php echo $geo_saved ? 'Mettre à jour ma position' : 'Utiliser ma position actuelle'; ?>
                        </button>
                        <div id="geo-status" class="geo-status" style="display:none;"></div>
                    </div>

                    <div class="form-group" id="group-adresse-livraison">
                        <label for="adresse_livraison">
                            <i class="fas fa-location-dot"></i> Adresse de livraison <span style="font-weight:400;color:var(--gris-moyen);">(facultatif — remplie automatiquement)</span>
                        </label>
                        <textarea id="adresse_livraison" name="adresse_livraison" rows="3"
                            placeholder="Sélectionnez une suggestion ci-dessus ou saisissez l'adresse complète…"><?php echo isset($_POST['adresse_livraison']) ? htmlspecialchars($_POST['adresse_livraison'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                    </div>
                    </div><!-- /#panel-livraison -->

                    <div class="form-group">
                        <label for="telephone_livraison">
                            <i class="fas fa-phone"></i>
                            <span id="tel-label-text"><?php echo $commande_mode_selected === 'retrait' ? 'Téléphone de contact' : 'Téléphone de livraison'; ?></span> *
                        </label>
                        <input type="tel" id="telephone_livraison" name="telephone_livraison" required
                            autocomplete="tel"
                            placeholder="+221 XX XXX XX XX"
                            value="<?php echo isset($_POST['telephone_livraison']) ? htmlspecialchars($_POST['telephone_livraison'], ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) ($user['telephone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <button type="submit" class="btn-submit-commande">
                        <i class="fas fa-check-circle"></i> Confirmer la commande
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <?php require_once __DIR__ . '/includes/auth_intl_tel_scripts.php'; ?>
    <?php require_once __DIR__ . '/includes/geo_native_bridge_script.php'; ?>
    <script src="/js/geo-address-format.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/geo-location.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/geo-location-search.js<?php echo asset_version_query(); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modeInput = document.getElementById('mode_livraison');
        var panelRetrait = document.getElementById('panel-retrait');
        var panelLivraison = document.getElementById('panel-livraison');
        var telLabel = document.getElementById('tel-label-text');
        var modeBtns = document.querySelectorAll('.cmd-mode-btn');

        function setCommandeMode(mode) {
            mode = mode === 'retrait' ? 'retrait' : 'livraison';
            if (modeInput) modeInput.value = mode;
            modeBtns.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-mode') === mode);
            });
            if (panelRetrait) panelRetrait.classList.toggle('is-visible', mode === 'retrait');
            if (panelLivraison) panelLivraison.classList.toggle('is-visible', mode === 'livraison');
            if (telLabel) {
                telLabel.textContent = mode === 'retrait' ? 'Téléphone de contact' : 'Téléphone de livraison';
            }
        }

        modeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setCommandeMode(btn.getAttribute('data-mode'));
            });
        });

        if (typeof window.initAuthIntlTel === 'function') {
            window.initAuthIntlTel('telephone_livraison');
        }
        var addrField = document.getElementById('adresse_livraison');
        if (addrField) {
            addrField.addEventListener('input', function () {
                delete addrField.dataset.geoAutofilled;
            });
        }
        if (window.GeoLocationCapture) {
            window.GeoLocationCapture.init({
                latInput: 'geo_lat',
                lngInput: 'geo_lng',
                precisionInput: 'geo_precision',
                sourceInput: 'geo_source',
                statusEl: 'geo-status',
                button: 'btn-geo-capture',
                mapContainer: 'geo-map',
                addressInput: 'adresse_livraison',
                <?php if ($geo_saved): ?>
                initial: {
                    lat: <?php echo json_encode((float) $geo_saved['lat']); ?>,
                    lng: <?php echo json_encode((float) $geo_saved['lng']); ?>,
                    precision: <?php echo json_encode($geo_saved['precision'] !== null ? (float) $geo_saved['precision'] : null); ?>
                },
                <?php endif; ?>
                auto: true
            });
        }
        if (window.GeoLocationSearch) {
            window.GeoLocationSearch.init({
                searchInput: 'geo_address_search',
                suggestionsList: 'geo_search_suggestions',
                countryCode: <?php echo json_encode($geo_search_country_iso); ?>,
                countryLabel: <?php echo json_encode($geo_search_country_label); ?>
            });
        }
    });
    </script>
    <script>
    (function() {
        var panierTotal = <?php echo $panier_total; ?>;
        var spanLivraison = document.getElementById('summary-livraison');
        var spanTotal = document.getElementById('summary-total');

        function formatNumber(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        var frais = 0;
        if (spanLivraison) spanLivraison.textContent = formatNumber(Math.round(frais)) + ' FCFA';
        if (spanTotal) spanTotal.textContent = formatNumber(Math.round(panierTotal + frais)) + ' FCFA';
    })();
    </script>
</body>

</html>
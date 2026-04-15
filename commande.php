<?php
/**
 * Page de commande
 * Programmation procédurale uniquement
 */

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
        // Envoi de la réponse immédiatement pour ne pas bloquer l'utilisateur
        ignore_user_abort(true);
        $nums = !empty($result['numeros_commandes']) ? $result['numeros_commandes'] : [$result['numero_commande']];
        header('Location: /user/mes-commandes.php?success=1&numeros=' . urlencode(implode(',', $nums)));
        echo ' ';
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
            if (ob_get_level()) {
                ob_end_flush();
            }
        }

        // Envoi notification + email en arrière-plan (une cible par boutique)
        if (file_exists(__DIR__ . '/services/send_new_commande_to_admin.php')) {
            require_once __DIR__ . '/services/send_new_commande_to_admin.php';
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
        }
        exit;
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}

// Récupérer les informations de l'utilisateur
$user = get_user_by_id($_SESSION['user_id']);

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
    <title>Passer la commande - FOUTA POIDS LOURDS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
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
        <p class="commande-page-subtitle">Indiquez un numéro de téléphone pour la livraison. Les modalités sont convenues avec chaque boutique.</p>

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
                        <img src="/upload/<?php echo htmlspecialchars($item_img); ?>"
                            alt="<?php echo htmlspecialchars($item['nom']); ?>"
                            onerror="this.src='/image/produit1.jpg'">
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
                    <i class="fas fa-phone"></i> Informations de livraison
                </h2>

                <form method="POST" action="" id="form-commande">
                    <input type="hidden" name="action" value="create_commande">
                    <?php if ($commande_boutique_admin && $commande_boutique_slug !== ''): ?>
                    <input type="hidden" name="boutique_slug" value="<?php echo htmlspecialchars($commande_boutique_slug, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="telephone_livraison">
                            <i class="fas fa-phone"></i> Téléphone de livraison *
                        </label>
                        <input type="tel" id="telephone_livraison" name="telephone_livraison" required
                            placeholder="+241 XX XX XX XX"
                            value="<?php echo isset($_POST['telephone_livraison']) ? htmlspecialchars($_POST['telephone_livraison']) : htmlspecialchars($user['telephone'] ?? ''); ?>">
                        <small>Numéro de téléphone pour la livraison</small>
                    </div>

                    <button type="submit" class="btn-submit-commande">
                        <i class="fas fa-check-circle"></i> Confirmer la commande
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

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
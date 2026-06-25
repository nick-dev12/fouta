<?php
/**
 * Partial — carte négociation client (mon compte)
 * Variables : $neg (array)
 */
if (!isset($neg) || !is_array($neg)) {
    return;
}

if (!function_exists('upload_image_url')) {
    require_once dirname(__DIR__) . '/image_optimizer.php';
}

$neg_id = (int) ($neg['id'] ?? 0);
$statut = (string) ($neg['statut'] ?? '');
$statut_label = function_exists('prix_negociation_statut_label')
    ? prix_negociation_statut_label($statut)
    : $statut;
$statut_class = function_exists('prix_negociation_statut_css_class')
    ? prix_negociation_statut_css_class($statut)
    : 'prix-neg-statut--attente';
$date_fmt = !empty($neg['date_maj']) ? date('d/m/Y H:i', strtotime($neg['date_maj'])) : '';
$prix_ref = (float) ($neg['prix_reference'] ?? 0);
$prix_propose = (float) ($neg['prix_propose_client'] ?? 0);
$prix_contre = isset($neg['prix_contre_vendeur']) ? (float) $neg['prix_contre_vendeur'] : 0;
$prix_convenu = function_exists('prix_negociation_prix_convenu_effectif')
    ? prix_negociation_prix_convenu_effectif($neg)
    : null;
$produit_id = (int) ($neg['produit_id'] ?? 0);
$produit_nom = (string) ($neg['produit_nom'] ?? 'Produit');
$produit_img = trim((string) ($neg['produit_image'] ?? ''));
$img_url = $produit_img !== '' ? upload_image_url($produit_img, 'sm') : '';
$opts = function_exists('prix_negociation_options_json_decode')
    ? prix_negociation_options_json_decode($neg['options_json'] ?? null)
    : [];
$can_repropose = in_array($statut, ['contre_proposee', 'refusee_finale'], true);
$can_order = function_exists('prix_negociation_peut_commander') && prix_negociation_peut_commander($neg);
?>
<article class="prix-neg-client-card" data-neg-id="<?php echo $neg_id; ?>">
    <div class="prix-neg-client-card__media">
        <?php if ($img_url !== ''): ?>
        <img src="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo htmlspecialchars($produit_nom, ENT_QUOTES, 'UTF-8'); ?>"
            loading="lazy" width="80" height="80">
        <?php else: ?>
        <span class="prix-neg-client-card__placeholder" aria-hidden="true"><i class="fas fa-box"></i></span>
        <?php endif; ?>
    </div>
    <div class="prix-neg-client-card__content">
        <div class="prix-neg-client-card__head">
            <div>
                <h4 class="prix-neg-client-card__title"><?php echo htmlspecialchars($produit_nom, ENT_QUOTES, 'UTF-8'); ?></h4>
                <p class="prix-neg-client-card__shop"><?php echo htmlspecialchars($neg['vendeur_boutique_nom'] ?? 'Boutique', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo $date_fmt; ?></p>
            </div>
            <span class="prix-neg-statut <?php echo htmlspecialchars($statut_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statut_label, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="prix-neg-client-card__prices">
            <div class="prix-neg-price-block prix-neg-price-block--ref">
                <span class="prix-neg-price-block__label">Prix catalogue</span>
                <span class="prix-neg-price-block__value prix-neg-price-block__value--strike"><?php echo number_format($prix_ref, 0, ',', ' '); ?> FCFA</span>
            </div>
            <?php if ($prix_contre > 0 && in_array($statut, ['contre_proposee', 'acceptee', 'commandee'], true)): ?>
            <div class="prix-neg-price-block prix-neg-price-block--vendor">
                <span class="prix-neg-price-block__label">Prix du vendeur</span>
                <span class="prix-neg-price-block__value prix-neg-price-block__value--accent"><?php echo number_format($prix_contre, 0, ',', ' '); ?> FCFA</span>
            </div>
            <?php elseif ($prix_convenu !== null && $statut === 'acceptee'): ?>
            <div class="prix-neg-price-block prix-neg-price-block--vendor">
                <span class="prix-neg-price-block__label">Prix accept&eacute;</span>
                <span class="prix-neg-price-block__value prix-neg-price-block__value--accent"><?php echo number_format($prix_convenu, 0, ',', ' '); ?> FCFA</span>
            </div>
            <?php endif; ?>
            <div class="prix-neg-price-block prix-neg-price-block--mine">
                <span class="prix-neg-price-block__label">Votre offre</span>
                <span class="prix-neg-price-block__value"><?php echo number_format($prix_propose, 0, ',', ' '); ?> FCFA</span>
            </div>
        </div>
        <div class="prix-neg-client-card__actions">
            <?php if ($can_order): ?>
            <form method="POST" action="/user/prix-negociation-action.php" class="prix-neg-client-card__form-inline">
                <input type="hidden" name="action" value="commander">
                <input type="hidden" name="negotiation_id" value="<?php echo $neg_id; ?>">
                <input type="hidden" name="redirect" value="/user/mon-compte.php">
                <button type="submit" class="prix-neg-btn prix-neg-btn--order"><i class="fas fa-cart-shopping"></i> Commander maintenant</button>
            </form>
            <?php endif; ?>
            <?php if ($can_repropose): ?>
            <button type="button" class="prix-neg-btn prix-neg-btn--ghost"
                data-prix-neg-client-open
                data-produit-id="<?php echo $produit_id; ?>"
                data-produit-nom="<?php echo htmlspecialchars($produit_nom, ENT_QUOTES, 'UTF-8'); ?>"
                data-produit-image="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>"
                data-prix-reference="<?php echo (float) $prix_ref; ?>"
                data-option-variante-id="<?php echo (int) ($opts['variante_id'] ?? 0); ?>"
                data-option-couleur="<?php echo htmlspecialchars((string) ($opts['couleur'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-option-poids="<?php echo htmlspecialchars((string) ($opts['poids'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-option-taille="<?php echo htmlspecialchars((string) ($opts['taille'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-option-variante-nom="<?php echo htmlspecialchars((string) ($opts['variante_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-option-variante-image="<?php echo htmlspecialchars((string) ($opts['variante_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                data-option-surcout-poids="<?php echo (float) ($opts['surcout_poids'] ?? 0); ?>"
                data-option-surcout-taille="<?php echo (float) ($opts['surcout_taille'] ?? 0); ?>">
                <i class="fas fa-handshake"></i> Proposer un nouveau prix
            </button>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php
/**
 * Partial — ligne négociation (vendeur ou client)
 * Variables : $neg (array), $prix_neg_side ('vendor'|'client'), $prix_neg_compact (bool, optional)
 */
if (!isset($neg) || !is_array($neg)) {
    return;
}
$prix_neg_side = ($prix_neg_side ?? '') === 'client' ? 'client' : 'vendor';
$prix_neg_compact = !empty($prix_neg_compact);
$neg_id = (int) ($neg['id'] ?? 0);
$statut = (string) ($neg['statut'] ?? '');
$statut_label = function_exists('prix_negociation_statut_label')
    ? prix_negociation_statut_label($statut)
    : $statut;
$statut_class = function_exists('prix_negociation_statut_css_class')
    ? prix_negociation_statut_css_class($statut)
    : 'prix-neg-statut--attente';
$date_fmt = !empty($neg['date_maj']) ? date('d/m/Y H:i', strtotime($neg['date_maj'])) : '';
$prix_propose = number_format((float) ($neg['prix_propose_client'] ?? 0), 0, ',', ' ');
$prix_ref = number_format((float) ($neg['prix_reference'] ?? 0), 0, ',', ' ');
$reject_overlay_id = 'prixNegReject' . $neg_id;
?>
<div class="prix-neg-row prix-neg-row--<?php echo htmlspecialchars($prix_neg_side, ENT_QUOTES, 'UTF-8'); ?>" data-neg-id="<?php echo $neg_id; ?>">
    <div class="prix-neg-row__top">
        <div>
            <?php if ($prix_neg_side === 'vendor'): ?>
            <p class="prix-neg-row__title"><?php echo htmlspecialchars(trim(($neg['user_prenom'] ?? '') . ' ' . ($neg['user_nom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="prix-neg-row__meta"><?php echo htmlspecialchars($neg['produit_nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo $date_fmt; ?></p>
            <?php else: ?>
            <p class="prix-neg-row__title"><?php echo htmlspecialchars($neg['produit_nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="prix-neg-row__meta"><?php echo htmlspecialchars($neg['vendeur_boutique_nom'] ?? 'Boutique', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo $date_fmt; ?></p>
            <?php endif; ?>
        </div>
        <span class="prix-neg-statut <?php echo htmlspecialchars($statut_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statut_label, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <?php if ($prix_neg_side === 'vendor'): ?>
    <div class="prix-neg-row__prices prix-neg-row__prices--grid">
        <div class="prix-neg-row-price">
            <span class="prix-neg-row-price__label">Prix de base</span>
            <span class="prix-neg-row-price__value prix-neg-row-price__value--strike"><?php echo $prix_ref; ?> FCFA</span>
        </div>
        <div class="prix-neg-row-price">
            <span class="prix-neg-row-price__label">Offre du client</span>
            <span class="prix-neg-row-price__value prix-neg-row-price__value--client"><?php echo $prix_propose; ?> FCFA</span>
        </div>
        <?php if (!empty($neg['prix_contre_vendeur'])): ?>
        <div class="prix-neg-row-price">
            <span class="prix-neg-row-price__label">Votre proposition</span>
            <span class="prix-neg-row-price__value"><?php echo number_format((float) $neg['prix_contre_vendeur'], 0, ',', ' '); ?> FCFA</span>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <p class="prix-neg-row__prices">
        Prix affich&eacute; : <strong><?php echo $prix_ref; ?> FCFA</strong>
        &middot; Offre : <strong><?php echo $prix_propose; ?> FCFA</strong>
        <?php if (!empty($neg['prix_contre_vendeur'])): ?>
        &middot; Contre-offre : <strong><?php echo number_format((float) $neg['prix_contre_vendeur'], 0, ',', ' '); ?> FCFA</strong>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <div class="prix-neg-row__actions">
        <?php if ($prix_neg_side === 'vendor' && $statut === 'en_attente'): ?>
        <form method="POST" action="/admin/prix-negociation-action.php" class="prix-neg-row__form-inline">
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="negotiation_id" value="<?php echo $neg_id; ?>">
            <input type="hidden" name="redirect" value="/admin/dashboard.php">
            <button type="submit" class="prix-neg-btn prix-neg-btn--accept"><i class="fas fa-check"></i> Valider l'offre</button>
        </form>
        <button type="button" class="prix-neg-btn prix-neg-btn--reject"
            data-prix-neg-reject-open="<?php echo htmlspecialchars($reject_overlay_id, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-times"></i> Rejeter l'offre
        </button>
        <?php elseif ($prix_neg_side === 'client'): ?>
            <?php if (function_exists('prix_negociation_peut_commander') && prix_negociation_peut_commander($neg)): ?>
            <form method="POST" action="/user/prix-negociation-action.php" class="prix-neg-row__form-inline">
                <input type="hidden" name="action" value="commander">
                <input type="hidden" name="negotiation_id" value="<?php echo $neg_id; ?>">
                <input type="hidden" name="redirect" value="/user/mon-compte.php">
                <button type="submit" class="prix-neg-btn prix-neg-btn--order"><i class="fas fa-cart-shopping"></i> Commander maintenant</button>
            </form>
            <?php endif; ?>
            <?php if (in_array($statut, ['contre_proposee', 'refusee_finale'], true)): ?>
            <a href="/produit.php?id=<?php echo (int) ($neg['produit_id'] ?? 0); ?>" class="prix-neg-btn prix-neg-btn--ghost">
                <i class="fas fa-handshake"></i> Proposer un nouveau prix
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($prix_neg_side === 'vendor' && $statut === 'en_attente'): ?>
<div class="prix-neg-reject-overlay" id="<?php echo htmlspecialchars($reject_overlay_id, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true" hidden>
    <div class="prix-neg-reject-overlay__backdrop" data-prix-neg-reject-close tabindex="-1"></div>
    <div class="prix-neg-reject-panel" role="dialog" aria-modal="true">
        <div class="prix-neg-reject-panel__head">
            <h3>Rejeter ou contre-proposer</h3>
            <button type="button" class="prix-neg-modal__close" data-prix-neg-reject-close aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="prix-neg-reject-panel__hint">Laissez le champ vide pour refuser l'offre, ou saisissez un prix &agrave; proposer au client.</p>
        <form method="POST" action="/admin/prix-negociation-action.php" class="prix-neg-reject-panel__form" data-prix-neg-reject-form>
            <input type="hidden" name="negotiation_id" value="<?php echo $neg_id; ?>">
            <input type="hidden" name="redirect" value="/admin/dashboard.php">
            <input type="hidden" name="action" value="reject_final" data-prix-neg-reject-action>
            <label for="prix-contre-<?php echo $neg_id; ?>">Votre prix (FCFA) — optionnel</label>
            <input type="number" name="prix_contre" id="prix-contre-<?php echo $neg_id; ?>" min="1" step="1"
                placeholder="Ex : 420 000" inputmode="numeric" data-prix-neg-reject-input>
            <div class="prix-neg-reject-panel__actions">
                <button type="button" class="prix-neg-modal__btn prix-neg-modal__btn--cancel" data-prix-neg-reject-close>Annuler</button>
                <button type="submit" class="prix-neg-btn prix-neg-btn--reject" data-prix-neg-reject-submit>
                    <i class="fas fa-times"></i> Rejeter l'offre
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

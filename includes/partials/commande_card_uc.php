<?php
/**
 * Carte commande — design client (mes-commandes.php), réutilisable vendeur.
 *
 * Variables :
 * - $commande (array)
 * - $card_context : 'client' | 'vendor' (défaut vendor)
 * - $card_title (string, optionnel)
 * - $card_phone (string, optionnel)
 * - $card_track_url (string, optionnel)
 * - $card_detail_url (string, optionnel)
 * - $card_show_urgent (bool, optionnel)
 * - $card_rating_html (string, optionnel — HTML étoiles déjà rendu)
 * - $card_footer_html (string, optionnel — actions client personnalisées)
 */
if (empty($commande) || !is_array($commande)) {
    return;
}

require_once __DIR__ . '/../commande_card_helpers.php';

$card_context = isset($card_context) ? (string) $card_context : 'vendor';
$cmd_id = (int) ($commande['id'] ?? 0);
$st = (string) ($commande['statut'] ?? 'en_attente');
$timeline = commande_card_timeline_steps($st);

$card_title = isset($card_title) ? trim((string) $card_title) : '';
if ($card_title === '') {
    $card_title = $card_context === 'vendor'
        ? commande_card_client_nom($commande)
        : 'Boutique';
}

$card_phone = isset($card_phone) ? trim((string) $card_phone) : commande_card_telephone($commande, $card_context);
$card_track_url = isset($card_track_url) ? (string) $card_track_url : '';
$card_detail_url = isset($card_detail_url) ? (string) $card_detail_url : $card_track_url;
$card_show_urgent = !empty($card_show_urgent) || ($card_context === 'vendor' && $st === 'en_attente');
$card_rating_html = isset($card_rating_html) ? (string) $card_rating_html : '';
$card_footer_html = isset($card_footer_html) ? (string) $card_footer_html : '';

$date_cmd = !empty($commande['date_commande'])
    ? date('d/m/Y', strtotime((string) $commande['date_commande']))
    : '&mdash;';

if ($card_track_url === '' && $cmd_id > 0) {
    $card_track_url = $card_context === 'vendor'
        ? 'details.php?id=' . $cmd_id
        : 'commande-categorie.php?commande_id=' . $cmd_id;
}
if ($card_detail_url === '' && $cmd_id > 0) {
    $card_detail_url = $card_track_url;
}
?>
<article class="uc-v2-card">
    <div class="uc-v2-card__top">
        <div class="uc-v2-card__ref">
            <div class="uc-v2-card__ref-head">
                <?php if ($card_show_urgent): ?>
                    <span class="uc-urgence" title="Action requise"></span>
                <?php endif; ?>
                <span class="uc-v2-card__boutique"><?php echo htmlspecialchars($card_title, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php if ($card_rating_html !== ''): ?>
                <div class="uc-v2-card__rating"><?php echo $card_rating_html; ?></div>
            <?php endif; ?>
        </div>
        <span class="uc-badge <?php echo commande_card_badge_class($st); ?>">
            <i class="fas <?php echo commande_card_icon($st); ?>" style="font-size:.7em;margin-right:3px;"></i>
            <?php echo commande_card_label($st); ?>
        </span>
    </div>

    <div class="uc-v2-card__body">
        <div class="uc-v2-card__info">
            <div class="uc-v2-card__amount">
                <?php echo number_format((float) ($commande['montant_total'] ?? 0), 0, ',', ' '); ?><small>FCFA</small>
            </div>
            <?php if ($card_phone !== ''): ?>
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $card_phone), ENT_QUOTES, 'UTF-8'); ?>"
                    class="uc-v2-card__tel<?php echo $card_context === 'client' ? ' uc-v2-card__tel--boutique' : ''; ?>">
                    <i class="fas <?php echo $card_context === 'client' ? 'fa-store' : 'fa-phone'; ?>"></i>
                    <?php echo htmlspecialchars($card_phone, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
            <?php if ($card_context === 'vendor' && !empty($commande['adresse_livraison'])): ?>
                <div class="uc-v2-card__addr">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars((string) $commande['adresse_livraison'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($timeline !== null): ?>
            <div class="uc-v2-timeline" aria-label="Avancement de la commande">
                <?php foreach ($timeline as $step): ?>
                    <div class="uc-tl-step uc-tl-step--<?php echo htmlspecialchars($step['state'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="uc-tl-dot">
                            <i class="fas <?php echo htmlspecialchars($step['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <span class="uc-tl-label"><?php echo $step['label']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="uc-v2-card__meta-bar">
        <span class="uc-v2-card__meta-line">
            <span class="uc-v2-card__ref-num">#<?php echo htmlspecialchars((string) ($commande['numero_commande'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="uc-v2-card__sep" aria-hidden="true">&middot;</span>
            <span class="uc-v2-card__date"><?php echo $date_cmd; ?></span>
        </span>
    </div>

    <div class="uc-v2-card__footer<?php echo $card_context === 'vendor' ? ' uc-v2-card__footer--vendor' : ''; ?>">
        <?php if ($card_footer_html !== ''): ?>
            <?php echo $card_footer_html; ?>
        <?php elseif ($card_context === 'vendor'): ?>
            <a href="<?php echo htmlspecialchars($card_detail_url, ENT_QUOTES, 'UTF-8'); ?>" class="uc-card-btn uc-card-btn--vendor-detail">
                <span>Voir la commande</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($card_track_url, ENT_QUOTES, 'UTF-8'); ?>" class="uc-card-btn uc-card-btn--track">
                <i class="fas fa-route"></i> Suivre
            </a>
            <a href="<?php echo htmlspecialchars($card_detail_url, ENT_QUOTES, 'UTF-8'); ?>" class="uc-card-btn uc-card-btn--detail">
                <i class="fas fa-eye"></i> Voir la commande
            </a>
        <?php endif; ?>
    </div>
</article>

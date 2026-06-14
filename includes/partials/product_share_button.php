<?php
/**
 * Bouton partage produit — coin haut-droit de la carte.
 * Variable : $produit (tableau avec id, nom, prix, prix_promotion optionnels).
 */
if (empty($produit) || !is_array($produit)) {
    return;
}
$pid = (int) ($produit['id'] ?? 0);
if ($pid <= 0) {
    return;
}
if (!function_exists('product_share_abs_url')) {
    require_once __DIR__ . '/../product_share.php';
}
$share_url = product_share_abs_url($pid);
$share_msg = product_share_message($produit);
$share_wa = product_share_whatsapp_url($produit);
$share_fb = product_share_facebook_url($produit);
$share_tw = product_share_twitter_url($produit);
$share_nom = htmlspecialchars((string) ($produit['nom'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
?>
<div class="pshare" data-pshare>
    <button type="button"
        class="pshare__toggle"
        aria-label="Partager <?php echo $share_nom; ?>"
        aria-expanded="false"
        aria-haspopup="true"
        data-share-url="<?php echo htmlspecialchars($share_url, ENT_QUOTES, 'UTF-8'); ?>"
        data-share-text="<?php echo htmlspecialchars($share_msg, ENT_QUOTES, 'UTF-8'); ?>"
        data-share-title="<?php echo $share_nom; ?>">
        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
    </button>
    <div class="pshare__menu" hidden role="menu" aria-label="Partager ce produit">
        <a class="pshare__item pshare__item--wa"
            href="<?php echo htmlspecialchars($share_wa, ENT_QUOTES, 'UTF-8'); ?>"
            target="_blank" rel="noopener noreferrer" role="menuitem">
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp
        </a>
        <a class="pshare__item"
            href="<?php echo htmlspecialchars($share_fb, ENT_QUOTES, 'UTF-8'); ?>"
            target="_blank" rel="noopener noreferrer" role="menuitem">
            <i class="fa-brands fa-facebook-f" aria-hidden="true"></i> Facebook
        </a>
        <a class="pshare__item"
            href="<?php echo htmlspecialchars($share_tw, ENT_QUOTES, 'UTF-8'); ?>"
            target="_blank" rel="noopener noreferrer" role="menuitem">
            <i class="fa-brands fa-x-twitter" aria-hidden="true"></i> X
        </a>
        <button type="button" class="pshare__item pshare__item--copy" data-share-copy role="menuitem">
            <i class="fa-solid fa-link" aria-hidden="true"></i> Copier le lien
        </button>
    </div>
</div>

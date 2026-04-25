<?php
/**
 * Carte « Nouveautés » compacte — alignée à gauche, prix + ligne promo (index).
 * Variables : $produit, $return_url (optionnel).
 */
if (empty($produit) || !is_array($produit)) {
    return;
}
$return_url = isset($return_url) ? (string) $return_url : (string) ($_SERVER['REQUEST_URI'] ?? '/index.php');
$pid = (int) ($produit['id'] ?? 0);
if ($pid <= 0) {
    return;
}
$prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
    ? (float) $produit['prix_promotion']
    : (float) ($produit['prix'] ?? 0);
$has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
$stock = isset($produit['stock']) ? (int) $produit['stock'] : null;
?>
<article class="mp-new-card">
    <span class="mp-new-badge" aria-label="Nouveau">Nouveau</span>
    <a href="produit.php?id=<?php echo $pid; ?>" class="mp-new-card-link">
        <div class="mp-new-card-img">
            <img src="/upload/<?php echo htmlspecialchars($produit['image_principale'] ?? 'produit1.jpg'); ?>"
                alt="<?php echo htmlspecialchars($produit['nom'] ?? ''); ?>"
                loading="lazy"
                onerror="this.src='/image/produit1.jpg'">
        </div>
        <?php if ($has_promotion): ?>
        <p class="mp-new-promo">Promotion en cours</p>
        <?php endif; ?>
        <p class="mp-new-price"><?php echo number_format($prix_affichage, 0, ',', ' '); ?> <span>FCFA</span></p>
        <p class="mp-new-moq"><?php echo $stock !== null && $stock > 0 ? 'En stock : ' . $stock : 'Réf. marketplace'; ?></p>
    </a>
    <form method="POST" action="/add-to-panier.php" class="mp-new-cart">
        <input type="hidden" name="produit_id" value="<?php echo $pid; ?>">
        <input type="hidden" name="quantite" value="1">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">
        <button type="submit" class="mp-new-cart-btn"><i class="fa-solid fa-cart-plus" aria-hidden="true"></i></button>
    </form>
</article>

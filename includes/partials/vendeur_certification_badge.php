<?php
/**
 * Badge certification vendeur — ruban récompense (lecture seule).
 * Variables : $cert_niveau (standard|vip|premium), $cert_size (xs|sm|md|lg|rail|tier, optionnel)
 */
if (empty($cert_niveau) || !in_array((string) $cert_niveau, ['standard', 'vip', 'premium'], true)) {
    return;
}
require_once __DIR__ . '/../../models/model_vendeur_certification.php';
$cert_meta = vendeur_certification_niveaux()[(string) $cert_niveau];
$cert_size_val = isset($cert_size) ? (string) $cert_size : 'md';
$cert_sizes_ok = ['xs', 'sm', 'md', 'lg', 'rail', 'tier'];
$cert_size_class = in_array($cert_size_val, $cert_sizes_ok, true) ? ' cert-ribbon--' . $cert_size_val : ' cert-ribbon--md';
$cert_label = (string) ($cert_meta['label'] ?? ucfirst((string) $cert_niveau));
$cert_sub_labels = [
    'standard' => 'Certifié',
    'vip' => 'Boutique',
    'premium' => 'Elite',
];
$cert_sub = $cert_sub_labels[(string) $cert_niveau] ?? 'Certifié';
?>
<span class="cert-ribbon cert-ribbon--<?php echo htmlspecialchars((string) $cert_niveau, ENT_QUOTES, 'UTF-8'); ?><?php echo $cert_size_class; ?>" title="Boutique certifiée <?php echo htmlspecialchars($cert_label, ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="Boutique certifiée <?php echo htmlspecialchars($cert_label, ENT_QUOTES, 'UTF-8'); ?>">
    <span class="cert-ribbon__seal">
        <span class="cert-ribbon__seal-inner">
            <span class="cert-ribbon__title"><?php echo htmlspecialchars($cert_label, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="cert-ribbon__sub"><?php echo htmlspecialchars($cert_sub, ENT_QUOTES, 'UTF-8'); ?></span>
        </span>
    </span>
    <span class="cert-ribbon__body" aria-hidden="true"></span>
    <span class="cert-ribbon__foot" aria-hidden="true"><i class="fas fa-trophy"></i></span>
</span>

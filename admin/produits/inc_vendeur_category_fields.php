<?php
/**
 * Catégories vendeur : rayon plateforme (categories_generales) + sous-catégorie (categories).
 * Variables optionnelles : $vcat_prefill_sub, $vcat_prefill_generale, $vendeur_subcats_for_form
 */
if (!function_exists('get_all_vendeur_subcategories_for_form')) {
    require_once __DIR__ . '/../../models/model_categories.php';
}

$vcat_s = isset($vcat_prefill_sub) ? (int) $vcat_prefill_sub : (int) ($_POST['categorie_id'] ?? 0);
$vcat_g = isset($vcat_prefill_generale) ? (int) $vcat_prefill_generale : (int) ($_POST['categorie_generale_id'] ?? 0);

if (!isset($vendeur_subcats_for_form) || !is_array($vendeur_subcats_for_form)) {
    $vendeur_subcats_for_form = [];
    if (function_exists('get_all_vendeur_subcategories_for_form') && isset($_SESSION['admin_id'])) {
        $vendeur_subcats_for_form = get_all_vendeur_subcategories_for_form((int) $_SESSION['admin_id']);
    }
}

$rayons_liste = (function_exists('categories_generales_table_exists') && categories_generales_table_exists())
    ? get_general_categories_ordered() : [];
$afficher_rayon = !empty($rayons_liste);
?>
<?php if ($afficher_rayon): ?>
<div class="fap-field fap-field-cat-generale fap-field-cat-vendeur-only">
    <label for="categorie_generale_id">Catégorie générale <span class="required">*</span></label>
    <select id="categorie_generale_id" name="categorie_generale_id" required>
        <option value=""><?php echo !empty($rayons_liste) ? 'Choisir un rayon' : 'Aucun rayon configuré'; ?></option>
        <?php foreach ($rayons_liste as $rg): ?>
            <?php $rid = (int) ($rg['id'] ?? 0); ?>
            <?php if ($rid <= 0) {
                continue;
            } ?>
        <option value="<?php echo $rid; ?>" <?php echo ($vcat_g === $rid) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars((string) ($rg['nom'] ?? '')); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <small class="fap-hint">Rayon de la boutique (table <?php echo htmlspecialchars('categories_generales'); ?>).</small>
</div>
<?php endif; ?>

<div class="fap-field fap-field-cat-sub fap-field-cat-vendeur-only">
    <label for="categorie_id">Sous-catégorie <span class="required">*</span></label>
    <select id="categorie_id" name="categorie_id" required>
        <option value=""><?php echo !empty($vendeur_subcats_for_form) ? 'Choisir une sous-catégorie' : 'Aucune sous-catégorie — créez-en dans Stock'; ?></option>
        <?php foreach ($vendeur_subcats_for_form as $row): ?>
            <?php
            $opt_id = (int) ($row['id'] ?? 0);
            if ($opt_id <= 0) {
                continue;
            }
            $opt_label = (string) ($row['nom'] ?? '');
            $data_cg = 0;
            if (isset($row['categorie_generale_id']) && $row['categorie_generale_id'] !== null && $row['categorie_generale_id'] !== '') {
                $data_cg = (int) $row['categorie_generale_id'];
            }
            ?>
        <option value="<?php echo $opt_id; ?>"
            data-categorie-generale-id="<?php echo $data_cg; ?>"
            <?php echo ($vcat_s === $opt_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($opt_label); ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($afficher_rayon): ?>
<script>
(function () {
    var selG = document.getElementById('categorie_generale_id');
    var selS = document.getElementById('categorie_id');
    if (!selG || !selS) return;

    function filterSubOptions() {
        var gid = parseInt(selG.value, 10) || 0;
        var i;
        var opts = selS.options;
        var hadSelection = false;
        for (i = 0; i < opts.length; i++) {
            var o = opts[i];
            if (!o.value) {
                o.disabled = false;
                continue;
            }
            var cg = parseInt(o.getAttribute('data-categorie-generale-id'), 10);
            if (isNaN(cg)) cg = 0;
            var ok = gid > 0 && (cg === 0 || cg === gid);
            o.disabled = !ok;
            if (o.selected && ok) hadSelection = true;
        }
        if (!hadSelection && gid > 0) {
            for (i = 0; i < opts.length; i++) {
                if (opts[i].value && !opts[i].disabled) {
                    selS.selectedIndex = i;
                    break;
                }
            }
        }
    }
    selG.addEventListener('change', filterSubOptions);
    filterSubOptions();
})();
</script>
<?php endif; ?>

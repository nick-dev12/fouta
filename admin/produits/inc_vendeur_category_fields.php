<?php
/**
 * Classification vendeur : genres (cases à cocher) ou ancien couple rayon + sous-catégorie.
 * Variables optionnelles : $vcat_prefill_sub, $vcat_prefill_generale, $vendeur_subcats_for_form,
 * $vendeur_genre_ids_prefill (tableau d’IDs pour édition).
 */
if (!function_exists('get_plateforme_sous_categories_for_form')) {
    require_once __DIR__ . '/../../models/model_categories.php';
}
if (!function_exists('vendeur_genres_mode_actif')) {
    require_once __DIR__ . '/../../models/model_genres.php';
}

$vcat_s = isset($vcat_prefill_sub) ? (int) $vcat_prefill_sub : (int) ($_POST['categorie_id'] ?? 0);
$vcat_g = isset($vcat_prefill_generale) ? (int) $vcat_prefill_generale : (int) ($_POST['categorie_generale_id'] ?? 0);
$vendeur_genre_ids_prefill = isset($vendeur_genre_ids_prefill) && is_array($vendeur_genre_ids_prefill)
    ? array_map('intval', $vendeur_genre_ids_prefill)
    : [];

if (function_exists('vendeur_genres_mode_actif') && vendeur_genres_mode_actif()) {
    $rayons_liste = (function_exists('categories_generales_table_exists') && categories_generales_table_exists())
        ? get_general_categories_ordered() : [];
    $genres_liste = genres_list_all();
    $genres_wrap_hidden = (!function_exists('genres_cg_links_table_exists') || !genres_cg_links_table_exists());
    ?>
<div class="fap-field fap-field-cat-generale fap-field-cat-vendeur-only">
    <label for="categorie_generale_id">Catégorie principale <span class="required">*</span></label>
    <select id="categorie_generale_id" name="categorie_generale_id" required>
        <option value=""><?php echo !empty($rayons_liste) ? 'Choisir une catégorie principale' : 'Aucune catégorie configurée'; ?></option>
        <?php foreach ($rayons_liste as $rg): ?>
            <?php $rid = (int) ($rg['id'] ?? 0); ?>
            <?php if ($rid <= 0) {
                continue;
            } ?>
        <option value="<?php echo $rid; ?>" <?php echo ($vcat_g === $rid) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars((string) ($rg['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <small class="fap-hint">Rayon défini par la plateforme (super administrateur).</small>
</div>

<div id="fap-genres-wrap" class="fap-field fap-field-genres fap-field-cat-vendeur-only"<?php echo $genres_wrap_hidden ? ' hidden' : ''; ?>>
    <fieldset class="fap-fieldset-genres">
        <legend>Genres <span class="required" id="fap-genres-legend-required" hidden aria-hidden="true">*</span></legend>
        <p class="fap-hint">Uniquement si des genres sont associés à la catégorie principale choisie (configuration super administrateur).</p>
        <?php if (empty($genres_liste)): ?>
            <p class="fap-hint fap-hint--warn">Aucun genre n’est encore configuré. Contactez le super administrateur.</p>
        <?php else: ?>
        <div class="fap-genres-grid" role="group" aria-label="Genres du produit" id="fap-genres-grid-inner">
            <?php foreach ($genres_liste as $gr): ?>
                <?php
                $gid = (int) ($gr['id'] ?? 0);
                if ($gid <= 0) {
                    continue;
                }
                $rayons_genre = function_exists('get_categorie_generale_ids_for_genre') ? get_categorie_generale_ids_for_genre($gid) : [];
                $rayons_attr = implode(',', $rayons_genre);
                $checked = false;
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['genre_ids']) && is_array($_POST['genre_ids'])) {
                    $checked = in_array($gid, array_map('intval', $_POST['genre_ids']), true);
                } else {
                    $checked = in_array($gid, $vendeur_genre_ids_prefill, true);
                }
                ?>
            <label class="fap-genre-label" data-genre-rayons="<?php echo htmlspecialchars($rayons_attr, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="checkbox" name="genre_ids[]" value="<?php echo $gid; ?>" <?php echo $checked ? 'checked' : ''; ?> disabled>
                <span><?php echo htmlspecialchars((string) ($gr['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </fieldset>
</div>

<script>
(function () {
    var sel = document.getElementById('categorie_generale_id');
    var wrap = document.getElementById('fap-genres-wrap');
    var reqStar = document.getElementById('fap-genres-legend-required');
    if (!sel || !wrap) return;
    var labels = wrap.querySelectorAll('.fap-genre-label');
    function syncGenresVisibility() {
        var cg = parseInt(sel.value, 10) || 0;
        var anyVisible = false;
        for (var i = 0; i < labels.length; i++) {
            var lab = labels[i];
            var raw = lab.getAttribute('data-genre-rayons') || '';
            var parts = raw.split(',');
            var ids = [];
            for (var j = 0; j < parts.length; j++) {
                var n = parseInt(parts[j].trim(), 10);
                if (n > 0) ids.push(n);
            }
            var ok = cg > 0 && ids.indexOf(cg) !== -1;
            lab.style.display = ok ? '' : 'none';
            var inp = lab.querySelector('input[type="checkbox"]');
            if (inp) {
                inp.disabled = !ok;
                if (!ok) inp.checked = false;
            }
            if (ok) anyVisible = true;
        }
        wrap.hidden = !anyVisible;
        if (reqStar) {
            reqStar.hidden = !anyVisible;
            reqStar.setAttribute('aria-hidden', anyVisible ? 'false' : 'true');
        }
    }
    sel.addEventListener('change', syncGenresVisibility);
    syncGenresVisibility();
})();
</script>
    <?php
    return;
}

if (!isset($vendeur_subcats_for_form) || !is_array($vendeur_subcats_for_form)) {
    $vendeur_subcats_for_form = [];
    if (function_exists('get_plateforme_sous_categories_for_form')) {
        $vendeur_subcats_for_form = get_plateforme_sous_categories_for_form();
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
    <small class="fap-hint">Rayon défini par la plateforme (super administrateur).</small>
</div>
<?php endif; ?>

<div class="fap-field fap-field-cat-sub fap-field-cat-vendeur-only">
    <label for="categorie_id">Sous-catégorie <span class="required">*</span></label>
    <select id="categorie_id" name="categorie_id" required>
        <option value=""><?php echo !empty($vendeur_subcats_for_form) ? 'Choisir une sous-catégorie' : 'Aucune sous-catégorie — contactez la plateforme'; ?></option>
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

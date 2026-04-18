<?php
/**
 * Rayons du catalogue (catégories générales).
 */
require_once __DIR__ . '/../includes/require_login.php';
require_once dirname(__DIR__, 2) . '/models/model_categories.php';
require_once dirname(__DIR__, 2) . '/models/model_super_admin.php';
require_once dirname(__DIR__, 2) . '/controllers/controller_super_admin.php';
require_once dirname(__DIR__, 2) . '/controllers/controller_categories.php';

$sa_id = (int) ($_SESSION['super_admin_id'] ?? 0);
$flash_ok = '';
$flash_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf_token'] ?? '';
    if (!super_admin_csrf_valid($tok)) {
        $flash_err = 'Jeton de sécurité invalide.';
    } elseif (!categories_generales_table_exists()) {
        $flash_err = 'Table categories_generales absente. Exécutez les migrations (categories_generales + colonne categorie_generale_id).';
    } else {
        if (isset($_POST['create_cg'])) {
            $nom = trim((string) ($_POST['cg_nom'] ?? ''));
            $d = trim((string) ($_POST['cg_description'] ?? ''));
            $ic = trim((string) ($_POST['cg_icone'] ?? ''));
            $so = (int) ($_POST['cg_sort'] ?? 0);
            $ap = isset($_POST['cg_attr_poids']) ? 1 : 0;
            $at = isset($_POST['cg_attr_taille']) ? 1 : 0;
            $am = isset($_POST['cg_attr_mesure']) ? 1 : 0;
            $ac = isset($_POST['cg_attr_couleur']) ? 1 : 0;
            $id = categories_generales_insert_row($nom, $d !== '' ? $d : null, $ic !== '' ? $ic : null, $so, $ap, $at, $am, $ac);
            if ($id) {
                super_admin_log_action($sa_id, 'categorie_generale_creee', 'categories_generales', $id, $nom);
                header('Location: categories-catalogue.php?ok=1', true, 303);
                exit;
            }
            $flash_err = 'Impossible d’ajouter la catégorie générale (nom déjà utilisé ou erreur).';
        } elseif (isset($_POST['update_cg'])) {
            $id = (int) ($_POST['cg_id'] ?? 0);
            $nom = trim((string) ($_POST['cg_nom'] ?? ''));
            $d = trim((string) ($_POST['cg_description'] ?? ''));
            $ic = trim((string) ($_POST['cg_icone'] ?? ''));
            $so = (int) ($_POST['cg_sort'] ?? 0);
            $ap = isset($_POST['cg_attr_poids']) ? 1 : 0;
            $at = isset($_POST['cg_attr_taille']) ? 1 : 0;
            $am = isset($_POST['cg_attr_mesure']) ? 1 : 0;
            $ac = isset($_POST['cg_attr_couleur']) ? 1 : 0;
            if (categories_generales_update_row($id, $nom, $d !== '' ? $d : null, $ic !== '' ? $ic : null, $so, $ap, $at, $am, $ac)) {
                super_admin_log_action($sa_id, 'categorie_generale_modifiee', 'categories_generales', $id, $nom);
                header('Location: categories-catalogue.php?ok=1', true, 303);
                exit;
            }
            $flash_err = 'Modification impossible (nom en doublon ou erreur).';
        } elseif (isset($_POST['delete_cg'])) {
            $id = (int) ($_POST['cg_id'] ?? 0);
            if (categories_generales_delete_row($id)) {
                super_admin_log_action($sa_id, 'categorie_generale_supprimee', 'categories_generales', $id, '');
                header('Location: categories-catalogue.php?ok=1', true, 303);
                exit;
            }
            $flash_err = 'Suppression impossible : supprimez d’abord les liaisons (ex. genres) liées à ce rayon.';
        }
    }
}

if (isset($_GET['ok'])) {
    $flash_ok = 'Enregistrement effectué.';
}

$cg_list = categories_generales_list_all();
$edit_cg = isset($_GET['edit_cg']) ? (int) $_GET['edit_cg'] : 0;
$row_edit_cg = $edit_cg > 0 ? get_categorie_generale_by_id($edit_cg) : false;
$attr_edit = $row_edit_cg ? categorie_generale_parse_attributs_row($row_edit_cg) : ['poids' => true, 'taille' => true, 'mesure' => true, 'couleur' => true];
$attr_cols_ok = function_exists('categories_generales_attr_columns_exist') && categories_generales_attr_columns_exist();

$csrf = super_admin_csrf_token();
$table_ok = categories_generales_table_exists() && categories_has_categorie_generale_id_column();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include dirname(__DIR__, 2) . '/includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories catalogue — Super Admin</title>
    <?php require_once dirname(__DIR__, 2) . '/includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-clients.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-parametres.css<?php echo asset_version_query(); ?>">
</head>

<body class="page-users admin-clients-page sa-users-page sa-param-hub-page sa-cat-page<?php echo $row_edit_cg ? ' sa-cat-modal-open' : ''; ?>">
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <div class="sa-users-shell sa-param-shell sa-cat-shell">
        <a class="sa-cat-back" href="index.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Paramètres</a>

        <header class="sa-param-hero" aria-labelledby="sa-cat-title">
            <div class="sa-param-hero__grid">
                <div>
                    <nav class="sa-param-breadcrumb" aria-label="Fil d’Ariane">
                        <ol>
                            <li><a href="../dashboard.php">Tableau de bord</a></li>
                            <li class="sa-param-breadcrumb__sep" aria-hidden="true"><i class="fas fa-chevron-right"></i></li>
                            <li><a href="index.php">Paramètres</a></li>
                            <li class="sa-param-breadcrumb__sep" aria-hidden="true"><i class="fas fa-chevron-right"></i></li>
                            <li aria-current="page">Catégories catalogue</li>
                        </ol>
                    </nav>
                    <p class="sa-param-hero__eyebrow">
                        <i class="fas fa-sitemap" aria-hidden="true"></i> Catalogue marketplace
                    </p>
                    <h1 class="sa-param-hero__title" id="sa-cat-title">
                        Rayons du catalogue
                        <span class="sa-param-hero__badge">Catalogue</span>
                    </h1>
                    <p class="sa-param-hero__lead">
                        Les <strong>catégories générales</strong> structurent le menu (grands rayons). Les vendeurs classent leurs produits par <strong><a href="genres-catalogue.php">genres</a></strong> (indépendants des rayons).
                    </p>
                </div>
                <div class="sa-param-hero__stamp" aria-hidden="true">
                    <div class="sa-param-hero__stamp-box">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($flash_ok !== ''): ?>
            <div class="sa-cat-alert sa-cat-alert--ok" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($flash_err !== ''): ?>
            <div class="sa-cat-alert sa-cat-alert--err" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$table_ok): ?>
            <div class="sa-cat-migrate-banner" role="note">
                <strong>Migration requise.</strong> Exécutez <code>php migrations/run_migrate_categories_generales_table.php</code>
                puis, si besoin, <code>php migrations/run_alter_categories_drop_unique_nom.php</code> pour les contraintes sur le nom des catégories plateforme.
            </div>
        <?php endif; ?>
        <?php if ($table_ok && !$attr_cols_ok): ?>
            <div class="sa-cat-migrate-banner" role="note">
                <strong>Champs produit par rayon.</strong> Pour activer les cases « Poids, taille, mesure, couleur » dans les formulaires de rayon, exécutez
                <code>php migrations/run_migrate_categories_generales_attributs_produit.php</code>.
            </div>
        <?php endif; ?>
        <section class="sa-cat-panel" aria-labelledby="sa-cg-title">
            <div class="sa-cat-panel__head" id="sa-cg-title">
                <span class="sa-cat-panel__head-icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                <div class="sa-cat-panel__head-text">
                    <h2>Catégories générales</h2>
                    <p>Rayons affichés dans la navigation : nom, description courte, icône et ordre.</p>
                </div>
            </div>
            <div class="sa-cat-panel__body">
                <div class="sa-cat-panel__toolbar">
                    <button type="button" class="sa-cat-btn sa-cat-btn--primary" id="btnOpenModalCg" data-open-modal="saModalCg" <?php echo !$table_ok ? 'disabled title="Migration requise"' : ''; ?> <?php echo $row_edit_cg ? 'disabled title="Terminez la modification en cours" aria-disabled="true"' : ''; ?>>
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        Ajouter un rayon
                    </button>
                </div>
                <div class="sa-cat-table-wrap">
                    <table class="sa-cat-table">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Icône</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cg_list as $cg): ?>
                                <tr>
                                    <td><span class="sa-cat-num"><?php echo (int) ($cg['sort_ordre'] ?? 0); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars((string) ($cg['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php
                                    $__d = (string) ($cg['description'] ?? '');
                                    $__short = function_exists('mb_substr') ? mb_substr($__d, 0, 120) : substr($__d, 0, 120);
                                    echo nl2br(htmlspecialchars($__short, ENT_QUOTES, 'UTF-8'));
                                    echo strlen($__d) > 120 ? '…' : '';
                                    ?></td>
                                    <td class="sa-cat-table__visuel">
                                        <?php
                                        $__ic_raw = trim((string) ($cg['icone'] ?? ''));
                                        if ($__ic_raw !== ''):
                                            $__ic_cls = categorie_fa_icon_class($cg);
                                        ?>
                                        <span class="sa-cat-table__fa-wrap" title="<?php echo htmlspecialchars($__ic_cls, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="<?php echo htmlspecialchars($__ic_cls, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                        </span>
                                        <code class="sa-cat-table__fa-code"><?php echo htmlspecialchars($__ic_raw, ENT_QUOTES, 'UTF-8'); ?></code>
                                        <?php else: ?>
                                        <span class="sa-cat-table__fa-placeholder" aria-hidden="true" title="Aucune icône"><i class="fas fa-minus"></i></span>
                                        <code class="sa-cat-table__fa-code">—</code>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sa-cat-actions">
                                        <a href="categories-catalogue.php?edit_cg=<?php echo (int) $cg['id']; ?>" class="sa-cat-btn sa-cat-btn--ghost sa-cat-btn--sm">Modifier</a>
                                        <form method="post" action="" class="sa-cat-inline-form" onsubmit="return confirm('Supprimer ce rayon ? Les liaisons catalogue doivent être retirées au préalable.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="cg_id" value="<?php echo (int) $cg['id']; ?>">
                                            <button type="submit" name="delete_cg" value="1" class="sa-cat-btn sa-cat-btn--danger sa-cat-btn--sm">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cg_list)): ?>
                                <tr class="sa-cat-empty-row"><td colspan="5">Aucune catégorie générale pour l’instant.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="sa-cat-modal" id="saModalCg" role="dialog" aria-modal="true" aria-labelledby="saModalCgTitle" <?php echo $row_edit_cg ? '' : 'hidden'; ?><?php echo $row_edit_cg ? ' data-sa-cat-close-reset-href="categories-catalogue.php"' : ''; ?>>
        <div class="sa-cat-modal__backdrop" data-sa-cat-close-modal tabindex="-1" aria-hidden="true"></div>
        <div class="sa-cat-modal__panel">
            <div class="sa-cat-modal__header">
                <h2 id="saModalCgTitle"><?php echo $row_edit_cg ? 'Modifier un rayon' : 'Nouveau rayon'; ?></h2>
                <button type="button" class="sa-cat-modal__close" data-sa-cat-close-modal aria-label="Fermer"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <div class="sa-cat-modal__body">
                <?php if ($row_edit_cg): ?>
                    <div class="sa-cat-form-block">
                        <form class="sa-cat-form" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="cg_id" value="<?php echo (int) $row_edit_cg['id']; ?>">
                            <p><strong>Modifier</strong> « <?php echo htmlspecialchars((string) $row_edit_cg['nom'], ENT_QUOTES, 'UTF-8'); ?> »</p>
                            <div class="sa-cat-field">
                                <label for="cg_nom">Nom</label>
                                <input type="text" id="cg_nom" name="cg_nom" required maxlength="255" value="<?php echo htmlspecialchars((string) $row_edit_cg['nom'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="sa-cat-field">
                                <label for="cg_description">Description</label>
                                <textarea id="cg_description" name="cg_description"><?php echo htmlspecialchars((string) ($row_edit_cg['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="sa-cat-field">
                                <label for="cg_icone">Icône (Font Awesome, ex. fa-solid fa-basket-shopping)</label>
                                <input type="text" id="cg_icone" name="cg_icone" maxlength="80" value="<?php echo htmlspecialchars((string) ($row_edit_cg['icone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="sa-cat-field">
                                <label for="cg_sort">Ordre d’affichage</label>
                                <input type="number" id="cg_sort" name="cg_sort" value="<?php echo (int) ($row_edit_cg['sort_ordre'] ?? 0); ?>">
                            </div>
                            <?php if ($attr_cols_ok): ?>
                            <fieldset class="sa-cat-field sa-cat-fieldset-attrs">
                                <legend>Champs vendeur (produit)</legend>
                                <p class="sa-cat-field-hint" style="margin:0 0 10px;font-size:13px;color:#555;">Cochez les attributs que le vendeur pourra renseigner pour un produit classé dans ce rayon.</p>
                                <div class="sa-cat-attrs-grid">
                                    <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_poids" value="1" <?php echo !empty($attr_edit['poids']) ? 'checked' : ''; ?>> Poids</label>
                                    <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_taille" value="1" <?php echo !empty($attr_edit['taille']) ? 'checked' : ''; ?>> Taille</label>
                                    <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_mesure" value="1" <?php echo !empty($attr_edit['mesure']) ? 'checked' : ''; ?>> Mesure / unité</label>
                                    <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_couleur" value="1" <?php echo !empty($attr_edit['couleur']) ? 'checked' : ''; ?>> Couleur</label>
                                </div>
                            </fieldset>
                            <?php else: ?>
                            <p class="sa-cat-field-hint" style="font-size:13px;color:#666;">Les cases « attributs produit » seront disponibles après exécution de la migration <code>run_migrate_categories_generales_attributs_produit.php</code>.</p>
                            <?php endif; ?>
                            <div class="sa-cat-actions">
                                <button type="submit" name="update_cg" value="1" class="sa-cat-btn sa-cat-btn--primary">Enregistrer</button>
                                <a href="categories-catalogue.php" class="sa-cat-btn sa-cat-btn--ghost">Annuler</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="sa-cat-form-block">
                        <div class="sa-cat-form-card">
                            <form class="sa-cat-form" method="post" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                <p><strong>Nouvelle</strong> catégorie générale</p>
                                <div class="sa-cat-field">
                                    <label for="cg_nom_new">Nom</label>
                                    <input type="text" id="cg_nom_new" name="cg_nom" required maxlength="255" placeholder="Ex. Alimentation">
                                </div>
                                <div class="sa-cat-field">
                                    <label for="cg_description_new">Description</label>
                                    <textarea id="cg_description_new" name="cg_description" placeholder="Texte d’aide (optionnel)"></textarea>
                                </div>
                                <div class="sa-cat-field">
                                    <label for="cg_icone_new">Icône (optionnel)</label>
                                    <input type="text" id="cg_icone_new" name="cg_icone" maxlength="80" placeholder="fa-solid fa-basket-shopping">
                                </div>
                                <div class="sa-cat-field">
                                    <label for="cg_sort_new">Ordre</label>
                                    <input type="number" id="cg_sort_new" name="cg_sort" value="0">
                                </div>
                                <?php if ($attr_cols_ok): ?>
                                <fieldset class="sa-cat-field sa-cat-fieldset-attrs">
                                    <legend>Champs vendeur (produit)</legend>
                                    <p class="sa-cat-field-hint" style="margin:0 0 10px;font-size:13px;color:#555;">Par défaut tout est activé ; décochez ce qui ne s’applique pas à ce rayon.</p>
                                    <div class="sa-cat-attrs-grid">
                                        <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_poids" value="1" checked> Poids</label>
                                        <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_taille" value="1" checked> Taille</label>
                                        <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_mesure" value="1" checked> Mesure / unité</label>
                                        <label class="sa-cat-attr-check"><input type="checkbox" name="cg_attr_couleur" value="1" checked> Couleur</label>
                                    </div>
                                </fieldset>
                                <?php else: ?>
                                <p class="sa-cat-field-hint" style="font-size:13px;color:#666;">Migration <code>run_migrate_categories_generales_attributs_produit.php</code> requise pour configurer les attributs par rayon.</p>
                                <?php endif; ?>
                                <button type="submit" name="create_cg" value="1" class="sa-cat-btn sa-cat-btn--primary" <?php echo $table_ok ? '' : 'disabled'; ?>>Ajouter le rayon</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
    (function () {
        var OPEN = 'sa-cat-modal-open';
        function openModal(el) {
            if (!el) return;
            el.removeAttribute('hidden');
            document.body.classList.add(OPEN);
        }
        function closeModal(el) {
            if (!el) return;
            var reset = el.getAttribute('data-sa-cat-close-reset-href');
            if (reset) {
                window.location.href = reset;
                return;
            }
            el.setAttribute('hidden', 'hidden');
            document.body.classList.remove(OPEN);
        }
        document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-open-modal');
                openModal(document.getElementById(id));
            });
        });
        document.querySelectorAll('[data-sa-cat-close-modal]').forEach(function (node) {
            node.addEventListener('click', function () {
                var m = node.closest('.sa-cat-modal');
                if (m) closeModal(m);
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var vis = document.querySelector('.sa-cat-modal:not([hidden])');
            if (vis) closeModal(vis);
        });
    })();
    </script>
</body>

</html>

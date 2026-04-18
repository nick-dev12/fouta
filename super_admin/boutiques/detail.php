<?php
/**
 * Détail d'une boutique vendeur — Super Admin
 */
require_once __DIR__ . '/../includes/require_login.php';
require_once dirname(__DIR__, 2) . '/models/model_super_admin.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace_helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$b = $id > 0 ? super_admin_get_boutique_stats($id) : false;
if (!$b) {
    header('Location: index.php');
    exit;
}

$vitrine = !empty($b['boutique_slug']) ? boutique_url('index.php', (string) $b['boutique_slug']) : '';
$titre = (string) ($b['boutique_nom'] ?: $b['nom']);
$dc = !empty($b['date_creation']) ? $b['date_creation'] : '';
$dc_fmt = $dc !== '' ? date('d/m/Y à H:i', strtotime((string) $dc)) : '—';
$est_actif = (($b['statut'] ?? '') === 'actif');

$n_cat = (int) $b['nb_produits_catalogue'];
$n_act = (int) $b['nb_produits_actifs'];
$n_rup = (int) $b['nb_produits_rupture'];
$n_ina = (int) $b['nb_produits_inactifs'];
$n_tot = (int) $b['nb_produits_total'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include dirname(__DIR__, 2) . '/includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?> — Boutique · Super Admin</title>
    <?php require_once dirname(__DIR__, 2) . '/includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-clients.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/super-admin-boutique-detail.css<?php echo asset_version_query(); ?>">
</head>

<body class="page-users admin-clients-page sa-users-page sa-boutique-detail">
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <div class="sa-users-shell">
        <header class="sa-users-hero" aria-labelledby="sa-bd-title">
            <div class="sa-users-hero__inner">
                <div>
                    <p class="sa-users-hero__eyebrow"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Fiche boutique vendeur</p>
                    <h1 class="sa-users-hero__title" id="sa-bd-title"><?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if (!empty($b['boutique_slug'])): ?>
                        <p class="sa-bd-slug" title="Slug URL"><i class="fas fa-link" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $b['boutique_slug'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div class="sa-bd-hero-actions">
                        <a class="sa-bd-btn sa-bd-btn--ghost" href="index.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à la liste</a>
                        <?php if ($vitrine !== ''): ?>
                            <a class="sa-bd-btn sa-bd-btn--accent" href="<?php echo htmlspecialchars($vitrine, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-external-link-alt" aria-hidden="true"></i> Ouvrir la vitrine
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sa-users-kpis" role="group" aria-label="Synthèse boutique">
                    <div class="sa-users-kpi">
                        <span class="sa-users-kpi__label">Accès vendeur</span>
                        <?php if ($est_actif): ?>
                            <span class="sa-badge-hero sa-badge-hero--ok" style="display:block;margin-top:6px;">Actif</span>
                        <?php else: ?>
                            <span class="sa-badge-hero sa-badge-hero--off" style="display:block;margin-top:6px;">Désactivé</span>
                        <?php endif; ?>
                    </div>
                    <div class="sa-users-kpi">
                        <span class="sa-users-kpi__label">Produits visibles</span>
                        <span class="sa-users-kpi__value"><?php echo $n_cat; ?></span>
                        <span style="display:block;font-size:0.72rem;opacity:0.85;margin-top:4px;">Catalogue (actif + rupture)</span>
                    </div>
                    <div class="sa-users-kpi">
                        <span class="sa-users-kpi__label">Références totales</span>
                        <span class="sa-users-kpi__value"><?php echo $n_tot; ?></span>
                        <span style="display:block;font-size:0.72rem;opacity:0.85;margin-top:4px;">Lignes produits liées</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="sa-bd-panels">
            <section class="sa-bd-card" aria-labelledby="sa-bd-coord">
                <div class="sa-bd-card__head">
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                    <h2 id="sa-bd-coord">Identité &amp; contact</h2>
                </div>
                <div class="sa-bd-card__body">
                    <div class="sa-bd-row">
                        <div class="sa-bd-label">Titulaire du compte</div>
                        <div class="sa-bd-value"><?php echo htmlspecialchars(trim($b['nom'] . ' ' . ($b['prenom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="sa-bd-row">
                        <div class="sa-bd-label">E-mail</div>
                        <div class="sa-bd-value"><?php echo htmlspecialchars((string) ($b['email'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="sa-bd-row">
                        <div class="sa-bd-label">Téléphone</div>
                        <div class="sa-bd-value"><?php echo htmlspecialchars((string) ($b['telephone'] ?: '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="sa-bd-row">
                        <div class="sa-bd-label">Compte créé le</div>
                        <div class="sa-bd-value"><?php echo htmlspecialchars($dc_fmt, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="sa-bd-row">
                        <div class="sa-bd-label">Connexion boutique</div>
                        <div class="sa-bd-value">
                            <?php if ($est_actif): ?>
                                <span class="sa-badge sa-badge--ok" style="vertical-align:middle;">Autorisée</span>
                            <?php else: ?>
                                <span class="sa-badge sa-badge--off" style="vertical-align:middle;">Bloquée</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="sa-bd-card" aria-labelledby="sa-bd-cat">
                <div class="sa-bd-card__head">
                    <i class="fas fa-boxes-stacked" aria-hidden="true"></i>
                    <h2 id="sa-bd-cat">Catalogue produits</h2>
                </div>
                <div class="sa-bd-card__body">
                    <div class="sa-bd-metrics" role="list">
                        <div class="sa-bd-metric" role="listitem">
                            <div class="sa-bd-metric__num"><?php echo $n_act; ?></div>
                            <div class="sa-bd-metric__label">Actifs</div>
                        </div>
                        <div class="sa-bd-metric" role="listitem">
                            <div class="sa-bd-metric__num"><?php echo $n_rup; ?></div>
                            <div class="sa-bd-metric__label">Rupture stock</div>
                        </div>
                        <div class="sa-bd-metric" role="listitem">
                            <div class="sa-bd-metric__num"><?php echo $n_ina; ?></div>
                            <div class="sa-bd-metric__label">Hors catalogue</div>
                        </div>
                        <div class="sa-bd-metric" role="listitem">
                            <div class="sa-bd-metric__num"><?php echo $n_tot; ?></div>
                            <div class="sa-bd-metric__label">Total lignes</div>
                        </div>
                    </div>
                    <p class="sa-bd-breakdown">
                        <strong><?php echo $n_cat; ?></strong> produit<?php echo $n_cat > 1 ? 's' : ''; ?> visibles pour les clients
                        (statut <strong>actif</strong> ou <strong>en rupture</strong> de stock).
                        Répartition : <strong><?php echo $n_act; ?></strong> actifs,
                        <strong><?php echo $n_rup; ?></strong> en rupture,
                        <strong><?php echo $n_ina; ?></strong> inactifs au catalogue.
                    </p>
                </div>
            </section>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

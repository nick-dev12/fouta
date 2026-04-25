<?php
/**
 * Page principale des paramètres - Regroupe toutes les configurations
 * Programmation procédurale uniquement
 */

session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/require_access.php';

$__param_role = admin_normalize_role_for_route($_SESSION['admin_role'] ?? 'admin');
$__param_show_site_modules = in_array($__param_role, ['admin', 'plateforme', 'vendeur'], true);
$__param_show_comptes = in_array($__param_role, ['admin', 'plateforme', 'vendeur', 'rh'], true);
$__param_retour = admin_role_default_redirect_path($__param_role);

require_once __DIR__ . '/../includes/site_url.php';
require_once __DIR__ . '/../includes/marketplace_helpers.php';

/** Lien « Voir le site » : vitrine vendeur ou accueil marketplace */
$__vendeur_boutique_slug = ($__param_role === 'vendeur')
    ? trim((string) ($_SESSION['admin_boutique_slug'] ?? ''))
    : '';
$__vendeur_site_path = '';
$__vendeur_site_full_url = '';
if ($__vendeur_boutique_slug !== '') {
    $__vendeur_site_path = boutique_url('index.php', $__vendeur_boutique_slug);
    $__vendeur_site_full_url = rtrim(get_site_base_url(), '/') . $__vendeur_site_path;
}
$__voir_site_href = ($__vendeur_boutique_slug !== '')
    ? $__vendeur_site_path
    : '../index.php';
$__vendeur_boutique_nom_aff = trim((string) ($_SESSION['admin_boutique_nom'] ?? ''));
if ($__vendeur_boutique_nom_aff === '') {
    $__vendeur_boutique_nom_aff = 'Ma boutique';
}

// Afficher le message de succès s'il existe
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Administration</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <?php if ($__param_role === 'vendeur'): ?>
    <style>
        .param-vendeur-vitrine {
            max-width: 960px;
            margin: 0 24px 20px;
            padding: 18px 20px;
            background: linear-gradient(135deg, rgba(53, 100, 166, 0.08), rgba(255, 107, 53, 0.06));
            border: 1px solid rgba(53, 100, 166, 0.2);
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(53, 100, 166, 0.08);
        }
        .param-vendeur-vitrine__title {
            margin: 0 0 6px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary, #1a1a2e);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .param-vendeur-vitrine__title i { color: var(--accent, #3564a6); }
        .param-vendeur-vitrine__lead {
            margin: 0 0 14px;
            font-size: 0.9rem;
            color: var(--text-muted, #5a5a6e);
            line-height: 1.45;
        }
        .param-vendeur-vitrine__row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: stretch;
        }
        .param-vendeur-vitrine__url {
            flex: 1 1 220px;
            min-width: 0;
            padding: 10px 12px;
            font-size: 0.88rem;
            font-family: ui-monospace, monospace;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            background: #fff;
            color: #1a1a2e;
        }
        .param-vendeur-vitrine__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .param-vendeur-vitrine__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s, transform 0.15s;
        }
        .param-vendeur-vitrine__btn:hover { opacity: 0.92; transform: translateY(-1px); }
        .param-vendeur-vitrine__btn--copy {
            background: #3564a6;
            color: #fff;
        }
        .param-vendeur-vitrine__btn--share {
            background: #ff6b35;
            color: #fff;
        }
        .param-vendeur-vitrine__warn {
            margin: 0;
            padding: 12px;
            background: rgba(255, 107, 53, 0.12);
            border-radius: 8px;
            font-size: 0.9rem;
            color: #6b2f20;
        }
        .param-vendeur-vitrine__warn a { color: #3564a6; font-weight: 600; }
        @media (max-width: 600px) {
            .param-vendeur-vitrine { margin: 0 16px 16px; padding: 14px; }
            .param-vendeur-vitrine__actions { width: 100%; }
            .param-vendeur-vitrine__btn { flex: 1 1 auto; }
        }
    </style>
    <?php endif; ?>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <header class="dashboard-page-header" aria-label="<?php echo $__param_show_site_modules ? 'Paramètres du site' : 'Compte et accès'; ?>">
        <div class="dashboard-page-header__intro">
            <p class="dashboard-page-header__eyebrow"><?php echo $__param_show_site_modules ? 'Contenu &amp; expérience' : 'Espace connecté'; ?></p>
            <h1 class="dashboard-page-header__title">
                <i class="fas fa-<?php echo $__param_show_site_modules ? 'sliders-h' : 'user-cog'; ?>" aria-hidden="true"></i>
                <span><?php echo $__param_show_site_modules ? 'Paramètres du site' : 'Compte et raccourcis'; ?></span>
            </h1>
            <?php if (!($__param_show_site_modules && $__param_role === 'vendeur')): ?>
            <p class="dashboard-page-header__lead">
                <?php if ($__param_show_site_modules): ?>
                Modifiez l’accueil, les médias et la logistique depuis un tableau clair. Chaque carte mène à un écran
                dédié&nbsp;; enregistrez après vos changements pour les voir en ligne.
                <?php else: ?>
                Accédez à votre profil<?php echo $__param_show_comptes ? ' et à la gestion des comptes d’accès' : ''; ?>
                depuis cette page. Les réglages du site public sont réservés aux administrateurs.
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        <div class="dashboard-page-header__toolbar" role="group" aria-label="Navigation rapide">
            <a href="<?php echo htmlspecialchars($__param_retour); ?>" class="dash-tool-btn dash-tool-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span><?php echo in_array($__param_role, ['admin', 'plateforme', 'vendeur'], true) ? 'Tableau de bord' : 'Retour'; ?></span>
            </a>
            <?php if ($__param_show_comptes): ?>
            <a href="comptes/index.php" class="dash-tool-btn dash-tool-btn--outline" title="Comptes d’accès">
                <i class="fas fa-user-shield" aria-hidden="true"></i>
                <span>Comptes d’accès</span>
            </a>
            <?php endif; ?>
            <a href="profil.php" class="dash-tool-btn dash-tool-btn--outline" title="Mon profil">
                <i class="fas fa-user" aria-hidden="true"></i>
                <span>Mon profil</span>
            </a>
            <a href="<?php echo htmlspecialchars($__voir_site_href, ENT_QUOTES, 'UTF-8'); ?>" class="dash-tool-btn dash-tool-btn--outline" target="_blank" rel="noopener noreferrer" title="<?php echo $__vendeur_boutique_slug !== '' ? 'Ouvrir la vitrine de votre boutique' : 'Ouvrir le site public'; ?>">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span><?php echo $__vendeur_boutique_slug !== '' ? 'Voir ma boutique' : 'Voir le site'; ?></span>
            </a>
        </div>
    </header>

    <?php if (!empty($success_message)): ?>
        <div class="message success parametres-flash-success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($__param_role === 'vendeur'): ?>
    <div class="param-vendeur-vitrine" role="region" aria-labelledby="param-vendeur-vitrine-title">
        <h2 id="param-vendeur-vitrine-title" class="param-vendeur-vitrine__title">
            <i class="fas fa-store" aria-hidden="true"></i>
            Lien de votre boutique en ligne
        </h2>
        <?php if ($__vendeur_boutique_slug !== ''): ?>
        <div class="param-vendeur-vitrine__row">
            <label class="visually-hidden" for="vendeurBoutiquePublicUrl">URL publique de la boutique</label>
            <input type="text" id="vendeurBoutiquePublicUrl" class="param-vendeur-vitrine__url" readonly value="<?php echo htmlspecialchars($__vendeur_site_full_url, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
            <div class="param-vendeur-vitrine__actions">
                <button type="button" class="param-vendeur-vitrine__btn param-vendeur-vitrine__btn--copy" id="vendeurCopyBoutiqueUrl" title="Copier le lien dans le presse-papiers">
                    <i class="fas fa-copy" aria-hidden="true"></i>
                    Copier le lien
                </button>
                <button type="button" class="param-vendeur-vitrine__btn param-vendeur-vitrine__btn--share" id="vendeurShareBoutiqueUrl" title="Partager via les applications du téléphone ou du navigateur">
                    <i class="fas fa-share-alt" aria-hidden="true"></i>
                    Partager
                </button>
            </div>
        </div>
        <p id="vendeurCopyBoutiqueFeedback" class="param-vendeur-vitrine__lead" style="margin-top:12px;margin-bottom:0;min-height:1.2em;font-weight:600;color:#3564a6;" aria-live="polite"></p>
        <?php else: ?>
        <p class="param-vendeur-vitrine__warn">
            Aucun identifiant de boutique n’est associé à votre compte. Définissez le nom et l’URL de votre boutique dans
            <a href="profil.php">votre profil administrateur</a>, puis reconnectez-vous si besoin.
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <section class="produits-section parametres-section" aria-labelledby="parametres-hub-title">
        <?php if ($__param_show_site_modules): ?>
        <div class="parametres-section__heading">
            <h2 id="parametres-hub-title">Modules à configurer</h2>
        </div>
        <?php if ($__param_role !== 'vendeur'): ?>
        <p class="parametres-section__meta">
            Six blocs couvrent la page d’accueil et les livraisons. Utilisez les boutons «&nbsp;Gérer&nbsp;» ou «&nbsp;Modifier&nbsp;» pour ouvrir l’écran correspondant.
        </p>
        <?php endif; ?>

        <div class="parametres-grid">
            <?php if ($__param_role === 'vendeur'): ?>
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h3 class="parametre-title">Apparence de ma boutique</h3>
                <a href="parametres-boutique-vendeur.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Personnaliser
                </a>
            </article>
            <?php endif; ?>
            <?php if ($__param_role !== 'vendeur'): ?>
            <!-- Bannière d'Accueil -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3 class="parametre-title">Bannière d'Accueil</h3>
                <p class="parametre-description">
                    Personnalisez la bannière principale de votre page d'accueil : modifiez le titre, le texte
                    d'accroche et l'image de fond pour créer une première impression mémorable.
                </p>
                <a href="parametres/section4.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier la bannière
                </a>
            </article>

            <!-- Section Tendance -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="parametre-title">Section Mise en Avant</h3>
                <p class="parametre-description">
                    Configurez la section de mise en avant des produits : définissez le label, le titre promotionnel, le
                    texte du bouton d'action et l'image illustrative.
                </p>
                <a href="parametres/trending.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Modifier la section
                </a>
            </article>
            <?php endif; ?>

            <!-- Carrousel Principal (admin, plateforme et vendeur) -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h3 class="parametre-title">Images d'affiche pub</h3>
                <?php if ($__param_role !== 'vendeur'): ?>
                <p class="parametre-description">
                    Gérez le slider d'images en haut de la page d'accueil : ajoutez, modifiez ou supprimez les slides
                    avec leurs titres, textes et boutons d'action.
                </p>
                <?php endif; ?>
                <a href="slider/index.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Gérer le slider
                </a>
            </article>

            <?php if ($__param_role !== 'vendeur'): ?>
            <!-- Section Vidéos -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-video"></i>
                </div>
                <h3 class="parametre-title">Section Vidéos</h3>
                <p class="parametre-description">
                    Gérez les vidéos du carrousel "Ils ont découvert ICON" : ajoutez, modifiez ou supprimez des vidéos
                    YouTube, Vimeo ou locales avec leurs images de prévisualisation.
                </p>
                <a href="parametres/videos.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Gérer les vidéos
                </a>
            </article>

            <!-- Logos Partenaires -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-images"></i>
                </div>
                <h3 class="parametre-title">Logos Partenaires</h3>
                <p class="parametre-description">
                    Gérez les logos affichés en carrousel sur la page d'accueil : ajoutez, modifiez ou supprimez des logos.
                </p>
                <a href="parametres/logos.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Gérer les logos
                </a>
            </article>

            <?php endif; ?>

            <!-- Zones de livraison -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="parametre-title">Zones de livraison</h3>
                <a href="zones-livraison/index.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Gérer les zones
                </a>
            </article>
        </div>
        <?php else: ?>
        <div class="parametres-section__heading">
            <h2 id="parametres-hub-title">Raccourcis</h2>
        </div>
        <p class="parametres-section__meta">
            Utilisez les boutons ci-dessus pour ouvrir votre profil<?php echo $__param_show_comptes ? ' ou la liste des comptes d’accès' : ''; ?>.
        </p>
        <?php endif; ?>
    </section>

    <?php include 'includes/footer.php'; ?>

    <?php if ($__param_role === 'vendeur' && $__vendeur_boutique_slug !== ''): ?>
    <script>
    (function () {
        var url = <?php echo json_encode($__vendeur_site_full_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var title = <?php echo json_encode($__vendeur_boutique_nom_aff . ' — vitrine', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var input = document.getElementById('vendeurBoutiquePublicUrl');
        var feedback = document.getElementById('vendeurCopyBoutiqueFeedback');
        var btnCopy = document.getElementById('vendeurCopyBoutiqueUrl');
        var btnShare = document.getElementById('vendeurShareBoutiqueUrl');

        function showFeedback(msg) {
            if (feedback) {
                feedback.textContent = msg;
                window.setTimeout(function () { if (feedback) feedback.textContent = ''; }, 3500);
            }
        }

        if (btnCopy && input) {
            btnCopy.addEventListener('click', function () {
                function fallbackCopy() {
                    input.focus();
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                    var ok = false;
                    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                    showFeedback(ok ? 'Lien copié dans le presse-papiers.' : 'Sélectionnez le lien et copiez-le (Ctrl+C).');
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        showFeedback('Lien copié dans le presse-papiers.');
                    }).catch(fallbackCopy);
                    return;
                }
                fallbackCopy();
            });
        }

        if (btnShare) {
            btnShare.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: title, text: title, url: url }).catch(function () {});
                } else {
                    if (btnCopy) btnCopy.click();
                    else showFeedback('Copiez le lien ci-dessus pour le partager.');
                }
            });
        }
    })();
    </script>
    <?php endif; ?>
</body>

</html>
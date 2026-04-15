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
            <p class="dashboard-page-header__lead">
                <?php if ($__param_show_site_modules): ?>
                Modifiez l’accueil, les médias et la logistique depuis un tableau clair. Chaque carte mène à un écran
                dédié&nbsp;; enregistrez après vos changements pour les voir en ligne.
                <?php else: ?>
                Accédez à votre profil<?php echo $__param_show_comptes ? ' et à la gestion des comptes d’accès' : ''; ?>
                depuis cette page. Les réglages du site public sont réservés aux administrateurs.
                <?php endif; ?>
            </p>
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
            <a href="../index.php" class="dash-tool-btn dash-tool-btn--outline" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span>Voir le site</span>
            </a>
        </div>
    </header>

    <?php if (!empty($success_message)): ?>
        <div class="message success parametres-flash-success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <section class="produits-section parametres-section" aria-labelledby="parametres-hub-title">
        <?php if ($__param_show_site_modules): ?>
        <div class="parametres-section__heading">
            <h2 id="parametres-hub-title">Modules à configurer</h2>
        </div>
        <p class="parametres-section__meta">
            Six blocs couvrent la page d’accueil et les livraisons. Utilisez les boutons «&nbsp;Gérer&nbsp;» ou «&nbsp;Modifier&nbsp;» pour ouvrir l’écran correspondant.
        </p>

        <div class="parametres-grid">
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

            <!-- Carrousel Principal -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h3 class="parametre-title">Slider Principal</h3>
                <p class="parametre-description">
                    Gérez le slider d'images en haut de la page d'accueil : ajoutez, modifiez ou supprimez les slides
                    avec leurs titres, textes et boutons d'action.
                </p>
                <a href="slider/index.php" class="parametre-link">
                    <i class="fas fa-edit" aria-hidden="true"></i> Gérer le slider
                </a>
            </article>

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

            <!-- Zones de livraison -->
            <article class="parametre-card">
                <div class="parametre-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="parametre-title">Zones de livraison</h3>
                <p class="parametre-description">
                    Définissez les zones géographiques, tarifs et modalités de livraison pour vos commandes en ligne.
                </p>
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
</body>

</html>
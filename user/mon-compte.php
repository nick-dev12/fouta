<?php
/**
 * Page tableau de bord utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les informations de l'utilisateur
require_once __DIR__ . '/../models/model_users.php';
$user = get_user_by_id($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

// Récupérer uniquement les produits des commandes livrées
require_once __DIR__ . '/../models/model_commandes.php';
$produits_commandes = get_produits_commandes_by_user($_SESSION['user_id'], 'livree');

// Récupérer les statistiques
require_once __DIR__ . '/../models/model_favoris.php';
require_once __DIR__ . '/../models/model_visites.php';
$nb_commandes = count_commandes_by_user($_SESSION['user_id']);
$nb_panier = count_panier_items_by_user($_SESSION['user_id']);
$nb_favoris = count_favoris_by_user($_SESSION['user_id']);
$nb_visites = count_visites_by_user($_SESSION['user_id']);

$avatar_initial = '';
$prenom_trim = trim((string) ($user['prenom'] ?? ''));
if ($prenom_trim !== '') {
    $avatar_initial = mb_strtoupper(mb_substr($prenom_trim, 0, 1, 'UTF-8'), 'UTF-8');
} else {
    $avatar_initial = '?';
}
$user_email_display = trim((string) ($user['email'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mon compte — Tableau de bord</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mon-compte.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-mon-compte">
    <?php include 'includes/user_nav.php'; ?>

    <div class="mc-dashboard">
        <!-- Bloc d’accueil glass -->
        <section class="mc-hero" aria-labelledby="mc-welcome-title">
            <div class="mc-hero-main">
                <div class="mc-hero-welcome">
                    <div class="mc-avatar" aria-hidden="true"><?php echo htmlspecialchars($avatar_initial); ?></div>
                    <div>
                        <p class="mc-eyebrow">Espace client</p>
                        <h1 id="mc-welcome-title">
                            <i class="fas fa-house-chimney" aria-hidden="true"></i>
                            Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                        </h1>
                        <?php if ($user_email_display !== ''): ?>
                        <p class="mc-user-email"><?php echo htmlspecialchars($user_email_display); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mc-hero-actions">
                    <button type="button" id="btn-enable-notifications"
                        class="mc-btn mc-btn--notify btn-enable-notifications">
                        <i class="fas fa-bell-slash" aria-hidden="true"></i> Activer les notifications
                    </button>
                    <a href="/index.php" class="mc-btn mc-btn--primary">
                        <i class="fas fa-store" aria-hidden="true"></i> Voir les produits
                    </a>
                </div>
            </div>
            <div class="mc-shop-row">
                <div class="mc-shop-row-left">
                    <div class="mc-shop-icon" aria-hidden="true">
                        <i class="fas fa-basket-shopping"></i>
                    </div>
                    <div class="mc-shop-text">
                        <h2>Continuer mes achats</h2>
                        <p>Parcourez le catalogue et complétez votre panier en toute simplicité.</p>
                    </div>
                </div>
                <a href="/index.php" class="mc-shop-cta">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i> Accueil boutique
                </a>
            </div>
        </section>

        <!-- Statistiques -->
        <div class="stats-grid" role="region" aria-label="Indicateurs du compte">
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">
                    <i class="fas fa-bag-shopping"></i>
                </div>
                <div class="stat-value"><?php echo (int) $nb_commandes; ?></div>
                <div class="stat-label">Commandes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">
                    <i class="fas fa-cart-shopping"></i>
                </div>
                <div class="stat-value"><?php echo (int) $nb_panier; ?></div>
                <div class="stat-label">Articles au panier</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value"><?php echo (int) $nb_favoris; ?></div>
                <div class="stat-label">Favoris</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-value"><?php echo (int) $nb_visites; ?></div>
                <div class="stat-label">Produits visités</div>
            </div>
        </div>

        <!-- Produits livrés -->
        <section class="content-section mc-products-section">
            <div class="section-title">
                <h2><i class="fas fa-circle-check" aria-hidden="true"></i> Mes produits livrés</h2>
            </div>

            <?php if (empty($produits_commandes)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open" aria-hidden="true"></i>
                <p>Aucun produit livré pour le moment.</p>
                <a href="mes-commandes.php" class="btn-primary">
                    <i class="fas fa-bag-shopping" aria-hidden="true"></i> Voir mes commandes
                </a>
            </div>
            <?php else: ?>
            <div class="produits-grid">
                <?php foreach ($produits_commandes as $produit): ?>
                <?php
                    $statut_class = 'statut-actif';
                    if ($produit['statut'] == 'inactif') {
                        $statut_class = 'statut-inactif';
                    } elseif ($produit['statut'] == 'rupture_stock') {
                        $statut_class = 'statut-rupture';
                    }
                    $statut_label = ucfirst(str_replace('_', ' ', $produit['statut']));
                    ?>
                <div class="produit-card">
                    <span class="statut-badge <?php echo $statut_class; ?>"><?php echo htmlspecialchars($statut_label); ?></span>
                    <img src="/upload/<?php echo htmlspecialchars($produit['image_principale']); ?>"
                        alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="produit-card-image"
                        onerror="this.src='/image/produit1.jpg'">
                    <div class="produit-card-body">
                        <h3 class="produit-card-nom"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                        <p class="produit-card-categorie">
                            <?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Sans catégorie'); ?>
                        </p>
                        <p class="produit-card-prix">
                            <?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?>
                            <span class="prix-unite">FCFA</span>
                            <?php if ($produit['prix_promotion']): ?>
                            <span class="prix-promo">
                                (Promo: <?php echo number_format($produit['prix_promotion'], 0, ',', ' '); ?> FCFA)
                            </span>
                            <?php endif; ?>
                        </p>
                        <p class="produit-card-stock produit-card-quantite">
                            <strong>Quantité commandée :</strong> <?php echo (int) $produit['quantite']; ?>
                            <?php if (!empty($produit['poids'])): ?>
                            (<?php echo htmlspecialchars($produit['poids']); ?>)
                            <?php endif; ?>
                        </p>
                        <p class="produit-card-stock produit-card-commande">
                            <strong>Commande :</strong> <?php echo htmlspecialchars($produit['numero_commande']); ?>
                        </p>
                        <div class="produit-card-actions">
                            <a href="/produit.php?id=<?php echo (int) $produit['id']; ?>" class="btn-card btn-view">
                                <i class="fas fa-eye" aria-hidden="true"></i> Voir
                            </a>
                            <a href="mes-commandes.php" class="btn-card btn-favorite">
                                <i class="fas fa-list" aria-hidden="true"></i> Détails
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <script src="https://www.gstatic.com/firebasejs/12.9.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging-compat.js"></script>
    <?php require_once __DIR__ . '/../includes/firebase_init.php'; ?>
    <script>
        if (window.FIREBASE_CONFIG) {
            firebase.initializeApp(window.FIREBASE_CONFIG);
        }
    </script>
    <script src="/js/firebase-notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('btn-enable-notifications');
            if (btn) {
                btn.addEventListener('click', function() {
                    if (typeof FirebaseNotifications !== 'undefined') {
                        FirebaseNotifications.enable('user', this);
                    } else {
                        alert('Erreur: Les scripts de notification ne sont pas chargés.');
                    }
                });
            }
        });
    </script>

    <?php include 'includes/user_footer.php'; ?>

<?php
/**
 * Page des commandes livrées
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer uniquement les commandes avec le statut "livree"
require_once __DIR__ . '/../models/model_commandes.php';
$commandes = get_commandes_by_user($_SESSION['user_id']);

// Filtrer pour ne garder que les commandes avec le statut "livree"
$commandes_livrees = array_filter($commandes, function ($commande) {
    return $commande['statut'] === 'livree';
});

$nb_livrees = count($commandes_livrees);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Produits livrés — FOUTA POIDS LOURDS</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-produits-livres.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-produits-livres">
    <?php include 'includes/user_nav.php'; ?>

    <div class="mc-orders">
        <header class="mc-orders-hero">
            <div class="mc-orders-hero__inner">
                <div class="mc-orders-hero__top">
                    <div class="mc-orders-hero__intro">
                        <p class="mc-eyebrow">Historique livré</p>
                        <h1 id="mc-pl-hero-heading">
                            <span class="mc-hero-icon" aria-hidden="true"><i class="fas fa-circle-check"></i></span>
                            <span class="mc-orders-hero__title-text">Produits livrés</span>
                        </h1>
                    </div>
                    <div class="mc-orders-hero__metrics" aria-labelledby="mc-pl-hero-heading">
                        <div class="mc-stat-pill mc-stat-pill--compact">
                            <i class="fas fa-truck-fast" aria-hidden="true"></i>
                            <div class="mc-stat-pill__text">
                                <strong><?php echo (int) $nb_livrees; ?></strong>
                                <span>Reçues</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mc-orders-lead">Historique de vos commandes livrées — consultez le détail des articles reçus
                    ci-dessous.</p>
            </div>
        </header>

        <section class="mc-continue-banner" aria-label="Navigation rapide">
            <div class="mc-continue-inner">
                <div class="mc-continue-icon" aria-hidden="true">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="mc-continue-text">
                    <strong>Encore des colis en route ?</strong>
                    <p>Suivez vos commandes en cours ou ajoutez de nouveaux articles au panier.</p>
                </div>
                <div class="mc-continue-actions">
                    <a href="mes-commandes.php" class="mc-btn mc-btn--primary">
                        <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                        Mes commandes
                    </a>
                    <a href="/produits.php" class="mc-btn mc-btn--secondary">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        Catalogue
                    </a>
                </div>
            </div>
        </section>

        <section class="content-section mc-orders-section">
            <div class="mc-section-head">
                <h2>
                    <span class="mc-section-icon" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                    Commandes reçues (<?php echo $nb_livrees; ?>)
                </h2>
            </div>

            <?php if (empty($commandes_livrees)): ?>
            <div class="mc-empty mc-empty--delivered">
                <div class="mc-empty-icon" aria-hidden="true"><i class="fas fa-parachute-box"></i></div>
                <p>Aucune commande livrée pour l’instant. Une fois votre colis reçu et confirmé, il apparaîtra ici.</p>
                <a href="mes-commandes.php" class="btn-primary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Voir mes commandes en cours
                </a>
            </div>
            <?php else: ?>
            <div class="mc-commandes-grid">
                <?php foreach ($commandes_livrees as $commande): ?>
                <article class="mc-commande-card mc-commande-card--delivered">
                    <div class="mc-commande-card__top">
                        <div>
                            <h3 class="mc-commande-card__ref">Commande #<?php echo htmlspecialchars($commande['numero_commande']); ?></h3>
                            <p class="mc-commande-card__date">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="commande-statut statut-livree mc-badge">
                            <i class="fas fa-check" aria-hidden="true"></i> Reçu
                        </span>
                    </div>
                    <div class="mc-commande-card__body">
                        <div class="mc-detail-row">
                            <label>Montant</label>
                            <div class="value value--montant">
                                <?php echo number_format($commande['montant_total'], 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                        <div class="mc-detail-row">
                            <label>Adresse</label>
                            <div class="value value--address">
                                <?php echo htmlspecialchars(substr($commande['adresse_livraison'], 0, 80)); ?><?php echo strlen($commande['adresse_livraison']) > 80 ? '…' : ''; ?>
                            </div>
                        </div>
                        <div class="mc-detail-row">
                            <label>Téléphone</label>
                            <div class="value"><?php echo htmlspecialchars($commande['telephone_livraison']); ?></div>
                        </div>
                        <?php if (!empty($commande['date_livraison'])): ?>
                        <div class="mc-detail-row">
                            <label>Date de livraison</label>
                            <div class="value"><?php echo date('d/m/Y', strtotime($commande['date_livraison'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mc-commande-card__actions commande-actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="btn-view-categories btn-view-commande">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits reçus
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>

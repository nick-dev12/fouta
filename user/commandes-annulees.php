<?php
/**
 * Page de liste des commandes annulées par l'utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les commandes annulées de l'utilisateur
require_once __DIR__ . '/../models/model_commandes.php';

// Traitement de la recommandation (ajouter les produits au panier)
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recommander'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        // Vérifier que la commande est annulée et appartient à l'utilisateur
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);

        if ($commande && $commande['statut'] === 'annulee') {
            // Récupérer les produits de la commande
            require_once __DIR__ . '/../models/model_panier.php';
            $produits_commande = get_commande_produits($commande_id);

            if (!empty($produits_commande)) {
                $added_count = 0;
                foreach ($produits_commande as $produit) {
                    // Vérifier si le produit existe encore et est actif
                    require_once __DIR__ . '/../models/model_produits.php';
                    $produit_info = get_produit_by_id($produit['produit_id']);

                    if ($produit_info && $produit_info['statut'] === 'actif' && $produit_info['stock'] > 0) {
                        // Vérifier si le produit est déjà dans le panier
                        $panier_existant = is_in_panier($_SESSION['user_id'], $produit['produit_id']);
                        if ($panier_existant) {
                            // Mettre à jour la quantité
                            $new_quantite = min($panier_existant['quantite'] + $produit['quantite'], $produit_info['stock']);
                            if (update_panier_quantite($panier_existant['id'], $new_quantite)) {
                                $added_count++;
                            }
                        } else {
                            // Ajouter au panier
                            $quantite = min($produit['quantite'], $produit_info['stock']);
                            if (add_to_panier($_SESSION['user_id'], $produit['produit_id'], $quantite)) {
                                $added_count++;
                            }
                        }
                    }
                }

                if ($added_count > 0) {
                    header('Location: /panier.php?recommande=1&count=' . $added_count);
                    exit;
                } else {
                    $error_message = 'Aucun produit disponible à recommander.';
                }
            } else {
                $error_message = 'Aucun produit trouvé dans cette commande.';
            }
        } else {
            $error_message = 'Cette commande ne peut pas être recommandée.';
        }
    }
}

// Récupérer toutes les commandes de l'utilisateur
$commandes = get_commandes_by_user($_SESSION['user_id']);

// Filtrer pour afficher uniquement les commandes annulées
$commandes_annulees = array_filter($commandes, function ($commande) {
    return $commande['statut'] === 'annulee';
});
$nb_annulees = count($commandes_annulees);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Commandes annulées — FOUTA POIDS LOURDS</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-commandes-annulees.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-commandes-annulees">
    <?php include 'includes/user_nav.php'; ?>

    <div class="mc-orders">
        <header class="mc-orders-hero">
            <div class="mc-orders-hero-text">
                <p class="mc-eyebrow">Historique</p>
                <h1>
                    <span class="mc-hero-icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
                    Commandes annulées
                </h1>
                <p class="mc-orders-lead">Retrouvez ici les commandes que vous avez annulées. Vous pouvez consulter le
                    détail des articles ou les remettre au panier si les produits sont encore disponibles.</p>
            </div>
            <div class="mc-orders-stats">
                <div class="mc-stat-pill">
                    <i class="fas fa-circle-xmark" aria-hidden="true"></i>
                    <div>
                        <strong><?php echo (int) $nb_annulees; ?></strong>
                        <span>Annulée<?php echo $nb_annulees > 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            </div>
        </header>

        <section class="mc-continue-banner" aria-label="Navigation rapide">
            <div class="mc-continue-inner">
                <div class="mc-continue-icon" aria-hidden="true">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="mc-continue-text">
                    <strong>Vos commandes en cours</strong>
                    <p>Passez à vos commandes actives ou continuez vos achats sur la boutique.</p>
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
            <?php if ($success_message): ?>
            <div class="mc-alert mc-alert--success" role="status">
                <i class="fas fa-circle-check" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="mc-alert mc-alert--error" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <div class="mc-section-head">
                <h2>
                    <span class="mc-section-icon" aria-hidden="true"><i class="fas fa-list-ul"></i></span>
                    Liste (<?php echo $nb_annulees; ?>)
                </h2>
            </div>

            <?php if (empty($commandes_annulees)): ?>
            <div class="mc-empty mc-empty--neutral">
                <div class="mc-empty-icon" aria-hidden="true"><i class="fas fa-inbox"></i></div>
                <p>Aucune commande annulée. Toutes vos commandes suivies apparaissent dans « Mes commandes ».</p>
                <a href="mes-commandes.php" class="btn-primary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Voir mes commandes
                </a>
            </div>
            <?php else: ?>
            <div class="mc-commandes-grid">
                <?php foreach ($commandes_annulees as $commande): ?>
                <article class="mc-commande-card mc-commande-card--cancelled">
                    <div class="mc-commande-card__top">
                        <div>
                            <h3 class="mc-commande-card__ref">Commande #<?php echo htmlspecialchars($commande['numero_commande']); ?></h3>
                            <p class="mc-commande-card__date">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="commande-statut statut-annulee mc-badge">Annulée</span>
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
                    </div>

                    <div class="mc-commande-card__actions commande-actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="btn-view-categories btn-view-commande">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits
                        </a>

                        <form method="post" action="">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit" name="recommander" class="btn-recommander">
                                <i class="fas fa-rotate-right" aria-hidden="true"></i> Recommander
                            </button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>

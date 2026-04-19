<?php
/**
 * Page de liste des commandes utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les commandes de l'utilisateur
require_once __DIR__ . '/../models/model_commandes.php';

// Traitement de la confirmation de livraison
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_livraison'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);
        if ($commande && $commande['statut'] === 'livraison_en_cours') {
            require_once __DIR__ . '/../models/model_commandes_admin.php';
            if (update_commande_statut($commande_id, 'paye')) {
                $success_message = 'Colis reçu confirmé avec succès !';
                header('Location: mes-commandes.php?livraison_confirmee=1');
                exit;
            }
        }
        if (empty($success_message)) {
            $error_message = 'Une erreur est survenue lors de la confirmation de la réception du colis.';
        }
    }
}

// Traitement de l'annulation de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_commande'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        // Vérifier que la commande peut être annulée (pas déjà livrée ou annulée)
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);

        if ($commande && $commande['statut'] !== 'livree' && $commande['statut'] !== 'annulee') {
            if (update_commande_statut_user($commande_id, $_SESSION['user_id'], 'annulee')) {
                $success_message = 'Commande annulée avec succès !';
                // Recharger les commandes pour afficher le nouveau statut
                header('Location: mes-commandes.php?commande_annulee=1');
                exit;
            } else {
                $error_message = 'Une erreur est survenue lors de l\'annulation de la commande.';
            }
        } else {
            $error_message = 'Cette commande ne peut pas être annulée.';
        }
    }
}

// Traitement de la recommandation (ajouter les produits au panier)
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
                require_once __DIR__ . '/../models/model_variantes.php';
                $added_count = 0;
                foreach ($produits_commande as $produit) {
                    require_once __DIR__ . '/../models/model_produits.php';
                    $produit_info = get_produit_by_id($produit['produit_id']);

                    if ($produit_info && $produit_info['statut'] === 'actif' && $produit_info['stock'] > 0) {
                        $quantite = min($produit['quantite'], $produit_info['stock']);
                        $variante_id = !empty($produit['variante_id']) ? (int) $produit['variante_id'] : null;
                        $variante_nom = !empty($produit['variante_nom']) ? trim($produit['variante_nom']) : null;
                        $variante_image = null;
                        if ($variante_id) {
                            $var = get_variante_by_id($variante_id);
                            $variante_image = $var && !empty($var['image']) ? $var['image'] : null;
                        }
                        $surcout_poids = isset($produit['surcout_poids']) ? (float) $produit['surcout_poids'] : 0;
                        $surcout_taille = isset($produit['surcout_taille']) ? (float) $produit['surcout_taille'] : 0;
                        $prix_unitaire = isset($produit['prix_unitaire']) ? (float) $produit['prix_unitaire'] : null;

                        if (
                            add_to_panier(
                                $_SESSION['user_id'],
                                $produit['produit_id'],
                                $quantite,
                                $produit['couleur'] ?? null,
                                $produit['poids'] ?? null,
                                $produit['taille'] ?? null,
                                $variante_id,
                                $variante_nom,
                                $variante_image,
                                $surcout_poids,
                                $surcout_taille,
                                $prix_unitaire
                            )
                        ) {
                            $added_count++;
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

// Message de succès après création de commande
if (isset($_GET['success']) && $_GET['success'] == '1' && !empty($_GET['numeros'])) {
    $nums = array_filter(array_map('trim', explode(',', (string) $_GET['numeros'])));
    if (count($nums) > 1) {
        $success_message = 'Vos commandes ont été créées avec succès : ' . implode(', ', array_map('htmlspecialchars', $nums)) . '.';
    } elseif (count($nums) === 1) {
        $success_message = 'Votre commande #' . htmlspecialchars($nums[0]) . ' a été créée avec succès !';
    }
} elseif (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['numero'])) {
    $success_message = 'Votre commande #' . htmlspecialchars($_GET['numero']) . ' a été créée avec succès !';
}

// Message de succès après confirmation de livraison
if (isset($_GET['livraison_confirmee']) && $_GET['livraison_confirmee'] == '1') {
    $success_message = 'Colis reçu confirmé avec succès !';
}

// Message de succès après annulation de commande
if (isset($_GET['commande_annulee']) && $_GET['commande_annulee'] == '1') {
    $success_message = 'Commande annulée avec succès !';
}

$commandes = get_commandes_by_user($_SESSION['user_id']);

// Filtrer pour exclure les commandes avec le statut "livree", "paye" et "annulee"
$commandes_actives = array_filter($commandes, function ($commande) {
    return $commande['statut'] !== 'livree' && $commande['statut'] !== 'paye' && $commande['statut'] !== 'annulee';
});

$nb_commandes_actives = count($commandes_actives);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mes commandes — COLObanes</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-mes-commandes">
    <?php include 'includes/user_nav.php'; ?>

    <div class="mc-orders">
        <header class="mc-orders-hero">
            <div class="mc-orders-hero__inner">
                <div class="mc-orders-hero__top">
                    <div class="mc-orders-hero__intro">
                        <p class="mc-eyebrow">Suivi en temps réel</p>
                        <h1 id="mc-orders-heading">
                            <span class="mc-hero-icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                            <span class="mc-orders-hero__title-text">Mes commandes</span>
                        </h1>
                    </div>
                    <div class="mc-orders-hero__metrics" aria-labelledby="mc-orders-heading">
                        <div class="mc-stat-pill mc-stat-pill--compact">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <div class="mc-stat-pill__text">
                                <strong><?php echo (int) $nb_commandes_actives; ?></strong>
                                <span>Actives</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="mc-orders-lead">Suivez vos commandes en cours et repassez commande depuis le catalogue.</p>
            </div>
        </header>

        <section class="mc-continue-banner" aria-label="Poursuivre vos achats">
            <div class="mc-continue-inner">
                <div class="mc-continue-icon" aria-hidden="true">
                    <i class="fas fa-store"></i>
                </div>
                <div class="mc-continue-text">
                    <strong>Continuer vos achats</strong>
                    <p>Retournez à l’accueil ou parcourez le catalogue pour compléter vos courses.</p>
                </div>
                <div class="mc-continue-actions">
                    <a href="/index.php" class="mc-btn mc-btn--primary">
                        <i class="fas fa-home" aria-hidden="true"></i>
                        Accueil
                    </a>
                    <a href="/produits.php" class="mc-btn mc-btn--secondary">
                        <i class="fas fa-shopping-basket" aria-hidden="true"></i>
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
                    <span class="mc-section-icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
                    Commandes actives (<?php echo $nb_commandes_actives; ?>)
                </h2>
                <div class="mc-section-actions">
                    <a href="commande-categorie.php" class="mc-btn-outline">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        Voir par catégorie
                    </a>
                </div>
            </div>

            <?php if (empty($commandes_actives)): ?>
            <div class="mc-empty">
                <div class="mc-empty-icon" aria-hidden="true"><i class="fas fa-box-open"></i></div>
                <p>Aucune commande active pour le moment. Parcourez votre catalogue et passez votre première commande.</p>
                <a href="/produits.php" class="btn-primary">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    Découvrir les produits
                </a>
            </div>
            <?php else: ?>
            <div class="mc-commandes-grid">
                <?php foreach ($commandes_actives as $commande): ?>
                <article class="mc-commande-card">
                    <div class="mc-commande-card__top">
                        <div>
                            <h3 class="mc-commande-card__ref">Commande #<?php echo htmlspecialchars($commande['numero_commande']); ?></h3>
                            <p class="mc-commande-card__date">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="commande-statut statut-<?php echo htmlspecialchars($commande['statut']); ?> mc-badge">
                            <?php
                            $statut_display = ucfirst(str_replace('_', ' ', $commande['statut']));
                            if ($commande['statut'] == 'livree' || $commande['statut'] == 'paye') {
                                $statut_display = 'Reçu';
                            }
                            if ($commande['statut'] == 'annulee') {
                                $statut_display = 'Annulée';
                            }
                            echo htmlspecialchars($statut_display);
                            ?>
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
                        <?php if ($commande['date_livraison']): ?>
                        <div class="mc-detail-row">
                            <label>Livraison</label>
                            <div class="value"><?php echo date('d/m/Y', strtotime($commande['date_livraison'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mc-commande-card__actions commande-actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="btn-view-categories btn-view-commande">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits
                        </a>

                        <?php if ($commande['statut'] == 'livraison_en_cours'): ?>
                        <form method="post" action="">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit" name="confirmer_livraison" class="btn-confirmer-livraison"
                                onclick="return confirm('Avez-vous bien reçu votre colis ?');">
                                <i class="fas fa-check-circle" aria-hidden="true"></i> Colis reçu
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php
                        $can_cancel = in_array($commande['statut'], ['en_attente', 'confirmee', 'prise_en_charge', 'en_preparation']);
                        if ($can_cancel):
                            ?>
                        <form method="post" action="">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit" name="annuler_commande" class="btn-annuler-commande"
                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.');">
                                <i class="fas fa-times-circle" aria-hidden="true"></i> Annuler la commande
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
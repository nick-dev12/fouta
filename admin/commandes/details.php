<?php
/**
 * Page de détails d'une commande (Admin)
 * Programmation procédurale uniquement
 */

session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Récupérer l'ID de la commande
$commande_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($commande_id <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../includes/require_access.php';

// Récupérer la commande et ses produits
require_once __DIR__ . '/../../models/model_commandes_admin.php';
require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../models/model_factures.php';
require_once __DIR__ . '/../../includes/format_commande_options.php';
$commande = get_commande_by_id($commande_id);
$produits = get_produits_by_commande($commande_id);
$produits = is_array($produits) ? $produits : [];
$facture = get_facture_by_commande($commande_id);

if (!$commande) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../includes/admin_route_access.php';
$vf_ck = admin_vendeur_filter_id();
if ($vf_ck !== null && (int) ($commande['vendeur_id'] ?? 0) !== $vf_ck) {
    header('Location: index.php');
    exit;
}

$statut_raw = (string) ($commande['statut'] ?? '');
$statut_safe_class = htmlspecialchars($statut_raw, ENT_QUOTES, 'UTF-8');
$statut_display = ucfirst(str_replace('_', ' ', $statut_raw));
if ($statut_raw === 'annulee') {
    $statut_display = 'Annulée';
} elseif ($statut_raw === 'paye') {
    $statut_display = 'Payée';
}

// Vérifier si la commande est annulée ou livrée (pas de modification possible)
$is_annulee = $commande['statut'] === 'annulee';
$is_livree = $commande['statut'] === 'livree';
$is_paye = $commande['statut'] === 'paye';

// Traiter les actions de statut (uniquement si la commande n'est pas annulée)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_annulee) {
    $statut_mis_a_jour = null;

    $admin_traitant = (int) ($_SESSION['admin_id'] ?? 0);

    if (isset($_POST['prendre_en_charge'])) {
        if (update_commande_statut($commande_id, 'prise_en_charge', $admin_traitant)) {
            $statut_mis_a_jour = 'prise_en_charge';
        }
    } elseif (isset($_POST['expedier'])) {
        if (update_commande_statut($commande_id, 'livraison_en_cours', $admin_traitant)) {
            $statut_mis_a_jour = 'livraison_en_cours';
        }
    } elseif (isset($_POST['changer_statut'])) {
        $nouveau_statut = $_POST['statut'] ?? '';
        if (in_array($nouveau_statut, ['en_attente', 'prise_en_charge', 'en_preparation', 'livraison_en_cours', 'paye', 'annulee'])) {
            if (update_commande_statut($commande_id, $nouveau_statut, $admin_traitant)) {
                $statut_mis_a_jour = $nouveau_statut;
            } else {
                $_SESSION['error_message'] = 'Impossible de mettre à jour le statut. Vérifiez que la migration "add_statut_paye_commandes" a été exécutée et que la commande contient des produits.';
            }
        }
    }

    if ($statut_mis_a_jour !== null) {
        $user_email = trim($commande['user_email'] ?? '');
        if (!empty($user_email)) {
            require_once __DIR__ . '/../../services/send_commande_notification.php';
            send_commande_status_notification(
                (int) ($commande['user_id'] ?? 0),
                $commande['numero_commande'],
                $statut_mis_a_jour,
                $user_email
            );
        }
        $_SESSION['success_message'] = !empty($user_email)
            ? 'Statut de la commande mis à jour avec succès. Une notification et un email ont été envoyés au client.'
            : 'Statut de la commande mis à jour avec succès.';
        header('Location: details.php?id=' . $commande_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Commande #<?php echo htmlspecialchars($commande['numero_commande'] ?? ''); ?> - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-commandes-details.css<?php echo asset_version_query(); ?>">
</head>

<body>
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container cmd-detail-page">
        <header class="cmd-detail-hero" aria-labelledby="cmd-detail-title">
            <div class="cmd-detail-hero__top">
                <a href="index.php" class="cmd-detail-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Liste des commandes</a>
                <span class="commande-statut statut-<?php echo $statut_safe_class; ?>"><?php echo htmlspecialchars($statut_display); ?></span>
            </div>
            <div class="cmd-detail-hero__main">
                <div>
                    <p class="cmd-detail-eyebrow">Fiche commande</p>
                    <h1 class="cmd-detail-hero__title" id="cmd-detail-title">Commande <span>#<?php echo htmlspecialchars($commande['numero_commande'] ?? ''); ?></span></h1>
                    <p class="cmd-detail-meta">
                        <span><i class="far fa-calendar-alt" aria-hidden="true"></i> <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></span>
                        <?php
                        $frais_liv = isset($commande['frais_livraison']) ? (float) $commande['frais_livraison'] : 0;
                        if ($frais_liv > 0):
                        ?>
                        <span><i class="fas fa-truck" aria-hidden="true"></i> Livraison <?php echo number_format($frais_liv, 0, ',', ' '); ?> FCFA</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="cmd-detail-hero__actions">
                    <?php if ($facture): ?>
                    <a href="facture.php?id=<?php echo (int) $facture['id']; ?>" class="btn-primary" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-file-invoice"></i> Voir la facture
                    </a>
                    <?php else: ?>
                    <a href="generer_facture.php?id=<?php echo (int) $commande_id; ?>" class="btn-primary">
                        <i class="fas fa-file-invoice"></i> Générer une facture
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-list" aria-hidden="true"></i> Retour liste
                    </a>
                </div>
            </div>
        </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($_SESSION['success_message'] ?? '');
            unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($_SESSION['error_message'] ?? '');
            unset($_SESSION['error_message']); ?></span>
        </div>
    <?php endif; ?>

    <div class="cmd-detail-grid">
        <div class="cmd-detail-panel">
            <h3 class="cmd-detail-panel__head"><i class="fas fa-user" aria-hidden="true"></i> Client</h3>
            <div class="detail-item">
                <label>Nom complet</label>
                <div class="value">
                    <?php echo htmlspecialchars(trim(($commande['user_prenom'] ?? '') . ' ' . ($commande['user_nom'] ?? ''))); ?>
                </div>
            </div>
            <div class="detail-item">
                <label>Email</label>
                <div class="value"><?php echo htmlspecialchars($commande['user_email'] ?? ''); ?></div>
            </div>
            <div class="detail-item">
                <label>Téléphone</label>
                <div class="value"><?php echo htmlspecialchars($commande['user_telephone'] ?? ''); ?></div>
            </div>
        </div>

        <div class="cmd-detail-panel">
            <h3 class="cmd-detail-panel__head"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Livraison</h3>
            <div class="detail-item">
                <label>Adresse</label>
                <div class="value"><?php echo nl2br(htmlspecialchars($commande['adresse_livraison'] ?? '')); ?></div>
            </div>
            <div class="detail-item">
                <label>Téléphone livraison</label>
                <div class="value"><?php echo htmlspecialchars($commande['telephone_livraison'] ?? ''); ?></div>
            </div>
            <?php if (!empty($commande['frais_livraison'])): ?>
                <div class="detail-item">
                    <label>Frais de livraison</label>
                    <div class="value"><?php echo number_format($commande['frais_livraison'], 0, ',', ' '); ?> FCFA</div>
                </div>
            <?php endif; ?>
            <div class="detail-item">
                <label>Date commande</label>
                <div class="value"><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></div>
            </div>
            <?php if ($commande['date_livraison']): ?>
                <div class="detail-item">
                    <label>Date livraison</label>
                    <div class="value"><?php echo date('d/m/Y à H:i', strtotime($commande['date_livraison'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <section class="cmd-products-section" aria-labelledby="cmd-products-heading">
        <div class="cmd-products-section__head">
            <h2 id="cmd-products-heading"><i class="fas fa-box-open" aria-hidden="true"></i> Lignes de commande</h2>
        </div>

        <div class="cmd-products-list">
            <?php if (empty($produits)): ?>
            <div class="cmd-products-empty">
                <i class="fas fa-box-open" aria-hidden="true"></i>
                <p>Aucune ligne produit enregistrée pour cette commande.</p>
            </div>
            <?php else: ?>
            <?php foreach ($produits as $produit): ?>
                <?php $img_src = !empty($produit['image_afficher']) ? $produit['image_afficher'] : ($produit['image_principale'] ?? ''); ?>
                <?php $nom_affichage = !empty($produit['variante_nom']) ? $produit['produit_nom'] . ' → ' . $produit['variante_nom'] : ($produit['produit_nom'] ?? ''); ?>
                <div class="cmd-produit-card">
                    <img src="/upload/<?php echo htmlspecialchars($img_src ?? ''); ?>"
                        alt="<?php echo htmlspecialchars($nom_affichage ?? ''); ?>"
                        onerror="this.src='/image/produit1.jpg'">
                    <div class="produit-info">
                        <h4><?php echo htmlspecialchars($nom_affichage ?? ''); ?></h4>
                        <div class="produit-info-lignes">
                            <div class="info-ligne">Quantité: <?php echo $produit['quantite']; ?></div>
                            <div class="info-ligne">Prix unitaire: <?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> FCFA</div>
                        </div>
                        <?php if (!empty($produit['couleur']) || !empty($produit['poids']) || !empty($produit['taille']) || !empty($produit['variante_nom']) || (!empty($produit['surcout_poids']) && $produit['surcout_poids'] > 0) || (!empty($produit['surcout_taille']) && $produit['surcout_taille'] > 0)): ?>
                        <div class="produit-options-detail">
                            <?php if (!empty($produit['variante_nom'])): ?>
                            <div class="option-detail option-variante">
                                <span class="option-label">Variante:</span>
                                <span class="option-value"><?php echo htmlspecialchars($produit['variante_nom'] ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($produit['couleur'])): ?>
                            <?php
                            $hex = trim($produit['couleur']);
                            $is_hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
                            $nom_couleur = format_couleur_commande($hex);
                            ?>
                            <div class="option-detail option-couleur">
                                <span class="option-label">Couleur:</span>
                                <?php if ($is_hex): ?>
                                <span class="couleur-swatch-large" style="background-color:<?php echo htmlspecialchars($hex ?? ''); ?>;" title="<?php echo htmlspecialchars($hex ?? ''); ?>"></span>
                                <?php endif; ?>
                                <span class="option-value"><?php echo htmlspecialchars($nom_couleur ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php 
                            $poids_raw = $produit['poids'] ?? '';
                            $taille_raw = $produit['taille'] ?? '';
                            $surcout_p = isset($produit['surcout_poids']) ? (float)$produit['surcout_poids'] : 0;
                            $surcout_t = isset($produit['surcout_taille']) ? (float)$produit['surcout_taille'] : 0;
                            $poids_lignes = parse_poids_taille_commande($poids_raw, $surcout_p);
                            $taille_lignes = parse_poids_taille_commande($taille_raw, $surcout_t);
                            $afficher_poids = !empty($poids_lignes);
                            $afficher_taille = !empty($taille_lignes);
                            ?>
                            <?php if ($afficher_poids): ?>
                            <div class="option-detail option-poids">
                                <span class="option-label">Poids:</span>
                                <div class="option-value options-lignes">
                                    <?php foreach ($poids_lignes as $opt): ?>
                                    <div class="option-ligne"><?php 
                                    echo htmlspecialchars($opt['v'] ?? ''); 
                                    if (($opt['s'] ?? 0) > 0) echo ' (poids +' . number_format($opt['s'], 0, ',', ' ') . ' FCFA)';
                                    ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($afficher_taille): ?>
                            <div class="option-detail option-taille">
                                <span class="option-label">Taille:</span>
                                <div class="option-value options-lignes">
                                    <?php foreach ($taille_lignes as $opt): ?>
                                    <div class="option-ligne"><?php 
                                    echo htmlspecialchars($opt['v'] ?? ''); 
                                    if (($opt['s'] ?? 0) > 0) echo ' (taille +' . number_format($opt['s'], 0, ',', ' ') . ' FCFA)';
                                    ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="produit-total">
                        <?php echo number_format($produit['prix_total'], 0, ',', ' '); ?> FCFA
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cmd-products-total">
                <?php
                $sous_total = array_sum(array_column($produits, 'prix_total'));
                $frais = isset($commande['frais_livraison']) ? (float) $commande['frais_livraison'] : 0;
                ?>
                <?php if ($frais > 0): ?>
                    <p class="cmd-products-total__line">Sous-total produits <strong><?php echo number_format($sous_total, 0, ',', ' '); ?> FCFA</strong></p>
                    <p class="cmd-products-total__line">Frais de livraison <strong><?php echo number_format($frais, 0, ',', ' '); ?> FCFA</strong></p>
                <?php endif; ?>
                <h3>Total commande <span class="total-value"><?php echo number_format($commande['montant_total'], 0, ',', ' '); ?> FCFA</span></h3>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="cmd-status-section" aria-labelledby="cmd-status-heading">
        <div class="cmd-status-section__head">
            <h2 id="cmd-status-heading"><i class="fas fa-sliders-h" aria-hidden="true"></i> Statut &amp; actions</h2>
        </div>
        <div class="cmd-status-body">

        <?php if ($is_annulee): ?>
            <div class="cmd-alert cmd-alert--danger" role="status">
                <h3><i class="fas fa-ban" aria-hidden="true"></i> Commande annulée</h3>
                <p>Cette commande a été annulée. Aucune modification n’est possible ; vous pouvez consulter les informations ci-dessus.</p>
            </div>
        <?php elseif ($is_livree): ?>
            <div class="cmd-alert cmd-alert--success" role="status">
                <h3><i class="fas fa-check-circle" aria-hidden="true"></i> Commande livrée</h3>
                <p>Le client a confirmé la réception. La commande est terminée.</p>
            </div>
        <?php elseif ($is_paye): ?>
            <div class="cmd-alert cmd-alert--success" role="status">
                <h3><i class="fas fa-money-bill-wave" aria-hidden="true"></i> Commande payée</h3>
                <p>Le paiement est enregistré et le stock a été mis à jour. La commande est terminée.</p>
            </div>
        <?php else: ?>
            <div class="statut-form cmd-statut-form">
                <div class="form-group">
                    <label>Statut actuel</label>
                    <div class="statut-current-wrap cmd-statut-current">
                        <span class="commande-statut statut-<?php echo $statut_safe_class; ?>">
                            <?php echo htmlspecialchars($statut_display); ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <?php if (in_array($commande['statut'], ['en_attente', 'confirmee'])): ?>
                        <form method="POST" action="">
                            <button type="submit" name="prendre_en_charge" class="btn-primary btn-prise-charge">
                                <i class="fas fa-hand-paper"></i> Prendre en charge la commande
                            </button>
                        </form>

                    <?php elseif ($commande['statut'] == 'prise_en_charge'): ?>
                        <form method="POST" action="">
                            <button type="submit" name="expedier" class="btn-primary btn-expedier">
                                <i class="fas fa-shipping-fast"></i> Mettre en livraison
                            </button>
                        </form>

                    <?php elseif ($commande['statut'] == 'livraison_en_cours'): ?>
                        <div class="alert-livraison">
                            <p><i class="fas fa-truck" aria-hidden="true"></i> Livraison en cours</p>
                            <p class="sub">Utilisez le menu ci-dessous pour passer à <strong>Payée</strong> une fois le règlement confirmé (mise à jour du stock).</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions-divider cmd-actions-divider">
                    <h3>Changement manuel de statut</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="statut">Nouveau statut</label>
                            <select id="statut" name="statut" required>
                                <option value="en_attente" <?php echo $commande['statut'] == 'en_attente' ? 'selected' : ''; ?>>En Attente</option>
                                <option value="prise_en_charge" <?php echo $commande['statut'] == 'prise_en_charge' ? 'selected' : ''; ?>>Prise en charge</option>
                                <option value="en_preparation" <?php echo $commande['statut'] == 'en_preparation' ? 'selected' : ''; ?>>En Préparation</option>
                                <option value="livraison_en_cours" <?php echo $commande['statut'] == 'livraison_en_cours' ? 'selected' : ''; ?>>Livraison en cours</option>
                                <option value="paye" <?php echo $commande['statut'] == 'paye' ? 'selected' : ''; ?>>Payée (décrémente le stock)</option>
                                <option value="annulee" <?php echo $commande['statut'] == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <?php if ($commande['notes']): ?>
                            <div class="form-group">
                                <label>Notes</label>
                                <div class="notes-box">
                                    <?php echo nl2br(htmlspecialchars($commande['notes'] ?? '')); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="submit" name="changer_statut" class="btn-primary">
                            <i class="fas fa-save"></i> Mettre à jour le statut
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </section>

    </div>

    <?php include '../includes/footer.php'; ?>
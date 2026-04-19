<?php
/**
 * Page d'accueil du tableau de bord administrateur
 * Programmation procédurale uniquement
 */

session_start();

// Vérifier si l'admin est connecté, sinon rediriger vers la page de connexion
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/require_access.php';

$dash_show_compta = admin_route_is_allowed($_SESSION['admin_role'] ?? 'admin', 'comptabilite/index.php');

require_once __DIR__ . '/../models/model_commandes_admin.php';
require_once __DIR__ . '/../models/model_commandes_personnalisees.php';
require_once __DIR__ . '/../models/model_produits.php';
require_once __DIR__ . '/../models/model_categories.php';
require_once __DIR__ . '/../includes/admin_route_access.php';

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$add_produit_error_message = '';
$add_produit_post_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_add_produit'])) {
    require_once __DIR__ . '/../controllers/controller_produits.php';
    $add_result = process_add_produit();
    if (!empty($add_result['success'])) {
        $_SESSION['success_message'] = $add_result['message'];
        header('Location: dashboard.php');
        exit;
    }
    $add_produit_error_message = $add_result['message'] ?? 'Erreur lors de l’ajout.';
    $add_produit_post_error = true;
}

$__role_dash = admin_normalize_role_for_route($_SESSION['admin_role'] ?? 'admin');
$fap_use_category_hierarchy = categories_hierarchy_enabled() && ($__role_dash === 'vendeur');
$vcat_prefill_sub = 0;
$vcat_prefill_generale = 0;
$vendeur_genre_ids_prefill = [];
$open_add_modal = isset($_GET['open_add']) && $_GET['open_add'] === '1';
$categorie_id_prefill_modal = isset($_GET['prefill_categorie']) ? (int) $_GET['prefill_categorie'] : 0;
if ($fap_use_category_hierarchy && $categorie_id_prefill_modal > 0) {
    $cp = get_categorie_by_id($categorie_id_prefill_modal);
    if ($cp && function_exists('categorie_est_utilisable_par_vendeur')
        && categorie_est_utilisable_par_vendeur((int) $cp['id'], (int) $_SESSION['admin_id'])) {
        $vcat_prefill_sub = (int) $cp['id'];
        if (function_exists('categories_has_categorie_generale_id_column') && categories_has_categorie_generale_id_column()) {
            $vcat_prefill_generale = (int) ($cp['categorie_generale_id'] ?? 0);
        }
    }
}
$categorie_id_prefill = $categorie_id_prefill_modal;

$vf_dash = admin_vendeur_filter_id();
$recherche = trim($_GET['recherche'] ?? '');
$categorie_id = isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : 0;
$categories = admin_categories_list_for_session();
$produits = get_all_produits(null, $vf_dash);

if (!empty($produits)) {
    $produits = array_values(array_filter($produits, function ($produit) use ($recherche, $categorie_id) {
        if ($categorie_id > 0 && (int) ($produit['categorie_id'] ?? 0) !== $categorie_id) {
            return false;
        }

        if ($recherche === '') {
            return true;
        }

        $needle = function_exists('mb_strtolower') ? mb_strtolower($recherche) : strtolower($recherche);
        $haystacks = [
            $produit['nom'] ?? '',
            $produit['description'] ?? '',
            $produit['categorie_nom'] ?? '',
            $produit['statut'] ?? ''
        ];

        foreach ($haystacks as $value) {
            $value = function_exists('mb_strtolower') ? mb_strtolower((string) $value) : strtolower((string) $value);
            if (strpos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }));
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Administration COLObanes</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        .admin-filters-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: end;
            margin-bottom: 20px;
            padding: 16px;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 12px;
        }

        .admin-filter-field {
            flex: 1 1 220px;
        }

        .admin-filter-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #6b2f20;
        }

        .admin-filter-field input,
        .admin-filter-field select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            background: #fff;
        }

        .admin-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-filter-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid #d9d9d9;
            color: #6b2f20;
            background: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        .produit-card-linkable {
            cursor: pointer;
        }

        .produit-card-linkable:hover .produit-card-nom {
            color: #c26638;
        }

        /* Modal plein écran — ajout produit (même principe que produits/index.php) */
        .adm-modal-add-produit[hidden] {
            display: none !important;
        }
        .adm-modal-add-produit {
            position: fixed;
            inset: 0;
            z-index: 9990;
            display: flex;
            flex-direction: column;
            background: rgba(13, 13, 13, 0.52);
            backdrop-filter: blur(6px);
        }
        .adm-modal-add-produit-inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            margin: 0;
            max-width: none;
            width: 100%;
            align-self: stretch;
            background: linear-gradient(165deg, var(--fond-secondaire, #fafafa) 0%, var(--blanc, #fff) 42%, rgba(53, 100, 166, 0.04) 100%);
            border-radius: 0;
            box-shadow: none;
            border: none;
            border-top: 3px solid var(--couleur-dominante, #3564a6);
            overflow: hidden;
        }
        .adm-modal-add-head {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 22px;
            background: var(--blanc, #fff);
            border-bottom: 1px solid var(--glass-border, rgba(0, 0, 0, 0.08));
        }
        .adm-modal-add-head h2 {
            margin: 0;
            font-size: 1.28rem;
            font-family: var(--font-titres, inherit);
            color: var(--titres, #0d0d0d);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .adm-modal-add-head h2 i {
            color: var(--couleur-dominante, #3564a6);
        }
        .adm-modal-add-head-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .adm-modal-add-close {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 12px;
            background: rgba(53, 100, 166, 0.1);
            color: var(--couleur-dominante, #3564a6);
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .adm-modal-add-close:hover {
            background: var(--couleur-dominante, #3564a6);
            color: var(--texte-clair, #fff);
        }
        .adm-modal-add-body {
            flex: 1;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            padding: 22px 24px 40px;
        }
        .adm-modal-add-body .form-add-container {
            max-width: 1280px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <!-- Barre de navigation verticale -->

    <!-- Contenu principal -->
    <div class="contents-container">
        <header class="dashboard-page-header" aria-label="En-tête du tableau de bord">
            <div class="dashboard-page-header__intro">
                <p class="dashboard-page-header__eyebrow">Espace administration</p>
                <h1 class="dashboard-page-header__title">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <span>Tableau de bord</span>
                </h1>
                <p class="dashboard-page-header__lead">
                    Suivez les commandes, gérez le catalogue et accédez en un geste aux réglages courants.
                </p>
            </div>
            <div class="dashboard-page-header__toolbar" role="group" aria-label="Actions rapides">
                <button type="button" id="btn-install-pwa" class="dash-tool-btn dash-tool-btn--ghost"
                    title="Installer l'application COLObanes sur cet appareil" style="display: none;">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    <span>Installer l’appli</span>
                </button>
                <button type="button" id="btn-enable-notifications" class="dash-tool-btn dash-tool-btn--outline"
                    title="Recevoir des notifications push pour les nouvelles commandes">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span>Notifications</span>
                </button>
                <?php if ($dash_show_compta): ?>
                <a href="comptabilite/index.php" class="dash-tool-btn dash-tool-btn--outline"
                    title="Comptabilité">
                    <i class="fas fa-calculator" aria-hidden="true"></i>
                    <span>Comptabilité</span>
                </a>
                <?php endif; ?>
                <button type="button" id="btnOpenAddProduitModalDash" class="dash-tool-btn dash-tool-btn--primary">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span>Nouveau produit</span>
                </button>
            </div>
        </header>

        <?php if (!empty($success_message)): ?>
            <div class="message success" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['notification_test_message'])) {
            $test_msg = $_SESSION['notification_test_message'];
            $test_type = $_SESSION['notification_test_type'] ?? 'success';
            unset($_SESSION['notification_test_message'], $_SESSION['notification_test_type']);
            ?>
            <div class="alert-box message-<?php echo htmlspecialchars($test_type); ?>" style="margin-bottom: 20px;">
                <p><i class="fas fa-<?php echo $test_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($test_msg); ?></p>
            </div>
            <?php
        }
        // Récupérer les statistiques des commandes (filtrées par boutique si vendeur)
        $total_commandes = count_commandes_by_statut(null, $vf_dash);
        $commandes_perso_en_attente = count_commandes_personnalisees_by_statut('en_attente', $vf_dash);
        $en_attente = count_commandes_by_statut('en_attente', $vf_dash);
        $prise_en_charge = count_commandes_by_statut('prise_en_charge', $vf_dash);
        $livraison_en_cours = count_commandes_by_statut('livraison_en_cours', $vf_dash);
        ?>

        <!-- Statistiques des commandes -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Commandes</h3>
                <div class="stat-value"><?php echo $total_commandes; ?></div>
            </div>
            <div class="stat-card stat-en-attente">
                <h3>En Attente</h3>
                <div class="stat-value"><?php echo $en_attente; ?></div>
            </div>
            <div class="stat-card stat-prise">
                <h3>Prise en charge</h3>
                <div class="stat-value"><?php echo $prise_en_charge; ?></div>
            </div>
            <div class="stat-card stat-livraison">
                <h3>Livraison en cours</h3>
                <div class="stat-value"><?php echo $livraison_en_cours; ?></div>
            </div>
        </div>

        <!-- Lien rapide vers les commandes -->
        <?php if ($en_attente > 0 || $prise_en_charge > 0): ?>
            <div class="alert-box">
                <p>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php if ($en_attente > 0): ?>
                        <?php echo $en_attente; ?> commande<?php echo $en_attente > 1 ? 's' : ''; ?> en attente de prise en
                        charge
                    <?php elseif ($prise_en_charge > 0): ?>
                        <?php echo $prise_en_charge; ?> commande<?php echo $prise_en_charge > 1 ? 's' : ''; ?>
                        prise<?php echo $prise_en_charge > 1 ? 's' : ''; ?> en charge,
                        prête<?php echo $prise_en_charge > 1 ? 's' : ''; ?> à être
                        expédiée<?php echo $prise_en_charge > 1 ? 's' : ''; ?>
                    <?php endif; ?>
                </p>
                <a href="commandes/index.php" class="btn-alert">
                    <i class="fas fa-arrow-right"></i> Gérer les commandes
                </a>
            </div>
        <?php endif; ?>

        <?php if ($commandes_perso_en_attente > 0): ?>
            <div class="alert-box" style="margin-top: 15px;">
                <p>
                    <i class="fas fa-palette"></i>
                    <?php echo $commandes_perso_en_attente; ?>
                    commande<?php echo $commandes_perso_en_attente > 1 ? 's' : ''; ?>
                    personnalisée<?php echo $commandes_perso_en_attente > 1 ? 's' : ''; ?> en attente
                </p>
                <a href="commandes-personnalisees/index.php" class="btn-alert">
                    <i class="fas fa-arrow-right"></i> Voir les commandes personnalisées
                </a>
            </div>
        <?php endif; ?>

        <!-- Section produits -->
        <section class="produits-section">
            <div class="section-title">
                <h2><i class="fas fa-box"></i> Mes Produits (<?php echo count($produits); ?>)</h2>
            </div>

            <form method="GET" action="" class="admin-filters-bar">
                <div class="admin-filter-field">
                    <label for="recherche">Recherche</label>
                    <input type="text" id="recherche" name="recherche" placeholder="Nom, description, statut..."
                        value="<?php echo htmlspecialchars($recherche); ?>">
                </div>
                <div class="admin-filter-field">
                    <label for="categorie_id">Catégorie</label>
                    <select id="categorie_id" name="categorie_id">
                        <option value="0">Toutes les catégories</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo (int) $categorie['id']; ?>"
                                <?php echo $categorie_id === (int) $categorie['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-filter-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="dashboard.php" class="btn-filter-reset">
                        <i class="fas fa-rotate-left"></i>&nbsp;Réinitialiser
                    </a>
                </div>
            </form>

            <?php if (empty($produits)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>Aucun produit enregistré pour le moment.</p>
                    <button type="button" class="btn-primary" id="btnOpenAddProduitModalDashEmpty">
                        <i class="fas fa-plus"></i> Ajouter le premier produit
                    </button>
                </div>
            <?php else: ?>
                <!-- Grille de produits -->
                <div class="produits-grid">
                    <?php foreach ($produits as $produit): ?>
                        <?php
                        $statut_class = 'statut-actif';
                        if ($produit['statut'] == 'inactif') {
                            $statut_class = 'statut-inactif';
                        } elseif ($produit['statut'] == 'rupture_stock') {
                            $statut_class = 'statut-rupture';
                        }
                        $statut_label = ucfirst(str_replace('_', ' ', $produit['statut']));
                        ?>
                        <div class="produit-card produit-card-linkable"
                            data-href="produits/modifier.php?id=<?php echo (int) $produit['id']; ?>">
                            <span class="statut-badge <?php echo $statut_class; ?>"><?php echo $statut_label; ?></span>
                            <img src="/upload/<?php echo htmlspecialchars($produit['image_principale']); ?>"
                                alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="produit-card-image"
                                onerror="this.src='/image/produit1.jpg'">
                            <div class="produit-card-body">
                                <h3 class="produit-card-nom"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                                <p class="produit-card-categorie">
                                    <?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Sans catégorie'); ?>
                                </p>
                                <p class="produit-card-prix">
                                    <?php echo number_format($produit['prix'], 0, ',', ' '); ?>
                                    <span class="prix-unite">FCFA</span>
                                    <?php if ($produit['prix_promotion']): ?>
                                        <span class="prix-promo">
                                            (Promo: <?php echo number_format($produit['prix_promotion'], 0, ',', ' '); ?> FCFA)
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p class="produit-card-stock">
                                    Stock: <span class="stock-value"><?php echo $produit['stock']; ?></span>

                                </p>
                                <div class="produit-card-actions">
                                    <a href="produits/modifier.php?id=<?php echo $produit['id']; ?>" class="btn-card btn-edit">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="produits/supprimer.php?id=<?php echo $produit['id']; ?>"
                                        class="btn-card btn-delete"
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php
    $add_produit_modal = true;
    $add_produit_form_action = 'dashboard.php';
    $modal_should_show = $add_produit_post_error || $open_add_modal;
    ?>
    <div id="modalAddProduitDash" class="adm-modal-add-produit" <?php echo $modal_should_show ? '' : 'hidden'; ?> aria-hidden="<?php echo $modal_should_show ? 'false' : 'true'; ?>">
        <div class="adm-modal-add-produit-inner">
            <div class="adm-modal-add-head">
                <h2><i class="fas fa-plus-circle"></i> Publier un produit</h2>
                <div class="adm-modal-add-head-actions">
                    <button type="button" class="adm-modal-add-close" id="btnCloseAddProduitModalDash" title="Fermer" aria-label="Fermer">&times;</button>
                </div>
            </div>
            <div class="adm-modal-add-body">
                <div class="form-add-container">
                    <?php require __DIR__ . '/produits/inc_form_ajouter_produit.php'; ?>
                </div>
            </div>
        </div>
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
        document.addEventListener('DOMContentLoaded', function () {
            var modalAdd = document.getElementById('modalAddProduitDash');
            var btnOpenDash = document.getElementById('btnOpenAddProduitModalDash');
            var btnOpenDashEmpty = document.getElementById('btnOpenAddProduitModalDashEmpty');
            var btnCloseDash = document.getElementById('btnCloseAddProduitModalDash');
            var btnCancelModal = document.getElementById('btn-fap-cancel-modal');
            function openAddModalDash() {
                if (!modalAdd) return;
                modalAdd.removeAttribute('hidden');
                modalAdd.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            function closeAddModalDash() {
                if (!modalAdd) return;
                modalAdd.setAttribute('hidden', '');
                modalAdd.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
            if (btnOpenDash) btnOpenDash.addEventListener('click', openAddModalDash);
            if (btnOpenDashEmpty) btnOpenDashEmpty.addEventListener('click', openAddModalDash);
            if (btnCloseDash) btnCloseDash.addEventListener('click', closeAddModalDash);
            if (btnCancelModal) btnCancelModal.addEventListener('click', closeAddModalDash);
            if (modalAdd) modalAdd.addEventListener('click', function (ev) {
                if (ev.target === modalAdd) closeAddModalDash();
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && modalAdd && !modalAdd.hasAttribute('hidden')) {
                    var vm = document.getElementById('fapVarianteModal');
                    if (vm && !vm.hidden) return;
                    closeAddModalDash();
                }
            });
            if (modalAdd && !modalAdd.hasAttribute('hidden')) {
                document.body.style.overflow = 'hidden';
            }
            try {
                var q = new URLSearchParams(window.location.search);
                if (q.get('open_add') === '1') {
                    openAddModalDash();
                    if (window.history && window.history.replaceState) {
                        var u = new URL(window.location.href);
                        u.searchParams.delete('open_add');
                        u.searchParams.delete('prefill_categorie');
                        window.history.replaceState({}, '', u.pathname + u.search + u.hash);
                    }
                }
            } catch (e) {}

            document.querySelectorAll('.produit-card-linkable').forEach(function(card) {
                card.addEventListener('click', function(event) {
                    if (event.target.closest('a, button, input, select, textarea, form')) {
                        return;
                    }
                    var href = card.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });

            var btn = document.getElementById('btn-enable-notifications');
            if (btn) {
                btn.addEventListener('click', function () {
                    if (typeof FirebaseNotifications !== 'undefined') {
                        FirebaseNotifications.enable('admin', this);
                    } else {
                        alert(
                            'Erreur: Les scripts de notification ne sont pas chargés. Vérifiez la console (F12).'
                        );
                    }
                });
            }

            var installBtn = document.getElementById('btn-install-pwa');
            var deferredPrompt;

            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                if (installBtn) installBtn.style.display = 'none';
            } else {
                window.addEventListener('beforeinstallprompt', function (e) {
                    e.preventDefault();
                    deferredPrompt = e;
                    if (installBtn) installBtn.style.display = 'inline-flex';
                });

                if (installBtn) {
                    installBtn.addEventListener('click', function () {
                        if (!deferredPrompt) {
                            alert(
                                'L\'installation n\'est pas disponible. Essayez depuis Chrome ou Edge en mode HTTPS.'
                            );
                            return;
                        }
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function (choiceResult) {
                            if (choiceResult.outcome === 'accepted') {
                                installBtn.style.display = 'none';
                            }
                            deferredPrompt = null;
                        });
                    });
                }
            }
        });
    </script>
    <?php include 'includes/footer.php'; ?>
<?php
/**
 * Inclusion de la barre de navigation admin
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/require_access.php';

// Déterminer le chemin de base selon le dossier actuel
$current_dir = dirname($_SERVER['PHP_SELF']);
$current_page = basename($_SERVER['PHP_SELF']);
$is_produits = strpos($current_dir, '/produits') !== false;
$is_categories = strpos($current_dir, '/categories') !== false;
$is_stock = strpos($current_dir, '/stock') !== false;
$is_slider = strpos($current_dir, '/slider') !== false;
$is_parametres = strpos($current_dir, '/parametres') !== false;
$is_commandes = strpos($current_dir, '/commandes') !== false;
$is_caisse = strpos($current_dir, '/caisse') !== false;
$is_caisse_encaisser = $is_caisse && ($current_page === 'encaisser-ticket.php');
$is_caisse_historique = $is_caisse && ($current_page === 'historique-encaissements.php');
$is_devis = strpos($current_dir, '/devis') !== false;
$is_users = strpos($current_dir, '/users') !== false;
$is_contacts = strpos($current_dir, '/contacts') !== false;
$is_zones_livraison = strpos($current_dir, '/zones-livraison') !== false;
$is_comptes = strpos($current_dir, '/comptes') !== false;
$is_commercial_hub = strpos($current_dir, '/commercial') !== false;
$is_comptabilite_hub = strpos($current_dir, '/comptabilite') !== false;
$admin_role = admin_normalize_role_for_route($_SESSION['admin_role'] ?? 'admin');
$is_vendeur_menu = ($admin_role === 'vendeur');

/** Titre marque dans la sidebar : nom commercial de la boutique si vendeur, sinon plateforme. */
$admin_sidebar_brand_title = 'COLObanes';
if ($is_vendeur_menu && !empty(
    $_SESSION['admin_id'])) {
    $bn_nav = trim((string) ($_SESSION['admin_boutique_nom'] ?? ''));
    if ($bn_nav === '') {
        require_once dirname(__DIR__, 2) . '/models/model_admin.php';
        $adm_nav = get_admin_by_id((int) $_SESSION['admin_id']);
        if ($adm_nav) {
            $bn_nav = trim((string) ($adm_nav['boutique_nom'] ?? ''));
            if ($bn_nav !== '') {
                $_SESSION['admin_boutique_nom'] = $bn_nav;
            }
        }
    }
    if ($bn_nav !== '') {
        $admin_sidebar_brand_title = $bn_nav;
    }
}

/** Badge navigation : commandes en cours (boutique vendeur uniquement) */
$nav_commandes_en_traitement = 0;
if ($is_vendeur_menu && !empty($_SESSION['admin_id'])) {
    require_once dirname(__DIR__, 2) . '/models/model_commandes_admin.php';
    $nav_commandes_en_traitement = count_commandes_en_traitement_vendeur((int) $_SESSION['admin_id']);
}

if ($is_produits || $is_categories || $is_stock || $is_slider || $is_parametres || $is_commandes || $is_caisse || $is_devis || $is_users || $is_contacts || $is_zones_livraison || $is_comptes || $is_commercial_hub || $is_comptabilite_hub) {
    $base_path = '../';
} else {
    $base_path = '';
}

?>
<!-- Bouton menu mobile -->
<button class="mobile-menu-toggle<?php echo $is_vendeur_menu ? ' admin-vendeur-nav-hide-under-1024' : ''; ?>" id="menuToggle" type="button" aria-label="Ouvrir le menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay pour mobile -->
<div class="sidebar-overlay<?php echo $is_vendeur_menu ? ' admin-vendeur-nav-hide-under-1024' : ''; ?>" id="sidebarOverlay"></div>

<script>
    (function () {
        function toggleAdminSidebar() {
            var sidebar = document.getElementById('adminSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            }
        }
        window.toggleSidebar = toggleAdminSidebar;
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('menuToggle');
            var overlay = document.getElementById('sidebarOverlay');
            if (btn) btn.addEventListener('click', toggleAdminSidebar);
            if (overlay) overlay.addEventListener('click', toggleAdminSidebar);
        });
    })();
</script>

<div class="admin-container<?php echo $is_vendeur_menu ? ' admin-shell--vendeur-dock' : ''; ?>">
    <!-- Barre de navigation verticale -->
    <aside class="admin-sidebar<?php echo $is_vendeur_menu ? ' admin-sidebar--vendeur' : ''; ?>" id="adminSidebar">
        <div class="sidebar-header">
            <i class="fas fa-store logo-icon"></i>
            <h2><?php echo htmlspecialchars($admin_sidebar_brand_title, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <nav class="sidebar-menu<?php echo $is_vendeur_menu ? ' sidebar-menu--rail' : ''; ?>"
            aria-label="Navigation administration">
            <?php if (in_array($admin_role, ['admin', 'plateforme', 'vendeur'], true)): ?>
            <a href="<?php echo $base_path; ?>dashboard.php"
                class="menu-item <?php echo ($current_page == 'dashboard.php' || ($is_vendeur_menu && $is_produits && $current_page == 'index.php')) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-home"></i></span>
                <span class="menu-item__text">Tableau de bord</span>
            </a>
            <?php if ($is_vendeur_menu): ?>
            <a href="<?php echo $base_path; ?>stock/index.php"
                class="menu-item <?php echo $is_stock ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                <span class="menu-item__text">Produits</span>
            </a>
            <?php endif; ?>
            <?php if (!$is_vendeur_menu): ?>
            <a href="<?php echo $base_path; ?>devis/index.php"
                class="menu-item <?php echo ($is_devis || $is_commercial_hub) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-handshake"></i></span>
                <span class="menu-item__text">Devis &amp; BL</span>
            </a>
            <?php endif; ?>
            <?php if (!$is_vendeur_menu): ?>
            <a href="<?php echo $base_path; ?>produits/index.php"
                class="menu-item <?php echo ($is_produits && $current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-box"></i></span>
                <span class="menu-item__text">Produits</span>
            </a>
            <?php endif; ?>
            <?php if (!$is_vendeur_menu): ?>
            <a href="<?php echo $base_path; ?>stock/index.php"
                class="menu-item <?php echo ($is_stock) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                <span class="menu-item__text">Stock</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>commandes/index.php"
                class="menu-item menu-item--has-badge <?php echo ($is_commandes && ($current_page == 'index.php' || $current_page == 'livrees.php' || $current_page == 'annulees.php' || $current_page == 'details.php' || $current_page == 'historique-ventes.php')) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                <span class="menu-item__text">Commandes</span>
                <?php if ($is_vendeur_menu && $nav_commandes_en_traitement > 0): ?>
                <span class="menu-item__badge" title="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement" aria-label="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement"><?php echo (int) $nav_commandes_en_traitement; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $base_path; ?>caisse/index.php"
                class="menu-item <?php echo ($is_caisse && !$is_caisse_encaisser) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                <span class="menu-item__text">Caisse</span>
            </a>
            <?php if (!$is_vendeur_menu): ?>
            <a href="<?php echo $base_path; ?>caisse/encaisser-ticket.php"
                class="menu-item <?php echo $is_caisse_encaisser ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                <span class="menu-item__text">Encaissement tickets</span>
            </a>
            <a href="<?php echo $base_path; ?>caisse/historique-encaissements.php"
                class="menu-item <?php echo $is_caisse_historique ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-history"></i></span>
                <span class="menu-item__text">Historique encaissements</span>
            </a>
            <a href="<?php echo $base_path; ?>contacts/index.php"
                class="menu-item <?php echo $is_contacts ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-address-book"></i></span>
                <span class="menu-item__text">Contacts</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>users/index.php"
                class="menu-item <?php echo $is_users ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
                <span class="menu-item__text">Clients</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo (
                    $current_page == 'parametres.php' ||
                    $current_page == 'parametres-boutique-vendeur.php' ||
                    strpos($current_dir, '/parametres') !== false ||
                    $is_zones_livraison ||
                    $is_comptes
                ) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php elseif ($admin_role === 'commercial'): ?>
            <a href="<?php echo $base_path; ?>devis/index.php"
                class="menu-item <?php echo ($is_devis || $is_commercial_hub) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-handshake"></i></span>
                <span class="menu-item__text">Devis &amp; BL</span>
            </a>
            <a href="<?php echo $base_path; ?>commandes/index.php"
                class="menu-item <?php echo ($is_commandes && ($current_page == 'index.php' || $current_page == 'livrees.php' || $current_page == 'annulees.php' || $current_page == 'details.php' || $current_page == 'historique-ventes.php')) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                <span class="menu-item__text">Commandes</span>
            </a>
            <a href="<?php echo $base_path; ?>caisse/index.php"
                class="menu-item <?php echo ($is_caisse && !$is_caisse_encaisser) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                <span class="menu-item__text">Caisse</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo ($current_page == 'parametres.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php elseif ($admin_role === 'caissier'): ?>
            <a href="<?php echo $base_path; ?>caisse/encaisser-ticket.php"
                class="menu-item <?php echo $is_caisse_encaisser ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
                <span class="menu-item__text">Encaissement tickets</span>
            </a>
            <a href="<?php echo $base_path; ?>caisse/historique-encaissements.php"
                class="menu-item <?php echo $is_caisse_historique ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-history"></i></span>
                <span class="menu-item__text">Historique encaissements</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo ($current_page == 'parametres.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php elseif ($admin_role === 'comptabilite'): ?>
            <a href="<?php echo $base_path; ?>comptabilite/index.php"
                class="menu-item <?php echo $is_comptabilite_hub ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-calculator"></i></span>
                <span class="menu-item__text">Comptabilité</span>
            </a>
            <a href="<?php echo $base_path; ?>commandes/historique-ventes.php"
                class="menu-item <?php echo ($is_commandes && $current_page === 'historique-ventes.php') ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                <span class="menu-item__text">Historique des ventes</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo ($current_page == 'parametres.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php elseif ($admin_role === 'rh'): ?>
            <a href="<?php echo $base_path; ?>contacts/index.php"
                class="menu-item <?php echo $is_contacts ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-address-book"></i></span>
                <span class="menu-item__text">Contacts</span>
            </a>
            <a href="<?php echo $base_path; ?>users/index.php"
                class="menu-item <?php echo $is_users ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
                <span class="menu-item__text">Clients</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo ($current_page == 'parametres.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php elseif ($admin_role === 'gestion_stock'): ?>
            <a href="<?php echo $base_path; ?>stock/index.php"
                class="menu-item <?php echo ($is_stock) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                <span class="menu-item__text">Stock</span>
            </a>
            <a href="<?php echo $base_path; ?>produits/index.php"
                class="menu-item <?php echo ($is_produits && $current_page == 'index.php') ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-box"></i></span>
                <span class="menu-item__text">Produits</span>
            </a>
            <a href="<?php echo $base_path; ?>parametres.php"
                class="menu-item <?php echo ($current_page == 'parametres.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>profil.php"
                class="menu-item <?php echo ($current_page === 'profil.php') ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                <span class="menu-item__text">Mon profil</span>
            </a>
            <a href="<?php echo $base_path; ?>logout.php" class="menu-item menu-item--logout">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                <span class="menu-item__text">Déconnexion</span>
            </a>
        </nav>

        <?php
        if ($is_vendeur_menu) {
            $vd_cmd_active_nav = (
                $is_commandes &&
                in_array(
                    $current_page,
                    ['index.php', 'livrees.php', 'annulees.php', 'details.php', 'historique-ventes.php'],
                    true
                )
            );
            $vd_param_sheet_active = (
                $current_page === 'parametres.php' ||
                $current_page === 'parametres-boutique-vendeur.php' ||
                strpos($current_dir, '/parametres') !== false ||
                $is_zones_livraison ||
                $is_comptes
            );
            $vd_profil_dock_act = ($current_page === 'profil.php');
            $vd_dashboard_dock_act = (($current_page === 'dashboard.php') || ($is_produits && $current_page === 'index.php'));
            $vd_stock_dock_act = $is_stock;
            $vd_caisse_dock_act = ($is_caisse && !$is_caisse_encaisser);
            $vd_clients_dock_act = $is_users;
            $vdock_menu_hint_sheet = (
                $vd_clients_dock_act ||
                $vd_caisse_dock_act ||
                $vd_param_sheet_active
            );
        }
        ?>
    </aside>

    <?php if ($is_vendeur_menu): ?>
    <!-- Dock vendeur : même structure DOM que la boutique (shop-bottom-dock → shop-dock-bar → shop-dock-primary) -->
    <div class="shop-bottom-dock" id="adminVendeurBottomDock" aria-label="Navigation vendeur rapide">
        <div class="shop-dock-bar" id="adminVendeurDockBar" aria-label="Navigation vendeur réduite" hidden>
            <nav class="shop-dock-primary shop-dock-primary--cols-5" aria-label="Raccourcis">
                <a href="<?php echo $base_path; ?>dashboard.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_dashboard_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-home"></i></span>
                    <span class="menu-item__text">Tableau de bord</span>
                </a>
                <a href="<?php echo $base_path; ?>stock/index.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_stock_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                    <span class="menu-item__text">Produits</span>
                </a>
                <a href="<?php echo $base_path; ?>commandes/index.php"
                    class="menu-item menu-item--dock-mini menu-item--has-badge<?php echo $vd_cmd_active_nav ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                    <span class="menu-item__text">Commandes</span>
                    <?php if ($nav_commandes_en_traitement > 0): ?>
                    <span class="menu-item__badge menu-item__badge--dock" title="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement" aria-label="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement"><?php echo (int) $nav_commandes_en_traitement; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $base_path; ?>profil.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_profil_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                    <span class="menu-item__text">Mon profil</span>
                </a>
                <button type="button"
                    id="adminVendeurDockMenuBtn"
                    aria-expanded="false"
                    aria-haspopup="dialog"
                    aria-controls="adminVendeurDockMenuPanel"
                    class="menu-item menu-item--dock-mini menu-item--dock-mini-btn<?php echo $vdock_menu_hint_sheet ? ' menu-item--dock-mini-btn--hint' : ''; ?>">
                    <span class="menu-item__icon menu-item__icon--hint-host" aria-hidden="true"><i class="fas fa-th"></i></span>
                    <span class="menu-item__text">Menu</span>
                </button>
            </nav>
        </div>
    </div>

    <div class="admin-vendeur-dock-menu-layer" id="adminVendeurDockMenuLayer" aria-hidden="true">
        <div class="admin-vendeur-dock-menu-backdrop" id="adminVendeurDockMenuBackdrop" role="presentation"></div>
        <div class="admin-vendeur-dock-menu-panel" id="adminVendeurDockMenuPanel" role="dialog" aria-modal="true"
            aria-labelledby="adminVendeurDockMenuTitle">
            <header class="admin-vendeur-dock-menu-panel-hd">
                <strong id="adminVendeurDockMenuTitle">Autres liens</strong>
                <button type="button" class="admin-vendeur-dock-menu-close" id="adminVendeurDockMenuClose"
                    aria-label="Fermer le menu">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <nav class="admin-vendeur-dock-menu-panel-nav" aria-label="Menu étendu">
                <a href="<?php echo $base_path; ?>users/index.php"
                    class="menu-item menu-item--dock-sheet-row<?php echo $vd_clients_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
                    <span class="menu-item__text">Clients</span>
                </a>
                <a href="<?php echo $base_path; ?>caisse/index.php"
                    class="menu-item menu-item--dock-sheet-row<?php echo $vd_caisse_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                    <span class="menu-item__text">Caisse</span>
                </a>
                <a href="<?php echo $base_path; ?>parametres.php"
                    class="menu-item menu-item--dock-sheet-row<?php echo $vd_param_sheet_active ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                    <span class="menu-item__text">Paramètres</span>
                </a>
                <div class="admin-vendeur-dock-menu-divider" role="presentation"></div>
                <a href="<?php echo $base_path; ?>logout.php"
                    class="menu-item menu-item--dock-sheet-row menu-item--dock-sheet-logout">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="menu-item__text">Déconnexion</span>
                </a>
            </nav>
        </div>
    </div>

    <style>
    /* ================================================================
       ADMIN VENDEUR DOCK — REDESIGN v2
       Barre navigation responsive mobile/tablette — fond dégradé sombre,
       icônes squircles colorées, état actif lumineux, panel sheet premium
       ================================================================ */

    /* Variables couleurs par onglet — palette site bleu/orange */
    #adminVendeurBottomDock {
        --vdock-c1: #3564a6;   /* Dashboard — bleu principal */
        --vdock-c2: #10b981;   /* Stock — vert */
        --vdock-c3: #FF6B35;   /* Commandes — orange site */
        --vdock-c4: #6366f1;   /* Profil — indigo */
        --vdock-c5: #3564a6;   /* Menu — bleu principal */
    }

    @media (max-width: 1024px) {

        /* ---- Dock bar — fond blanc nacré, liseré bleu du site ---- */
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-bar {
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(22px) saturate(1.5) !important;
            -webkit-backdrop-filter: blur(22px) saturate(1.5) !important;
            border-top: 2px solid rgba(53,100,166,0.14) !important;
            box-shadow: 0 -6px 28px rgba(53,100,166,0.12), 0 -1px 0 rgba(53,100,166,0.07) !important;
            padding: 8px 10px calc(env(safe-area-inset-bottom,0px) + 8px) !important;
            gap: 0 !important;
        }

        /* ---- Nav grid — fond transparent ---- */
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            padding: 0 !important;
            gap: 4px !important;
        }

        /* ---- Item de base ---- */
        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini {
            background: transparent !important;
            border: none !important;
            border-radius: 16px !important;
            padding: 9px 6px !important;
            min-height: auto !important;
            gap: 5px !important;
            color: var(--gris-moyen, #737373) !important;
            box-shadow: none !important;
            transition: background 0.2s, transform 0.15s !important;
        }

        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini:active {
            transform: scale(0.92) !important;
        }

        /* ---- Icône squircle ---- */
        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__icon {
            width: 42px !important;
            height: 42px !important;
            min-width: 42px !important;
            min-height: 42px !important;
            max-width: 42px !important;
            max-height: 42px !important;
            border-radius: 14px !important;
            font-size: 1.12rem !important;
            background: #f0f4fa !important;
            color: var(--gris-moyen, #737373) !important;
            border: none !important;
            transition: background 0.22s, color 0.22s, box-shadow 0.22s, transform 0.22s !important;
        }

        /* ---- Couleurs squircles par position ---- */
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(1) .menu-item__icon {
            background: rgba(53,100,166,0.1) !important;
            color: #3564a6 !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(2) .menu-item__icon {
            background: rgba(16,185,129,0.1) !important;
            color: #059669 !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(3) .menu-item__icon {
            background: rgba(255,107,53,0.1) !important;
            color: #FF6B35 !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(4) .menu-item__icon {
            background: rgba(99,102,241,0.1) !important;
            color: #6366f1 !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(5) .menu-item__icon {
            background: rgba(53,100,166,0.1) !important;
            color: #3564a6 !important;
        }

        /* ---- Texte label ---- */
        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__text {
            font-size: 0.62rem !important;
            font-weight: 600 !important;
            color: var(--gris-moyen, #737373) !important;
            margin-top: 0 !important;
            padding: 0 2px !important;
            letter-spacing: 0.01em !important;
            transition: color 0.2s !important;
        }

        /* ---- État ACTIF — tuile bleu pâle + icône pleine couleur ---- */
        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini.active {
            background: rgba(53,100,166,0.08) !important;
            border: none !important;
            box-shadow: none !important;
        }

        #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini.active .menu-item__text {
            color: var(--bleu-principal, #3564a6) !important;
            font-weight: 700 !important;
        }

        /* Squircle actif avec couleur pleine + glow */
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(1).active .menu-item__icon {
            background: var(--vdock-c1) !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(53,100,166,0.4) !important;
            transform: scale(1.08) !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(2).active .menu-item__icon {
            background: var(--vdock-c2) !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(16,185,129,0.4) !important;
            transform: scale(1.08) !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(3).active .menu-item__icon {
            background: var(--vdock-c3) !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(255,107,53,0.4) !important;
            transform: scale(1.08) !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(4).active .menu-item__icon {
            background: var(--vdock-c4) !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(99,102,241,0.4) !important;
            transform: scale(1.08) !important;
        }
        #adminVendeurBottomDock.shop-bottom-dock .shop-dock-primary > .menu-item:nth-child(5).active .menu-item__icon {
            background: var(--vdock-c5) !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(53,100,166,0.4) !important;
            transform: scale(1.08) !important;
        }

        /* ---- Badge commandes ---- */
        #adminVendeurBottomDock.shop-bottom-dock .menu-item__badge--dock {
            background: var(--orange, #FF6B35) !important;
            border: 2px solid #fff !important;
            font-size: 0.6rem !important;
            font-weight: 800 !important;
            min-width: 1.1rem !important;
            height: 1.1rem !important;
            line-height: 1.1rem !important;
            top: 4px !important;
            right: 8px !important;
            padding: 0 4px !important;
        }

        /* ---- Point hint sur bouton Menu ---- */
        #adminVendeurBottomDock.shop-bottom-dock
            button.menu-item.menu-item--dock-mini.menu-item--dock-mini-btn--hint
            .menu-item__icon--hint-host::after {
            background: var(--orange, #FF6B35) !important;
            border: 2px solid #fff !important;
            width: 8px !important;
            height: 8px !important;
            top: -3px !important;
            right: -3px !important;
        }

        /* ---- Mobile strict (< 600px) ---- */
        @media (max-width: 599px) {
            #adminVendeurBottomDock.shop-bottom-dock .shop-dock-bar {
                padding: 6px 6px calc(env(safe-area-inset-bottom,0px) + 6px) !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini {
                padding: 7px 4px !important;
                border-radius: 13px !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__icon {
                width: 36px !important;
                height: 36px !important;
                min-width: 36px !important;
                min-height: 36px !important;
                max-width: 36px !important;
                max-height: 36px !important;
                border-radius: 11px !important;
                font-size: 1rem !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__text {
                font-size: 0.56rem !important;
            }
        }

        /* ---- Tablette (600–1024px) ---- */
        @media (min-width: 600px) and (max-width: 1024px) {
            #adminVendeurBottomDock.shop-bottom-dock .shop-dock-bar {
                padding: 10px 20px calc(env(safe-area-inset-bottom,0px) + 10px) !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini {
                padding: 10px 10px !important;
                border-radius: 18px !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__icon {
                width: 48px !important;
                height: 48px !important;
                min-width: 48px !important;
                min-height: 48px !important;
                max-width: 48px !important;
                max-height: 48px !important;
                border-radius: 16px !important;
                font-size: 1.2rem !important;
            }
            #adminVendeurBottomDock.shop-bottom-dock .menu-item.menu-item--dock-mini .menu-item__text {
                font-size: 0.7rem !important;
            }
        }

        /* ================================================================
           PANEL SHEET "Menu" — redesign premium
           ================================================================ */

        .admin-vendeur-dock-menu-panel {
            background: #fff !important;
            border-radius: 28px 28px 0 0 !important;
            border: none !important;
            box-shadow: 0 -20px 60px rgba(10,15,40,0.22), 0 -1px 0 rgba(255,255,255,0.9) !important;
        }

        .admin-vendeur-dock-menu-panel-hd {
            background: linear-gradient(135deg, var(--bleu-fonce, #2d5690) 0%, var(--bleu-principal, #3564a6) 100%) !important;
            padding: 20px 20px 16px !important;
            border-radius: 28px 28px 0 0 !important;
        }

        .admin-vendeur-dock-menu-panel-hd strong {
            color: #fff !important;
            font-size: 1.02rem !important;
            font-weight: 800 !important;
            font-family: var(--font-titres, 'Poppins', sans-serif) !important;
        }

        .admin-vendeur-dock-menu-close {
            background: rgba(255,255,255,0.18) !important;
            color: #fff !important;
            border-radius: 12px !important;
            border: 1px solid rgba(255,255,255,0.22) !important;
            width: 38px !important;
            height: 38px !important;
        }

        .admin-vendeur-dock-menu-close:hover {
            background: rgba(255,255,255,0.3) !important;
        }

        /* Items du panel en grille 2 colonnes */
        .admin-vendeur-dock-menu-panel-nav {
            padding: 18px 16px !important;
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row {
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            gap: 9px !important;
            padding: 18px 12px !important;
            min-height: 92px !important;
            border-radius: 16px !important;
            border: 1.5px solid rgba(53,100,166,0.1) !important;
            background: #f8faff !important;
            text-decoration: none !important;
            transition: background 0.2s, border-color 0.2s, transform 0.15s !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row:hover {
            background: rgba(53,100,166,0.07) !important;
            border-color: rgba(53,100,166,0.22) !important;
            transform: translateY(-2px) !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row:active {
            transform: scale(0.96) !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row .menu-item__icon {
            width: 46px !important;
            height: 46px !important;
            border-radius: 14px !important;
            font-size: 1.1rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            border: none !important;
        }

        /* Couleurs icônes du panel */
        .admin-vendeur-dock-menu-panel-nav .menu-item:nth-child(1) .menu-item__icon { background: rgba(53,100,166,0.12) !important; color: #3564a6 !important; }
        .admin-vendeur-dock-menu-panel-nav .menu-item:nth-child(2) .menu-item__icon { background: rgba(249,115,22,0.12) !important; color: #f97316 !important; }
        .admin-vendeur-dock-menu-panel-nav .menu-item:nth-child(3) .menu-item__icon { background: rgba(16,185,129,0.12) !important; color: #10b981 !important; }
        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-logout .menu-item__icon {
            background: rgba(239,68,68,0.1) !important;
            color: #b91c1c !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row .menu-item__text {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: var(--titres, #0d0d0d) !important;
            white-space: nowrap !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-row.active {
            background: rgba(53,100,166,0.07) !important;
            border-color: rgba(53,100,166,0.25) !important;
        }

        .admin-vendeur-dock-menu-divider {
            grid-column: 1 / -1 !important;
            height: 1px !important;
            background: rgba(53,100,166,0.08) !important;
            margin: 2px 0 !important;
        }

        .admin-vendeur-dock-menu-panel-nav .menu-item.menu-item--dock-sheet-logout:hover {
            background: rgba(239,68,68,0.06) !important;
            border-color: rgba(239,68,68,0.18) !important;
        }

        /* Panel tablette : 3 colonnes */
        @media (min-width: 600px) and (max-width: 1024px) {
            .admin-vendeur-dock-menu-panel-nav {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

    } /* fin @media ≤ 1024px */

    </style>

    <script>
        (function () {
            function adminVdockQs() {
                return {
                    dockBar: document.getElementById('adminVendeurDockBar'),
                    menuLayer: document.getElementById('adminVendeurDockMenuLayer'),
                    backdrop: document.getElementById('adminVendeurDockMenuBackdrop'),
                    btn: document.getElementById('adminVendeurDockMenuBtn'),
                    closeBtn: document.getElementById('adminVendeurDockMenuClose'),
                    panel: document.getElementById('adminVendeurDockMenuPanel'),
                };
            }
            function isAdminVdockMq() {
                return window.matchMedia('(max-width: 1024px)').matches;
            }
            function openAdminVdockMenu() {
                var q = adminVdockQs();
                if (!q.menuLayer || !q.btn || !isAdminVdockMq()) return;
                q.menuLayer.classList.add('is-open');
                q.menuLayer.setAttribute('aria-hidden', 'false');
                q.btn.setAttribute('aria-expanded', 'true');
                document.documentElement.style.overflow = 'hidden';
            }
            function shutAdminVdockMenu() {
                var q = adminVdockQs();
                if (!q.menuLayer || !q.btn) return;
                q.menuLayer.classList.remove('is-open');
                q.menuLayer.setAttribute('aria-hidden', 'true');
                q.btn.setAttribute('aria-expanded', 'false');
                document.documentElement.style.overflow = '';
            }
            function toggleAdminVdockBarVisibility() {
                var q = adminVdockQs();
                if (!q.dockBar) return;
                if (isAdminVdockMq()) {
                    q.dockBar.removeAttribute('hidden');
                } else {
                    q.dockBar.setAttribute('hidden', '');
                    shutAdminVdockMenu();
                }
            }
            document.addEventListener('DOMContentLoaded', function () {
                toggleAdminVdockBarVisibility();
                var q = adminVdockQs();
                if (q.btn) q.btn.addEventListener('click', openAdminVdockMenu);
                if (q.backdrop) q.backdrop.addEventListener('click', shutAdminVdockMenu);
                if (q.closeBtn) q.closeBtn.addEventListener('click', shutAdminVdockMenu);
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') shutAdminVdockMenu();
                });
                window.addEventListener('resize', function () {
                    toggleAdminVdockBarVisibility();
                });
            });
        })();
    </script>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="admin-content" id="adminContent">
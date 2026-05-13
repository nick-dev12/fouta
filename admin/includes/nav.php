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
                <span class="menu-item__text">Stock</span>
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
                    $current_page == 'profil.php' ||
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
                class="menu-item <?php echo ($current_page == 'parametres.php' || $current_page == 'profil.php' || $is_comptes) ? 'active' : ''; ?>">
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
                class="menu-item <?php echo ($current_page == 'parametres.php' || $current_page == 'profil.php' || $is_comptes) ? 'active' : ''; ?>">
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
                class="menu-item <?php echo ($current_page == 'parametres.php' || $current_page == 'profil.php' || $is_comptes) ? 'active' : ''; ?>">
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
                class="menu-item <?php echo ($current_page == 'parametres.php' || $current_page == 'profil.php' || $is_comptes) ? 'active' : ''; ?>">
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
                class="menu-item <?php echo ($current_page == 'parametres.php' || $current_page == 'profil.php' || $is_comptes) ? 'active' : ''; ?>">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cog"></i></span>
                <span class="menu-item__text">Paramètres</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $base_path; ?>logout.php" class="menu-item menu-item--logout">
                <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                <span class="menu-item__text">Déconnexion</span>
            </a>
        </nav>

        <?php if ($is_vendeur_menu):
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
                $current_page === 'profil.php' ||
                $current_page === 'parametres-boutique-vendeur.php' ||
                strpos($current_dir, '/parametres') !== false ||
                $is_zones_livraison ||
                $is_comptes
            );
            $vd_dashboard_dock_act = (($current_page === 'dashboard.php') || ($is_produits && $current_page === 'index.php'));
            $vd_stock_dock_act = $is_stock;
            $vd_caisse_dock_act = ($is_caisse && !$is_caisse_encaisser);
            $vd_clients_dock_act = $is_users;
            $vdock_menu_hint_sheet = (
                $vd_clients_dock_act ||
                $vd_param_sheet_active
            );
            ?>
        <!-- Bas d’écran vendeur : 4 raccourcis + Menu (≤1024px) -->
        <div class="admin-vendeur-dock-bar" id="adminVendeurDockBar" aria-label="Navigation vendeur réduite" hidden>
            <nav class="admin-vendeur-dock-primary" aria-label="Raccourcis">
                <a href="<?php echo $base_path; ?>dashboard.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_dashboard_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-home"></i></span>
                    <span class="menu-item__text">Tableau de bord</span>
                </a>
                <a href="<?php echo $base_path; ?>stock/index.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_stock_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-boxes-stacked"></i></span>
                    <span class="menu-item__text">Stock</span>
                </a>
                <a href="<?php echo $base_path; ?>commandes/index.php"
                    class="menu-item menu-item--dock-mini menu-item--has-badge<?php echo $vd_cmd_active_nav ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                    <span class="menu-item__text">Commandes</span>
                    <?php if ($nav_commandes_en_traitement > 0): ?>
                    <span class="menu-item__badge menu-item__badge--dock" title="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement" aria-label="<?php echo (int) $nav_commandes_en_traitement; ?> commande<?php echo $nav_commandes_en_traitement > 1 ? 's' : ''; ?> en cours de traitement"><?php echo (int) $nav_commandes_en_traitement; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $base_path; ?>caisse/index.php"
                    class="menu-item menu-item--dock-mini<?php echo $vd_caisse_dock_act ? ' active' : ''; ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas fa-cash-register"></i></span>
                    <span class="menu-item__text">Caisse</span>
                </a>
            </nav>
            <button type="button"
                class="admin-vendeur-dock-menu-btn<?php echo $vdock_menu_hint_sheet ? ' admin-vendeur-dock-menu-btn--hint' : ''; ?>"
                id="adminVendeurDockMenuBtn"
                aria-expanded="false"
                aria-haspopup="dialog"
                aria-controls="adminVendeurDockMenuPanel">
                <span class="admin-vendeur-dock-menu-btn__icon" aria-hidden="true"><i class="fas fa-th"></i></span>
                <span class="admin-vendeur-dock-menu-btn__label">Menu</span>
            </button>
        </div>
        <?php endif; ?>
    </aside>

    <?php if ($is_vendeur_menu): ?>
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
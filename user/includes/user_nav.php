<?php
/**
 * Inclusion de la barre de navigation utilisateur
 * Programmation procédurale uniquement
 */

$current_page = basename($_SERVER['PHP_SELF']);

$user_nav_items = [
    [
        'key' => 'dashboard',
        'href' => 'mon-compte.php',
        'match' => ['mon-compte.php'],
        'fa' => 'fa-home',
        'class_base' => 'menu-item menu-item--dashboard',
        'label' => 'Tableau de bord',
        'dock' => 'primary',
    ],
    [
        'key' => 'cart',
        'href' => '/panier.php',
        'match' => ['panier.php'],
        'fa' => 'fa-shopping-cart',
        'class_base' => 'menu-item menu-item--cart',
        'label' => 'Mon panier',
        'dock' => 'primary',
    ],
    [
        'key' => 'orders',
        'href' => 'mes-commandes.php',
        'match' => ['mes-commandes.php'],
        'fa' => 'fa-shopping-bag',
        'class_base' => 'menu-item menu-item--orders',
        'label' => 'Mes commandes',
        'dock' => 'primary',
    ],
    [
        'key' => 'cancelled',
        'href' => 'commandes-annulees.php',
        'match' => ['commandes-annulees.php'],
        'fa' => 'fa-ban',
        'class_base' => 'menu-item menu-item--cancelled',
        'label' => 'Commandes annulées',
        'dock' => 'more',
    ],
    [
        'key' => 'delivered',
        'href' => 'produits-livres.php',
        'match' => ['produits-livres.php'],
        'fa' => 'fa-check-circle',
        'class_base' => 'menu-item menu-item--delivered',
        'label' => 'Produits livrés',
        'dock' => 'more',
    ],
    [
        'key' => 'visited',
        'href' => 'produits-visites.php',
        'match' => ['produits-visites.php'],
        'fa' => 'fa-eye',
        'class_base' => 'menu-item menu-item--visited',
        'label' => 'Produits visités',
        'dock' => 'more',
    ],
    [
        'key' => 'profile',
        'href' => 'profil.php',
        'match' => ['profil.php'],
        'fa' => 'fa-user',
        'class_base' => 'menu-item menu-item--profile',
        'label' => 'Mon profil',
        'dock' => 'primary',
    ],
    [
        'key' => 'logout',
        'href' => 'deconnexion.php',
        'match' => [],
        'fa' => 'fa-sign-out-alt',
        'class_base' => 'menu-item menu-item--logout',
        'label' => 'Déconnexion',
        'dock' => 'more',
    ],
];

if (!function_exists('user_nav_link_classes')) {
    /**
     * Classes du lien sidebar / dock selon page courante
     *
     * @param array<string, mixed> $item
     */
    function user_nav_link_classes(array $item, string $current_page, string $suffix_class = '')
    {
        $parts = preg_split('/\s+/u', $item['class_base'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_values(array_unique($parts));
        if (!empty($item['match']) && is_array($item['match']) && in_array($current_page, $item['match'], true)) {
            $parts[] = 'active';
        }
        $extra = preg_split('/\s+/u', $suffix_class, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($extra as $c) {
            $parts[] = $c;
        }
        return trim(implode(' ', array_unique(array_filter($parts))));
    }
}

if (!function_exists('user_nav_more_has_active_child')) {
    /**
     * Page active parmi les liens du groupe « Menu » ?
     *
     * @param array<int, array<string, mixed>> $items
     */
    function user_nav_more_has_active_child(array $items, string $current_page)
    {
        foreach ($items as $it) {
            if (($it['dock'] ?? '') !== 'more') {
                continue;
            }
            if (
                !empty($it['match']) &&
                is_array($it['match']) &&
                in_array($current_page, $it['match'], true)
            ) {
                return true;
            }
        }
        return false;
    }
}

$dock_more_has_active_child = user_nav_more_has_active_child($user_nav_items, $current_page);
?>
<!-- Bouton menu mobile (réaffiché seulement en desktop élargi si utilisé hors zone dock) -->
<button class="mobile-menu-toggle" id="menuToggle" type="button" aria-label="Ouvrir le menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay pour mobile (principalement désactivé en dock bas) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    (function () {
        function toggleUserSidebar() {
            var sidebar = document.getElementById('userSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.documentElement.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            }
        }
        window.toggleSidebar = toggleUserSidebar;
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('menuToggle');
            var overlay = document.getElementById('sidebarOverlay');
            if (btn) btn.addEventListener('click', toggleUserSidebar);
            if (overlay) overlay.addEventListener('click', toggleUserSidebar);
        });
    })();
</script>

<div class="user-container">
    <!-- Barre de navigation verticale (desktop) + dock compact (tablet/mobile) -->
    <aside class="user-sidebar user-sidebar--glass" id="userSidebar" aria-label="Navigation compte client">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon" aria-hidden="true">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="sidebar-brand-text">
                    <span class="sidebar-brand-eyebrow">Espace client</span>
                    <h2>Mon compte</h2>
                </div>
            </div>
        </div>
        <!-- Rail complet desktop -->
        <nav class="sidebar-menu sidebar-menu--rail" aria-label="Menu principal">
            <?php foreach ($user_nav_items as $nav_item): ?>
                <?php if (($nav_item['key'] ?? '') === 'logout'): ?>
            <div class="sidebar-menu-split" role="presentation" aria-hidden="true"></div>
                <?php endif; ?>
            <a href="<?php echo htmlspecialchars((string) $nav_item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                class="<?php echo htmlspecialchars(
                    user_nav_link_classes($nav_item, $current_page),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas <?php echo htmlspecialchars(
                    (string) $nav_item['fa'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"></i></span>
                <span class="menu-item-text"><?php echo htmlspecialchars(
                    (string) $nav_item['label'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Dock client : même structure DOM que boutique / vendeur (shop-bottom-dock → shop-dock-bar → shop-dock-primary) -->
    <div class="shop-bottom-dock" id="userBottomDock" aria-label="Navigation compte rapide">
        <div class="shop-dock-bar" id="userDockBar" aria-label="Navigation compte réduite" hidden>
            <nav class="shop-dock-primary shop-dock-primary--cols-5" aria-label="Raccourcis">
                <?php foreach ($user_nav_items as $nav_item): ?>
                    <?php if (($nav_item['dock'] ?? '') !== 'primary') {
                        continue;
                    } ?>
                <a href="<?php echo htmlspecialchars((string) $nav_item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="<?php echo htmlspecialchars(
                        user_nav_link_classes($nav_item, $current_page, 'menu-item--dock-mini'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>">
                    <span class="menu-item__icon" aria-hidden="true"><i class="fas <?php echo htmlspecialchars(
                        (string) $nav_item['fa'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"></i></span>
                    <span class="menu-item__text"><?php echo htmlspecialchars(
                        (string) $nav_item['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?></span>
                </a>
                <?php endforeach; ?>
            <button type="button"
                id="userDockMenuBtn"
                aria-expanded="false"
                aria-haspopup="dialog"
                aria-controls="userDockMenuPanel"
                class="menu-item menu-item--dock-mini menu-item--dock-mini-btn<?php echo $dock_more_has_active_child ? ' menu-item--dock-mini-btn--hint' : ''; ?>">
                <span class="menu-item__icon menu-item__icon--hint-host" aria-hidden="true"><i class="fas fa-th"></i></span>
                <span class="menu-item__text">Menu</span>
            </button>
            </nav>
        </div>
    </div>

    <!-- Panneau « autres liens » — hors aside (backdrop-filter évite fullscreen fixed) -->
    <div class="user-dock-menu-layer" id="userDockMenuLayer" aria-hidden="true">
        <div class="user-dock-menu-backdrop" id="userDockMenuBackdrop" role="presentation"></div>
        <div class="user-dock-menu-panel" id="userDockMenuPanel" role="dialog" aria-modal="true"
            aria-labelledby="userDockMenuTitle">
            <header class="user-dock-menu-panel-hd">
                <strong id="userDockMenuTitle">Autres liens</strong>
                <button type="button" class="user-dock-menu-close" id="userDockMenuClose"
                    aria-label="Fermer le menu">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <nav class="user-dock-menu-panel-nav" aria-label="Menu étendu">
                <?php foreach ($user_nav_items as $nav_item): ?>
                    <?php if (($nav_item['dock'] ?? '') !== 'more') {
                        continue;
                    } ?>
                    <?php if (($nav_item['key'] ?? '') !== 'logout'): ?>
                <a href="<?php echo htmlspecialchars((string) $nav_item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="<?php echo htmlspecialchars(
                        user_nav_link_classes($nav_item, $current_page, 'menu-item--dock-sheet-row'),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>">
                    <span class="menu-item-icon" aria-hidden="true"><i class="fas <?php echo htmlspecialchars(
                        (string) $nav_item['fa'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"></i></span>
                    <span class="menu-item-text"><?php echo htmlspecialchars(
                        (string) $nav_item['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?></span>
                </a>
                    <?php else: ?>
                <div class="user-dock-menu-divider" role="presentation"></div>
                <a href="<?php echo htmlspecialchars((string) $nav_item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                    class="<?php echo htmlspecialchars(
                        user_nav_link_classes(
                            $nav_item,
                            $current_page,
                            'menu-item--dock-sheet-row menu-item--dock-sheet-logout'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>">
                    <span class="menu-item-icon" aria-hidden="true"><i class="fas <?php echo htmlspecialchars(
                        (string) $nav_item['fa'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"></i></span>
                    <span class="menu-item-text"><?php echo htmlspecialchars(
                        (string) $nav_item['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?></span>
                </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <script>
        (function () {
            function userDockQs() {
                return {
                    dockBar: document.getElementById('userDockBar'),
                    menuLayer: document.getElementById('userDockMenuLayer'),
                    backdrop: document.getElementById('userDockMenuBackdrop'),
                    btn: document.getElementById('userDockMenuBtn'),
                    closeBtn: document.getElementById('userDockMenuClose'),
                    panel: document.getElementById('userDockMenuPanel'),
                };
            }
            function isDockMq() {
                return window.matchMedia('(max-width: 1024px)').matches;
            }
            function openDockMenu() {
                var q = userDockQs();
                if (!q.menuLayer || !q.btn || !isDockMq()) return;
                q.menuLayer.classList.add('is-open');
                q.menuLayer.setAttribute('aria-hidden', 'false');
                q.btn.setAttribute('aria-expanded', 'true');
                document.documentElement.style.overflow = 'hidden';
            }
            function shutDockMenu() {
                var q = userDockQs();
                if (!q.menuLayer || !q.btn) return;
                q.menuLayer.classList.remove('is-open');
                q.menuLayer.setAttribute('aria-hidden', 'true');
                q.btn.setAttribute('aria-expanded', 'false');
                document.documentElement.style.overflow = '';
            }
            function toggleDockDockBarVisibility() {
                var q = userDockQs();
                if (!q.dockBar) return;
                if (isDockMq()) {
                    q.dockBar.removeAttribute('hidden');
                } else {
                    q.dockBar.setAttribute('hidden', '');
                    shutDockMenu();
                }
            }
            document.addEventListener('DOMContentLoaded', function () {
                toggleDockDockBarVisibility();
                var q = userDockQs();
                if (q.btn) q.btn.addEventListener('click', openDockMenu);
                if (q.backdrop) q.backdrop.addEventListener('click', shutDockMenu);
                if (q.closeBtn) q.closeBtn.addEventListener('click', shutDockMenu);
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') shutDockMenu();
                });
                window.addEventListener('resize', function () {
                    toggleDockDockBarVisibility();
                });
            });
        })();
    </script>

    <!-- Contenu principal -->
    <main class="user-content" id="userContent">

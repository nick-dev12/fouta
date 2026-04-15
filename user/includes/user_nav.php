<?php
/**
 * Inclusion de la barre de navigation utilisateur
 * Programmation procédurale uniquement
 */

// Déterminer le chemin de base
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Bouton menu mobile -->
<button class="mobile-menu-toggle" id="menuToggle" type="button" aria-label="Ouvrir le menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay pour mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    (function () {
        function toggleUserSidebar() {
            var sidebar = document.getElementById('userSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
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
    <!-- Barre de navigation verticale -->
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
        <nav class="sidebar-menu" aria-label="Menu principal">
            <a href="mon-compte.php"
                class="menu-item menu-item--dashboard <?php echo $current_page == 'mon-compte.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-home"></i></span>
                <span class="menu-item-text">Tableau de bord</span>
            </a>
            <a href="/panier.php" class="menu-item menu-item--cart">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                <span class="menu-item-text">Mon panier</span>
            </a>
            <a href="mes-commandes.php"
                class="menu-item menu-item--orders <?php echo $current_page == 'mes-commandes.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <span class="menu-item-text">Mes commandes</span>
            </a>
            <a href="commandes-annulees.php"
                class="menu-item menu-item--cancelled <?php echo $current_page == 'commandes-annulees.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
                <span class="menu-item-text">Commandes annulées</span>
            </a>
            <a href="produits-livres.php"
                class="menu-item menu-item--delivered <?php echo $current_page == 'produits-livres.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <span class="menu-item-text">Produits livrés</span>
            </a>
            <a href="produits-visites.php"
                class="menu-item menu-item--visited <?php echo $current_page == 'produits-visites.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-eye"></i></span>
                <span class="menu-item-text">Produits visités</span>
            </a>

            <a href="profil.php"
                class="menu-item menu-item--profile <?php echo $current_page == 'profil.php' ? 'active' : ''; ?>">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                <span class="menu-item-text">Mon profil</span>
            </a>
            <div class="sidebar-menu-split" role="presentation" aria-hidden="true"></div>
            <a href="deconnexion.php" class="menu-item menu-item--logout">
                <span class="menu-item-icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                <span class="menu-item-text">Déconnexion</span>
            </a>
        </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="user-content" id="userContent">
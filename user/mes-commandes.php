<?php
/**
 * Page de liste des commandes utilisateur — redesign v2
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_commandes.php';
require_once __DIR__ . '/../models/model_admin.php';
require_once __DIR__ . '/../includes/flash_toast.php';
require_once __DIR__ . '/../includes/commande_mode_helpers.php';
require_once __DIR__ . '/../includes/boutique_vendeur_display.php';

$success_message = '';
$error_message   = '';

// Toast commande créée : une seule fois, puis URL propre (évite réaffichage à chaque F5)
if (!empty($_GET['success']) && (string) $_GET['success'] === '1') {
    $cmd_success_msg = '';
    if (!empty($_GET['numeros'])) {
        $nums = array_filter(array_map('trim', explode(',', (string) $_GET['numeros'])));
        $cmd_success_msg = count($nums) > 1
            ? 'Vos commandes ont été créées : ' . implode(', ', $nums) . '.'
            : 'Votre commande #' . ($nums[0] ?? '') . ' a été créée avec succès !';
    } elseif (!empty($_GET['numero'])) {
        $cmd_success_msg = 'Votre commande #' . trim((string) $_GET['numero']) . ' a été créée avec succès !';
    }
    if ($cmd_success_msg !== '') {
        flash_toast_push('success', $cmd_success_msg);
        $tab_keep = isset($_GET['tab']) ? trim((string) $_GET['tab']) : '';
        $redir = '/user/mes-commandes.php';
        if ($tab_keep !== '' && $tab_keep !== 'actives') {
            $redir .= '?tab=' . rawurlencode($tab_keep);
        }
        http_redirect_safe($redir);
    }
}

// ---- Confirmation livraison ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_livraison'])) {
    $commande_id = (int) ($_POST['commande_id'] ?? 0);
    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);
        if ($commande && $commande['statut'] === 'livraison_en_cours') {
            require_once __DIR__ . '/../models/model_commandes_admin.php';
            if (update_commande_statut($commande_id, 'paye')) {
                require_once __DIR__ . '/../services/send_commande_notification.php';
                send_commande_status_notification((int) $_SESSION['user_id'], $commande['numero_commande'], 'paye', trim($_SESSION['user_email'] ?? ''));
                $vendeur_id = (int) ($commande['vendeur_id'] ?? 0);
                if ($vendeur_id > 0) send_commande_vendeur_action_notification($vendeur_id, $commande_id, $commande['numero_commande'], 'R&eacute;ception confirm&eacute;e par le client (pay&eacute;e)');
                header('Location: mes-commandes.php?livraison_confirmee=1&noter=' . $commande_id);
                exit;
            }
        }
        $error_message = 'Une erreur est survenue lors de la confirmation.';
    }
}

// ---- Annulation commande ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_commande'])) {
    $commande_id = (int) ($_POST['commande_id'] ?? 0);
    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);
        if ($commande && $commande['statut'] !== 'livree' && $commande['statut'] !== 'annulee') {
            if (update_commande_statut_user($commande_id, $_SESSION['user_id'], 'annulee')) {
                require_once __DIR__ . '/../services/send_commande_notification.php';
                send_commande_status_notification((int) $_SESSION['user_id'], $commande['numero_commande'], 'annulee', trim($_SESSION['user_email'] ?? ''));
                $vendeur_id = (int) ($commande['vendeur_id'] ?? 0);
                if ($vendeur_id > 0) send_commande_vendeur_action_notification($vendeur_id, $commande_id, $commande['numero_commande'], 'Annul&eacute;e par le client');
                header('Location: mes-commandes.php?commande_annulee=1');
                exit;
            }
        }
        $error_message = empty($error_message) ? 'Cette commande ne peut pas &ecirc;tre annul&eacute;e.' : $error_message;
    }
}

// ---- Recommander (re-commander) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recommander'])) {
    $commande_id = (int) ($_POST['commande_id'] ?? 0);
    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);
        if ($commande && $commande['statut'] === 'annulee') {
            require_once __DIR__ . '/../models/model_panier.php';
            $produits_commande = get_commande_produits($commande_id);
            if (!empty($produits_commande)) {
                require_once __DIR__ . '/../models/model_variantes.php';
                require_once __DIR__ . '/../models/model_produits.php';
                $added_count = 0;
                foreach ($produits_commande as $produit) {
                    $produit_info = get_produit_by_id($produit['produit_id']);
                    if ($produit_info && $produit_info['statut'] === 'actif' && $produit_info['stock'] > 0) {
                        $quantite      = min($produit['quantite'], $produit_info['stock']);
                        $variante_id   = !empty($produit['variante_id'])  ? (int)   $produit['variante_id']   : null;
                        $variante_nom  = !empty($produit['variante_nom']) ? trim($produit['variante_nom'])    : null;
                        $variante_img  = null;
                        if ($variante_id) { $var = get_variante_by_id($variante_id); $variante_img = $var && !empty($var['image']) ? $var['image'] : null; }
                        if (add_to_panier($_SESSION['user_id'], $produit['produit_id'], $quantite, $produit['couleur'] ?? null, $produit['poids'] ?? null, $produit['taille'] ?? null, $variante_id, $variante_nom, $variante_img, (float)($produit['surcout_poids'] ?? 0), (float)($produit['surcout_taille'] ?? 0), isset($produit['prix_unitaire']) ? (float)$produit['prix_unitaire'] : null)) {
                            $added_count++;
                        }
                    }
                }
                if ($added_count > 0) { header('Location: /panier.php?recommande=1&count=' . $added_count); exit; }
                $error_message = 'Aucun produit disponible &agrave; recommander.';
            } else { $error_message = 'Aucun produit trouv&eacute; dans cette commande.'; }
        } else { $error_message = 'Cette commande ne peut pas &ecirc;tre recommand&eacute;e.'; }
    }
}

// commande_annulee, livraison_confirmee : toast via flash_toast_from_query()

// ---- Données ----
$toutes_commandes  = get_commandes_by_user($_SESSION['user_id']);
$toutes_commandes  = is_array($toutes_commandes) ? $toutes_commandes : [];

$commandes_actives = array_values(array_filter($toutes_commandes, fn($c) => !in_array($c['statut'], ['livree', 'paye', 'annulee'])));
$commandes_livrees = array_values(array_filter($toutes_commandes, fn($c) => in_array($c['statut'], ['livree', 'paye'])));
$commandes_annulees = array_values(array_filter($toutes_commandes, fn($c) => $c['statut'] === 'annulee'));

$nb_actives  = count($commandes_actives);
$nb_livrees  = count($commandes_livrees);
$nb_annulees = count($commandes_annulees);
$nb_total    = count($toutes_commandes);

$tab = $_GET['tab'] ?? 'actives';

/**
 * Infos boutique associée à une commande (multi-boutique).
 *
 * @return array{nom:string, telephone:string}
 */
function uc_boutique_info_commande(array $commande) {
    static $cache = [];
    $vid = (int) ($commande['vendeur_id'] ?? 0);
    if ($vid <= 0) {
        return ['nom' => 'Boutique', 'telephone' => ''];
    }
    if (!isset($cache[$vid])) {
        $admin = get_admin_by_id($vid);
        if (!is_array($admin)) {
            $cache[$vid] = ['nom' => 'Boutique', 'telephone' => ''];
        } else {
            $nom = trim((string) ($admin['boutique_nom'] ?? ''));
            $cache[$vid] = [
                'nom' => $nom !== '' ? $nom : 'Boutique',
                'telephone' => trim((string) ($admin['telephone'] ?? '')),
            ];
        }
    }
    return $cache[$vid];
}

function uc_boutique_nom_commande(array $commande) {
    return uc_boutique_info_commande($commande)['nom'];
}

$commandes_affichees = match($tab) {
    'livrees'  => $commandes_livrees,
    'annulees' => $commandes_annulees,
    default    => $commandes_actives,
};

$commandes_avis_stats = [];
$commandes_noter_pending = [];
if (file_exists(__DIR__ . '/../models/model_produits_avis.php')) {
    require_once __DIR__ . '/../models/model_produits_avis.php';
    if (!empty($commandes_affichees)) {
        $ids_cmd = [];
        foreach ($commandes_affichees as $c) {
            $ids_cmd[] = (int) ($c['id'] ?? 0);
        }
        if (function_exists('produits_avis_moyennes_commandes')) {
            $commandes_avis_stats = produits_avis_moyennes_commandes($ids_cmd);
        }
        if (function_exists('produits_avis_commandes_notation_en_attente')) {
            $commandes_noter_pending = produits_avis_commandes_notation_en_attente((int) $_SESSION['user_id'], $ids_cmd);
        }
    }
}

$pr_open_noter_commande = isset($_GET['noter']) ? (int) $_GET['noter'] : 0;

if (!empty($success_message)) {
    flash_toast_queue_page('success', $success_message);
}
if (!empty($error_message)) {
    flash_toast_queue_page('error', $error_message);
}

// ---- Helpers statut ----
function cmd_user_label($s) {
    return match($s) {
        'en_attente'        => 'En attente',
        'prise_en_charge'   => 'Confirm&eacute;e',
        'livraison_en_cours' => 'En livraison',
        'livree'            => 'Livr&eacute;e',
        'paye'              => 'Re&ccedil;ue',
        'annulee'           => 'Annul&eacute;e',
        default             => ucfirst(str_replace('_', ' ', $s)),
    };
}

function cmd_user_badge($s) {
    return match($s) {
        'en_attente'        => 'ub--wait',
        'prise_en_charge'   => 'ub--confirm',
        'livraison_en_cours' => 'ub--delivery',
        'livree', 'paye'    => 'ub--done',
        'annulee'           => 'ub--cancel',
        default             => 'ub--wait',
    };
}

function cmd_user_icon($s) {
    return match($s) {
        'en_attente'        => 'fa-clock',
        'prise_en_charge'   => 'fa-box-open',
        'livraison_en_cours' => 'fa-truck',
        'livree', 'paye'    => 'fa-circle-check',
        'annulee'           => 'fa-ban',
        default             => 'fa-clock',
    };
}

// Timeline steps
function cmd_timeline_steps($statut) {
    $steps = [
        ['key' => 'en_attente',        'label' => 'Re&ccedil;ue',   'icon' => 'fa-circle-dot'],
        ['key' => 'prise_en_charge',   'label' => 'Confirm&eacute;e', 'icon' => 'fa-box-open'],
        ['key' => 'livraison_en_cours', 'label' => 'En livraison', 'icon' => 'fa-truck'],
        ['key' => 'livree',            'label' => 'Livr&eacute;e',  'icon' => 'fa-circle-check'],
    ];
    if ($statut === 'annulee') {
        return null;
    }
    if (in_array($statut, ['livree', 'paye'], true)) {
        $result = [];
        foreach ($steps as $s) {
            $result[] = $s + ['state' => 'done'];
        }
        return $result;
    }
    $order = ['en_attente' => 0, 'prise_en_charge' => 1, 'livraison_en_cours' => 2, 'livree' => 3, 'paye' => 3];
    $cur = $order[$statut] ?? -1;
    $result = [];
    foreach ($steps as $i => $s) {
        if ($i < $cur) {
            $result[] = $s + ['state' => 'done'];
        } elseif ($i === $cur) {
            $result[] = $s + ['state' => 'current'];
        } else {
            $result[] = $s + ['state' => 'pending'];
        }
    }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mes commandes &mdash; COLObanes</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
    <style>
        /* ===== MES COMMANDES v2 ===== */

        .uc-v2-page {
            max-width: 900px;
            margin: 0 auto;
            padding: clamp(16px, 4vw, 36px) clamp(14px, 4vw, 24px) 90px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            font-family: var(--font-corps);
        }

        /* ---- Header ---- */
        .uc-v2-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .uc-v2-header__left { display: flex; flex-direction: column; gap: 3px; }

        .uc-v2-header__eyebrow {
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--bleu-clair, #4a7ab8);
            display: flex; align-items: center; gap: 5px;
        }

        .uc-v2-header__title {
            font-size: clamp(1.3rem, 3vw, 1.75rem);
            font-weight: 800;
            color: var(--titres, #0d0d0d);
            font-family: var(--font-titres);
            line-height: 1.15;
            letter-spacing: -0.025em;
        }

        .uc-v2-header__actions { display: flex; gap: 9px; align-items: center; flex-wrap: wrap; }

        /* ---- Boutons ---- */
        .uc-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px;
            border-radius: 11px;
            font-size: 0.81rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            font-family: var(--font-corps);
            transition: all 0.2s;
            white-space: nowrap;
        }

        .uc-btn--primary { background: var(--couleur-dominante, #3564a6); color: #fff; box-shadow: 0 4px 14px rgba(53,100,166,0.25); }
        .uc-btn--primary:hover { background: var(--bleu-fonce, #2d5690); transform: translateY(-1px); }
        .uc-btn--outline { background: #fff; color: var(--couleur-dominante, #3564a6); border: 1.5px solid rgba(53,100,166,0.22); }
        .uc-btn--outline:hover { background: rgba(53,100,166,0.05); }

        /* ---- Notifications ---- */
        .uc-v2-notif {
            padding: 13px 18px;
            border-radius: 13px;
            display: flex; align-items: center; gap: 11px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .uc-v2-notif--success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25); color: #15803d; }
        .uc-v2-notif--error   { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #b91c1c; }

        /* ---- Hero banner ---- */
        .uc-v2-hero {
            background: linear-gradient(135deg, var(--couleur-dominante, #3564a6) 0%, #1e3f7a 55%, #0f2550 100%);
            border-radius: 20px;
            padding: clamp(20px, 3.5vw, 34px);
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 44px rgba(53,100,166,0.28);
        }

        .uc-v2-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -40px;
            width: 240px; height: 240px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            pointer-events: none;
        }

        .uc-v2-hero__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .uc-v2-hero__label {
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.6);
            margin-bottom: 5px;
        }

        .uc-v2-hero__title {
            font-size: clamp(1.5rem, 4vw, 2.2rem);
            font-weight: 900;
            color: #fff;
            font-family: var(--font-titres);
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .uc-v2-hero__pills {
            margin-top: 16px;
            display: flex; gap: 10px; flex-wrap: wrap;
        }

        .uc-v2-hero__pill {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 50px;
            padding: 7px 16px;
            display: flex; align-items: center; gap: 7px;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .uc-v2-hero__pill i { opacity: 0.8; }
        .uc-v2-hero__pill strong { font-size: 1.06em; }
        .uc-v2-hero__pill--warn { background: rgba(255,193,7,0.2); border-color: rgba(255,193,7,0.3); }
        .uc-v2-hero__pill--ok   { background: rgba(34,197,94,0.18); border-color: rgba(34,197,94,0.28); }

        .uc-v2-hero__cta {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.15);
            border: 1.5px solid rgba(255,255,255,0.25);
            border-radius: 11px;
            color: #fff;
            font-size: 0.81rem;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .uc-v2-hero__cta:hover { background: rgba(255,255,255,0.24); }

        /* ---- Onglets ---- */
        .uc-v2-tabs-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 11px;
        }

        .uc-v2-tabs {
            display: flex;
            gap: 5px;
            background: rgba(53,100,166,0.05);
            border-radius: 13px;
            padding: 5px;
            flex-wrap: wrap;
        }

        .uc-v2-tab {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--gris-moyen, #737373);
            transition: all 0.17s;
            white-space: nowrap;
        }

        .uc-v2-tab:hover { color: var(--titres); background: rgba(255,255,255,0.8); }

        .uc-v2-tab.active {
            background: #fff;
            color: var(--couleur-dominante, #3564a6);
            box-shadow: 0 2px 8px rgba(53,100,166,0.12);
        }

        .uc-v2-tab__count {
            background: rgba(53,100,166,0.09);
            color: var(--couleur-dominante, #3564a6);
            font-size: 0.67rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 50px;
        }

        .uc-v2-tab.active .uc-v2-tab__count { background: var(--couleur-dominante, #3564a6); color: #fff; }
        .uc-v2-tab--warn .uc-v2-tab__count  { background: rgba(255,193,7,0.15); color: #9a6800; }
        .uc-v2-tab--warn.active              { color: #c8960f; }
        .uc-v2-tab--warn.active .uc-v2-tab__count { background: #c8960f; }

        /* ---- Cards commandes ---- */
        .uc-v2-list { display: flex; flex-direction: column; gap: 14px; }

        .uc-v2-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(53,100,166,0.09);
            box-shadow: 0 2px 14px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .uc-v2-card:hover { box-shadow: 0 6px 24px rgba(53,100,166,0.12); }

        /* Top bar */
        .uc-v2-card__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px 12px;
            border-bottom: 1px solid rgba(53,100,166,0.06);
            flex-wrap: wrap;
            gap: 10px;
        }

        .uc-v2-card__ref {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            min-width: 0;
            flex: 1;
        }

        .uc-v2-card__ref-head {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            width: 100%;
        }

        .uc-v2-card__boutique {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--titres);
            font-family: var(--font-titres);
            line-height: 1.25;
            word-break: break-word;
        }

        .uc-v2-card__meta-line {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 0.72rem;
            color: var(--gris-moyen, #737373);
        }

        .uc-v2-card__ref-num {
            font-weight: 700;
            color: var(--couleur-dominante, #3564a6);
            font-family: var(--font-titres);
        }

        .uc-v2-card__sep {
            opacity: 0.45;
            font-weight: 700;
        }

        .uc-v2-card__date {
            font-size: inherit;
            color: inherit;
            display: inline;
        }

        /* Badges */
        .uc-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            white-space: nowrap;
        }

        .ub--wait     { background: rgba(255,193,7,0.14); color: #9a6800; }
        .ub--confirm  { background: rgba(255,107,53,0.14); color: #c04a10; }
        .ub--delivery { background: rgba(53,100,166,0.12); color: var(--bleu-fonce, #2d5690); }
        .ub--done     { background: rgba(34,197,94,0.12); color: #15803d; }
        .ub--cancel   { background: rgba(239,68,68,0.1); color: #b91c1c; }

        /* Body */
        .uc-v2-card__body {
            padding: 16px 20px 10px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .uc-v2-card__meta-bar {
            padding: 0 20px 14px;
            border-bottom: 1px solid rgba(53,100,166,0.06);
        }

        /* Infos montant + adresse */
        .uc-v2-card__info { flex: 1; min-width: 180px; }

        .uc-v2-card__amount {
            font-size: 1.45rem;
            font-weight: 900;
            color: var(--titres);
            font-family: var(--font-titres);
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .uc-v2-card__amount small {
            font-size: 0.48em;
            font-weight: 600;
            color: var(--gris-moyen, #737373);
            margin-left: 3px;
        }

        .uc-v2-card__addr {
            display: flex; align-items: flex-start; gap: 6px;
            font-size: 0.78rem;
            color: var(--gris-moyen, #737373);
            margin-top: 6px;
        }

        .uc-v2-card__addr i { color: var(--couleur-dominante, #3564a6); font-size: 0.72rem; margin-top: 2px; flex-shrink: 0; }

        .uc-v2-card__tel {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.78rem;
            color: var(--gris-moyen, #737373);
            margin-top: 4px;
            text-decoration: none;
        }

        .uc-v2-card__tel i { color: var(--couleur-dominante, #3564a6); font-size: 0.72rem; }
        .uc-v2-card__tel:hover { color: var(--couleur-dominante); }

        /* Modal annulation */
        .uc-cancel-modal {
            position: fixed;
            inset: 0;
            z-index: 6000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .uc-cancel-modal[hidden] { display: none !important; }

        .uc-cancel-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(13, 13, 13, 0.45);
            backdrop-filter: blur(3px);
        }

        .uc-cancel-modal__sheet {
            position: relative;
            z-index: 1;
            width: min(100%, 360px);
            background: #fff;
            border-radius: 18px;
            padding: 22px 20px 18px;
            box-shadow: 0 20px 50px rgba(53, 100, 166, 0.22);
            border: 1px solid rgba(53, 100, 166, 0.1);
        }

        .uc-cancel-modal__title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--titres);
            margin: 0 0 8px;
            font-family: var(--font-titres);
        }

        .uc-cancel-modal__text {
            font-size: 0.86rem;
            color: var(--gris-fonce, #4a4a4a);
            line-height: 1.45;
            margin: 0 0 18px;
        }

        .uc-cancel-modal__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .uc-cancel-modal__btn {
            border: none;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 0.84rem;
            font-weight: 700;
            font-family: var(--font-corps);
            cursor: pointer;
            transition: background 0.18s ease, transform 0.15s ease;
        }

        .uc-cancel-modal__btn--no {
            background: rgba(53, 100, 166, 0.08);
            color: var(--gris-fonce, #4a4a4a);
        }

        .uc-cancel-modal__btn--no:hover {
            background: rgba(53, 100, 166, 0.14);
        }

        .uc-cancel-modal__btn--yes {
            background: #b91c1c;
            color: #fff;
        }

        .uc-cancel-modal__btn--yes:hover {
            background: #991b1b;
        }

        /* Timeline */
        .uc-v2-timeline {
            display: flex;
            align-items: center;
            gap: 0;
            min-width: 0;
            flex: 1;
        }

        .uc-tl-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            flex: 1;
            position: relative;
        }

        .uc-tl-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 14px;
            left: calc(50% + 14px);
            right: calc(-50% + 14px);
            height: 2px;
            background: rgba(53,100,166,0.12);
            z-index: 0;
        }

        .uc-tl-step--done:not(:last-child)::after   { background: rgba(34,197,94,0.4); }
        .uc-tl-step--current:not(:last-child)::after { background: rgba(53,100,166,0.2); }

        .uc-tl-dot {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            transition: transform 0.2s;
        }

        .uc-tl-step--done .uc-tl-dot    { background: rgba(34,197,94,0.15); color: #16a34a; }
        .uc-tl-step--current .uc-tl-dot  { background: var(--couleur-dominante, #3564a6); color: #fff; box-shadow: 0 3px 10px rgba(53,100,166,0.35); animation: pulse-tl 2s ease-in-out infinite; }
        .uc-tl-step--pending .uc-tl-dot  { background: rgba(0,0,0,0.05); color: #ccc; }

        @keyframes pulse-tl {
            0%, 100% { box-shadow: 0 3px 10px rgba(53,100,166,0.35); }
            50%       { box-shadow: 0 3px 18px rgba(53,100,166,0.55); }
        }

        .uc-tl-label {
            font-size: 0.62rem;
            font-weight: 600;
            text-align: center;
            line-height: 1.2;
        }

        .uc-tl-step--done .uc-tl-label    { color: #16a34a; }
        .uc-tl-step--current .uc-tl-label { color: var(--couleur-dominante, #3564a6); font-weight: 700; }
        .uc-tl-step--pending .uc-tl-label { color: var(--gris-clair, #a3a3a3); }

        /* Footer actions */
        .uc-v2-card__footer {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(53,100,166,0.025);
            border-top: 1px solid rgba(53,100,166,0.06);
            flex-wrap: wrap;
        }

        .uc-card-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 0.79rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-family: var(--font-corps);
            transition: all 0.18s;
        }

        .uc-card-btn--track    { background: rgba(53,100,166,0.1); color: var(--couleur-dominante, #3564a6); }
        .uc-card-btn--track:hover { background: rgba(53,100,166,0.18); }

        .uc-card-btn--confirm  { background: rgba(34,197,94,0.12); color: #15803d; }
        .uc-card-btn--confirm:hover { background: rgba(34,197,94,0.2); }

        .uc-card-btn--cancel   { background: rgba(239,68,68,0.08); color: #b91c1c; }
        .uc-card-btn--cancel:hover { background: rgba(239,68,68,0.16); }

        .uc-card-btn--reorder  { background: rgba(255,107,53,0.1); color: var(--orange, #FF6B35); }

        .uc-card-btn--gmaps {
            background: #fff;
            color: #4285F4;
            border: 1px solid rgba(66, 133, 244, 0.28);
            box-shadow: 0 1px 4px rgba(66, 133, 244, 0.12);
        }
        .uc-card-btn--gmaps:hover {
            background: rgba(66, 133, 244, 0.08);
            border-color: rgba(66, 133, 244, 0.4);
        }
        .uc-card-btn--gmaps .fab.fa-google {
            font-size: 1em;
        }

        .uc-card-btn--wa-share {
            background: #25D366;
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 211, 102, 0.28);
        }
        .uc-card-btn--wa-share:hover {
            background: #1da851;
            color: #fff;
        }
        .uc-card-btn--reorder:hover { background: rgba(255,107,53,0.18); }

        .uc-card-btn--rate {
            background: linear-gradient(135deg, rgba(240, 180, 41, 0.22) 0%, rgba(224, 149, 0, 0.14) 100%);
            color: #9a6b00;
            border: 1px solid rgba(240, 180, 41, 0.35);
            box-shadow: 0 2px 8px rgba(240, 180, 41, 0.15);
        }
        .uc-card-btn--rate:hover {
            background: linear-gradient(135deg, rgba(240, 180, 41, 0.32) 0%, rgba(224, 149, 0, 0.22) 100%);
            transform: translateY(-1px);
        }

        .uc-card-btn--detail   { background: rgba(53,100,166,0.07); color: var(--gris-fonce, #4a4a4a); }
        .uc-card-btn--detail:hover { background: rgba(53,100,166,0.13); }

        /* Badge urgence */
        .uc-urgence {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #ef4444;
            display: inline-block;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.6; transform: scale(0.8); }
        }

        /* Empty state */
        .uc-v2-empty {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(53,100,166,0.08);
            padding: 56px 24px;
            text-align: center;
            color: var(--gris-moyen, #737373);
        }

        .uc-v2-empty__icon {
            width: 68px; height: 68px;
            border-radius: 18px;
            background: rgba(53,100,166,0.07);
            color: var(--couleur-dominante, #3564a6);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.7rem;
            margin: 0 auto 16px;
        }

        .uc-v2-empty h3 { font-size: 1.05rem; font-weight: 700; color: var(--titres); margin-bottom: 6px; }
        .uc-v2-empty p  { font-size: 0.86rem; max-width: 340px; margin: 0 auto 20px; }

        /* Responsive */
        @media (max-width: 600px) {
            .uc-v2-card__top { padding: 12px 14px 10px; }
            .uc-v2-card__boutique { font-size: 0.84rem; }
            .uc-v2-card__meta-line { font-size: 0.68rem; gap: 5px; }
            .uc-v2-card__body { flex-direction: column; gap: 14px; padding: 12px 14px 8px; }
            .uc-v2-card__meta-bar { padding: 0 14px 12px; }
            .uc-v2-card__amount { font-size: 1.25rem; }
            .uc-v2-timeline { display: none; }
            .uc-v2-tabs { gap: 3px; }
            .uc-v2-tab { padding: 7px 11px; font-size: 0.75rem; }
        }
    </style>
</head>

<body class="user-page-mes-commandes">
    <?php include 'includes/user_nav.php'; ?>

    <div class="uc-v2-page">

        <!-- ===== HEADER ===== -->
        <header class="uc-v2-header">
            <div class="uc-v2-header__left">
                <p class="uc-v2-header__eyebrow"><i class="fas fa-route"></i> Suivi en temps r&eacute;el</p>
                <h1 class="uc-v2-header__title">Mes commandes</h1>
            </div>
        </header>

        <!-- ===== HERO ===== -->
        <div class="uc-v2-hero">
            <div class="uc-v2-hero__top">
                <div>
                    <p class="uc-v2-hero__label">Mes commandes &mdash; Vue d'ensemble</p>
                    <div class="uc-v2-hero__title"><?php echo $nb_total; ?> commande<?php echo $nb_total > 1 ? 's' : ''; ?></div>
                </div>
                <a href="/produits.php" class="uc-v2-hero__cta">
                    <i class="fas fa-shopping-basket"></i> Commander &agrave; nouveau
                </a>
            </div>
            <div class="uc-v2-hero__pills">
                <div class="uc-v2-hero__pill <?php echo $nb_actives > 0 ? 'uc-v2-hero__pill--warn' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span><strong><?php echo $nb_actives; ?></strong> en cours</span>
                </div>
                <div class="uc-v2-hero__pill uc-v2-hero__pill--ok">
                    <i class="fas fa-circle-check"></i>
                    <span><strong><?php echo $nb_livrees; ?></strong> livr&eacute;e<?php echo $nb_livrees > 1 ? 's' : ''; ?></span>
                </div>
                <?php if ($nb_annulees > 0): ?>
                    <div class="uc-v2-hero__pill">
                        <i class="fas fa-ban"></i>
                        <span><strong><?php echo $nb_annulees; ?></strong> annul&eacute;e<?php echo $nb_annulees > 1 ? 's' : ''; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== ONGLETS ===== -->
        <div class="uc-v2-tabs-row">
            <div class="uc-v2-tabs" role="tablist">
                <a href="?tab=actives"
                    class="uc-v2-tab uc-v2-tab--warn <?php echo $tab === 'actives' ? 'active' : ''; ?>"
                    role="tab">
                    <i class="fas fa-clock"></i>
                    En cours
                    <span class="uc-v2-tab__count"><?php echo $nb_actives; ?></span>
                </a>
                <a href="?tab=livrees"
                    class="uc-v2-tab <?php echo $tab === 'livrees' ? 'active' : ''; ?>"
                    role="tab">
                    <i class="fas fa-circle-check"></i>
                    Livr&eacute;es
                    <span class="uc-v2-tab__count"><?php echo $nb_livrees; ?></span>
                </a>
                <a href="?tab=annulees"
                    class="uc-v2-tab <?php echo $tab === 'annulees' ? 'active' : ''; ?>"
                    role="tab">
                    <i class="fas fa-ban"></i>
                    Annul&eacute;es
                    <span class="uc-v2-tab__count"><?php echo $nb_annulees; ?></span>
                </a>
            </div>
        </div>

        <!-- ===== LISTE COMMANDES ===== -->
        <?php if (empty($commandes_affichees)): ?>
            <div class="uc-v2-empty">
                <div class="uc-v2-empty__icon">
                    <?php if ($tab === 'livrees'): ?>
                        <i class="fas fa-circle-check"></i>
                    <?php elseif ($tab === 'annulees'): ?>
                        <i class="fas fa-ban"></i>
                    <?php else: ?>
                        <i class="fas fa-bag-shopping"></i>
                    <?php endif; ?>
                </div>
                <?php if ($tab === 'livrees'): ?>
                    <h3>Aucune commande livr&eacute;e</h3>
                    <p>Vos commandes livr&eacute;es apparaîtront ici.</p>
                <?php elseif ($tab === 'annulees'): ?>
                    <h3>Aucune commande annul&eacute;e</h3>
                    <p>Vous n'avez annul&eacute; aucune commande.</p>
                <?php else: ?>
                    <h3>Aucune commande en cours</h3>
                    <p>Vous n'avez pas de commande active. Passez votre premi&egrave;re commande !</p>
                <?php endif; ?>
                <a href="/produits.php" class="uc-btn uc-btn--primary">
                    <i class="fas fa-store"></i> D&eacute;couvrir les produits
                </a>
            </div>
        <?php else: ?>
            <div class="uc-v2-list">
                <?php foreach ($commandes_affichees as $commande):
                    $st = $commande['statut'] ?? 'en_attente';
                    $is_urgent = $st === 'en_attente';
                    $timeline  = cmd_timeline_steps($st);
                    $boutique_info = uc_boutique_info_commande($commande);
                    $boutique_nom = $boutique_info['nom'];
                    $boutique_tel = $boutique_info['telephone'];
                    $date_cmd = isset($commande['date_commande'])
                        ? date('d/m/Y', strtotime($commande['date_commande']))
                        : '&mdash;';
                    $can_cancel  = in_array($st, ['en_attente', 'confirmee', 'prise_en_charge', 'en_preparation']);
                    $can_confirm = $st === 'livraison_en_cours';
                    $can_reorder = $st === 'annulee';
                    $cmd_id = (int) ($commande['id'] ?? 0);
                    $cmd_avis = ($cmd_id > 0 && isset($commandes_avis_stats[$cmd_id]))
                        ? $commandes_avis_stats[$cmd_id]
                        : ['moyenne' => 0.0, 'count' => 0];
                    $can_noter = in_array($st, ['livree', 'paye'], true)
                        && $cmd_id > 0
                        && !empty($commandes_noter_pending[$cmd_id]);
                    $is_retrait_cmd = commande_is_retrait($commande);
                    $pickup_maps_url = '';
                    $pickup_wa_url = '';
                    if ($is_retrait_cmd) {
                        $vendeur_pickup_id = (int) ($commande['vendeur_id'] ?? 0);
                        $adm_pickup = ($vendeur_pickup_id > 0) ? get_admin_by_id($vendeur_pickup_id) : null;
                        $pickup_info = boutique_pickup_info_from_admin(
                            $adm_pickup && is_array($adm_pickup) ? $adm_pickup : null,
                            $boutique_nom
                        );
                        $pickup_maps_url = trim((string) ($pickup_info['maps_url'] ?? ''));
                        $pickup_wa_url = trim((string) ($pickup_info['whatsapp_url'] ?? ''));
                    }
                ?>
                    <article class="uc-v2-card">

                        <!-- Top bar -->
                        <div class="uc-v2-card__top">
                            <div class="uc-v2-card__ref">
                                <div class="uc-v2-card__ref-head">
                                    <?php if ($is_urgent): ?><span class="uc-urgence" title="Action possible"></span><?php endif; ?>
                                    <span class="uc-v2-card__boutique"><?php echo htmlspecialchars($boutique_nom); ?></span>
                                </div>
                                <?php if (!empty($cmd_avis['count'])): ?>
                                    <div class="uc-v2-card__rating">
                                        <?php
                                        $note = (float) ($cmd_avis['moyenne'] ?? 0);
                                        $count = (int) ($cmd_avis['count'] ?? 0);
                                        $size = 'sm';
                                        require __DIR__ . '/../includes/partials/product_rating_stars.php';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="uc-badge <?php echo cmd_user_badge($st); ?>">
                                <i class="fas <?php echo cmd_user_icon($st); ?>" style="font-size:.7em;margin-right:3px;"></i>
                                <?php echo cmd_user_label($st); ?>
                            </span>
                        </div>

                        <!-- Body -->
                        <div class="uc-v2-card__body">
                            <!-- Montant + infos -->
                            <div class="uc-v2-card__info">
                                <div class="uc-v2-card__amount">
                                    <?php echo number_format((float) $commande['montant_total'], 0, ',', ' '); ?><small>FCFA</small>
                                </div>
                                <?php if ($boutique_tel !== ''): ?>
                                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $boutique_tel)); ?>"
                                        class="uc-v2-card__tel uc-v2-card__tel--boutique">
                                        <i class="fas fa-store"></i>
                                        <?php echo htmlspecialchars($boutique_tel); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Timeline -->
                            <?php if ($timeline !== null): ?>
                                <div class="uc-v2-timeline" aria-label="Avancement de la commande">
                                    <?php foreach ($timeline as $step): ?>
                                        <div class="uc-tl-step uc-tl-step--<?php echo $step['state']; ?>">
                                            <div class="uc-tl-dot">
                                                <i class="fas <?php echo $step['icon']; ?>"></i>
                                            </div>
                                            <span class="uc-tl-label"><?php echo $step['label']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="uc-v2-card__meta-bar">
                            <span class="uc-v2-card__meta-line">
                                <span class="uc-v2-card__ref-num">#<?php echo htmlspecialchars($commande['numero_commande']); ?></span>
                                <span class="uc-v2-card__sep" aria-hidden="true">&middot;</span>
                                <span class="uc-v2-card__date"><?php echo $date_cmd; ?></span>
                            </span>
                        </div>

                        <!-- Footer actions -->
                        <div class="uc-v2-card__footer">
                            <?php if ($is_retrait_cmd && $pickup_maps_url !== ''): ?>
                                <a href="<?php echo htmlspecialchars($pickup_maps_url, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="uc-card-btn uc-card-btn--gmaps" target="_blank" rel="noopener noreferrer"
                                    title="Ouvrir la localisation de la boutique sur Google Maps">
                                    <i class="fab fa-google" aria-hidden="true"></i> Localisation
                                </a>
                            <?php endif; ?>
                            <?php if ($is_retrait_cmd && $pickup_wa_url !== ''): ?>
                                <a href="<?php echo htmlspecialchars($pickup_wa_url, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="uc-card-btn uc-card-btn--wa-share" target="_blank" rel="noopener noreferrer"
                                    title="Partager la localisation de la boutique sur WhatsApp">
                                    <i class="fab fa-whatsapp" aria-hidden="true"></i> Partager
                                </a>
                            <?php endif; ?>

                            <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                                class="uc-card-btn uc-card-btn--track">
                                <i class="fas fa-route"></i> Suivre
                            </a>

                            <?php if ($can_noter): ?>
                                <button type="button"
                                    class="uc-card-btn uc-card-btn--rate uc-btn-open-rating"
                                    data-commande-id="<?php echo $cmd_id; ?>"
                                    aria-label="Noter les produits de cette commande">
                                    <i class="fas fa-star"></i> Noter
                                </button>
                            <?php endif; ?>

                            <?php if ($can_confirm): ?>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                                    <button type="submit" name="confirmer_livraison" class="uc-card-btn uc-card-btn--confirm"
                                        onclick="return confirm('Confirmez-vous la r&eacute;ception de votre colis ?');">
                                        <i class="fas fa-check-circle"></i> Colis re&ccedil;u
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($can_cancel): ?>
                                <form method="post" action="" class="uc-cancel-form" style="display:inline;">
                                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                                    <button type="button" class="uc-card-btn uc-card-btn--cancel uc-btn-open-cancel"
                                        data-commande-id="<?php echo (int) $commande['id']; ?>">
                                        <i class="fas fa-times-circle"></i> Annuler
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($can_reorder): ?>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                                    <button type="submit" name="recommander" class="uc-card-btn uc-card-btn--reorder">
                                        <i class="fas fa-rotate-right"></i> Recommander
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>

                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /.uc-v2-page -->

    <div id="ucCancelModal" class="uc-cancel-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ucCancelModalTitle">
        <div class="uc-cancel-modal__backdrop" id="ucCancelModalBackdrop"></div>
        <div class="uc-cancel-modal__sheet">
            <h2 class="uc-cancel-modal__title" id="ucCancelModalTitle">Annuler la commande</h2>
            <p class="uc-cancel-modal__text">&Ecirc;tes-vous vraiment s&ucirc;r de vouloir annuler cette commande ? Cette action est irr&eacute;versible.</p>
            <div class="uc-cancel-modal__actions">
                <button type="button" class="uc-cancel-modal__btn uc-cancel-modal__btn--no" id="ucCancelModalNo">Annuler</button>
                <button type="button" class="uc-cancel-modal__btn uc-cancel-modal__btn--yes" id="ucCancelModalYes">Oui</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.getElementById('ucCancelModal');
        var backdrop = document.getElementById('ucCancelModalBackdrop');
        var btnNo = document.getElementById('ucCancelModalNo');
        var btnYes = document.getElementById('ucCancelModalYes');
        var pendingForm = null;

        if (!modal || !btnYes || !btnNo) {
            return;
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        }

        function openModal(form) {
            pendingForm = form;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            btnNo.focus();
        }

        document.querySelectorAll('.uc-btn-open-cancel').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('form.uc-cancel-form');
                if (form) {
                    openModal(form);
                }
            });
        });

        btnNo.addEventListener('click', closeModal);
        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        btnYes.addEventListener('click', function () {
            if (!pendingForm) {
                closeModal();
                return;
            }
            var submit = document.createElement('input');
            submit.type = 'hidden';
            submit.name = 'annuler_commande';
            submit.value = '1';
            pendingForm.appendChild(submit);
            pendingForm.submit();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>

    <?php include 'includes/user_footer.php'; ?>

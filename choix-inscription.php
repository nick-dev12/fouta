<?php
/**
 * Sélection du type de compte à créer (client, vendeur).
 */
session_start();

$redirect = isset($_GET['redirect']) ? trim((string) $_GET['redirect']) : '';
$safe_redirect = preg_match('/^[a-z0-9_-]+$/i', $redirect) ? $redirect : '';
$q = $safe_redirect !== '' ? ('?' . http_build_query(['redirect' => $safe_redirect])) : '';

require_once __DIR__ . '/includes/asset_version.php';
$vq = asset_version_query();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte — COLObanes</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo $vq; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .page-choix-inscription {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-corps, system-ui, sans-serif);
            background: var(--fond-page);
            color: var(--texte-fonce);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(20px, 4vw, 40px);
            position: relative;
            overflow-x: hidden;
        }

        .page-choix-inscription::before {
            content: "";
            position: fixed;
            inset: -40%;
            background:
                radial-gradient(ellipse 55% 45% at 15% 25%, var(--bleu-pale), transparent 55%),
                radial-gradient(ellipse 50% 40% at 85% 75%, var(--orange-pale), transparent 50%),
                radial-gradient(ellipse 40% 35% at 50% 10%, rgba(53, 100, 166, 0.06), transparent 45%);
            pointer-events: none;
            z-index: 0;
        }

        .choix-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 920px;
        }

        .choix-head {
            text-align: center;
            margin-bottom: clamp(28px, 5vw, 44px);
            animation: choix-head-in 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .choix-head h1 {
            font-family: var(--font-titres);
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: var(--titres);
            margin: 0 0 12px;
            letter-spacing: -0.02em;
        }

        .choix-head .choix-lead {
            margin: 0 auto;
            max-width: 520px;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--gris-fonce);
        }

        .choix-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: clamp(16px, 3vw, 24px);
            margin-bottom: 20px;
        }

        .choix-card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-decoration: none;
            color: inherit;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: clamp(22px, 4vw, 30px);
            box-shadow: var(--glass-shadow);
            transition:
                transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.35s ease,
                border-color 0.25s ease;
            overflow: hidden;
            animation: choix-card-in 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .choix-card:nth-child(1) { animation-delay: 0.1s; }
        .choix-card:nth-child(2) { animation-delay: 0.2s; }

        .choix-card::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.35s ease;
            pointer-events: none;
        }

        .choix-card--client::after {
            background: linear-gradient(145deg, var(--bleu-pale), transparent 55%);
        }

        .choix-card--vendor::after {
            background: linear-gradient(145deg, var(--orange-pale), transparent 55%);
        }

        .choix-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--ombre-gourmande);
            border-color: rgba(53, 100, 166, 0.22);
        }

        .choix-card:hover::after {
            opacity: 1;
        }

        .choix-card:focus-visible {
            outline: 3px solid var(--focus-ring);
            outline-offset: 3px;
        }

        .choix-card--client:hover {
            border-color: rgba(53, 100, 166, 0.35);
        }

        .choix-card--vendor:hover {
            border-color: rgba(255, 107, 53, 0.4);
        }

        .choix-card__icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            margin-bottom: 18px;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            box-shadow: var(--ombre-douce);
        }

        .choix-card:hover .choix-card__icon {
            transform: scale(1.06) rotate(-3deg);
        }

        .choix-card--vendor:hover .choix-card__icon {
            transform: scale(1.06) rotate(3deg);
        }

        .choix-card--client .choix-card__icon {
            background: linear-gradient(135deg, var(--bleu-principal), var(--bleu-clair));
            color: var(--texte-clair);
        }

        .choix-card--vendor .choix-card__icon {
            background: linear-gradient(135deg, var(--orange-fonce), var(--orange));
            color: var(--texte-clair);
        }

        .choix-card__title {
            font-family: var(--font-titres);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--titres);
            margin-bottom: 8px;
        }

        .choix-card__desc {
            font-size: 0.9rem;
            line-height: 1.55;
            color: var(--gris-moyen);
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .choix-card__cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-top: auto;
            transition: gap 0.25s ease;
        }

        .choix-card--client .choix-card__cta {
            color: var(--couleur-dominante);
        }

        .choix-card--vendor .choix-card__cta {
            color: var(--accent-promo);
        }

        .choix-card:hover .choix-card__cta {
            gap: 12px;
        }

        .choix-card__cta i {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .choix-row-secondary {
            animation: choix-card-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.28s both;
        }

        .choix-card--ghost {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 24px;
            background: var(--fond-secondaire);
            border: 1px dashed var(--border-input);
            box-shadow: none;
            margin: 0 auto;
            max-width: 420px;
        }

        .choix-card--ghost::after {
            display: none;
        }

        .choix-card--ghost:hover {
            transform: translateY(-4px);
            background: var(--blanc);
            border-style: solid;
            border-color: var(--couleur-dominante);
            box-shadow: var(--ombre-douce);
        }

        .choix-card--ghost .choix-card__icon {
            width: 44px;
            height: 44px;
            margin-bottom: 0;
            font-size: 1.1rem;
            background: var(--bleu-pale);
            color: var(--couleur-dominante);
            box-shadow: none;
            border-radius: 12px;
        }

        .choix-card--ghost:hover .choix-card__icon {
            transform: none;
        }

        .choix-card--ghost .choix-card__text {
            text-align: left;
        }

        .choix-card--ghost .choix-card__title {
            margin-bottom: 2px;
            font-size: 1rem;
        }

        .choix-card--ghost .choix-card__desc {
            margin: 0;
            font-size: 0.82rem;
        }

        .choix-foot {
            margin-top: 28px;
            text-align: center;
            font-size: 0.88rem;
            color: var(--gris-moyen);
            animation: choix-head-in 0.5s ease 0.35s both;
        }

        .choix-foot a {
            color: var(--couleur-dominante);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s, color 0.2s;
        }

        .choix-foot a:hover {
            color: var(--couleur-dominante-hover);
            border-bottom-color: rgba(53, 100, 166, 0.35);
        }

        @keyframes choix-head-in {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes choix-card-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .choix-head,
            .choix-card,
            .choix-row-secondary {
                animation: none;
            }

            .choix-card:hover {
                transform: none;
            }

            .choix-card:hover .choix-card__icon {
                transform: none;
            }
        }
    </style>
</head>
<body class="page-choix-inscription">
    <main class="choix-shell">
        <header class="choix-head">
            <h1>Créer un compte</h1>
            <p class="choix-lead">Choisissez le profil qui vous correspond : acheteur sur la place de marché ou vendeur avec votre propre vitrine.</p>
        </header>

        <div class="choix-grid" role="navigation" aria-label="Types de compte">
            <a class="choix-card choix-card--client"
                href="/user/inscription.php<?php echo $safe_redirect !== '' ? htmlspecialchars($q) : ''; ?>">
                <span class="choix-card__icon" aria-hidden="true"><i class="fas fa-bag-shopping"></i></span>
                <span class="choix-card__title">Compte client</span>
                <span class="choix-card__desc">Parcourir le catalogue, commander des pièces et suivre vos livraisons en toute simplicité.</span>
                <span class="choix-card__cta">Commencer <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>

            <a class="choix-card choix-card--vendor" href="/admin/inscription-vendeur.php">
                <span class="choix-card__icon" aria-hidden="true"><i class="fas fa-store"></i></span>
                <span class="choix-card__title">Compte vendeur</span>
                <span class="choix-card__desc">Publiez vos produits, gérez votre boutique et touchez les acheteurs de la plateforme.</span>
                <span class="choix-card__cta">Ouvrir ma boutique <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>
        </div>

        <div class="choix-row-secondary">
            <a class="choix-card choix-card--ghost"
                href="/choix-connexion.php<?php echo $safe_redirect !== '' ? htmlspecialchars($q) : ''; ?>">
                <span class="choix-card__icon" aria-hidden="true"><i class="fas fa-right-to-bracket"></i></span>
                <span class="choix-card__text">
                    <span class="choix-card__title">Déjà inscrit ?</span>
                    <span class="choix-card__desc">Connectez-vous à votre espace</span>
                </span>
            </a>
        </div>

        <p class="choix-foot"><a href="/index.php"><i class="fas fa-house" aria-hidden="true"></i> Retour au site</a></p>
    </main>
</body>
</html>

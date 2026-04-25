<?php
session_start();

require_once __DIR__ . '/includes/produit_boutique_line.php';

// Inclusion du fichier de connexion à la BDD

// Récupérez l'ID du commerçant à partir de la session
// Récupérez l'ID de l'utilisateur depuis la variable de session
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/site_brand.php';
$base = get_site_base_url();
$seo_title = site_brand_seo_title_default();
$seo_description = site_brand_seo_description_default();
$seo_keywords = site_brand_seo_keywords_default();
$seo_canonical = $base . '/';
?>




<!DOCTYPE html>
<html lang="fr" class="aos-not-ready">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <style>
        /* AOS masque [data-aos] dans sa feuille CSS avant que le JS n’ajoute .aos-animate — affichage immédiat jusqu’à init */
        html.aos-not-ready [data-aos] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
            pointer-events: auto !important;
        }
    </style>
    <link rel="stylesheet" href="/css/owl.carousel.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <style>
        /* Bannière vitrine - Design site vitrine professionnel */
        .vitrine-hero {
            position: relative;
            padding: 60px 24px 50px;
            overflow: hidden;
        }

        .vitrine-hero-bg {
            position: absolute;
            inset: 0;
            background: var(--fond-page);
            z-index: 0;
        }

        .vitrine-hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233564a6' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.6;
        }

        .vitrine-hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .vitrine-hero-title {
            font-size: clamp(24px, 4vw, 36px);
            font-weight: 700;
            color: var(--couleur-dominante);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0 0 20px;
            font-family: var(--font-titres);
        }

        .vitrine-hero-desc {
            font-size: clamp(14px, 1.8vw, 16px);
            line-height: 1.7;
            color: var(--gris-fonce);
            margin: 0 0 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .vitrine-services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }

        .vitrine-service-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 28px 20px;
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            min-height: 140px;
        }

        .vitrine-service-block:hover {
            background: var(--couleur-dominante-hover);
            transform: translateY(-4px);
            box-shadow: var(--ombre-gourmande);
        }

        .vitrine-service-icon {
            font-size: 36px;
            opacity: 0.95;
        }

        .vitrine-service-label {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .vitrine-hero {
                padding: 40px 16px 36px;
            }

            .vitrine-services-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .vitrine-service-block {
                padding: 24px 16px;
                min-height: 120px;
            }

            .vitrine-service-icon {
                font-size: 28px;
            }

            .vitrine-service-label {
                font-size: 11px;
            }
        }

        @media (max-width: 400px) {
            .vitrine-services-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Nouveaux produits et Produits populaires : flex-wrap, Owl désactivé, 6 produits max */
        .carousel-produits-outer {
            position: relative;
            width: 100%;
        }

        .carousel-produits-outer .carousel1.carousel1-flex-mode {
            display: flex !important;
            flex-wrap: wrap;
            justify-content: space-around;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
        }

        .carousel-produits-outer .carousel1.carousel1-flex-mode .carousel {
            width: 320px;
            min-width: 170px;
            max-width: 320px;
            flex: 0 0 320px;
        }

        .carousel-produits-outer .carousel1.carousel1-flex-mode .carousel:nth-child(n+7) {
            display: none !important;
        }

        @media (max-width: 650px) {
            .carousel-produits-outer .carousel1.carousel1-flex-mode {
                gap: 12px;
                padding: 12px;
            }
        }

        @media (max-width: 400px) {
            .carousel-produits-outer .carousel1.carousel1-flex-mode {
                gap: 10px;
                padding: 10px;
            }
        }

        /* Carrousel des catégories */
        .marques-section {
            padding: 20px 0 20px;
            background: var(--blanc);
            border-top: 1px solid var(--glass-border);
        }

        .marques-container {
            position: relative;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 60px;
        }

        .marques-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 1px solid var(--gris-clair);
            background: var(--blanc);
            color: var(--couleur-dominante);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.25s ease;
            box-shadow: var(--ombre-douce);
        }

        .marques-nav:hover {
            background: var(--couleur-dominante);
            color: var(--blanc);
            border-color: var(--couleur-dominante);
            box-shadow: var(--ombre-promo);
        }

        .marques-nav-prev {
            left: 0;
        }

        .marques-nav-next {
            right: 0;
        }

        .marques-carousel.owl-carousel .owl-stage-outer {
            overflow: hidden;
        }

        .marques-carousel.owl-carousel .owl-nav {
            display: none;
        }

        .marques-carousel.owl-carousel .owl-dots {
            display: none;
        }

        .marque-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            text-align: center;
        }

        .marque-item-link {
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease;
        }

        .marque-item-link:hover {
            transform: translateY(-2px);
        }

        .marque-item-link:hover .marque-name {
            color: var(--couleur-dominante);
        }

        .marque-logo-wrap {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            overflow: hidden;
            background: var(--blanc-casse);
        }

        .marque-logo-wrap img {
            width: 70%;
            height: 70%;
            object-fit: contain;
        }

        .marque-fallback {
            display: none;
            font-size: 26px;
            font-weight: 700;
            color: var(--couleur-dominante);
            letter-spacing: 1px;
        }

        .marque-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--gris-fonce);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.3;
        }

        @media (max-width: 768px) {
            .marques-section {
                padding: 45px 0 55px;
            }

            .marques-container {
                padding: 0 50px;
            }

            .marques-nav {
                width: 44px;
                height: 44px;
                font-size: 14px;
            }

            .marque-item {
                padding: 22px 16px;
            }

            .marque-logo-wrap {
                width: 100px;
                height: 100px;
                margin-bottom: 14px;
            }

            .marque-fallback {
                font-size: 22px;
            }

            .marque-name {
                font-size: 11px;
            }
        }

        /* —— Accueil marketplace (grilles type vitrine B2B, ex. Alibaba) —— */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .mp-main {
            background: var(--blanc-neige);
            padding-bottom: 48px;
            font-family: var(--font-corps);
        }

        .mp-shell {
            box-sizing: border-box;
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 16px;
            overflow-x: hidden;
        }

        .mp-slider-wrap {
            max-width: 1320px;
            margin: 0 auto 12px;
            padding: 12px 16px 0;
        }

        .mp-slider-wrap .slider-area.owl-carousel {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            max-height: 320px;
        }

        .mp-slider-wrap .slider-item img {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            display: block;
        }

        /* Bandeau hero : zone slide ≤ 300px, images entières (object-fit: contain) */
        .mp-hero {
            --mp-hero-slide-h: 300px;
            background: var(--blanc-neige);
            border-bottom: 1px solid var(--glass-border);
            padding: 0 0 8px;
            margin-bottom: 8px;
        }

        .mp-hero .mp-hero-slider-wrap {
            box-sizing: border-box;
            max-height: none;
            padding: 0 16px;
            margin-bottom: 0;
            overflow: visible;
        }

        .mp-hero .mp-hero-slider-wrap .slider-area.owl-carousel {
            max-height: none;
            overflow: visible;
        }

        .mp-hero .mp-hero-slider-wrap .owl-stage-outer {
            height: var(--mp-hero-slide-h);
            max-height: var(--mp-hero-slide-h);
        }

        .mp-hero .mp-hero-slider-wrap .owl-stage,
        .mp-hero .mp-hero-slider-wrap .owl-item {
            height: var(--mp-hero-slide-h);
            max-height: var(--mp-hero-slide-h);
        }

        .mp-hero .mp-hero-slider-wrap .slider-item {
            box-sizing: border-box;
            height: var(--mp-hero-slide-h);
            max-height: var(--mp-hero-slide-h);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--fond-secondaire, #fafafa);
            line-height: 0;
        }

        .mp-hero .mp-hero-slider-wrap .slider-item img {
            box-sizing: border-box;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .mp-hero .mp-hero-slider-wrap .owl-dots {
            margin: 6px 0 0;
            padding: 0;
            line-height: 1;
        }

        .mp-hero .mp-hero-slider-wrap .owl-nav {
            margin-top: 0;
        }

        .mp-hero-slider-wrap {
            margin-bottom: 0;
        }

        .mp-hero-placeholder {
            min-height: 200px;
            max-height: var(--mp-hero-slide-h, 300px);
            margin: 8px 16px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--bleu-pale) 0%, var(--fond-secondaire) 100%);
            border: 1px dashed var(--border-input);
        }

        .mp-hero-inner {
            max-width: 920px;
            margin: 0 auto;
            text-align: center;
        }

        .mp-hero .mp-hero-sub {
            font-size: 14px;
            color: var(--gris-fonce);
            margin: 0 0 20px;
            line-height: 1.5;
        }

        .mp-search {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            max-width: 720px;
            margin: 0 auto;
            background: var(--blanc);
            border: 2px solid var(--couleur-dominante);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--glass-shadow);
        }

        .mp-search-input {
            flex: 1 1 200px;
            border: none;
            padding: 14px 20px;
            font-size: 15px;
            outline: none;
            min-width: 0;
        }

        .mp-search-btn {
            border: none;
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            font-weight: 600;
            padding: 14px 24px;
            cursor: pointer;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease;
        }

        .mp-search-btn:hover {
            background: var(--couleur-dominante-hover);
        }

        .mp-trust-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px 28px;
            max-width: 920px;
            margin: 20px auto 0;
        }

        .mp-trust-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gris-fonce);
        }

        .mp-trust-item i {
            color: var(--bleu-clair);
            font-size: 16px;
        }

        /* (Grille catégories pleine largeur retirée : voir vitrine mp-showcase) */

        /* Sections produits */
        .mp-block {
            margin: 22px 0 0;
        }

        .mp-block-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
            padding: 0 2px;
        }

        .mp-block-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--titres);
            font-family: var(--font-titres);
        }

        .mp-block-more {
            font-size: 14px;
            font-weight: 600;
            color: var(--couleur-dominante);
            text-decoration: none;
        }

        .mp-block-more:hover {
            text-decoration: underline;
        }

        .mp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }

        @media (min-width: 1400px) {
            .mp-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        .mp-card {
            background: var(--blanc);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .mp-card:hover {
            box-shadow: var(--ombre-douce);
            border-color: var(--bleu-pale);
        }

        .mp-card-link {
            text-decoration: none;
            color: inherit;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .mp-card-img {
            aspect-ratio: 1 / 1;
            background: var(--blanc-casse);
            overflow: hidden;
        }

        .mp-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .mp-card-body {
            padding: 10px 12px 8px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .mp-card-title {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.35;
            color: var(--noir-clair);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.7em;
        }

        .mp-card .produit-card-boutique {
            font-size: 11px;
            margin: 0 0 4px;
            color: var(--gris-moyen);
            line-height: 1.3;
        }

        .mp-card .produit-card-boutique-link {
            color: var(--bleu-clair);
            font-weight: 600;
            text-decoration: none;
        }

        .mp-card .produit-card-boutique-link:hover {
            text-decoration: underline;
        }

        .mp-card-meta {
            margin: 0 0 8px;
            font-size: 11px;
            color: var(--gris-clair);
        }

        .mp-card-price-row {
            margin-top: auto;
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 6px 10px;
        }

        .mp-card-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent-promo);
        }

        .mp-card-price-old {
            font-size: 12px;
            color: var(--gris-clair);
            text-decoration: line-through;
        }

        .mp-card-cart {
            padding: 0 10px 10px;
            margin: 0;
        }

        .mp-card-btn {
            width: 100%;
            border: 1px solid var(--couleur-dominante);
            background: var(--blanc);
            color: var(--couleur-dominante);
            font-weight: 600;
            font-size: 13px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .mp-card-btn:hover {
            background: var(--couleur-dominante);
            color: var(--texte-clair);
        }

        .mp-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 36px 16px;
            color: var(--gris-moyen);
            background: var(--blanc);
            border: 1px dashed var(--glass-border);
            border-radius: 10px;
        }

        .mp-footer-cta {
            text-align: center;
            margin: 28px 0 8px;
        }

        .mp-footer-cta a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            font-weight: 600;
            text-decoration: none;
            border-radius: 24px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .mp-footer-cta a:hover {
            background: var(--couleur-dominante-hover);
        }

        /* —— Vitrine (catégories + tendances + coups de cœur) : mobile-first = 1 colonne —— */
        .mp-showcase {
            padding: 20px 0 8px;
            overflow-x: hidden;
        }

        .mp-showcase-inner {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: stretch;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .mp-showcase-inner>* {
            min-width: 0;
        }

        @media (min-width: 1101px) {
            .mp-showcase-inner {
                grid-template-columns: 240px minmax(0, 1fr) minmax(260px, 320px);
            }

            .mp-showcase-inner.mp-showcase-inner--no-nav {
                grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
            }
        }

        .mp-showcase-nav {
            background: var(--blanc);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 420px;
        }

        .mp-showcase-nav-head {
            font-size: 14px;
            font-weight: 700;
            color: var(--titres);
            padding: 14px 16px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-titres);
        }

        .mp-showcase-nav-head i {
            color: var(--accent-promo);
        }

        .mp-showcase-nav-list {
            list-style: none;
            margin: 0;
            padding: 8px 0;
            overflow-y: auto;
            flex: 1;
        }

        .mp-showcase-nav-list li {
            margin: 0;
        }

        .mp-showcase-nav-list a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--gris-fonce);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
            border-bottom: 1px solid var(--blanc-casse);
        }

        .mp-showcase-nav-list a:hover {
            background: var(--bleu-pale);
            color: var(--couleur-dominante);
        }

        .mp-showcase-nav-list .mp-sn-ico {
            width: 28px;
            text-align: center;
            color: var(--bleu-clair);
            font-size: 15px;
        }

        .mp-showcase-nav-list .mp-sn-chev {
            margin-left: auto;
            color: var(--gris-clair);
            font-size: 11px;
        }

        .mp-showcase-nav-foot {
            padding: 10px 12px;
            border-top: 1px solid var(--glass-border);
        }

        .mp-showcase-nav-foot a {
            display: block;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--couleur-dominante);
            text-decoration: none;
        }

        /* Après les styles .mp-showcase-nav : masquer sur mobile (accès via menu latéral) */
        @media (max-width: 768px) {
            .mp-showcase-nav {
                display: none !important;
            }
        }

        .mp-showcase-center {
            background: var(--blanc);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 16px 14px 18px;
            box-shadow: var(--glass-shadow);
            position: relative;
        }

        .mp-showcase-center-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .mp-showcase-center-top h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--titres);
            font-family: var(--font-titres);
        }

        .mp-showcase-center-top p {
            margin: 4px 0 0;
            font-size: 12px;
            color: var(--gris-moyen);
        }

        .mp-trend-nav {
            display: flex;
            gap: 6px;
        }

        .mp-trend-nav button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--glass-border);
            background: var(--blanc);
            color: var(--titres);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--ombre-douce);
            transition: background 0.2s, color 0.2s;
        }

        .mp-trend-nav button:hover {
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            border-color: var(--couleur-dominante);
        }

        .mp-trend-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            padding-bottom: 6px;
            margin: 0 -4px;
            padding-left: 4px;
            padding-right: 4px;
            -webkit-overflow-scrolling: touch;
        }

        .mp-trend-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .mp-trend-scroll::-webkit-scrollbar-thumb {
            background: var(--bleu-pale);
            border-radius: 4px;
        }

        .mp-trend-card {
            flex: 0 0 168px;
            scroll-snap-align: start;
            background: var(--blanc-casse);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 10px;
            text-align: center;
        }

        .mp-trend-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--couleur-dominante);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .mp-trend-sub {
            display: block;
            font-size: 12px;
            color: var(--gris-fonce);
            margin-bottom: 10px;
            line-height: 1.3;
            min-height: 2.6em;
        }

        .mp-trend-card a.mp-trend-img-link {
            display: block;
            aspect-ratio: 1 / 1;
            background: var(--blanc);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .mp-trend-card a.mp-trend-img-link img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Coups de cœur — carte mise en avant */
        .mp-showcase-spotlight {
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            justify-self: stretch;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(155deg, #ffffff 0%, var(--blanc-casse) 38%, rgba(53, 100, 166, 0.07) 100%);
            border: 1px solid rgba(53, 100, 166, 0.14);
            box-shadow: 0 4px 24px rgba(53, 100, 166, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
            padding: clamp(16px, 3vw, 24px) clamp(14px, 2.5vw, 22px) clamp(16px, 2.5vw, 22px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: clamp(10px, 2vw, 18px);
            min-height: min(360px, 52vh);
        }

        .mp-showcase-spotlight::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(ellipse 90% 70% at 0% 0%, rgba(53, 100, 166, 0.12) 0%, transparent 55%),
                radial-gradient(ellipse 80% 60% at 100% 100%, rgba(255, 107, 53, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .mp-sp-content {
            position: relative;
            z-index: 1;
        }

        .mp-sp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--couleur-dominante);
            background: rgba(53, 100, 166, 0.1);
            border: 1px solid rgba(53, 100, 166, 0.18);
            padding: 5px 10px;
            border-radius: 999px;
            margin-bottom: 10px;
        }

        .mp-sp-badge i {
            font-size: 0.7rem;
            opacity: 0.9;
        }

        .mp-sp-content h2 {
            margin: 0 0 clamp(6px, 1.2vw, 10px);
            font-size: clamp(1.15rem, 2.4vw, 1.45rem);
            font-weight: 700;
            color: var(--titres);
            font-family: var(--font-titres);
            letter-spacing: -0.03em;
            line-height: 1.2;
        }

        .mp-sp-content p {
            margin: 0;
            font-size: clamp(12px, 1.8vw, 14px);
            line-height: 1.55;
            color: var(--gris-fonce);
            max-width: 28em;
        }

        .mp-sp-visual {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            margin: 0;
            padding: clamp(8px, 2vw, 14px);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.85) 0%, rgba(250, 250, 250, 0.95) 100%);
            border: 1px solid rgba(53, 100, 166, 0.1);
            border-radius: 16px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 8px 28px rgba(53, 100, 166, 0.08);
        }

        .mp-sp-slides-stack {
            display: grid;
            grid-template-areas: "mpSpotStack";
            width: 100%;
            min-height: 100px;
            place-items: center;
        }

        .mp-sp-slide {
            grid-area: mpSpotStack;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            opacity: 0;
            transition: opacity 0.45s ease;
            pointer-events: none;
            z-index: 0;
        }

        .mp-sp-slide.is-active {
            opacity: 1;
            pointer-events: auto;
            z-index: 1;
        }

        .mp-sp-grid--single {
            grid-template-columns: 1fr;
            max-width: 180px;
        }

        .mp-sp-visual img {
            max-width: 100%;
            width: auto;
            height: auto;
            max-height: clamp(110px, 22vw, 150px);
            object-fit: contain;
            border-radius: 12px;
            filter: drop-shadow(0 6px 16px rgba(53, 100, 166, 0.14));
        }

        .mp-sp-visual .mp-sp-fallback-ico {
            font-size: clamp(2.5rem, 12vw, 3.5rem);
            color: var(--bleu-clair);
            opacity: 0.35;
        }

        .mp-sp-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
        }

        .mp-sp-tile {
            display: block;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            border: 1px solid rgba(53, 100, 166, 0.15);
        }

        .mp-sp-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            max-height: none;
            filter: none;
            display: block;
        }

        .mp-sp-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 1;
            padding: 4px 0 0;
        }

        .mp-sp-dots button.mp-sp-dot {
            width: 8px;
            height: 8px;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: var(--gris-clair);
            opacity: 0.45;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s, background 0.2s;
        }

        .mp-sp-dots button.mp-sp-dot.is-active,
        .mp-sp-dots button.mp-sp-dot[aria-current="true"] {
            background: var(--couleur-dominante);
            opacity: 1;
            transform: scale(1.2);
        }

        .mp-sp-cta {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: clamp(11px, 2.5vw, 14px) clamp(14px, 3vw, 20px);
            background: linear-gradient(135deg, var(--couleur-dominante) 0%, var(--bleu-fonce) 100%);
            color: var(--texte-clair) !important;
            font-weight: 600;
            font-size: clamp(13px, 2vw, 14px);
            border-radius: 999px;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            margin-top: 0;
            box-shadow: 0 4px 14px rgba(53, 100, 166, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .mp-sp-cta i {
            font-size: 0.9em;
            opacity: 0.95;
            transition: transform 0.2s ease;
        }

        .mp-sp-cta:hover {
            background: linear-gradient(135deg, var(--couleur-dominante-hover) 0%, var(--bleu-fonce) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(53, 100, 166, 0.4);
        }

        .mp-sp-cta:hover i {
            transform: translateX(3px);
        }

        @media (max-width: 1100px) {
            .mp-showcase-spotlight {
                min-height: auto;
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            .mp-sp-visual img {
                max-height: clamp(130px, 28vw, 180px);
            }
        }

        @media (max-width: 600px) {
            .mp-showcase-spotlight {
                border-radius: 16px;
                padding: 16px 14px 16px;
                gap: 12px;
            }

            .mp-sp-badge {
                font-size: 0.6rem;
                padding: 4px 9px;
            }

            .mp-sp-visual {
                min-height: 108px;
                padding: 10px;
                border-radius: 14px;
            }

            .mp-sp-visual img {
                max-height: min(170px, 42vw);
            }

            .mp-sp-dots {
                padding-top: 2px;
            }
        }

        @media (max-width: 380px) {
            .mp-showcase-spotlight {
                padding: 14px 12px 14px;
            }

            .mp-sp-content p {
                line-height: 1.45;
            }

            .mp-sp-cta {
                gap: 8px;
            }
        }

        /* Bandeau « commande / personnalisation » */
        .mp-promo-b2b {
            margin: 22px 0;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(115deg, var(--bleu-fonce) 0%, var(--couleur-dominante) 42%, var(--bleu-clair) 100%);
            box-shadow: var(--ombre-promo);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .mp-promo-b2b-inner {
            display: grid;
            grid-template-columns: minmax(260px, 380px) 1fr;
            gap: 24px;
            align-items: center;
            padding: 32px 28px 32px 32px;
        }

        @media (max-width: 900px) {
            .mp-promo-b2b-inner {
                grid-template-columns: 1fr;
                padding: 24px 18px;
            }
        }

        .mp-promo-copy {
            color: var(--texte-clair);
        }

        .mp-promo-copy .mp-promo-ico {
            font-size: 22px;
            opacity: 0.95;
            margin-bottom: 10px;
            display: block;
        }

        .mp-promo-copy h2 {
            margin: 0 0 14px;
            font-size: clamp(20px, 3vw, 26px);
            font-weight: 700;
            font-family: var(--font-titres);
            line-height: 1.2;
        }

        .mp-promo-list {
            list-style: none;
            margin: 0 0 22px;
            padding: 0;
        }

        .mp-promo-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .mp-promo-list i {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .mp-promo-cta {
            display: inline-flex;
            padding: 12px 26px;
            background: var(--blanc);
            color: var(--bleu-fonce) !important;
            font-weight: 700;
            border-radius: 999px;
            text-decoration: none;
            font-size: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .mp-promo-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .mp-strip-scroll {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding: 4px 2px 10px;
            scroll-snap-type: x mandatory;
        }

        .mp-strip-card {
            flex: 0 0 148px;
            scroll-snap-align: start;
            background: var(--blanc);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }

        .mp-strip-card-img {
            aspect-ratio: 1 / 1;
            background: var(--blanc-casse);
        }

        .mp-strip-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mp-strip-pill {
            margin: -14px 8px 8px;
            position: relative;
            z-index: 1;
            background: rgba(13, 13, 13, 0.82);
            color: var(--texte-clair);
            font-size: 10px;
            font-weight: 600;
            padding: 5px 8px;
            border-radius: 999px;
            text-align: center;
            line-height: 1.2;
        }

        .mp-strip-body {
            padding: 0 10px 12px;
        }

        .mp-strip-price {
            font-size: 15px;
            font-weight: 700;
            color: var(--titres);
            margin: 0 0 4px;
        }

        .mp-strip-moq {
            margin: 0;
            font-size: 11px;
            color: var(--gris-moyen);
        }

        /* Deux panneaux : Top + Nouveautés */
        .mp-pair {
            margin: 8px 0 24px;
        }

        .mp-pair-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        @media (max-width: 900px) {
            .mp-pair-grid {
                grid-template-columns: 1fr;
            }
        }

        .mp-panel {
            background: var(--blanc);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 20px 18px 22px;
            box-shadow: var(--glass-shadow);
        }

        .mp-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .mp-panel-head h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--titres);
            font-family: var(--font-titres);
        }

        .mp-panel-head p {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--gris-moyen);
            line-height: 1.4;
        }

        .mp-panel-more {
            font-size: 13px;
            font-weight: 600;
            color: var(--couleur-dominante);
            text-decoration: none;
            white-space: nowrap;
        }

        .mp-panel-more:hover {
            text-decoration: underline;
        }

        .mp-panel-products {
            display: grid;
            /* Mobile / tablette : 2×2 ; grand écran : une ligne de 4 */
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 14px;
        }

        @media (min-width: 900px) {
            .mp-panel-products {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px 12px;
            }
        }

        @media (max-width: 400px) {
            .mp-panel-products {
                gap: 8px 10px;
            }

            .mp-new-card {
                padding: 8px 6px;
            }

            .mp-new-price {
                font-size: 14px;
            }

            .mp-new-moq {
                font-size: 10px;
            }

            .mp-new-cart-btn {
                padding: 6px;
                font-size: 13px;
            }
        }

        /* Carte TOP */
        .mp-top-card {
            text-align: center;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 10px 14px;
            background: var(--blanc-casse);
        }

        .mp-top-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .mp-top-card-img-wrap {
            position: relative;
            margin: 0 auto 12px;
            max-width: 120px;
        }

        .mp-top-card-img {
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            overflow: hidden;
            background: var(--blanc);
            border: 1px solid var(--glass-border);
        }

        .mp-top-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mp-top-badge {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--bleu-fonce), var(--couleur-dominante));
            color: var(--texte-clair);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 6px 14px;
            clip-path: polygon(10% 0%, 90% 0%, 100% 50%, 90% 100%, 10% 100%, 0% 50%);
            box-shadow: var(--ombre-douce);
        }

        .mp-top-title {
            margin: 14px 0 6px;
            font-size: 13px;
            font-weight: 700;
            color: var(--titres);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .mp-top-sub {
            margin: 0;
            font-size: 11px;
            color: var(--gris-moyen);
        }

        .mp-top-cart {
            margin: 12px 0 0;
        }

        .mp-top-cart-btn {
            width: 100%;
            border: none;
            background: var(--couleur-dominante);
            color: var(--texte-clair);
            font-size: 12px;
            font-weight: 600;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mp-top-cart-btn:hover {
            background: var(--couleur-dominante-hover);
        }

        /* Cartes nouveautés compactes */
        .mp-new-card {
            position: relative;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px;
            background: var(--blanc-casse);
            display: flex;
            flex-direction: column;
        }

        .mp-new-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;
            background: var(--accent-promo);
            color: var(--blanc);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.35);
            pointer-events: none;
        }

        .mp-new-card-link {
            text-decoration: none;
            color: inherit;
            flex: 1;
        }

        .mp-new-card-img {
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            overflow: hidden;
            background: var(--blanc);
            margin-bottom: 10px;
            border: 1px solid var(--glass-border);
        }

        .mp-new-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mp-new-promo {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--accent-promo);
        }

        .mp-new-price {
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 800;
            color: var(--titres);
        }

        .mp-new-price span {
            font-size: 12px;
            font-weight: 600;
            color: var(--gris-moyen);
        }

        .mp-new-moq {
            margin: 0;
            font-size: 11px;
            color: var(--gris-moyen);
        }

        .mp-new-cart {
            margin: 10px 0 0;
        }

        .mp-new-cart-btn {
            width: 100%;
            border: 1px solid var(--couleur-dominante);
            background: var(--blanc);
            color: var(--couleur-dominante);
            border-radius: 8px;
            padding: 8px;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s, color 0.2s;
        }

        .mp-new-cart-btn:hover {
            background: var(--couleur-dominante);
            color: var(--texte-clair);
        }

        .mp-logos-wrap {
            margin: 20px 0 8px;
        }

        @media (max-width: 576px) {
            .mp-search {
                border-radius: 8px;
                flex-direction: column;
            }

            .mp-search-btn {
                justify-content: center;
                width: 100%;
            }

            .mp-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
    </style>

</head>


<body>

    <?php
    $section1_mp_page_title = 'Marché en ligne — Global Marketplace';
    include 'nav_bar.php';
    unset($section1_mp_page_title);
    ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <div class="commande-perso-success"
            style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: var(--success-bg); border-left: 4px solid var(--bleu); border-radius: 8px; color: var(--titres);">
            <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="commande-perso-success"
            style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: var(--error-bg); border-left: 4px solid var(--error-border); border-radius: 8px; color: var(--titres);">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['commande_perso_success'])): ?>
        <div class="commande-perso-success"
            style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: var(--success-bg); border-left: 4px solid var(--bleu); border-radius: 8px; color: var(--titres);">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['commande_perso_success']);
            unset($_SESSION['commande_perso_success']); ?>
        </div>
    <?php endif; ?>

    <?php
    $return_url_mp = (string) ($_SERVER['REQUEST_URI'] ?? '/index.php');

    $slides = [];
    if (file_exists(__DIR__ . '/models/model_slider.php')) {
        require_once __DIR__ . '/models/model_slider.php';
        $slides_result = get_all_slides('actif');
        $slides = is_array($slides_result) ? $slides_result : [];
    }

    $mp_categories = [];
    if (file_exists(__DIR__ . '/models/model_categories.php')) {
        require_once __DIR__ . '/models/model_categories.php';
        $mp_categories = get_all_categories();
        if (is_array($mp_categories) && count($mp_categories) > 24) {
            $mp_categories = array_slice($mp_categories, 0, 24);
        }
    }
    if (!is_array($mp_categories)) {
        $mp_categories = [];
    }

    $logos_carousel = [];
    if (file_exists(__DIR__ . '/models/model_logos.php')) {
        require_once __DIR__ . '/models/model_logos.php';
        $logos_carousel = get_all_logos('actif', null);
    }
    if (!is_array($logos_carousel)) {
        $logos_carousel = [];
    }

    $produits_nouveaux = [];
    $produits_populaires = [];
    $produits_tous = [];
    $total_produits = 0;
    if (file_exists(__DIR__ . '/models/model_produits.php')) {
        require_once __DIR__ . '/models/model_produits.php';
        $produits_nouveaux = get_all_produits_paginated(0, 12);
        $produits_tous = get_all_produits_paginated(0, 24);
        $total_produits = count_all_produits_actifs();
    }
    if (file_exists(__DIR__ . '/models/model_visites.php')) {
        require_once __DIR__ . '/models/model_visites.php';
        $produits_populaires = get_produits_plus_visites(12);
    }
    if (!is_array($produits_nouveaux)) {
        $produits_nouveaux = [];
    }
    if (!is_array($produits_populaires)) {
        $produits_populaires = [];
    }
    if (!is_array($produits_tous)) {
        $produits_tous = [];
    }

    $card_partial = __DIR__ . '/includes/partials/home_mp_product_card.php';
    $partial_top = __DIR__ . '/includes/partials/home_mp_top_product.php';
    $partial_newc = __DIR__ . '/includes/partials/home_mp_new_compact.php';

    $mp_nav_cats = [];
    $mp_nav_is_generale = false;
    if (function_exists('categories_generales_table_exists') && categories_generales_table_exists()) {
        $mp_nav_generales = get_general_categories_ordered();
        if (is_array($mp_nav_generales) && !empty($mp_nav_generales)) {
            $mp_nav_cats = array_slice($mp_nav_generales, 0, 14);
            $mp_nav_is_generale = true;
        }
    }
    if (empty($mp_nav_cats) && !empty($mp_categories)) {
        $mp_nav_cats = array_slice($mp_categories, 0, 14);
        $mp_nav_is_generale = false;
    }

    $mp_nav_sidebar_icons = ['fa-star', 'fa-tshirt', 'fa-headphones', 'fa-futbol', 'fa-spray-can', 'fa-gem', 'fa-laptop', 'fa-couch', 'fa-car', 'fa-mobile-alt', 'fa-heart', 'fa-cube', 'fa-tag', 'fa-th'];

    require_once __DIR__ . '/includes/marketplace_home_helpers.php';
    if (file_exists(__DIR__ . '/models/model_recherches_catalogue.php')) {
        require_once __DIR__ . '/models/model_recherches_catalogue.php';
    }
    if (file_exists(__DIR__ . '/models/model_favoris.php')) {
        require_once __DIR__ . '/models/model_favoris.php';
    }

    /* Recherches fréquentes : produits liés au journal de recherche catalogue (mélangés, max 20) */
    $recherche_candidats = function_exists('get_produits_lies_aux_recherches_frequentes')
        ? get_produits_lies_aux_recherches_frequentes(40)
        : [];
    $produits_tendance = marketplace_produits_aleatoires_avec_seuil($recherche_candidats, 20, 5);

    /* 4 articles / panneau : grille 2×2 sur mobile, 4 en ligne sur grand écran */
    $produits_top_panneau = !empty($produits_populaires)
        ? array_slice($produits_populaires, 0, 4)
        : array_slice($produits_nouveaux, 0, 4);
    $produits_new_panneau = function_exists('get_produits_nouveautes_aleatoires_panneau')
        ? get_produits_nouveautes_aleatoires_panneau(4, 50)
        : array_slice($produits_nouveaux, 0, 4);

    /* Bandeau B2B : best-sellers (quantités commandées), mélangés */
    $vendus_candidats = function_exists('get_produits_plus_vendus_marketplace')
        ? get_produits_plus_vendus_marketplace(60)
        : [];
    $produits_strip = marketplace_produits_aleatoires_avec_seuil($vendus_candidats, 8, 5);

    /* Coups de cœur : carrousel — plusieurs diapos (2 produits), rotation côté client toutes les 1 min */
    $favoris_candidats = function_exists('get_produits_plus_favoris_marketplace')
        ? get_produits_plus_favoris_marketplace(40)
        : [];
    $spotlight_pool = marketplace_produits_aleatoires_avec_seuil($favoris_candidats, 12, 5);
    if (!is_array($spotlight_pool)) {
        $spotlight_pool = [];
    }
    $spotlight_slides = [];
    for ($i = 0; $i < count($spotlight_pool) - 1; $i += 2) {
        $spotlight_slides[] = array_slice($spotlight_pool, $i, 2);
    }
    if (count($spotlight_pool) % 2 === 1) {
        $spotlight_slides[] = [$spotlight_pool[count($spotlight_pool) - 1]];
    }
    $spotlight_slides = array_slice($spotlight_slides, 0, 5);

    $hero_affiches = [];
    if (file_exists(__DIR__ . '/models/model_marketplace_hero.php')) {
        require_once __DIR__ . '/models/model_marketplace_hero.php';
        $hero_affiches = marketplace_hero_list_actifs();
    }
    if (!is_array($hero_affiches)) {
        $hero_affiches = [];
    }

    $mp_spotlight_img = null;
    if (!empty($hero_affiches[0]['image'])) {
        $mp_spotlight_img = '/upload/marketplace_hero/' . rawurlencode((string) $hero_affiches[0]['image']);
    } elseif (!empty($slides[0]['image'])) {
        $mp_spotlight_img = '/upload/slider/' . htmlspecialchars((string) $slides[0]['image'], ENT_QUOTES, 'UTF-8');
    }
    ?>

    <main class="mp-main">

        <section class="mp-hero" aria-label="Affichage marketplace">
            <?php if (!empty($hero_affiches)): ?>
                <div class="mp-slider-wrap mp-hero-slider-wrap">
                    <div class="slider-area owl-carousel">
                        <?php foreach ($hero_affiches as $ha): ?>
                            <div class="slider-item">
                                <img src="/upload/marketplace_hero/<?php echo htmlspecialchars((string) ($ha['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars((string) ($ha['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    onerror="this.src='/image/produit1.jpg'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="mp-hero-placeholder" role="img" aria-label="Aucune bannière"></div>
            <?php endif; ?>
        </section>

        <div class="mp-shell">
            <section class="mp-showcase" aria-label="Catégories et tendances">
                <div class="mp-showcase-inner<?php echo empty($mp_nav_cats) ? ' mp-showcase-inner--no-nav' : ''; ?>">
                    <?php if (!empty($mp_nav_cats)): ?>
                        <aside class="mp-showcase-nav">
                            <div class="mp-showcase-nav-head">
                                <i class="fas fa-star" aria-hidden="true"></i> Catégories pour vous
                            </div>
                            <ul class="mp-showcase-nav-list">
                                <?php foreach ($mp_nav_cats as $ni => $nrow): ?>
                                    <?php
                                    $nlabel = (string) ($nrow['nom'] ?? '');
                                    if ($nlabel === '') {
                                        continue;
                                    }
                                    $sn_ico = $mp_nav_sidebar_icons[$ni % count($mp_nav_sidebar_icons)];
                                    if ($mp_nav_is_generale) {
                                        $nhref = 'produits.php?recherche=' . rawurlencode($nlabel);
                                    } else {
                                        $nid = (int) ($nrow['id'] ?? 0);
                                        $nhref = $nid > 0 ? 'categorie.php?id=' . $nid : 'produits.php';
                                    }
                                    ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($nhref); ?>">
                                            <span class="mp-sn-ico"><i class="fas <?php echo htmlspecialchars($sn_ico); ?>"
                                                    aria-hidden="true"></i></span>
                                            <?php echo htmlspecialchars($nlabel); ?>
                                            <span class="mp-sn-chev" aria-hidden="true"><i
                                                    class="fas fa-chevron-right"></i></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="mp-showcase-nav-foot">
                                <a href="produits.php">Tout le catalogue</a>
                            </div>
                        </aside>
                    <?php endif; ?>

                    <div class="mp-showcase-center">
                        <div class="mp-showcase-center-top">
                            <div>
                                <h2>Recherches fréquentes</h2>
                                <p>Articles issus des requêtes les plus saisies sur le catalogue (ordre aléatoire).</p>
                            </div>
                            <div class="mp-trend-nav">
                                <button type="button" class="mp-trend-prev" aria-label="Faire défiler vers la gauche">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="mp-trend-next" aria-label="Faire défiler vers la droite">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mp-trend-scroll" id="mpTrendScroll">
                            <?php if (empty($produits_tendance)): ?>
                                <p class="mp-empty" style="min-width:100%;margin:0;">Aucun produit à afficher.</p>
                            <?php else: ?>
                                <?php foreach ($produits_tendance as $produit): ?>
                                    <?php
                                    $tid = (int) ($produit['id'] ?? 0);
                                    if ($tid <= 0) {
                                        continue;
                                    }
                                    $_nom_raw = (string) ($produit['nom'] ?? '');
                                    $tsub = !empty($produit['categorie_nom'])
                                        ? (string) $produit['categorie_nom']
                                        : (function_exists('mb_substr') ? mb_substr($_nom_raw, 0, 32) : substr($_nom_raw, 0, 32));
                                    ?>
                                    <div class="mp-trend-card">
                                        <span class="mp-trend-label">Recherché</span>
                                        <span class="mp-trend-sub"><?php echo htmlspecialchars($tsub); ?></span>
                                        <a class="mp-trend-img-link" href="produit.php?id=<?php echo $tid; ?>">
                                            <img src="/upload/<?php echo htmlspecialchars($produit['image_principale'] ?? 'produit1.jpg'); ?>"
                                                alt="" loading="lazy" onerror="this.src='/image/produit1.jpg'">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="mp-showcase-spotlight" aria-labelledby="mp-sp-title">
                        <div class="mp-sp-content">
                            <span class="mp-sp-badge"><i class="fas fa-star" aria-hidden="true"></i> Communauté</span>
                            <h2 id="mp-sp-title">Mieux notés</h2>
                        </div>
                        <div class="mp-sp-visual" id="mpSpotlightVisual">
                            <?php if (!empty($spotlight_slides)): ?>
                            <div class="mp-sp-slides-stack" id="mpSpotlightSlides" data-spotlight-interval="60000" role="region" aria-label="Produits mis en avant" aria-live="polite">
                                <?php foreach ($spotlight_slides as $sidx => $slide_products): ?>
                                <div class="mp-sp-slide<?php echo (int) $sidx === 0 ? ' is-active' : ''; ?>"
                                    data-spotlight-slide="<?php echo (int) $sidx; ?>"
                                    <?php if ((int) $sidx !== 0): ?>aria-hidden="true"<?php else: ?>aria-hidden="false"<?php endif; ?>>
                                    <div class="mp-sp-grid<?php echo count($slide_products) < 2 ? ' mp-sp-grid--single' : ''; ?>" role="list">
                                        <?php foreach ($slide_products as $sp):
                                            $spid = (int) ($sp['id'] ?? 0);
                                            if ($spid <= 0) {
                                                continue;
                                            }
                                            ?>
                                        <a class="mp-sp-tile" role="listitem" href="produit.php?id=<?php echo $spid; ?>">
                                            <img src="/upload/<?php echo htmlspecialchars($sp['image_principale'] ?? 'produit1.jpg'); ?>"
                                                alt="<?php echo htmlspecialchars($sp['nom'] ?? 'Produit'); ?>"
                                                loading="<?php echo (int) $sidx === 0 ? 'eager' : 'lazy'; ?>"
                                                onerror="this.src='/image/produit1.jpg'">
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($mp_spotlight_img): ?>
                                <img src="<?php echo htmlspecialchars($mp_spotlight_img, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Aperçu de la sélection" onerror="this.style.display='none'">
                            <?php else: ?>
                                <span class="mp-sp-fallback-ico" aria-hidden="true"><i class="fas fa-store"></i></span>
                            <?php endif; ?>
                        </div>
                        <a class="mp-sp-cta" href="produits.php"><span>En savoir plus</span><i
                                class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <?php if (!empty($spotlight_slides) && count($spotlight_slides) > 1): ?>
                        <div class="mp-sp-dots" id="mpSpotlightDots" role="group" aria-label="Navigation des sélections de produits">
                            <?php foreach ($spotlight_slides as $didx => $_s): ?>
                            <button type="button" class="mp-sp-dot<?php echo (int) $didx === 0 ? ' is-active' : ''; ?>"
                                id="mpSpotlightDot<?php echo (int) $didx; ?>"
                                data-slide-to="<?php echo (int) $didx; ?>"
                                aria-selected="<?php echo (int) $didx === 0 ? 'true' : 'false'; ?>"
                                aria-label="Sélection <?php echo (int) $didx + 1; ?> sur <?php echo (int) count($spotlight_slides); ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php if (!empty($spotlight_slides) && count($spotlight_slides) > 1): ?>
                    <script>
                    (function () {
                        var stack = document.getElementById('mpSpotlightSlides');
                        if (!stack) { return; }
                        var ms = parseInt(stack.getAttribute('data-spotlight-interval') || '60000', 10);
                        if (isNaN(ms) || ms < 5000) { ms = 60000; }
                        var slides = stack.querySelectorAll('.mp-sp-slide');
                        var dots = document.querySelectorAll('#mpSpotlightDots .mp-sp-dot');
                        if (slides.length < 2) { return; }
                        var n = slides.length;
                        var cur = 0;
                        function go(i) {
                            i = ((i % n) + n) % n;
                            cur = i;
                            for (var j = 0; j < n; j++) {
                                var on = (j === i);
                                slides[j].classList.toggle('is-active', on);
                                slides[j].setAttribute('aria-hidden', on ? 'false' : 'true');
                                if (dots[j]) {
                                    dots[j].classList.toggle('is-active', on);
                                    dots[j].setAttribute('aria-selected', on ? 'true' : 'false');
                                }
                            }
                        }
                        setInterval(function () { go(cur + 1); }, ms);
                        for (var d = 0; d < dots.length; d++) {
                            (function (idx) {
                                dots[idx].addEventListener('click', function () { go(idx); });
                            })(d);
                        }
                    })();
                    </script>
                    <?php endif; ?>
                    </aside>
                </div>
            </section>
        </div>

        <div class="mp-shell">
            <section class="mp-promo-b2b" aria-labelledby="mp-b2b-title">
                <div class="mp-promo-b2b-inner">
                    <div class="mp-promo-copy">
                        <span class="mp-promo-ico" aria-hidden="true"><i class="fas fa-trophy"></i></span>
                        <h2 id="mp-b2b-title">Top des ventes</h2>
                        <ul class="mp-promo-list">
                            <li><i class="fas fa-check" aria-hidden="true"></i> Articles les plus commandés sur la marketplace (volumes réels de vente)</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Découvrez ce que les professionnels et particuliers achètent le plus</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Sélection mélangée aléatoirement à chaque visite</li>
                        </ul>
                        <a class="mp-promo-cta" href="produits.php">Découvrir dès maintenant</a>
                    </div>
                    <div class="mp-strip-scroll" id="mpStripScroll">
                        <?php foreach ($produits_strip as $sproduit): ?>
                            <?php
                            $sid = (int) ($sproduit['id'] ?? 0);
                            if ($sid <= 0) {
                                continue;
                            }
                            $slabel = !empty($sproduit['categorie_nom'])
                                ? (string) $sproduit['categorie_nom']
                                : 'Pièce détachée';
                            $spx = !empty($sproduit['prix_promotion']) && $sproduit['prix_promotion'] < $sproduit['prix']
                                ? (float) $sproduit['prix_promotion'] : (float) ($sproduit['prix'] ?? 0);
                            $sstock = isset($sproduit['stock']) ? (int) $sproduit['stock'] : null;
                            ?>
                            <a class="mp-strip-card" href="produit.php?id=<?php echo $sid; ?>">
                                <div class="mp-strip-card-img">
                                    <img src="/upload/<?php echo htmlspecialchars($sproduit['image_principale'] ?? 'produit1.jpg'); ?>"
                                        alt="" loading="lazy" onerror="this.src='/image/produit1.jpg'">
                                </div>
                                <div class="mp-strip-pill"><?php
                                $sl_short = function_exists('mb_substr') ? mb_substr($slabel, 0, 22) : substr($slabel, 0, 22);
                                echo htmlspecialchars($sl_short);
                                ?></div>
                                <div class="mp-strip-body">
                                    <p class="mp-strip-price"><?php echo number_format($spx, 0, ',', ' '); ?> FCFA</p>
                                    <p class="mp-strip-moq">
                                        <?php echo ($sstock !== null && $sstock >= 0) ? 'Stock : ' . $sstock : 'Marketplace'; ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="mp-shell">

            <?php if (!empty($logos_carousel)): ?>
                <div class="mp-logos-wrap">
                    <section class="marques-section logos-section" data-aos="fade-up" data-aos-duration="600">
                        <div class="marques-container">
                            <button type="button" class="marques-nav marques-nav-prev logos-nav-prev"
                                aria-label="Logos précédents">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="marques-carousel owl-carousel logos-owl">
                                <?php foreach ($logos_carousel as $logo): ?>
                                    <?php
                                    $logo_path = '/image/produit1.jpg';
                                    if (!empty($logo['image'])) {
                                        $upload_path = '/upload/' . htmlspecialchars($logo['image']);
                                        $file_path = __DIR__ . '/upload/' . $logo['image'];
                                        if (file_exists($file_path)) {
                                            $logo_path = $upload_path;
                                        }
                                    }
                                    ?>
                                    <div class="marque-item marque-item-logo">
                                        <div class="marque-logo-wrap">
                                            <img src="<?php echo $logo_path; ?>" alt="Logo partenaire"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <span class="marque-fallback" style="display: none;"><i
                                                    class="fas fa-image"></i></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="marques-nav marques-nav-next logos-nav-next"
                                aria-label="Logos suivants">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            <section class="mp-pair" aria-label="Classements et nouveautés">
                <div class="mp-pair-grid">
                    <article class="mp-panel">
                        <header class="mp-panel-head">
                            <div>
                                <h2>Produits au top du classement</h2>
                                <p>Suivez les tendances grâce aux articles les plus consultés sur la plateforme.</p>
                            </div>
                            <a class="mp-panel-more" href="produits.php">En savoir plus &gt;</a>
                        </header>
                        <div class="mp-panel-products">
                            <?php if (empty($produits_top_panneau)): ?>
                                <p class="mp-empty" style="grid-column:1/-1;">Aucun produit à mettre en avant pour le
                                    moment.</p>
                            <?php else: ?>
                                <?php
                                foreach ($produits_top_panneau as $produit) {
                                    $return_url = $return_url_mp;
                                    require $partial_top;
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    </article>
                    <article class="mp-panel">
                        <header class="mp-panel-head">
                            <div>
                                <h2>Nouveautés</h2>
                                <p>Les derniers articles publiés par les vendeurs — restez en avance sur les arrivages.
                                </p>
                            </div>
                            <a class="mp-panel-more" href="produits.php?tri=date">En savoir plus &gt;</a>
                        </header>
                        <div class="mp-panel-products">
                            <?php if (empty($produits_new_panneau)): ?>
                                <p class="mp-empty" style="grid-column:1/-1;">Pas de nouveautés pour le moment.</p>
                            <?php else: ?>
                                <?php
                                foreach ($produits_new_panneau as $produit) {
                                    $return_url = $return_url_mp;
                                    require $partial_newc;
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mp-block" aria-labelledby="mp-all-heading">
                <header class="mp-block-head">
                    <h2 id="mp-all-heading">Tous les produits sur la plateforme</h2>
                    <?php if ($total_produits > 0): ?>
                        <a class="mp-block-more" href="produits.php">Catalogue (<?php echo (int) $total_produits; ?>)</a>
                    <?php endif; ?>
                </header>
                <div class="mp-grid" id="produits-container">
                    <?php if (empty($produits_tous)): ?>
                        <p class="mp-empty">Aucun produit publié pour le moment.</p>
                    <?php else: ?>
                        <?php
                        foreach ($produits_tous as $produit) {
                            $return_url = $return_url_mp;
                            require $card_partial;
                        }
                        ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($produits_tous) && $total_produits > count($produits_tous)): ?>
                    <div class="mp-footer-cta">
                        <a href="produits.php"><i class="fas fa-th" aria-hidden="true"></i> Afficher tous les produits</a>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>
    <?php include('footer.php') ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script>
        (function () {
            function finishAos() {
                document.documentElement.classList.remove('aos-not-ready');
            }

            function runAos() {
                if (typeof AOS === 'undefined') {
                    finishAos();
                    return;
                }
                AOS.init({
                    duration: 800,
                    once: true,
                    offset: 24,
                    disable: function () {
                        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    }
                });
                requestAnimationFrame(function () {
                    requestAnimationFrame(finishAos);
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', runAos);
            } else {
                runAos();
            }
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('mpTrendScroll');
            var prev = document.querySelector('.mp-trend-prev');
            var next = document.querySelector('.mp-trend-next');
            if (!el) return;
            var step = 220;
            if (prev) {
                prev.addEventListener('click', function () {
                    el.scrollBy({ left: -step, behavior: 'smooth' });
                });
            }
            if (next) {
                next.addEventListener('click', function () {
                    el.scrollBy({ left: step, behavior: 'smooth' });
                });
            }
        });
    </script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/owl.carousel.js"></script>
    <script src="/js/owl.animate.js"></script>
    <script src="/js/owl.autoplay.js"></script>

    <script>
        $(document).ready(function () {

            $('.slider1').owlCarousel({
                items: 2,
                loop: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 4000,
                animateOut: 'slideOutDown',
                animateIn: 'flipInX',
                smartSpeed: 400,
                stagePadding: 0,
                nav: true,
                navText: ['<i class="fa-solid fa-chevron-left"></i>',
                    '<i class="fa-solid fa-chevron-right"></i>'
                ]
            });
            var carousel2 = $('.slider1').owlCarousel();
            $('.owl-next2').click(function () {
                carousel2.trigger('next.owl.carousel');
            })
            $('.owl-prev2').click(function () {
                carousel2.trigger('prev.owl.carousel');
            })

            // Nouveaux produits et Produits populaires : Owl désactivé, toujours en mode flex-wrap

            if ($('.slider-area').length && $('.slider-area .slider-item').length) {
                $('.slider-area').owlCarousel({
                    items: 1,
                    loop: true,
                    dots: true,
                    autoplay: true,
                    autoplayTimeout: 6000,
                    animateOut: 'slideOutDown',
                    animateIn: 'flipInX',
                    smartSpeed: 800,
                    stagePadding: 1,
                    nav: true,
                    navText: ['<i class="fa-solid fa-chevron-left"></i>',
                        '<i class="fa-solid fa-chevron-right"></i>'
                    ]
                });
            }
            var carousel2 = $('.carousel2').owlCarousel();
            $('.owl-next2').click(function () {
                carousel2.trigger('next.owl.carousel');
            })
            $('.owl-prev2').click(function () {
                carousel2.trigger('prev.owl.carousel');
            })


            // Carrousel des catégories
            if ($('.marques-owl').length && $('.marques-owl .marque-item').length) {
                var marquesCarousel = $('.marques-owl').owlCarousel({
                    items: 4,
                    loop: true,
                    dots: false,
                    nav: false,
                    margin: 28,
                    stagePadding: 15,
                    autoplay: true,
                    autoplayTimeout: 3000,
                    autoplayHoverPause: true,
                    smartSpeed: 500,
                    responsive: {
                        0: {
                            items: 2,
                            margin: 16
                        },
                        480: {
                            items: 3,
                            margin: 20
                        },
                        768: {
                            items: 4,
                            margin: 28
                        },
                        992: {
                            items: 5,
                            margin: 28
                        }
                    }
                });
                $('.marques-nav-prev').on('click', function () {
                    marquesCarousel.trigger('prev.owl.carousel');
                });
                $('.marques-nav-next').on('click', function () {
                    marquesCarousel.trigger('next.owl.carousel');
                });
            }

            // Carrousel des logos partenaires
            if ($('.logos-owl').length && $('.logos-owl .marque-item').length) {
                var logosCarousel = $('.logos-owl').owlCarousel({
                    items: 4,
                    loop: true,
                    dots: false,
                    nav: false,
                    margin: 28,
                    stagePadding: 15,
                    autoplay: true,
                    autoplayTimeout: 3000,
                    autoplayHoverPause: true,
                    smartSpeed: 500,
                    responsive: {
                        0: {
                            items: 2,
                            margin: 16
                        },
                        480: {
                            items: 3,
                            margin: 20
                        },
                        768: {
                            items: 4,
                            margin: 28
                        },
                        992: {
                            items: 5,
                            margin: 28
                        }
                    }
                });
                $('.logos-nav-prev').on('click', function () {
                    logosCarousel.trigger('prev.owl.carousel');
                });
                $('.logos-nav-next').on('click', function () {
                    logosCarousel.trigger('next.owl.carousel');
                });
            }

            // Carrousel catégories : 1 item < 350px, 2 items >= 350px sur mobile
            $('.categorie').owlCarousel({
                items: 5,
                loop: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 2000,
                autoplaySpeed: 3000,
                animateOut: 'slideOutDown',
                animateIn: 'flipInX',
                smartSpeed: 1200,
                stagePadding: 20,
                margin: 15,
                nav: true,
                navText: ['<i class="fa-solid fa-chevron-left"></i>',
                    '<i class="fa-solid fa-chevron-right"></i>'
                ],
                responsive: {
                    0: {
                        items: 1,
                        stagePadding: 10,
                        margin: 10,
                        nav: true,
                        dots: true
                    },
                    350: {
                        items: 2,
                        stagePadding: 10,
                        margin: 12,
                        nav: true,
                        dots: true
                    },
                    576: {
                        items: 2,
                        stagePadding: 15,
                        margin: 15,
                        nav: true,
                        dots: true
                    },
                    768: {
                        items: 3,
                        stagePadding: 15,
                        margin: 15,
                        nav: true,
                        dots: true
                    },
                    992: {
                        items: 4,
                        stagePadding: 20,
                        margin: 15,
                        nav: true,
                        dots: true
                    },
                    1200: {
                        items: 4,
                        stagePadding: 20,
                        margin: 15,
                        nav: true,
                        dots: true
                    }
                }
            });
            var carousel2 = $('.carousel2').owlCarousel();
            $('.owl-next2').click(function () {
                carousel2.trigger('next.owl.carousel');
            })
            $('.owl-prev2').click(function () {
                carousel2.trigger('prev.owl.carousel');
            })


        });
    </script>

    <script>
        // Slider vidéo simple en JavaScript vanilla
        document.addEventListener('DOMContentLoaded', function () {
            var slider = document.getElementById('videosSlider');
            var prevBtn = document.getElementById('videosPrev');
            var nextBtn = document.getElementById('videosNext');
            var dotsContainer = document.getElementById('videosDots');
            var autoplayInterval;
            var autoplayDelay = 8000; // 8 secondes

            if (!slider) {
                return; // Pas de slider, ne rien faire
            }

            var cards = slider.querySelectorAll('.video-card');
            if (cards.length === 0) {
                return; // Pas de vidéos
            }

            var currentIndex = 0;
            var itemsPerView = 1; // Par défaut mobile
            var dots = [];

            // Fonction pour déterminer le nombre d'éléments visibles
            function getItemsPerView() {
                var width = window.innerWidth;
                if (width >= 992) {
                    return 3; // Grand écran : 3 vidéos
                } else if (width >= 768) {
                    return 2; // Tablette : 2 vidéos
                }
                return 1; // Mobile : 1 vidéo
            }

            // Fonction pour créer les dots
            function createDots() {
                if (!dotsContainer) return;

                itemsPerView = getItemsPerView();
                var totalPages = Math.ceil(cards.length / itemsPerView);

                dotsContainer.innerHTML = '';
                dots = [];

                for (var i = 0; i < totalPages; i++) {
                    var dot = document.createElement('span');
                    dot.className = 'dot';
                    if (i === 0) {
                        dot.classList.add('active');
                    }
                    dot.setAttribute('data-page', i);
                    dot.addEventListener('click', function () {
                        var page = parseInt(this.getAttribute('data-page'));
                        currentIndex = page * itemsPerView;
                        updateSlider();
                        stopAutoplay();
                        startAutoplay();
                    });
                    dotsContainer.appendChild(dot);
                    dots.push(dot);
                }
            }

            // Fonction pour calculer le nombre de slides possibles
            function getMaxIndex() {
                itemsPerView = getItemsPerView();
                return Math.max(0, cards.length - itemsPerView);
            }

            // Fonction pour mettre à jour la position du slider
            function updateSlider() {
                var maxIndex = getMaxIndex();
                if (currentIndex > maxIndex) {
                    currentIndex = maxIndex;
                }

                if (cards.length === 0) return;

                // Calculer la translation en fonction de la largeur des cartes
                var cardWidth = cards[0].offsetWidth;
                var gap = 20;
                var translateX = -(currentIndex * (cardWidth + gap));

                slider.style.transform = 'translateX(' + translateX + 'px)';

                // Mettre à jour les dots
                var dotIndex = Math.floor(currentIndex / itemsPerView);
                dots.forEach(function (dot, index) {
                    dot.classList.remove('active');
                    if (index === dotIndex) {
                        dot.classList.add('active');
                    }
                });

                // Afficher/masquer les boutons selon la position
                if (prevBtn) {
                    prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
                }
                if (nextBtn) {
                    nextBtn.style.display = currentIndex >= maxIndex ? 'none' : 'flex';
                }
            }

            function nextSlide() {
                var maxIndex = getMaxIndex();
                if (currentIndex < maxIndex) {
                    currentIndex += itemsPerView;
                } else {
                    currentIndex = 0; // Retour au début
                }
                updateSlider();
            }

            function prevSlide() {
                var maxIndex = getMaxIndex();
                if (currentIndex > 0) {
                    currentIndex -= itemsPerView;
                    if (currentIndex < 0) {
                        currentIndex = maxIndex; // Aller à la fin
                    }
                } else {
                    currentIndex = maxIndex; // Aller à la fin
                }
                updateSlider();
            }

            function startAutoplay() {
                autoplayInterval = setInterval(function () {
                    nextSlide();
                }, autoplayDelay);
            }

            function stopAutoplay() {
                if (autoplayInterval) {
                    clearInterval(autoplayInterval);
                }
            }

            // Événements pour les boutons
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    nextSlide();
                    stopAutoplay();
                    startAutoplay();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    prevSlide();
                    stopAutoplay();
                    startAutoplay();
                });
            }

            // Pause autoplay au survol
            var sliderWrapper = document.querySelector('.videos-slider-wrapper');
            if (sliderWrapper) {
                sliderWrapper.addEventListener('mouseenter', stopAutoplay);
                sliderWrapper.addEventListener('mouseleave', startAutoplay);
            }

            // Gérer le redimensionnement de la fenêtre
            var resizeTimeout;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function () {
                    currentIndex = 0;
                    createDots();
                    updateSlider();
                }, 250);
            });

            // Initialiser
            createDots();
            updateSlider();
            if (cards.length > getItemsPerView()) {
                startAutoplay();
            }
        });
    </script>

</body>

</html>
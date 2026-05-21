<?php
session_start();

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = "Conditions d'Utilisation - COLObanes";
$seo_description = "Conditions générales d'utilisation de COLObanes : services marketplace, comptes, commandes, livraisons, responsabilités, propriété intellectuelle et résolution des litiges.";
$seo_canonical = $base . '/conditions-utilisation.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <style>
        .legal-page {
            max-width: 920px;
            margin: 40px auto;
            padding: 40px 24px 80px;
            background: var(--blanc);
            border-radius: 10px;
            box-shadow: var(--ombre-douce);
        }
        .legal-page h1 {
            color: var(--titres);
            font-size: 32px;
            margin-bottom: 16px;
            text-align: center;
            border-bottom: 3px solid var(--couleur-dominante);
            padding-bottom: 15px;
        }
        .legal-page .legal-updated {
            text-align: center;
            color: var(--gris-moyen);
            font-size: 14px;
            margin-bottom: 28px;
        }
        .legal-page h2 {
            color: var(--titres);
            font-size: 1.25rem;
            margin-top: 36px;
            margin-bottom: 14px;
            padding-top: 8px;
            border-top: 1px solid var(--noir-pale);
        }
        .legal-page h2:first-of-type {
            border-top: none;
            padding-top: 0;
        }
        .legal-page h3 {
            color: var(--gris-fonce);
            font-size: 1.05rem;
            margin-top: 22px;
            margin-bottom: 10px;
        }
        .legal-page p, .legal-page li {
            color: var(--texte-fonce);
            font-size: 15px;
            line-height: 1.85;
            margin-bottom: 12px;
        }
        .legal-page p {
            text-align: justify;
        }
        .legal-page ul, .legal-page ol {
            margin: 12px 0 18px;
            padding-left: 28px;
        }
        .legal-page li {
            margin-bottom: 8px;
        }
        .legal-page a {
            color: var(--couleur-dominante);
            text-decoration: none;
        }
        .legal-page a:hover {
            color: var(--orange);
            text-decoration: underline;
        }
        .legal-toc {
            background: var(--fond-secondaire);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 20px 22px;
            margin-bottom: 32px;
        }
        .legal-toc strong {
            display: block;
            margin-bottom: 12px;
            color: var(--titres);
        }
        .legal-toc ol {
            margin: 0;
            padding-left: 22px;
            counter-reset: toc;
        }
        .legal-toc li {
            margin-bottom: 6px;
            font-size: 14px;
        }
        .legal-note {
            font-size: 13px;
            color: var(--gris-moyen);
            font-style: italic;
            text-align: left;
            margin-top: 20px;
            padding: 14px;
            background: var(--bleu-pale);
            border-radius: 8px;
            border-left: 4px solid var(--couleur-dominante);
        }
        .legal-cross {
            margin-top: 28px;
            padding: 16px;
            border-radius: 8px;
            background: var(--fond-secondaire);
            font-size: 14px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            padding: 12px 24px;
            background: var(--couleur-dominante);
            color: var(--texte-clair) !important;
            text-decoration: none !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: var(--orange);
            color: var(--texte-clair) !important;
        }
    </style>
</head>
<body>
    <?php include('nav_bar.php'); ?>

    <div class="legal-page">
        <h1><i class="fas fa-file-contract" aria-hidden="true"></i> Conditions générales d'utilisation</h1>
        <p class="legal-updated"><strong>Dernière mise à jour :</strong> <?php echo htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8'); ?></p>

        <p>
            Les présentes conditions générales d'utilisation (« <strong>CGU</strong> ») régissent l'accès et l'usage du site et des services proposés par la plateforme
            <strong>COLObanes</strong>, marketplace en ligne mettant en relation des acheteurs et des vendeurs ou boutiques partenaires établis ou livrant au Sénégal.
            En créant un compte, en parcourant le site, en passant commande ou en utilisant toute fonctionnalité associée (y compris l'application mobile ou la version web progressive le cas échéant),
            vous reconnaissez avoir lu, compris et accepté sans réserve les présentes CGU, ainsi que notre
            <a href="/politique-confidentialite.php">Politique de confidentialité</a>, qui fait partie intégrante du contrat vous liant à COLObanes.
        </p>

        <div class="legal-toc">
            <strong>Sommaire</strong>
            <ol>
                <li><a href="#cgu-1">Objet, définitions et champ d'application</a></li>
                <li><a href="#cgu-2">Édition du service et contact</a></li>
                <li><a href="#cgu-3">Création de compte, identifiants et sécurité</a></li>
                <li><a href="#cgu-4">Description des services et rôle de la plateforme</a></li>
                <li><a href="#cgu-4b">Application mobile et autorisations système</a></li>
                <li><a href="#cgu-5">Règles d'utilisation acceptables</a></li>
                <li><a href="#cgu-6">Offres, prix, disponibilité et erreurs manifestes</a></li>
                <li><a href="#cgu-7">Commande, validation et preuve</a></li>
                <li><a href="#cgu-8">Paiement</a></li>
                <li><a href="#cgu-9">Livraison, réception et transfert des risques</a></li>
                <li><a href="#cgu-10">Annulation, rétractation, échanges et garanties</a></li>
                <li><a href="#cgu-11">Propriété intellectuelle</a></li>
                <li><a href="#cgu-12">Liens hypertextes et services tiers</a></li>
                <li><a href="#cgu-13">Données personnelles</a></li>
                <li><a href="#cgu-14">Force majeure</a></li>
                <li><a href="#cgu-15">Limitation et exclusion de responsabilité</a></li>
                <li><a href="#cgu-16">Modification des CGU, durée et résiliation</a></li>
                <li><a href="#cgu-17">Droit applicable et règlement des litiges</a></li>
            </ol>
        </div>

        <h2 id="cgu-1">1. Objet, définitions et champ d'application</h2>
        <p>
            Les CGU définissent les droits et obligations des utilisateurs du service COLObanes. Sont notamment concernés : tout visiteur du site,
            tout acheteur ou client enregistré, tout professionnel ou vendeur exploitant une boutique présente sur la marketplace, ainsi que les personnes agissant pour leur compte
            (mandataires, préposés) dans la limite des pouvoirs qui leur sont confiés.
        </p>
        <h3>1.1 Définitions</h3>
        <ul>
            <li><strong>Plateforme ou Service</strong> : l'ensemble des pages web, API, outils d'administration, espaces « marchand » ou « vendeur », ainsi que les applications logicielles publiées sous la marque COLObanes permettant d'accéder aux mêmes services.</li>
            <li><strong>Utilisateur</strong> : toute personne physique ou morale utilisant la Plateforme.</li>
            <li><strong>Client</strong> : Utilisateur passant une commande ou ouvrant un compte acheteur.</li>
            <li><strong>Vendeur ou Boutique partenaire</strong> : professionnel ou entité commercialisant des produits via une vitrine hébergée sur COLObanes.</li>
            <li><strong>Contenu utilisateur</strong> : textes, images, avis, messages adressés au support, fiches produits fournies par un Vendeur, etc.</li>
        </ul>
        <h3>1.2 Champ d'application matériel et temporel</h3>
        <p>
            Les CGU s'appliquent à chaque connexion au Service, quelle que soit l'origine géographique de l'Utilisateur, dès lors que la commande porte sur une livraison ou une relation commerciale relevant du périmètre d'intervention des vendeurs inscrits.
            COLObanes se réserve le droit de refuser l'accès au Service à toute personne ne respectant pas les CGU ou la réglementation applicable.
        </p>

        <h2 id="cgu-2">2. Édition du service et contact</h2>
        <p>
            Le site <strong>COLObanes</strong> est exploité en vue de fournir une infrastructure technique et commerciale de marketplace.
            Pour toute question relative aux présentes CGU ou au fonctionnement général de la Plateforme :
        </p>
        <ul>
            <li><strong>Adresse postale d'accueil (siège d'exploitation associé au site)</strong> : Rond Point Colobane, Dakar, Sénégal ;</li>
            <li><strong>Courriel</strong> : <a href="mailto:contact@colobanes.com">contact@colobanes.com</a>.</li>
        </ul>
        <p>
            Les coordonnées affichées sur le site au moment de votre requête prévalent en cas de mise à jour ultérieure non encore reflétée dans le présent document.
        </p>

        <h2 id="cgu-3">3. Création de compte, identifiants et sécurité</h2>
        <h3>3.1 Exactitude des informations</h3>
        <p>
            L'Utilisateur s'engage à fournir des informations sincères, complètes et à jour (identité, coordonnées, adresse de livraison, numéro de téléphone joignable pour le suivi de commande).
            Toute fausse déclaration, identité usurpée ou tentative d'usurpation pourra entraîner la suspension immédiate du compte et, le cas échéant, le dépôt de plainte auprès des autorités.
        </p>
        <h3>3.2 Confidentialité des identifiants</h3>
        <p>
            Les identifiants (adresse e-mail ou identifiant de connexion, mot de passe) sont strictement personnels. L'Utilisateur est seul responsable des actions publiées ou des commandes passées depuis son compte,
            sauf preuve d'une faille de sécurité imputable à COLObanes dûment établie. En cas de suspicion d'utilisation frauduleuse, l'Utilisateur doit sans délai modifier son mot de passe et informer le support.
        </p>
        <h3>3.3 Suspension et clôture</h3>
        <p>
            COLObanes peut suspendre ou clôturer un compte en cas de manquement grave ou répété aux CGU, de fraude, d'atteinte aux droits de tiers, d'impayé ou d'injonction administrative ou judiciaire.
            La clôture du compte client n'efface pas les obligations nées avant la clôture (ex. : livraisons en cours, litiges, factures dues).
        </p>

        <h2 id="cgu-4">4. Description des services et rôle de la plateforme</h2>
        <p>
            COLObanes met à disposition un environnement numérique permettant la publication de catalogues produits par des boutiques indépendantes, la constitution d'un panier, la passation de commandes,
            la coordination logistique dans les limites décrites sur le site, et des échanges d'informations entre Clients et Vendeurs dans le cadre des commandes.
        </p>
        <h3>4.1 Statut d'intermédiaire technique</h3>
        <p>
            Sauf mention expresse contraire sur un article ou une offre donnée, <strong>le contrat de vente de plein droit est conclu entre le Client et le Vendeur concerné</strong>.
            COLObanes agit comme opérateur de la Plateforme : hébergement des contenus, mise en relation, outils de commande, et le cas échéant facturation ou perception de paiements selon les modalités affichées lors de la commande.
            Les Vendeurs demeurent responsables du respect des réglementations sectorielles applicables à leurs produits (hygiène, étiquetage, garanties, licences, etc.).
        </p>
        <h3>4.2 Absence de conseil personnalisé</h3>
        <p>
            Les contenus, notices, photographies et descriptifs publiés n'ont pas vocation à se substituer à un avis médical, juridique, technique ou professionnel. Le Client reste seul juge de l'adéquation d'un produit à ses besoins.
        </p>

        <h2 id="cgu-4b">4 bis. Application mobile et autorisations système (iOS / Android)</h2>
        <p>
            L'application mobile COLObanes donne accès aux mêmes services que le site web. Elle peut solliciter, <strong>uniquement sur votre initiative</strong>, les autorisations suivantes&nbsp;:
        </p>
        <ul>
            <li><strong>Caméra et photothèque</strong> — pour prendre ou choisir une photo (profil, produit vendeur, pièce jointe). Exemple&nbsp;: photographier un article mis en vente.</li>
            <li><strong>Localisation (pendant l'utilisation)</strong> — pour afficher ou confirmer un point de livraison sur la carte lors d'une commande. Exemple&nbsp;: positionner votre adresse de livraison à Dakar.</li>
            <li><strong>Notifications</strong> — pour vous informer du statut de vos commandes ou de messages liés à votre compte.</li>
        </ul>
        <p>
            L'application <strong>ne demande pas l'accès au microphone</strong> et <strong>ne suit pas votre position en arrière-plan</strong>.
            Le refus d'une autorisation limite la fonction concernée sans empêcher la navigation générale ni la consultation du catalogue.
            Le détail des traitements de données liés à ces fonctions figure dans la
            <a href="/politique-confidentialite.php#priv-9">Politique de confidentialité (section&nbsp;9)</a>.
        </p>
        <p>
            En installant l'application depuis l'App Store ou Google Play, vous acceptez également les conditions propres à ces plateformes (Apple Media Services, Google Play) pour les téléchargements, mises à jour et achats intégrés le cas échéant.
        </p>

        <h2 id="cgu-5">5. Règles d'utilisation acceptables</h2>
        <p>Il est notamment interdit de :</p>
        <ul>
            <li>contourner ou attaquer les mesures de sécurité du site, d'autres comptes ou infrastructures ;</li>
            <li>extraire massivement des données (scraping non autorisé), surcharger les serveurs, utiliser des robots de manière abusive ;</li>
            <li>publier des contenus illicites, diffamatoires, discriminatoires, violents, obscènes ou portant atteinte aux droits de propriété intellectuelle ;</li>
            <li>usurper l'identité d'un tiers ou d'une entreprise ;</li>
            <li>utiliser la Plateforme pour du démarchage commercial non sollicité, du spam ou des chaînes de revente non conformes aux conditions marchandes ;</li>
            <li>tenter de manipuler les avis, les classements ou les Indicateurs de disponibilité et de prix.</li>
        </ul>
        <p>Toute violation pourra donner lieu à des poursuites civiles ou pénales, indépendamment des sanctions contractuelles.</p>

        <h2 id="cgu-6">6. Offres, prix, disponibilité et erreurs manifestes</h2>
        <p>
            Les prix sont en principe affichés en <strong>franc CFA (FCFA)</strong>, toutes taxes et frais inclus lorsque la loi l'exige, avec mention distincte des frais de livraison avant validation du paiement.
            Les photographies et descriptions sont fournies par les Vendeurs ou par COLObanes en tant qu'hébergeur d'information ; des écarts mineurs de rendu (couleur, taille apparente sur écran) peuvent exister sans engager la responsabilité de la Plateforme au-delà des obligations légales.
        </p>
        <p>
            En cas d'<strong>erreur manifeste</strong> de prix (prix dérisoire, incohérence avec une unité de mesure, bug d'affichage), COLObanes ou le Vendeur pourra refuser ou annuler la commande après information du Client et, le cas échéant, procéder au remboursement intégral des sommes déjà encaissées.
        </p>
        <p>
            La disponibilité affichée dépend des stocks déclarés par chaque Vendeur ; un article peut exceptionnellement être indisponible après validation dans l'hypothèse d'un pic de demande ou d'un écart d'inventaire. Le Client en est informé dans les meilleurs délais et peut opter pour un remboursement, un avoir ou un produit de remplacement selon les options présentées.
        </p>

        <h2 id="cgu-7">7. Commande, validation et preuve</h2>
        <p>
            La commande est formée par les étapes successives indiquées sur le parcours d'achat (panier, identification ou connexion, choix de livraison, acceptation des CGU et de la politique de confidentialité le cas échéant, confirmation et paiement lorsque le paiement est exigible à la commande).
            L'enregistrement électronique des données sur les serveurs de COLObanes, sous réserve de preuve contraire, fait foi quant au contenu de la commande et à sa date.
        </p>
        <h3>7.1 Personnalisation</h3>
        <p>
            Pour les produits sur mesure, l'Utilisateur est invité à vérifier orthographe, couleurs et options sélectionnées : une commande validée avec des options incorrectes peut ne pas donner lieu à un échange si la fabrication a débuté conformément aux instructions reçues.
        </p>

        <h2 id="cgu-8">8. Paiement</h2>
        <p>
            Les moyens de paiement acceptés (paiement à la livraison, mobile money, carte bancaire, virement ou tout autre mode) sont indiqués au moment du passage de commande.
            COLObanes ou ses prestataires de paiement agréés traitent les données de paiement conformément aux normes de sécurité en vigueur ; les coordonnées complètes de carte bancaire ne sont en principe pas conservées sur les serveurs marchands au-delà du nécessaire à la transaction (tokenisation ou redirection sécurisée selon l'intégration).
        </p>
        <p>
            Tout impayé, rétrofacturation frauduleuse ou contestation abusive pourra entraîner la suspension des comptes et le recouvrement des sommes dues majorées des frais raisonnablement engagés.
        </p>

        <h2 id="cgu-9">9. Livraison, réception et transfert des risques</h2>
        <p>
            Les zones desservies, délais indicatifs et tarifs sont précisés avant validation. Les délais sont fournis à titre indicatif sauf engagement ferme expresse du Vendeur ou de COLObanes sur une offre donnée.
            Le Client s'engage à être joignable au numéro indiqué et, le cas échéant, à faciliter l'accès au lieu de livraison (codes, étage, consignes). En cas d'absence répétée ou d'adresse incomplète, des frais de réexpédition ou de garde pourront être facturés selon les règles affichées ou le droit commun.
        </p>
        <p>
            Les risques de perte ou de détérioration des biens sont transférés au Client au moment de la remise physique au Client ou à un tiers mandaté, sauf disposition impérative contraire.
        </p>

        <h2 id="cgu-10">10. Annulation, rétractation, échanges et garanties</h2>
        <h3>10.1 Annulation avant expédition</h3>
        <p>
            Sauf mention contraire sur une offre promotionnelle ou un produit fabriqué sur commande, une demande d'annulation peut être acceptée tant que la préparation ou l'expédition n'a pas commencé.
            Les annulations sont traitées via les canaux indiqués dans l'espace « Mes commandes » ou par contact avec le support.
        </p>
        <h3>10.2 Droit de rétractation et exceptions</h3>
        <p>
            Le droit de rétractation applicable aux contrats conclus à distance dépend de la nature du produit et du droit applicable au contrat conclu avec le Vendeur. <strong>En règle générale, les denrées périssables, les produits d'hygiène ouverts, les biens personnalisés après fabrication, ou les scellés de logiciels ouverts peuvent être exclus</strong> du droit standard de rétractation lorsque la loi le prévoit.
            Le Client est invité à consulter les conditions affichées sur la fiche produit et, en cas de doute, à contacter le Vendeur ou le support avant achat.
        </p>
        <h3>10.3 Produits non conformes ou défectueux</h3>
        <p>
            Les garanties légales (notamment de conformité et des vices cachés, dans les conditions et délais prévus par la loi applicable) s'exercent à l'égard du <strong>vendeur professionnel</strong>.
            COLObanes peut faciliter la médiation ou la transmission de la réclamation au Vendeur sans pour autant se substituer à ce dernier sur le fond des obligations de garantie, sauf stipulation ou offre commerciale spécifique de COLObanes.
        </p>

        <h2 id="cgu-11">11. Propriété intellectuelle</h2>
        <p>
            L'ensemble des éléments distinctifs de la Plateforme COLObanes (structure, charte graphique générique, base de données structurée lorsque protégeable, marques, logos, noms de domaine, code logiciel spécifique à l'outil de marketplace) restent la propriété exclusive de leurs titulaires et sont protégés par le droit des marques, d'auteur et/ou le droit sui generis des bases de données.
        </p>
        <p>
            Les contenus déposés par les Vendeurs restent leur propriété intellectuelle ; en les publiant, ils concèdent à COLObanes une licence non exclusive, mondiale et gratuite d'exploitation pour les besoins d'hébergement, de promotion du Service et de traitement des commandes, pour la durée de mise en ligne et un délai raisonnable de retrait des caches techniques.
        </p>

        <h2 id="cgu-12">12. Liens hypertextes et services tiers</h2>
        <p>
            Le site peut contenir des liens vers des sites tiers (réseaux sociaux, partenaires logistiques, prestataires de paiement). COLObanes n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leurs contenus, politiques de confidentialité ou pratiques commerciales.
            L'activation d'un lien tiers se fait sous la seule responsabilité de l'Utilisateur.
        </p>

        <h2 id="cgu-13">13. Données personnelles</h2>
        <p>
            Le traitement des données à caractère personnel dans le cadre du Service est décrit de manière détaillée dans notre
            <a href="/politique-confidentialite.php">Politique de confidentialité</a>, notamment les sections relatives à l'<a href="/politique-confidentialite.php#priv-9">application mobile et aux permissions d'appareil</a>.
            Vous devez en prendre connaissance avant toute utilisation extensive du compte, du formulaire de commande ou des fonctions natives (caméra, localisation, notifications).
        </p>

        <h2 id="cgu-14">14. Force majeure</h2>
        <p>
            La responsabilité de COLObanes ne saurait être engagée en cas d'événement imprévisible, irrésistible et extérieur auquel il est raisonnablement impossible de remédier (catastrophe naturelle, décisions gouvernementales, grèves générales affectant les réseaux, pannes massives, cyber-attaques d'envergure nationale ou régionale, etc.),
            dans la mesure où l'exécution des obligations en cause est alors temporairement ou définitivement rendue impossible.
        </p>

        <h2 id="cgu-15">15. Limitation et exclusion de responsabilité</h2>
        <p>
            Dans les limites autorisées par la loi applicable, COLObanes ne pourra être tenue responsable des dommages indirects ou immatériels (perte de chance, trouble commercial, atteinte à l'image, perte de données sur un poste Utilisateur) résultant de l'utilisation ou de l'impossibilité d'utiliser le Service.
        </p>
        <p>
            La Plateforme est fournie « en l'état » ; COLObanes s'efforce d'assurer une disponibilité continue mais ne garantit pas l'absence totale d'interruptions (maintenance, mise à jour, incident technique).
        </p>

        <h2 id="cgu-16">16. Modification des CGU, durée et résiliation</h2>
        <p>
            COLObanes peut adapter les présentes CGU pour refléter l'évolution légale, jurisprudentielle, technique ou commerciale du Service. La version en vigueur est toujours celle publiée sur cette page, avec sa date de mise à jour.
            Pour les modifications substantielles susceptibles d'affecter sensiblement vos droits, une information par e-mail ou une alerte sur le site pourra être utilisée lorsque cela est techniquement possible.
            La poursuite d'utilisation du Service après entrée en vigueur des nouvelles CGU vaut acceptation, sauf si la loi impose une procédure de consentement renforcé.
        </p>

        <h2 id="cgu-17">17. Droit applicable et règlement des litiges</h2>
        <p>
            Sauf disposition impérative d'ordre public plus favorable au consommateur, les présentes CGU sont régies par le <strong>droit sénégalais</strong>.
            En cas de différend, vous êtes invité à contacter préalablement le service client afin de rechercher une solution amiable. À défaut d'accord dans un délai raisonnable, les tribunaux compétents du Sénégal seront seuls compétents,
            sous réserve d'éventuelles dispositions d'arbitrage ou de médiation obligatoire applicables à certaines catégories de litiges.
        </p>

        <p class="legal-note">
            Les présentes CGU visent à informer de manière transparente sur les règles d'usage du Service. Elles ne constituent pas un conseil juridique personnalisé. Pour toute situation complexe (litige élevé, contrat B2B spécifique), il est recommandé de consulter un professionnel du droit.
        </p>

        <div class="legal-cross">
            <strong>Documents associés :</strong>
            <a href="/politique-confidentialite.php">Politique de confidentialité</a>
        </div>

        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour
        </a>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>

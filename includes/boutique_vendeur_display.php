<?php
/**
 * Données publiques vendeur (vitrine) + surcharge CSS variables
 * Ne jamais exposer le hash mot de passe : utiliser boutique_vendeur_display_from_row uniquement sur champs métier.
 */

if (!function_exists('boutique_vendeur_display_from_row')) {
    /**
     * @param array $row Ligne admin (vendeur)
     * @return array<string, string>
     */
    function boutique_vendeur_display_from_row(array $row) {
        return [
            'boutique_nom' => trim((string) ($row['boutique_nom'] ?? '')),
            'nom' => trim((string) ($row['nom'] ?? '')),
            'prenom' => trim((string) ($row['prenom'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'telephone' => trim((string) ($row['telephone'] ?? '')),
            'boutique_logo' => trim((string) ($row['boutique_logo'] ?? '')),
            'boutique_couleur_principale' => trim((string) ($row['boutique_couleur_principale'] ?? '')),
            'boutique_couleur_accent' => trim((string) ($row['boutique_couleur_accent'] ?? '')),
            'boutique_adresse' => trim((string) ($row['boutique_adresse'] ?? '')),
        ];
    }
}

if (!function_exists('boutique_normalize_hex_color')) {
    /**
     * Valide et normalise #RRGGBB
     */
    function boutique_normalize_hex_color($hex) {
        $h = trim((string) $hex);
        if ($h === '') {
            return '';
        }
        if ($h[0] !== '#') {
            $h = '#' . $h;
        }
        if (strlen($h) !== 7 || !ctype_xdigit(substr($h, 1))) {
            return '';
        }
        return '#' . strtolower(substr($h, 1));
    }
}

if (!function_exists('boutique_echo_theme_style_override')) {
    /**
     * À inclure dans &lt;head&gt; (ex. via seo_meta.php) après _init boutique.
     */
    function boutique_echo_theme_style_override() {
        if (!defined('BOUTIQUE_ADMIN_ID')) {
            return;
        }
        $d = $GLOBALS['BOUTIQUE_VENDEUR_DISPLAY'] ?? null;
        if (!is_array($d)) {
            return;
        }
        $c1 = boutique_normalize_hex_color($d['boutique_couleur_principale'] ?? '');
        $c2 = boutique_normalize_hex_color($d['boutique_couleur_accent'] ?? '');
        if ($c1 === '' && $c2 === '') {
            return;
        }
        echo '<style id="boutique-vendeur-theme">' . "\n";
        echo ":root {\n";
        if ($c1 !== '') {
            echo "  --couleur-dominante: {$c1};\n";
            echo "  --bleu-principal: {$c1};\n";
            echo "  --bleu: {$c1};\n";
            echo "  --boutons-secondaires: {$c1};\n";
            echo "  --couleur-dominante-hover: color-mix(in srgb, {$c1} 78%, black);\n";
            echo "  --bleu-fonce: color-mix(in srgb, {$c1} 62%, black);\n";
            echo "  --boutons-secondaires-hover: color-mix(in srgb, {$c1} 78%, black);\n";
        }
        if ($c2 !== '') {
            echo "  --accent-promo: {$c2};\n";
            echo "  --orange: {$c2};\n";
        }
        echo "}\n</style>\n";
    }
}

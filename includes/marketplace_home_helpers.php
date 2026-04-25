<?php
/**
 * Tirage aléatoire sur une liste de produits « qualifiés », ou catalogue complet
 * si le volume ne dépasse pas le seuil (règle métier : > 5 produits = garde le mode « intelligent »).
 *
 * @param array $produits_source Produits ciblés (recherches, ventes, favoris, etc.)
 * @param int   $limite         Nombre max à retourner
 * @param int   $seuil          Tant que count > seuil, on mélange la source (sinon fallback catalogue)
 * @return array
 */
function marketplace_produits_aleatoires_avec_seuil($produits_source, $limite, $seuil = 5)
{
    $produits_source = is_array($produits_source) ? array_values($produits_source) : [];
    if (count($produits_source) > $seuil) {
        if (function_exists('random_int')) {
            mt_srand((int) (microtime(true) * 1000000) + random_int(0, 9999));
        } else {
            mt_srand((int) (microtime(true) * 1000000));
        }
        shuffle($produits_source);
        return array_slice($produits_source, 0, $limite);
    }
    if (!function_exists('get_all_produits_paginated')) {
        require_once __DIR__ . '/../models/model_produits.php';
    }
    $tous = get_all_produits_paginated(0, max(200, $limite * 6));
    if (empty($tous)) {
        return [];
    }
    if (function_exists('random_int')) {
        mt_srand((int) (microtime(true) * 1000000) + random_int(0, 9999));
    } else {
        mt_srand((int) (microtime(true) * 1000000));
    }
    shuffle($tous);
    return array_slice($tous, 0, $limite);
}

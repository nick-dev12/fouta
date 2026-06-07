<?php
/**
 * Helpers partagés — cartes commande (client + vendeur).
 */

if (!function_exists('commande_card_label')) {
    function commande_card_label($statut)
    {
        return match ($statut) {
            'en_attente' => 'En attente',
            'prise_en_charge' => 'Confirm&eacute;e',
            'livraison_en_cours' => 'En livraison',
            'livree' => 'Livr&eacute;e',
            'paye' => 'Re&ccedil;ue',
            'annulee' => 'Annul&eacute;e',
            default => ucfirst(str_replace('_', ' ', (string) $statut)),
        };
    }
}

if (!function_exists('commande_card_badge_class')) {
    function commande_card_badge_class($statut)
    {
        return match ($statut) {
            'en_attente' => 'ub--wait',
            'prise_en_charge' => 'ub--confirm',
            'livraison_en_cours' => 'ub--delivery',
            'livree', 'paye' => 'ub--done',
            'annulee' => 'ub--cancel',
            default => 'ub--wait',
        };
    }
}

if (!function_exists('commande_card_icon')) {
    function commande_card_icon($statut)
    {
        return match ($statut) {
            'en_attente' => 'fa-clock',
            'prise_en_charge' => 'fa-box-open',
            'livraison_en_cours' => 'fa-truck',
            'livree', 'paye' => 'fa-circle-check',
            'annulee' => 'fa-ban',
            default => 'fa-clock',
        };
    }
}

if (!function_exists('commande_card_timeline_steps')) {
    function commande_card_timeline_steps($statut)
    {
        $steps = [
            ['key' => 'en_attente', 'label' => 'Re&ccedil;ue', 'icon' => 'fa-circle-dot'],
            ['key' => 'prise_en_charge', 'label' => 'Confirm&eacute;e', 'icon' => 'fa-box-open'],
            ['key' => 'livraison_en_cours', 'label' => 'En livraison', 'icon' => 'fa-truck'],
            ['key' => 'livree', 'label' => 'Livr&eacute;e', 'icon' => 'fa-circle-check'],
        ];

        if ($statut === 'annulee') {
            return null;
        }

        if (in_array($statut, ['livree', 'paye'], true)) {
            $result = [];
            foreach ($steps as $step) {
                $result[] = $step + ['state' => 'done'];
            }
            return $result;
        }

        $order = [
            'en_attente' => 0,
            'prise_en_charge' => 1,
            'livraison_en_cours' => 2,
            'livree' => 3,
            'paye' => 3,
        ];
        $cur = $order[$statut] ?? -1;
        $result = [];

        foreach ($steps as $i => $step) {
            if ($i < $cur) {
                $result[] = $step + ['state' => 'done'];
            } elseif ($i === $cur) {
                $result[] = $step + ['state' => 'current'];
            } else {
                $result[] = $step + ['state' => 'pending'];
            }
        }

        return $result;
    }
}

if (!function_exists('commande_card_client_nom')) {
    function commande_card_client_nom(array $commande)
    {
        $nom = trim(($commande['user_prenom'] ?? '') . ' ' . ($commande['user_nom'] ?? ''));
        return $nom !== '' ? $nom : 'Client inconnu';
    }
}

if (!function_exists('commande_card_telephone')) {
    function commande_card_telephone(array $commande, $context = 'vendor')
    {
        if ($context === 'client') {
            return '';
        }

        $tel = trim((string) ($commande['user_telephone'] ?? ''));
        if ($tel === '') {
            $tel = trim((string) ($commande['telephone_livraison'] ?? ''));
        }
        if ($tel === '') {
            $tel = trim((string) ($commande['client_telephone'] ?? ''));
        }

        if ($tel !== '' && function_exists('commande_suivi_format_phone_display')) {
            return commande_suivi_format_phone_display($tel);
        }

        return $tel;
    }
}

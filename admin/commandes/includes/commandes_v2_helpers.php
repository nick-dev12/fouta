<?php
/**
 * Helpers affichage commandes v2 (cartes, badges)
 */

if (!function_exists('cmd_v2_statut_label')) {
    function cmd_v2_statut_label($statut)
    {
        $map = [
            'en_attente' => 'En attente',
            'prise_en_charge' => 'Pris en charge',
            'livraison_en_cours' => 'En livraison',
            'livree' => 'Livrée',
            'paye' => 'Payée',
            'annulee' => 'Annulée',
        ];
        return $map[$statut] ?? ucfirst(str_replace('_', ' ', (string) $statut));
    }
}

if (!function_exists('cmd_v2_statut_class')) {
    function cmd_v2_statut_class($statut)
    {
        $map = [
            'en_attente' => 'cmd-badge--attente',
            'prise_en_charge' => 'cmd-badge--prise',
            'livraison_en_cours' => 'cmd-badge--livraison',
            'livree' => 'cmd-badge--livree',
            'paye' => 'cmd-badge--paye',
            'annulee' => 'cmd-badge--annulee',
        ];
        return $map[$statut] ?? 'cmd-badge--attente';
    }
}

if (!function_exists('cmd_v2_client_initial')) {
    function cmd_v2_client_initial($client_nom)
    {
        $trim = trim((string) $client_nom);
        if ($trim === '') {
            return '?';
        }
        return function_exists('mb_strtoupper')
            ? mb_strtoupper(mb_substr($trim, 0, 1, 'UTF-8'), 'UTF-8')
            : strtoupper(substr($trim, 0, 1));
    }
}

if (!function_exists('cmd_v2_render_card')) {
    function cmd_v2_render_card(array $commande, array $options = [])
    {
        $show_date = !empty($options['show_date']);
        $show_delivery = !empty($options['show_delivery']);

        $client_nom = trim(($commande['user_prenom'] ?? '') . ' ' . ($commande['user_nom'] ?? ''));
        if ($client_nom === '') {
            $client_nom = 'Client inconnu';
        }

        $statut = $commande['statut'] ?? 'en_attente';
        $avatar = cmd_v2_client_initial($client_nom);
        $adresse = htmlspecialchars((string) ($commande['adresse_livraison'] ?? ''), ENT_QUOTES, 'UTF-8');
        $id = (int) ($commande['id'] ?? 0);
        ?>
        <article class="cmd-v2-card">
            <div class="cmd-v2-card__top">
                <span class="cmd-v2-card__num">
                    <i class="fas fa-hashtag"></i>
                    <?php echo htmlspecialchars((string) ($commande['numero_commande'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="cmd-badge <?php echo cmd_v2_statut_class($statut); ?>">
                    <?php echo htmlspecialchars(cmd_v2_statut_label($statut), ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <div class="cmd-v2-card__body">
                <div class="cmd-v2-client">
                    <div class="cmd-v2-client__avatar"><?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="cmd-v2-client__info">
                        <div class="cmd-v2-client__name"><?php echo htmlspecialchars($client_nom, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if ($show_date && !empty($commande['date_commande'])): ?>
                        <div class="cmd-v2-client__date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($show_delivery && !empty($commande['date_livraison'])): ?>
                        <div class="cmd-v2-client__date">
                            <i class="fas fa-truck"></i>
                            Livrée le <?php echo date('d/m/Y', strtotime($commande['date_livraison'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cmd-v2-amount-row">
                    <span class="cmd-v2-amount-label">Montant</span>
                    <strong class="cmd-v2-amount-value">
                        <?php echo number_format((float) ($commande['montant_total'] ?? 0), 0, ',', ' '); ?>
                        <small>FCFA</small>
                    </strong>
                </div>
                <?php if ($adresse !== ''): ?>
                <div class="cmd-v2-addr">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo $adresse; ?></span>
                </div>
                <?php endif; ?>
            </div>
            <a href="details.php?id=<?php echo $id; ?>" class="cmd-v2-card__cta">
                <span><i class="fas fa-eye"></i>&nbsp; Voir la commande</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </article>
        <?php
    }
}

if (!function_exists('cmd_v2_tri_commandes')) {
    function cmd_v2_tri_commandes(array $commandes, $tri = 'date_desc')
    {
        $allowed = ['date_desc', 'date_asc', 'montant_desc', 'montant_asc'];
        if (!in_array($tri, $allowed, true)) {
            $tri = 'date_desc';
        }

        usort($commandes, function ($a, $b) use ($tri) {
            $da = strtotime($a['date_commande'] ?? 'now');
            $db = strtotime($b['date_commande'] ?? 'now');
            $ma = (float) ($a['montant_total'] ?? 0);
            $mb = (float) ($b['montant_total'] ?? 0);

            switch ($tri) {
                case 'date_asc':
                    return $da <=> $db;
                case 'montant_desc':
                    return $mb <=> $ma ?: $db <=> $da;
                case 'montant_asc':
                    return $ma <=> $mb ?: $db <=> $da;
                default:
                    return $db <=> $da;
            }
        });

        return array_values($commandes);
    }
}

if (!function_exists('cmd_v2_filtre_statut_commandes')) {
    function cmd_v2_filtre_statut_commandes(array $commandes, $filtre_statut)
    {
        if ($filtre_statut === '' || $filtre_statut === 'toutes') {
            return $commandes;
        }

        return array_values(array_filter($commandes, function ($c) use ($filtre_statut) {
            $st = $c['statut'] ?? '';
            if ($filtre_statut === 'vendues') {
                return in_array($st, ['livree', 'paye'], true);
            }
            if ($filtre_statut === 'en_cours') {
                return !in_array($st, ['livree', 'paye', 'annulee'], true);
            }
            if ($filtre_statut === 'annulees') {
                return $st === 'annulee';
            }
            return true;
        }));
    }
}

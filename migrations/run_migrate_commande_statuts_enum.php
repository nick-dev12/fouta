<?php
/**
 * Aligne l'ENUM commandes.statut (livraison_en_cours, paye, etc.)
 */
require_once dirname(__DIR__) . '/conn/conn.php';

if (!$db) {
    fwrite(STDERR, "Connexion BDD indisponible.\n");
    exit(1);
}

echo "Migration ENUM commandes.statut…\n";

$sql = "
ALTER TABLE `commandes`
MODIFY COLUMN `statut` ENUM(
    'en_attente',
    'confirmee',
    'prise_en_charge',
    'en_preparation',
    'livraison_en_cours',
    'expediee',
    'livree',
    'paye',
    'annulee'
) NOT NULL DEFAULT 'en_attente'
";

try {
    $db->exec("UPDATE commandes SET statut = 'livraison_en_cours' WHERE statut = 'en_cours_expedition'");
    $db->exec($sql);
    echo "  OK — ENUM commandes.statut à jour.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Terminé.\n";

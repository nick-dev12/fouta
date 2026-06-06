<?php
if (PHP_SAPI !== 'cli') exit(1);
require __DIR__ . '/../conn/conn.php';
$root = dirname(__DIR__) . '/upload/';

$stmt = $db->query("SELECT id, image_principale FROM produits WHERE image_principale IS NOT NULL AND image_principale != ''");
$ok = $missing = $webp_db = 0;
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $p = trim((string) $r['image_principale']);
    if (str_ends_with(strtolower($p), '.webp')) $webp_db++;
    if (is_file($root . str_replace('/', DIRECTORY_SEPARATOR, $p))) $ok++;
    else $missing++;
}
echo "produits.image_principale : ok={$ok} missing={$missing} webp_en_bdd={$webp_db}\n";

$stmt = $db->query("SELECT id, image FROM categories WHERE image IS NOT NULL AND image != ''");
$ok = $missing = $webp_db = 0;
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $p = trim((string) $r['image']);
    if (str_ends_with(strtolower($p), '.webp')) $webp_db++;
    if (is_file($root . str_replace('/', DIRECTORY_SEPARATOR, $p))) $ok++;
    else $missing++;
}
echo "categories.image : ok={$ok} missing={$missing} webp_en_bdd={$webp_db}\n";

if (in_array('--missing', $argv, true)) {
    echo "\n--- categories manquantes ---\n";
    $stmt = $db->query("SELECT id, image FROM categories WHERE image IS NOT NULL AND image != ''");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $p = trim((string) $r['image']);
        if (!is_file($root . str_replace('/', DIRECTORY_SEPARATOR, $p))) {
            echo $r['id'] . ' ' . $p . "\n";
        }
    }
    echo "\n--- produits manquants ---\n";
    $stmt = $db->query("SELECT id, image_principale FROM produits WHERE image_principale IS NOT NULL");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $p = trim((string) $r['image_principale']);
        if (!is_file($root . str_replace('/', DIRECTORY_SEPARATOR, $p))) {
            echo $r['id'] . ' ' . $p . "\n";
        }
    }
}

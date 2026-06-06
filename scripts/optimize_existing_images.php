<?php
/**
 * Recompression batch des images déjà présentes dans upload/ (produits, catégories…).
 *
 * Usage CLI : php scripts/optimize_existing_images.php
 * Usage CLI (dossier ciblé) : php scripts/optimize_existing_images.php produits
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en ligne de commande.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/image_optimizer.php';

$target_subdir = isset($argv[1]) ? trim((string) $argv[1], '/\\') : '';
$upload_root = realpath(__DIR__ . '/../upload');
if ($upload_root === false || !is_dir($upload_root)) {
    fwrite(STDERR, "Dossier upload/ introuvable.\n");
    exit(1);
}

$scan_dir = $target_subdir !== '' ? $upload_root . DIRECTORY_SEPARATOR . $target_subdir : $upload_root;
if (!is_dir($scan_dir)) {
    fwrite(STDERR, "Dossier cible introuvable : {$scan_dir}\n");
    exit(1);
}

$skip_suffixes = ['_md', '_sm'];
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$processed = 0;
$skipped = 0;
$failed = 0;
$saved_bytes = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scan_dir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file_info) {
    if (!$file_info->isFile()) {
        continue;
    }
    $abs = $file_info->getPathname();
    $ext = strtolower($file_info->getExtension());
    if (!in_array($ext, $allowed_ext, true)) {
        continue;
    }

    $base = pathinfo($abs, PATHINFO_FILENAME);
    foreach ($skip_suffixes as $suffix) {
        if (str_ends_with($base, $suffix)) {
            continue 2;
        }
    }

    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($upload_root))), '/');
    $dir_abs = dirname($abs);
    $rel_dir = dirname($rel);
    $rel_subdir = ($rel_dir === '.' ? '' : $rel_dir);

    if ($ext === 'webp' && is_file($dir_abs . DIRECTORY_SEPARATOR . $base . '_md.webp')) {
        $skipped++;
        continue;
    }

    $prefix = 'img_';

    $bytes_before = (int) filesize($abs);
    $result = image_optimizer_process_tmp($abs, $dir_abs, $rel_subdir, $prefix);
    if (empty($result['success'])) {
        $failed++;
        fwrite(STDERR, "Échec [{$rel}] : " . ($result['message'] ?? 'erreur') . "\n");
        continue;
    }

    if ($abs !== $upload_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $result['relative_path'])) {
        @unlink($abs);
    }

    $processed++;
    $saved_bytes += max(0, $bytes_before - (int) ($result['bytes_after'] ?? $bytes_before));
    echo "OK {$rel} → " . ($result['relative_path'] ?? '') . "\n";
}

echo "\nTerminé : {$processed} optimisée(s), {$skipped} ignorée(s), {$failed} échec(s), " . round($saved_bytes / 1024, 1) . " Ko économisés.\n";

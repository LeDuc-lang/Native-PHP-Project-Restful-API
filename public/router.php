<?php

/**
 * Routeur pour le serveur web interne à PHP.
 *
 * Les fichiers réellement présents dans /public (CSS, JS, client.php, etc.)
 * sont servis directement. Toutes les autres URL (ex. /api/user) sont
 * transmises au contrôleur frontal.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__ . $path);

if (
    $path !== '/'
    && $file !== false
    && is_file($file)
    && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
) {
    return false;
}

require __DIR__ . '/index.php';

<?php

/**
 * Journalise un événement applicatif.
 *
 * La trace est toujours envoyée au journal du serveur (stderr avec `php -S`,
 * donc visible via `docker compose logs`) puis, si possible, écrite dans
 * `logs/error.log`.
 */
function logWithDate($subject, $message, $destination = null): void
{
    $logDirectory = __DIR__ . '/../logs';

    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0775, true);
    }

    if ($destination === null) {
        $destination = $logDirectory . '/error.log';
    }

    $entry = sprintf('[%s] %s: %s', date('Y-m-d H:i:s'), $subject, $message);

    // Toujours visible dans `docker compose logs -f app`.
    error_log($entry);

    // Journalisation fichier, sans faire échouer l'application si le dossier
    // n'est pas accessible en écriture.
    if (is_dir(dirname($destination)) && is_writable(dirname($destination))) {
        error_log($entry . PHP_EOL, 3, $destination);
    }
}

function throwDbNullConnection($connection): void
{
    if ($connection === null) {
        throw new Exception('Database connection is null');
    }
}

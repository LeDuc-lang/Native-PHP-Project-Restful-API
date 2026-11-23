<?php

require_once __DIR__ . '/../src/Core/DotEnv.php';
require_once __DIR__ . '/../helpers/global_helper.php';
require_once __DIR__ . '/../helpers/http_response_helper.php';

try {
    $requiredVariables = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'JWT_SECRET'];
    foreach (array_merge($requiredVariables, ['JWT_TTL', 'JWT_REFRESH_TTL']) as $name) {
        $value = getenv($name, true);
        if ($value !== false) {
            $_ENV[$name] = $value;
        }
    }

    $envPath = __DIR__ . '/../.env';
    if (is_file($envPath)) {
        DotEnv::loadFromFile($envPath);
    }

    $missingVariables = [];
    foreach ($requiredVariables as $name) {
        if (!isset($_ENV[$name]) || ($name !== 'DB_PASS' && $_ENV[$name] === '')) {
            $missingVariables[] = $name;
        }
    }
    if ($missingVariables) {
        throw new RuntimeException('Missing environment variables: ' . implode(', ', $missingVariables));
    }
} catch (InvalidArgumentException|RuntimeException $e) {
    logWithDate('Env configuration failed', $e->getMessage());
    sendResponseCustom('Server configuration is incomplete', null, 'Error', 500);
    return;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestUri === '/client.php' || $requestUri === '/') {
    require_once __DIR__ . '/client.php';
} else {
    require_once __DIR__ . '/../src/Core/Database.php';
    require_once __DIR__ . '/front_controller.php';
}

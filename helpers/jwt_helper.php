<?php

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string
{
    $b64 = strtr($data, '-_', '+/');
    $padding = strlen($b64) % 4;
    if ($padding > 0) {
        $b64 .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($b64);
}

/**
 * Récupère l'en-tête Authorization quel que soit le serveur (PHP natif, Apache, etc.).
 */
function getAuthorizationHeader(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $serverKey) {
        if (!empty($_SERVER[$serverKey])) {
            return (string)$_SERVER[$serverKey];
        }
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'authorization') {
                return (string)$value;
            }
        }
    }

    return '';
}

function authenticateRequest(JwtService $jwtService): ?array
{
    $authHeader = trim(getAuthorizationHeader());

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return null;
    }

    return $jwtService->verifyToken(trim($matches[1]));
}

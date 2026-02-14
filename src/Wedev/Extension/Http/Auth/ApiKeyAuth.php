<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Authentification par API Key dans un header custom.
 *
 * Utilisée pour les APIs qui attendent la clé dans un header spécifique
 * plutôt que dans Authorization.
 *
 * @example
 * // Header par défaut: X-API-Key
 * $auth = new ApiKeyAuth('your-api-key');
 * // Génère: X-API-Key: your-api-key
 *
 * // Header personnalisé
 * $auth = new ApiKeyAuth('your-api-key', 'X-Auth-Token');
 * // Génère: X-Auth-Token: your-api-key
 *
 * // Avec un service externe
 * $auth = new ApiKeyAuth($_ENV['SENDGRID_API_KEY'], 'X-SG-Key');
 * $response = $client
 *     ->withAuth($auth)
 *     ->postJson('https://api.sendgrid.com/v3/mail/send', [...]);
 */
final class ApiKeyAuth implements AuthInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $headerName = 'X-API-Key'
    ) {
    }

    public function getHeaders(): array
    {
        return [
            $this->headerName => $this->apiKey,
        ];
    }
}


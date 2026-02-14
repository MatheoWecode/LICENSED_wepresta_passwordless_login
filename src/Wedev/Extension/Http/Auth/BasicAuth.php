<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Authentification HTTP Basic.
 *
 * Encode les credentials en Base64 pour l'envoi via le header Authorization.
 * Utilisée pour les APIs qui requièrent une authentification Basic.
 *
 * @example
 * // Authentification basique
 * $auth = new BasicAuth('username', 'password');
 * $response = $client
 *     ->withAuth($auth)
 *     ->get('https://api.example.com/protected');
 *
 * // Avec certaines APIs (ex: Jira, Confluence)
 * $auth = new BasicAuth('email@example.com', 'api-token');
 * $response = $client
 *     ->withAuth($auth)
 *     ->get('https://your-domain.atlassian.net/rest/api/3/issue/KEY-123');
 */
final class BasicAuth implements AuthInterface
{
    public function __construct(
        private readonly string $username,
        private readonly string $password
    ) {
    }

    public function getHeaders(): array
    {
        $credentials = base64_encode("{$this->username}:{$this->password}");

        return [
            'Authorization' => 'Basic ' . $credentials,
        ];
    }
}


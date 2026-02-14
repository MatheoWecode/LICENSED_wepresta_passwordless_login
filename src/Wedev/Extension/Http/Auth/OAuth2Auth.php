<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Http\HttpException;

/**
 * Authentification OAuth2 avec refresh automatique.
 *
 * Gère automatiquement l'obtention et le renouvellement des tokens OAuth2
 * en utilisant le grant type "client_credentials".
 *
 * @example
 * // Configuration OAuth2
 * $auth = new OAuth2Auth(
 *     clientId: 'your-client-id',
 *     clientSecret: 'your-client-secret',
 *     tokenUrl: 'https://auth.example.com/oauth/token',
 *     scopes: ['read', 'write']
 * );
 *
 * // Le token est obtenu automatiquement lors de la première requête
 * $response = $client
 *     ->withAuth($auth)
 *     ->get('https://api.example.com/protected-resource');
 *
 * // Le token est renouvelé automatiquement s'il expire
 * $response = $client
 *     ->withAuth($auth)
 *     ->get('https://api.example.com/another-resource');
 */
final class OAuth2Auth implements AuthInterface
{
    /**
     * Marge de sécurité avant expiration (en secondes).
     */
    private const EXPIRY_MARGIN = 60;

    private ?string $accessToken = null;
    private ?int $expiresAt = null;

    /**
     * @param array<string> $scopes Scopes demandés
     */
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $tokenUrl,
        private readonly array $scopes = []
    ) {
    }

    public function getHeaders(): array
    {
        $this->ensureValidToken();

        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];
    }

    /**
     * Force le renouvellement du token.
     */
    public function refreshToken(): void
    {
        $this->accessToken = null;
        $this->expiresAt = null;
        $this->fetchToken();
    }

    /**
     * Retourne le token actuel (peut être null si non encore obtenu).
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Vérifie si le token est encore valide.
     */
    public function isTokenValid(): bool
    {
        if ($this->accessToken === null || $this->expiresAt === null) {
            return false;
        }

        return time() < ($this->expiresAt - self::EXPIRY_MARGIN);
    }

    /**
     * S'assure qu'un token valide est disponible.
     */
    private function ensureValidToken(): void
    {
        if ($this->isTokenValid()) {
            return;
        }

        $this->fetchToken();
    }

    /**
     * Obtient un nouveau token depuis le serveur OAuth2.
     *
     * @throws HttpException Si l'obtention du token échoue
     */
    private function fetchToken(): void
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if (!empty($this->scopes)) {
            $data['scope'] = implode(' ', $this->scopes);
        }

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ]),
                'content' => http_build_query($data),
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($this->tokenUrl, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw HttpException::requestFailed(
                'OAuth2 token request failed: ' . ($error['message'] ?? 'Unknown error')
            );
        }

        try {
            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw HttpException::invalidResponse('Invalid OAuth2 response: ' . $e->getMessage());
        }

        if (isset($json['error'])) {
            throw HttpException::requestFailed(
                'OAuth2 error: ' . ($json['error_description'] ?? $json['error'])
            );
        }

        if (!isset($json['access_token'])) {
            throw HttpException::invalidResponse('OAuth2 response missing access_token');
        }

        $this->accessToken = $json['access_token'];
        $this->expiresAt = time() + ($json['expires_in'] ?? 3600);
    }
}


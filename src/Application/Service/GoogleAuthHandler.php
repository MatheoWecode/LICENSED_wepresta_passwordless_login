<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;

class GoogleAuthHandler
{
    private const TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=';

    /**
     * Check if Google Login is enabled and properly configured.
     */
    public function isEnabled(): bool
    {
        return (bool) Configuration::get('WEPRESTA_PL_GOOGLE_ENABLED')
            && !empty(Configuration::get('WEPRESTA_PL_GOOGLE_CLIENT_ID'));
    }

    /**
     * Verify a Google ID token and extract user info.
     *
     * @return array{email: string, sub: string, given_name: string, family_name: string}|null
     */
    public function verifyToken(string $idToken): ?array
    {
        $clientId = Configuration::get('WEPRESTA_PL_GOOGLE_CLIENT_ID');
        if (empty($clientId)) {
            return null;
        }

        $url = self::TOKEN_INFO_URL . urlencode($idToken);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['email'])) {
            return null;
        }

        // Verify the audience matches our client ID
        if (!isset($data['aud']) || $data['aud'] !== $clientId) {
            return null;
        }

        // Verify the email is verified
        if (!isset($data['email_verified']) || $data['email_verified'] !== 'true') {
            return null;
        }

        return [
            'email' => strtolower($data['email']),
            'sub' => $data['sub'] ?? '',
            'given_name' => $data['given_name'] ?? '',
            'family_name' => $data['family_name'] ?? '',
        ];
    }
}

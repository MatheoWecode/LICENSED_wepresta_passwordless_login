<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use PrestaShopLogger;

class GoogleAuthHandler
{
    private const TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=';
    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

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
            $this->log('Google Client ID is not configured', 2);
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
            $this->log('Network error calling Google tokeninfo endpoint', 2);
            return null;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['email'])) {
            $this->log('Invalid response from Google tokeninfo (missing email)', 2);
            return null;
        }

        // Verify the issuer
        if (!isset($data['iss']) || !in_array($data['iss'], self::VALID_ISSUERS, true)) {
            $this->log('Google token issuer mismatch: ' . ($data['iss'] ?? 'missing'), 2);
            return null;
        }

        // Verify the audience matches our client ID
        if (!isset($data['aud']) || $data['aud'] !== $clientId) {
            $this->log('Google token audience mismatch. Expected: ' . $clientId . ', got: ' . ($data['aud'] ?? 'missing'), 2);
            return null;
        }

        // Verify the email is verified
        if (!isset($data['email_verified']) || $data['email_verified'] !== 'true') {
            $this->log('Google email not verified for: ' . $data['email'], 2);
            return null;
        }

        // Verify sub (unique Google user ID) is present
        if (empty($data['sub'])) {
            $this->log('Google token missing sub (user ID)', 2);
            return null;
        }

        return [
            'email' => strtolower($data['email']),
            'sub' => $data['sub'],
            'given_name' => $data['given_name'] ?? '',
            'family_name' => $data['family_name'] ?? '',
        ];
    }

    /**
     * Log a message when debug mode is enabled.
     */
    private function log(string $message, int $severity = 1): void
    {
        if (Configuration::get('WEPRESTA_PL_DEBUG')) {
            PrestaShopLogger::addLog(
                'PasswordlessLogin Google: ' . $message,
                $severity,
                null,
                'Module',
                0,
                true
            );
        }
    }
}

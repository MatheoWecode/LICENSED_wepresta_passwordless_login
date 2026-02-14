<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Authentification Bearer Token.
 *
 * Utilisée pour les APIs qui acceptent un token dans le header Authorization.
 * C'est le cas de la plupart des APIs modernes (OpenAI, Stripe, etc.).
 *
 * @example
 * // Avec une clé API
 * $auth = new BearerAuth('sk-1234567890');
 * $client = (new HttpClient())->withAuth($auth);
 *
 * // OpenAI
 * $auth = new BearerAuth($_ENV['OPENAI_API_KEY']);
 * $response = $client
 *     ->withAuth($auth)
 *     ->postJson('https://api.openai.com/v1/chat/completions', [...]);
 *
 * // Stripe
 * $auth = new BearerAuth($_ENV['STRIPE_SECRET_KEY']);
 * $response = $client
 *     ->withAuth($auth)
 *     ->post('https://api.stripe.com/v1/charges', [...]);
 */
final class BearerAuth implements AuthInterface
{
    public function __construct(
        private readonly string $token
    ) {
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }
}


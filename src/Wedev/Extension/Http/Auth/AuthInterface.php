<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les stratégies d'authentification HTTP.
 *
 * Implémentez cette interface pour créer de nouvelles
 * méthodes d'authentification (API Key, Bearer, OAuth2, etc.).
 *
 * @example
 * class CustomAuth implements AuthInterface
 * {
 *     public function __construct(
 *         private readonly string $username,
 *         private readonly string $password
 *     ) {}
 *
 *     public function getHeaders(): array
 *     {
 *         $credentials = base64_encode("{$this->username}:{$this->password}");
 *         return ['Authorization' => "Basic {$credentials}"];
 *     }
 * }
 */
interface AuthInterface
{
    /**
     * Retourne les headers d'authentification à ajouter à la requête.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array;
}


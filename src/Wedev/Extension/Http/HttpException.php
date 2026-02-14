<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Exception\ModuleException;

/**
 * Exception pour les erreurs HTTP.
 *
 * Lancée lors des échecs de requêtes HTTP, timeouts,
 * erreurs de parsing, ou problèmes de rate limiting.
 *
 * @example
 * try {
 *     $response = $client->get('https://api.example.com/data');
 * } catch (HttpException $e) {
 *     if ($e->isTimeout()) {
 *         // Gérer le timeout
 *     } elseif ($e->isRateLimit()) {
 *         // Attendre et réessayer
 *     } else {
 *         // Autre erreur
 *     }
 * }
 */
final class HttpException extends ModuleException
{
    private const CODE_REQUEST_FAILED = 3000;
    private const CODE_TIMEOUT = 3001;
    private const CODE_RATE_LIMIT = 3002;
    private const CODE_INVALID_RESPONSE = 3003;
    private const CODE_CONNECTION = 3004;

    /**
     * Crée une exception pour une requête échouée.
     */
    public static function requestFailed(string $message): self
    {
        return new self($message, self::CODE_REQUEST_FAILED);
    }

    /**
     * Crée une exception pour un timeout.
     */
    public static function timeout(int $seconds): self
    {
        return new self(
            sprintf('Request timed out after %d seconds', $seconds),
            self::CODE_TIMEOUT
        );
    }

    /**
     * Crée une exception pour un rate limit atteint.
     */
    public static function rateLimit(?int $retryAfter = null): self
    {
        $message = 'Rate limit exceeded';
        if ($retryAfter !== null) {
            $message .= sprintf('. Retry after %d seconds', $retryAfter);
        }

        return new self($message, self::CODE_RATE_LIMIT);
    }

    /**
     * Crée une exception pour une réponse invalide.
     */
    public static function invalidResponse(string $message): self
    {
        return new self($message, self::CODE_INVALID_RESPONSE);
    }

    /**
     * Crée une exception pour une erreur de connexion.
     */
    public static function connectionFailed(string $host): self
    {
        return new self(
            sprintf('Failed to connect to %s', $host),
            self::CODE_CONNECTION
        );
    }

    /**
     * Vérifie si c'est une erreur de timeout.
     */
    public function isTimeout(): bool
    {
        return $this->getCode() === self::CODE_TIMEOUT;
    }

    /**
     * Vérifie si c'est une erreur de rate limit.
     */
    public function isRateLimit(): bool
    {
        return $this->getCode() === self::CODE_RATE_LIMIT;
    }

    /**
     * Vérifie si c'est une erreur de réponse invalide.
     */
    public function isInvalidResponse(): bool
    {
        return $this->getCode() === self::CODE_INVALID_RESPONSE;
    }

    /**
     * Vérifie si c'est une erreur de connexion.
     */
    public function isConnectionError(): bool
    {
        return $this->getCode() === self::CODE_CONNECTION;
    }
}


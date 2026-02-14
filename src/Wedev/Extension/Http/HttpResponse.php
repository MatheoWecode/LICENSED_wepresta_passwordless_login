<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Réponse HTTP typée avec helpers.
 *
 * Encapsule la réponse d'une requête HTTP et fournit
 * des méthodes utilitaires pour accéder aux données.
 *
 * @example
 * $response = $client->get('https://api.example.com/users');
 *
 * if ($response->isSuccess()) {
 *     $users = $response->json();
 *     foreach ($users as $user) {
 *         echo $user['name'];
 *     }
 * }
 *
 * // Accès aux headers
 * $contentType = $response->getHeader('content-type');
 * $rateLimit = $response->getHeader('x-rate-limit-remaining');
 *
 * // Gestion des erreurs
 * if ($response->isClientError()) {
 *     $error = $response->json();
 *     throw new Exception($error['message']);
 * }
 */
final class HttpResponse
{
    /**
     * @param array<string, string> $headers Headers normalisés en lowercase
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $body,
        private readonly array $headers = []
    ) {
    }

    /**
     * Retourne le code HTTP de la réponse.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retourne le corps brut de la réponse.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Retourne tous les headers de la réponse.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retourne un header spécifique.
     *
     * @param string $name Nom du header (case insensitive)
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Vérifie si un header existe.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    /**
     * Vérifie si la requête a réussi (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Vérifie si c'est une redirection (3xx).
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Vérifie si c'est une erreur client (4xx).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Vérifie si c'est une erreur serveur (5xx).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Vérifie si c'est une erreur (4xx ou 5xx).
     */
    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    /**
     * Vérifie si la ressource n'a pas été trouvée (404).
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    /**
     * Vérifie si l'accès est non autorisé (401).
     */
    public function isUnauthorized(): bool
    {
        return $this->statusCode === 401;
    }

    /**
     * Vérifie si l'accès est interdit (403).
     */
    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    /**
     * Vérifie si le rate limit est atteint (429).
     */
    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    // -------------------------------------------------------------------------
    // Body parsing
    // -------------------------------------------------------------------------

    /**
     * Parse le corps comme JSON.
     *
     * @return array<string, mixed>
     *
     * @throws HttpException Si le JSON est invalide
     */
    public function json(): array
    {
        if (empty($this->body)) {
            return [];
        }

        try {
            $data = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : [];
        } catch (\JsonException $e) {
            throw HttpException::invalidResponse('Invalid JSON: ' . $e->getMessage());
        }
    }

    /**
     * Parse le corps comme JSON, retourne null en cas d'erreur.
     *
     * @return array<string, mixed>|null
     */
    public function jsonOrNull(): ?array
    {
        try {
            return $this->json();
        } catch (HttpException) {
            return null;
        }
    }

    /**
     * Vérifie si le content-type est JSON.
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('content-type') ?? '';

        return str_contains($contentType, 'application/json');
    }

    /**
     * Lance une exception si la réponse est une erreur.
     *
     * @throws HttpException Si la réponse est une erreur
     */
    public function throw(): self
    {
        if ($this->isError()) {
            $message = sprintf('HTTP Error %d', $this->statusCode);

            // Essayer d'extraire un message d'erreur du JSON
            $json = $this->jsonOrNull();
            if ($json !== null) {
                $message = $json['message']
                    ?? $json['error']
                    ?? $json['error_description']
                    ?? $message;
            }

            throw HttpException::requestFailed($message);
        }

        return $this;
    }

    /**
     * Retourne une représentation pour le debug.
     */
    public function __toString(): string
    {
        return sprintf(
            'HttpResponse[%d] %s',
            $this->statusCode,
            substr($this->body, 0, 100)
        );
    }
}


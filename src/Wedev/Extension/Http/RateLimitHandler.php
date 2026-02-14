<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Gestion du rate limiting côté client.
 *
 * Implémente un token bucket algorithm pour respecter
 * les limites d'API avant même d'envoyer les requêtes.
 *
 * @example
 * // Limiter à 10 requêtes par seconde
 * $handler = new RateLimitHandler(10, 1);
 *
 * for ($i = 0; $i < 100; $i++) {
 *     $handler->wait(); // Bloque si nécessaire
 *     $client->get('https://api.example.com/data');
 * }
 *
 * // Limiter à 100 requêtes par minute
 * $handler = new RateLimitHandler(100, 60);
 */
final class RateLimitHandler
{
    /**
     * Historique des timestamps de requêtes.
     *
     * @var array<float>
     */
    private array $requestTimes = [];

    public function __construct(
        private readonly int $maxRequests,
        private readonly int $perSeconds
    ) {
    }

    /**
     * Attend si nécessaire pour respecter le rate limit.
     *
     * Cette méthode bloque l'exécution si le nombre de requêtes
     * dans la fenêtre temporelle dépasse la limite.
     */
    public function wait(): void
    {
        $now = microtime(true);
        $windowStart = $now - $this->perSeconds;

        // Nettoyer les anciennes entrées (hors de la fenêtre)
        $this->requestTimes = array_filter(
            $this->requestTimes,
            static fn (float $time): bool => $time > $windowStart
        );

        // Si on a atteint la limite, attendre
        if (count($this->requestTimes) >= $this->maxRequests) {
            $oldestRequest = min($this->requestTimes);
            $waitTime = ($oldestRequest + $this->perSeconds - $now);

            if ($waitTime > 0) {
                // Convertir en microsecondes et attendre
                usleep((int) ($waitTime * 1_000_000));
            }
        }

        // Enregistrer cette requête
        $this->requestTimes[] = microtime(true);
    }

    /**
     * Retourne le nombre de requêtes restantes dans la fenêtre actuelle.
     */
    public function getRemainingRequests(): int
    {
        $now = microtime(true);
        $windowStart = $now - $this->perSeconds;

        $recentRequests = count(array_filter(
            $this->requestTimes,
            static fn (float $time): bool => $time > $windowStart
        ));

        return max(0, $this->maxRequests - $recentRequests);
    }

    /**
     * Vérifie si on peut effectuer une requête immédiatement.
     */
    public function canRequest(): bool
    {
        return $this->getRemainingRequests() > 0;
    }

    /**
     * Retourne le temps d'attente estimé avant la prochaine requête (en secondes).
     */
    public function getWaitTime(): float
    {
        if ($this->canRequest()) {
            return 0.0;
        }

        $now = microtime(true);
        $oldestRequest = min($this->requestTimes);

        return max(0.0, $oldestRequest + $this->perSeconds - $now);
    }

    /**
     * Réinitialise le compteur (utile pour les tests).
     */
    public function reset(): void
    {
        $this->requestTimes = [];
    }
}


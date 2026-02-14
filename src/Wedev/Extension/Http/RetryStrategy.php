<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Stratégie de retry avec backoff exponentiel.
 *
 * Implémente un algorithme de retry intelligent avec:
 * - Backoff exponentiel (1s, 2s, 4s, 8s, ...)
 * - Jitter aléatoire pour éviter les thundering herds
 * - Délai maximum de 30 secondes
 *
 * @example
 * $strategy = new RetryStrategy(maxAttempts: 3, exponentialBackoff: true);
 *
 * // Délais calculés:
 * // Attempt 1: ~1000ms (1s + jitter)
 * // Attempt 2: ~2000ms (2s + jitter)
 * // Attempt 3: ~4000ms (4s + jitter)
 *
 * // Vérifier si on doit retry
 * if ($strategy->shouldRetry(503)) {
 *     $delay = $strategy->getDelay($attempt);
 *     usleep($delay * 1000);
 * }
 */
final class RetryStrategy
{
    /**
     * Délai de base en millisecondes.
     */
    private const BASE_DELAY_MS = 1000;

    /**
     * Délai maximum en millisecondes (30 secondes).
     */
    private const MAX_DELAY_MS = 30000;

    /**
     * Codes HTTP qui déclenchent un retry.
     */
    private const RETRIABLE_STATUS_CODES = [
        408, // Request Timeout
        429, // Too Many Requests
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
    ];

    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly bool $exponentialBackoff = true
    ) {
    }

    /**
     * Retourne le nombre maximum de tentatives.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Calcule le délai avant le prochain retry (en millisecondes).
     *
     * @param int $attempt Numéro de la tentative (1, 2, 3, ...)
     *
     * @return int Délai en millisecondes
     */
    public function getDelay(int $attempt): int
    {
        if (!$this->exponentialBackoff) {
            return self::BASE_DELAY_MS + $this->getJitter();
        }

        // Exponential backoff: 1s, 2s, 4s, 8s, ...
        $delay = self::BASE_DELAY_MS * (int) pow(2, $attempt - 1);

        // Ajouter du jitter (±25%)
        $delay += $this->getJitter();

        // Limiter au maximum
        return min($delay, self::MAX_DELAY_MS);
    }

    /**
     * Détermine si le code HTTP doit déclencher un retry.
     */
    public function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRIABLE_STATUS_CODES, true);
    }

    /**
     * Calcule un jitter aléatoire (±25% du délai de base).
     */
    private function getJitter(): int
    {
        $jitterRange = (int) (self::BASE_DELAY_MS * 0.25);

        return random_int(-$jitterRange, $jitterRange);
    }
}


<?php
/**
 * WEDEV Core - CacheService
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Service;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Cache;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de cache unifié.
 *
 * Utilise le cache Symfony si disponible, sinon PrestaShop Cache.
 */
class CacheService
{
    private ?CacheInterface $symfonyCache;
    private string $prefix;
    private int $defaultTtl;

    public function __construct(
        ?CacheInterface $symfonyCache = null,
        string $prefix = 'wepresta_passwordless_login_',
        int $defaultTtl = 3600
    ) {
        $this->symfonyCache = $symfonyCache;
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Récupère une valeur du cache ou la calcule.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $prefixedKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        // Symfony Cache si disponible
        if ($this->symfonyCache !== null) {
            return $this->symfonyCache->get($prefixedKey, function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);

                return $callback();
            });
        }

        // Fallback: PrestaShop Cache
        $cached = Cache::getInstance()->get($prefixedKey);
        if ($cached !== false) {
            return $cached;
        }

        $value = $callback();
        Cache::getInstance()->set($prefixedKey, $value, $ttl);

        return $value;
    }

    /**
     * Définit une valeur dans le cache.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $prefixedKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        if ($this->symfonyCache !== null) {
            $this->symfonyCache->delete($prefixedKey);
            $this->get($key, fn() => $value, $ttl);

            return;
        }

        Cache::getInstance()->set($prefixedKey, $value, $ttl);
    }

    /**
     * Supprime une valeur du cache.
     */
    public function delete(string $key): void
    {
        $prefixedKey = $this->prefix . $key;

        if ($this->symfonyCache !== null) {
            $this->symfonyCache->delete($prefixedKey);

            return;
        }

        Cache::getInstance()->delete($prefixedKey);
    }

    /**
     * Supprime toutes les clés avec le préfixe du module.
     */
    public function clear(): void
    {
        // Pour le cache Symfony, on ne peut pas clear par préfixe facilement
        // Pour PrestaShop, on peut utiliser clean()
        Cache::getInstance()->clean('*' . $this->prefix . '*');
    }

    /**
     * Génère une clé de cache basée sur des paramètres.
     */
    public function buildKey(string $base, array $params = []): string
    {
        if (empty($params)) {
            return $base;
        }

        $hash = md5(json_encode($params, JSON_THROW_ON_ERROR));

        return $base . '_' . $hash;
    }

    /**
     * Cache le résultat d'une méthode avec ses arguments.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, array $params, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->buildKey($key, $params);

        return $this->get($cacheKey, $callback, $ttl);
    }
}


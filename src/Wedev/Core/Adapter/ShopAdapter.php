<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Adapter;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Context;
use Shop;

/**
 * Adapter pour la gestion multi-shop PrestaShop.
 *
 * Fournit une interface typée et testable pour les opérations multi-boutique.
 *
 * @example
 * $shopAdapter = new ShopAdapter();
 *
 * // Vérifier si multi-shop actif
 * if ($shopAdapter->isMultiShopActive()) {
 *     $shops = $shopAdapter->getActiveShops();
 * }
 *
 * // Exécuter dans le contexte d'une boutique spécifique
 * $shopAdapter->executeInShopContext(1, function() {
 *     // Code exécuté avec shop_id = 1
 *     return Configuration::get('MY_CONFIG');
 * });
 *
 * // Exécuter pour toutes les boutiques
 * $shopAdapter->forEachShop(function(array $shop) {
 *     echo "Processing shop: " . $shop['name'];
 * });
 */
final class ShopAdapter
{
    /**
     * Vérifie si la fonctionnalité multi-shop est active.
     */
    public function isMultiShopActive(): bool
    {
        return Shop::isFeatureActive();
    }

    /**
     * Retourne l'ID de la boutique courante.
     */
    public function getCurrentShopId(): int
    {
        return (int) Context::getContext()->shop->id;
    }

    /**
     * Retourne l'ID du groupe de la boutique courante.
     */
    public function getCurrentShopGroupId(): int
    {
        return (int) Context::getContext()->shop->id_shop_group;
    }

    /**
     * Retourne le nom de la boutique courante.
     */
    public function getCurrentShopName(): string
    {
        return Context::getContext()->shop->name ?? '';
    }

    /**
     * Retourne le contexte actuel du shop (CONTEXT_SHOP, CONTEXT_GROUP, CONTEXT_ALL).
     */
    public function getShopContext(): int
    {
        return Shop::getContext();
    }

    /**
     * Vérifie si on est en contexte de boutique unique.
     */
    public function isSingleShopContext(): bool
    {
        return Shop::getContext() === Shop::CONTEXT_SHOP;
    }

    /**
     * Vérifie si on est en contexte "toutes les boutiques".
     */
    public function isAllShopsContext(): bool
    {
        return Shop::getContext() === Shop::CONTEXT_ALL;
    }

    /**
     * Retourne la liste des boutiques actives.
     *
     * @return array<int, array{id_shop: int, name: string, active: bool, id_shop_group: int}>
     */
    public function getActiveShops(): array
    {
        $shops = Shop::getShops(true, null, true);

        return array_map(static fn (array $shop): array => [
            'id_shop' => (int) $shop['id_shop'],
            'name' => (string) $shop['name'],
            'active' => (bool) $shop['active'],
            'id_shop_group' => (int) $shop['id_shop_group'],
        ], $shops);
    }

    /**
     * Retourne toutes les boutiques (actives et inactives).
     *
     * @return array<int, array{id_shop: int, name: string, active: bool, id_shop_group: int}>
     */
    public function getAllShops(): array
    {
        $shops = Shop::getShops(false, null, true);

        return array_map(static fn (array $shop): array => [
            'id_shop' => (int) $shop['id_shop'],
            'name' => (string) $shop['name'],
            'active' => (bool) $shop['active'],
            'id_shop_group' => (int) $shop['id_shop_group'],
        ], $shops);
    }

    /**
     * Retourne les IDs des boutiques actives.
     *
     * @return array<int>
     */
    public function getActiveShopIds(): array
    {
        return array_column($this->getActiveShops(), 'id_shop');
    }

    /**
     * Exécute une callback dans le contexte d'une boutique spécifique.
     *
     * Le contexte original est restauré après l'exécution.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function executeInShopContext(int $shopId, callable $callback): mixed
    {
        $originalShopId = $this->getCurrentShopId();
        $originalContext = Shop::getContext();

        try {
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

            return $callback();
        } finally {
            Shop::setContext($originalContext, $originalShopId);
        }
    }

    /**
     * Exécute une callback pour chaque boutique active.
     *
     * @param callable(array{id_shop: int, name: string, active: bool, id_shop_group: int}): void $callback
     */
    public function forEachShop(callable $callback): void
    {
        foreach ($this->getActiveShops() as $shop) {
            $this->executeInShopContext($shop['id_shop'], static function () use ($callback, $shop): void {
                $callback($shop);
            });
        }
    }

    /**
     * Exécute une callback pour chaque boutique et collecte les résultats.
     *
     * @template T
     *
     * @param callable(array{id_shop: int, name: string, active: bool, id_shop_group: int}): T $callback
     *
     * @return array<int, T> Résultats indexés par shop_id
     */
    public function mapShops(callable $callback): array
    {
        $results = [];

        foreach ($this->getActiveShops() as $shop) {
            $results[$shop['id_shop']] = $this->executeInShopContext(
                $shop['id_shop'],
                static fn () => $callback($shop)
            );
        }

        return $results;
    }

    /**
     * Vérifie si une boutique existe.
     */
    public function shopExists(int $shopId): bool
    {
        return Shop::getShop($shopId) !== false;
    }

    /**
     * Retourne les informations d'une boutique spécifique.
     *
     * @return array{id_shop: int, name: string, active: bool, id_shop_group: int}|null
     */
    public function getShopById(int $shopId): ?array
    {
        $shop = Shop::getShop($shopId);

        if ($shop === false) {
            return null;
        }

        return [
            'id_shop' => (int) $shop['id_shop'],
            'name' => (string) $shop['name'],
            'active' => (bool) $shop['active'],
            'id_shop_group' => (int) $shop['id_shop_group'],
        ];
    }
}


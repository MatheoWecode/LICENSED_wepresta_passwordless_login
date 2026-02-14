<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface marqueur pour les services injectables.
 *
 * Utilisée pour identifier les services qui peuvent être
 * injectés via le conteneur de dépendances Symfony.
 *
 * Cette interface est intentionnellement vide (marqueur).
 * Elle permet d'identifier les services dans les configurations
 * et lors de l'analyse statique.
 *
 * @example
 * class ProductService implements ServiceInterface
 * {
 *     public function __construct(
 *         private readonly ProductRepository $repository,
 *         private readonly CacheService $cache
 *     ) {}
 *
 *     public function getProduct(int $id): ?Product
 *     {
 *         return $this->cache->get(
 *             "product_{$id}",
 *             fn() => $this->repository->findById($id)
 *         );
 *     }
 * }
 *
 * // Dans services.yml
 * services:
 *     _instanceof:
 *         Wepresta_Passwordless_Login\Core\Contract\ServiceInterface:
 *             public: true
 */
interface ServiceInterface
{
}


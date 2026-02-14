<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface de base pour les repositories.
 *
 * Définit les opérations CRUD standard pour l'accès aux données.
 *
 * @template T of object
 *
 * @example
 * class ProductRepository implements RepositoryInterface
 * {
 *     public function findById(int $id): ?Product
 *     {
 *         $product = new Product($id);
 *         return $product->id ? $product : null;
 *     }
 *
 *     public function findAll(): array
 *     {
 *         return Product::getProducts($this->langId, 0, 0, 'id_product', 'ASC');
 *     }
 *
 *     public function save(object $entity): bool
 *     {
 *         return $entity->save();
 *     }
 *
 *     public function delete(int $id): bool
 *     {
 *         $product = new Product($id);
 *         return $product->delete();
 *     }
 * }
 */
interface RepositoryInterface
{
    /**
     * Trouve une entité par son ID.
     *
     * @return T|null
     */
    public function findById(int $id): ?object;

    /**
     * Retourne toutes les entités.
     *
     * @return array<T>
     */
    public function findAll(): array;

    /**
     * Persiste une entité.
     *
     * @param T $entity
     *
     * @return bool True si la sauvegarde a réussi
     */
    public function save(object $entity): bool;

    /**
     * Supprime une entité par son ID.
     *
     * @return bool True si la suppression a réussi
     */
    public function delete(int $id): bool;
}


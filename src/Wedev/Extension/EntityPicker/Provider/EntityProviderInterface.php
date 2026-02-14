<?php
/**
 * WEDEV Extension - EntityPicker
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityPicker\Provider;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les fournisseurs de recherche d'entités.
 *
 * Implémentez cette interface pour créer un provider de recherche
 * pour n'importe quelle entité PrestaShop.
 */
interface EntityProviderInterface
{
    /**
     * Recherche des entités par terme.
     *
     * @param string $term Terme de recherche
     * @param int $limit Nombre maximum de résultats
     *
     * @return array<int, array{id: int, name: string, image: string}> Résultats formatés
     */
    public function search(string $term, int $limit = 20): array;

    /**
     * Récupère des entités par leurs IDs.
     *
     * @param int[] $ids Liste des IDs
     *
     * @return array<int, array{id: int, name: string, image: string}> Entités formatées
     */
    public function getByIds(array $ids): array;

    /**
     * Retourne le type d'entité (ex: 'product', 'category', 'customer').
     */
    public function getEntityType(): string;

    /**
     * Retourne le label localisé de l'entité.
     */
    public function getEntityLabel(): string;
}


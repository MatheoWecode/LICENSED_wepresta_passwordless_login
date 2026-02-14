<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityFields;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Helper class for building context arrays for entity field location rules.
 *
 * Context arrays are used by ACF location rules to determine if field groups
 * should be displayed for a specific entity.
 *
 * @example
 * $context = EntityFieldContext::build('product', 123, [
 *     'category_ids' => [1, 2, 3],
 *     'product_type' => 'simple',
 * ]);
 *
 * // Result:
 * // [
 * //     'entity_type' => 'product',
 * //     'entity_id' => 123,
 * //     'category_ids' => [1, 2, 3],
 * //     'product_type' => 'simple',
 * // ]
 */
final class EntityFieldContext
{
    /**
     * Builds a context array for an entity.
     *
     * The context always includes:
     * - 'entity_type': The entity type identifier
     * - 'entity_id': The entity ID
     *
     * Additional context data can be provided via $additional.
     *
     * @param string $entityType Entity type identifier
     * @param int $entityId Entity ID
     * @param array<string, mixed> $additional Additional context data
     * @return array<string, mixed> Context array
     */
    public static function build(string $entityType, int $entityId, array $additional = []): array
    {
        return array_merge(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            $additional
        );
    }

    /**
     * Builds context from a provider.
     *
     * Convenience method that gets the provider from the registry
     * and builds context using the provider's buildContext method.
     *
     * @param EntityFieldRegistry $registry Registry instance
     * @param string $entityType Entity type identifier
     * @param int $entityId Entity ID
     * @return array<string, mixed> Context array
     */
    public static function buildFromProvider(
        EntityFieldRegistry $registry,
        string $entityType,
        int $entityId
    ): array {
        $provider = $registry->getEntityType($entityType);
        if ($provider === null) {
            // Fallback to basic context
            return self::build($entityType, $entityId);
        }

        return $provider->buildContext($entityId);
    }
}



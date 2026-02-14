<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityFields;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;

/**
 * EntityFields Extension - Generic infrastructure for attaching custom fields to entities.
 *
 * Provides:
 * - Entity type registry
 * - Hook management
 * - Context building for location rules
 *
 * @example
 * // In a module's services.yml:
 * imports:
 *     - { resource: '../src/Wedev/Extension/EntityFields/config/services_entityfields.yml' }
 */
final class EntityFieldExtension implements ExtensionInterface
{
    public static function getName(): string
    {
        return 'EntityFields';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }
}



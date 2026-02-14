<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface for sub-plugins (plugins that extend other plugins).
 *
 * This is a generic pattern for "plugins that extend plugins":
 * - ACF Bricks (custom field types)
 * - Payment Gateways
 * - Shipping Carriers
 * - Export Formats
 * - etc.
 *
 * Each module can define its own interface extending SubPluginInterface
 * with module-specific methods.
 *
 * @example
 * // ACF module defines its brick interface
 * interface BrickInterface extends SubPluginInterface
 * {
 *     public function getFieldType(): FieldTypeInterface;
 * }
 *
 * // Third-party module implements it
 * final class SignatureBrick implements BrickInterface
 * {
 *     public static function getName(): string { return 'signature'; }
 *     public static function getVersion(): string { return '1.0.0'; }
 *     public static function getDependencies(): array { return ['wepresta_acf']; }
 *     public function getType(): string { return 'field_type'; }
 *     public function getDescription(): string { return 'Signature field type'; }
 *     public function getAuthor(): string { return 'WeCode'; }
 *     // ...
 * }
 */
interface SubPluginInterface extends PluginInterface
{
    /**
     * Returns the type/category of this sub-plugin.
     *
     * Examples:
     * - 'field_type' for ACF field types
     * - 'payment' for payment gateways
     * - 'carrier' for shipping carriers
     * - 'exporter' for export formats
     */
    public function getType(): string;

    /**
     * Returns a human-readable description of the sub-plugin.
     */
    public function getDescription(): string;

    /**
     * Returns the author name or company.
     */
    public function getAuthor(): string;
}


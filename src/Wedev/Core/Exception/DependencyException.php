<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Exception;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Exception pour les erreurs de dépendances.
 *
 * Lancée lorsqu'une extension requise n'est pas disponible
 * ou qu'un service n'est pas trouvé dans le conteneur.
 *
 * @example
 * // Extension non trouvée
 * if (!ExtensionLoader::isAvailable('Http')) {
 *     throw DependencyException::extensionNotFound('Http');
 * }
 *
 * // Service non trouvé
 * if (!$container->has(MyService::class)) {
 *     throw DependencyException::serviceNotFound(MyService::class);
 * }
 */
final class DependencyException extends ModuleException
{
    private const CODE_DEPENDENCY = 2000;

    /**
     * Crée une exception pour une extension non trouvée.
     */
    public static function extensionNotFound(string $extension): self
    {
        return new self(
            sprintf(
                'Required extension "%s" not found. Install it with: wedev ps module add-ext %s',
                $extension,
                strtolower($extension)
            ),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour un service non trouvé.
     */
    public static function serviceNotFound(string $service): self
    {
        return new self(
            sprintf('Service "%s" not found in container.', $service),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour une dépendance circulaire.
     *
     * @param array<string>|string $chain La chaîne de dépendances qui a créé le cycle
     */
    public static function circularDependency(array|string $chain): self
    {
        $message = is_array($chain)
            ? implode(' -> ', $chain)
            : $chain;

        return new self(
            sprintf('Circular dependency detected: %s', $message),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour une version d'extension incompatible.
     */
    public static function incompatibleVersion(
        string $extension,
        string $required,
        string $installed
    ): self {
        return new self(
            sprintf(
                'Extension "%s" version %s is incompatible. Required: %s',
                $extension,
                $installed,
                $required
            ),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour une dépendance manquante d'une extension.
     */
    public static function missingExtensionDependency(string $extension, string $requires): self
    {
        return new self(
            sprintf(
                'Extension "%s" requires extension "%s" which is not installed.',
                $extension,
                $requires
            ),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour un plugin non trouvé.
     */
    public static function pluginNotFound(string $plugin): self
    {
        return new self(
            sprintf('Plugin "%s" not found.', $plugin),
            self::CODE_DEPENDENCY
        );
    }

    /**
     * Crée une exception pour une dépendance manquante d'un plugin.
     */
    public static function missingPluginDependency(string $plugin, string $requires): self
    {
        return new self(
            sprintf(
                'Plugin "%s" requires "%s" which is not available.',
                $plugin,
                $requires
            ),
            self::CODE_DEPENDENCY
        );
    }
}


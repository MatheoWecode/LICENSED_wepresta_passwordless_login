<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Extension;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Exception\DependencyException;

/**
 * Charge et vérifie les extensions disponibles.
 *
 * Détecte automatiquement les extensions WEDEV présentes dans le module
 * et gère les dépendances entre extensions.
 *
 * @example
 * // Vérifier si une extension est disponible
 * if (ExtensionLoader::isAvailable('Http')) {
 *     $client = new HttpClient();
 * }
 *
 * // Exiger une extension (lance une exception si absente)
 * ExtensionLoader::require('Http');
 *
 * // Lister les extensions disponibles
 * $extensions = ExtensionLoader::getAvailableExtensions();
 * // ['UI', 'Http']
 *
 * // Obtenir les infos d'une extension
 * $info = ExtensionLoader::getExtensionInfo('Http');
 * // ['name' => 'Http', 'version' => '1.0.0', 'dependencies' => []]
 */
final class ExtensionLoader
{
    /**
     * Mapping des extensions vers leurs classes principales.
     *
     * @var array<string, class-string<ExtensionInterface>>
     */
    private const EXTENSIONS = [
        'Http' => \Wepresta_Passwordless_Login\Extension\Http\HttpClient::class,
        'Rules' => \Wepresta_Passwordless_Login\Extension\Rules\RuleEngine::class,
        'Jobs' => \Wepresta_Passwordless_Login\Extension\Jobs\JobDispatcher::class,
        'Audit' => \Wepresta_Passwordless_Login\Extension\Audit\AuditLogger::class,
        'Notifications' => \Wepresta_Passwordless_Login\Extension\Notifications\NotificationService::class,
        'Import' => \Wepresta_Passwordless_Login\Extension\Import\AbstractImporter::class,
        'UI' => \Wepresta_Passwordless_Login\Extension\UI\Twig\UiExtension::class,
    ];

    /**
     * Cache des extensions vérifiées.
     *
     * @var array<string, bool>|null
     */
    private static ?array $availabilityCache = null;

    /**
     * Vérifie si une extension est disponible.
     */
    public static function isAvailable(string $extension): bool
    {
        if (self::$availabilityCache === null) {
            self::$availabilityCache = [];
        }

        if (!isset(self::$availabilityCache[$extension])) {
            self::$availabilityCache[$extension] = self::checkAvailability($extension);
        }

        return self::$availabilityCache[$extension];
    }

    /**
     * Exige qu'une extension soit disponible.
     *
     * @throws DependencyException Si l'extension n'est pas disponible
     */
    public static function require(string $extension): void
    {
        if (!self::isAvailable($extension)) {
            throw DependencyException::extensionNotFound($extension);
        }

        // Vérifier les dépendances de l'extension
        self::checkDependencies($extension);
    }

    /**
     * Retourne la liste des extensions disponibles.
     *
     * @return array<string>
     */
    public static function getAvailableExtensions(): array
    {
        return array_keys(array_filter(
            self::EXTENSIONS,
            static fn (string $class): bool => class_exists($class),
        ));
    }

    /**
     * Retourne les informations d'une extension.
     *
     * @return array{name: string, version: string, dependencies: array<string>}|null
     */
    public static function getExtensionInfo(string $extension): ?array
    {
        if (!self::isAvailable($extension)) {
            return null;
        }

        $class = self::EXTENSIONS[$extension];

        return [
            'name' => $class::getName(),
            'version' => $class::getVersion(),
            'dependencies' => $class::getDependencies(),
        ];
    }

    /**
     * Retourne toutes les extensions avec leurs informations.
     *
     * @return array<string, array{name: string, version: string, dependencies: array<string>, available: bool}>
     */
    public static function getAllExtensionsInfo(): array
    {
        $info = [];

        foreach (array_keys(self::EXTENSIONS) as $extension) {
            $extensionInfo = self::getExtensionInfo($extension);

            $info[$extension] = $extensionInfo !== null
                ? array_merge($extensionInfo, ['available' => true])
                : [
                    'name' => $extension,
                    'version' => 'N/A',
                    'dependencies' => [],
                    'available' => false,
                ];
        }

        return $info;
    }

    /**
     * Vérifie si toutes les dépendances d'une extension sont satisfaites.
     *
     * @throws DependencyException Si une dépendance est manquante
     */
    public static function checkDependencies(string $extension): void
    {
        if (!isset(self::EXTENSIONS[$extension])) {
            return;
        }

        if (!class_exists(self::EXTENSIONS[$extension])) {
            return;
        }

        $class = self::EXTENSIONS[$extension];
        $dependencies = $class::getDependencies();

        foreach ($dependencies as $dependency) {
            if (!self::isAvailable($dependency)) {
                throw DependencyException::missingExtensionDependency($extension, $dependency);
            }
        }
    }

    /**
     * Réinitialise le cache (utile pour les tests).
     */
    public static function clearCache(): void
    {
        self::$availabilityCache = null;
    }

    /**
     * Vérifie réellement si une extension est disponible.
     */
    private static function checkAvailability(string $extension): bool
    {
        if (!isset(self::EXTENSIONS[$extension])) {
            return false;
        }

        return class_exists(self::EXTENSIONS[$extension]);
    }
}


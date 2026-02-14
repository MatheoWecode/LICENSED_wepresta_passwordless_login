<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les Extensions WEDEV.
 *
 * Toutes les extensions (Http, Rules, Jobs, etc.) doivent implémenter
 * cette interface pour être détectables par l'ExtensionLoader.
 *
 * @example
 * final class HttpClient implements ExtensionInterface
 * {
 *     public static function getName(): string
 *     {
 *         return 'Http';
 *     }
 *
 *     public static function getVersion(): string
 *     {
 *         return '1.0.0';
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return []; // Pas de dépendance
 *     }
 * }
 *
 * final class NotificationService implements ExtensionInterface
 * {
 *     public static function getName(): string
 *     {
 *         return 'Notifications';
 *     }
 *
 *     public static function getVersion(): string
 *     {
 *         return '1.0.0';
 *     }
 *
 *     public static function getDependencies(): array
 *     {
 *         return ['Http']; // Requiert l'extension Http
 *     }
 * }
 */
interface ExtensionInterface
{
    /**
     * Retourne le nom de l'extension.
     *
     * Doit correspondre au nom du dossier dans Extension/
     */
    public static function getName(): string;

    /**
     * Retourne la version de l'extension.
     *
     * Format SemVer: MAJOR.MINOR.PATCH
     */
    public static function getVersion(): string;

    /**
     * Retourne les dépendances de l'extension.
     *
     * @return array<string> Noms des extensions requises
     */
    public static function getDependencies(): array;
}


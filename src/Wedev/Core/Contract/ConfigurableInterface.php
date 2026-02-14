<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les classes avec configuration.
 *
 * Implémentez cette interface pour les services ou modules
 * qui nécessitent une configuration persistante.
 *
 * @example
 * class MyService implements ConfigurableInterface
 * {
 *     public function getDefaultConfiguration(): array
 *     {
 *         return [
 *             'enabled' => true,
 *             'api_key' => '',
 *             'cache_ttl' => 3600,
 *         ];
 *     }
 *
 *     public function getConfigurationPrefix(): string
 *     {
 *         return 'MY_MODULE';
 *     }
 * }
 */
interface ConfigurableInterface
{
    /**
     * Retourne la configuration par défaut.
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfiguration(): array;

    /**
     * Retourne le préfixe utilisé pour les clés de configuration.
     *
     * Ex: 'MY_MODULE' pour des clés comme 'MY_MODULE_ENABLED'
     */
    public function getConfigurationPrefix(): string;
}


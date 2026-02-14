<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les composants installables.
 *
 * Implémentez cette interface pour les composants qui nécessitent
 * une installation/désinstallation (tables SQL, configuration, etc.).
 *
 * @example
 * class MyFeature implements InstallableInterface
 * {
 *     public function install(): bool
 *     {
 *         return $this->createTables() && $this->registerHooks();
 *     }
 *
 *     public function uninstall(): bool
 *     {
 *         return $this->dropTables() && $this->unregisterHooks();
 *     }
 *
 *     public function isInstalled(): bool
 *     {
 *         return $this->tablesExist();
 *     }
 * }
 */
interface InstallableInterface
{
    /**
     * Installe le composant.
     *
     * @return bool True si l'installation a réussi
     */
    public function install(): bool;

    /**
     * Désinstalle le composant.
     *
     * @return bool True si la désinstallation a réussi
     */
    public function uninstall(): bool;

    /**
     * Vérifie si le composant est installé.
     */
    public function isInstalled(): bool;
}


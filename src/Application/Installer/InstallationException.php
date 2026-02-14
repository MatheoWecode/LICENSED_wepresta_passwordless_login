<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Exception thrown during module installation/uninstallation.
 */
class InstallationException extends \Exception
{
    /**
     * Create an exception for a specific component.
     *
     * @param string $component The component that failed (e.g., 'database', 'tabs', 'hooks')
     * @param string $message The error message
     * @param \Throwable|null $previous The previous exception
     *
     * @return self
     */
    public static function forComponent(string $component, string $message, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Installation failed for component \'%s\': %s', $component, $message), 0, $previous);
    }

    /**
     * Create an exception for a missing configuration key.
     *
     * @param string $key The missing configuration key
     *
     * @return self
     */
    public static function missingConfig(string $key): self
    {
        return new self(sprintf('Required configuration key \'%s\' is missing', $key));
    }

    /**
     * Create an exception for a database error.
     *
     * @param string $operation The database operation that failed
     * @param string $details Additional error details
     *
     * @return self
     */
    public static function databaseError(string $operation, string $details): self
    {
        return new self(sprintf('Database operation \'%s\' failed: %s', $operation, $details));
    }

    /**
     * Create an exception for a hook registration error.
     *
     * @param string $hookName The hook name that failed to register
     *
     * @return self
     */
    public static function hookRegistrationFailed(string $hookName): self
    {
        return new self(sprintf('Failed to register hook \'%s\'', $hookName));
    }

    /**
     * Create an exception for a tab installation error.
     *
     * @param string $tabClass The tab class that failed to install
     *
     * @return self
     */
    public static function tabInstallationFailed(string $tabClass): self
    {
        return new self(sprintf('Failed to install tab \'%s\'', $tabClass));
    }

    /**
     * Create an exception for a configuration error.
     *
     * @param string $configKey The configuration key that failed
     *
     * @return self
     */
    public static function configurationFailed(string $configKey): self
    {
        return new self(sprintf('Failed to install configuration \'%s\'', $configKey));
    }
}


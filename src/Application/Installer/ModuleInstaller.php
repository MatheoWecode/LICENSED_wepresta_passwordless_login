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

use Module;
use PrestaShopLogger;

/**
 * Main orchestrator for module installation/uninstallation.
 * Coordinates all sub-installers (tabs, hooks, database, configuration).
 *
 * This class is GENERIC and reusable across all modules.
 * Module-specific installation logic should be passed via $customInstallers.
 *
 * Configuration format:
 * [
 *     'database' => [...],     // DatabaseInstaller config
 *     'configurations' => [...], // ConfigurationInstaller config
 *     'tabs' => [...],         // TabInstaller config
 *     'hooks' => [...],        // HookInstaller config
 *     'custom_installers' => [...] // Additional installers
 * ]
 */
class ModuleInstaller
{
    private Module $module;

    private TabInstaller $tabInstaller;
    private HookInstaller $hookInstaller;
    private DatabaseInstaller $databaseInstaller;
    private ConfigurationInstaller $configurationInstaller;

    private array $configuration = [];

    /** @var array<callable> */
    private array $customInstallers = [];

    /**
     * @param Module $module
     * @param array $configuration Configuration for the installers
     * @param array<callable> $customInstallers Optional custom installers for module-specific logic
     */
    public function __construct(Module $module, array $configuration = [], array $customInstallers = [])
    {
        $this->module = $module;
        $this->configuration = $configuration;
        $this->customInstallers = $customInstallers;

        $this->tabInstaller = new TabInstaller($module);
        $this->hookInstaller = new HookInstaller($module);
        $this->databaseInstaller = new DatabaseInstaller($module->name, $module->getLocalPath());
        $this->configurationInstaller = new ConfigurationInstaller();
    }

    /**
     * Install the module completely.
     *
     * @param bool $clearCache Whether to clear Symfony cache after installation
     * @return bool
     * @throws InstallationException
     */
    public function install(bool $clearCache = true): bool
    {
        try {
            $this->log('Starting module installation: ' . $this->module->name);

            $this->ensureComposerAutoload();

            // Install database first
            if (isset($this->configuration['database'])) {
                $this->databaseInstaller->install($this->configuration['database']);
                $this->log('Database installation completed');
            }

            // Run custom installers
            $this->runCustomInstallers('install');
            $this->log('Custom installers completed');

            // Install configurations
            if (isset($this->configuration['configurations'])) {
                $this->configurationInstaller->install($this->configuration['configurations']);
                $this->log('Configuration installation completed');
            }

            // Install tabs
            if (isset($this->configuration['tabs'])) {
                $this->tabInstaller->install($this->configuration['tabs']);
                $this->log('Tab installation completed');
            }

            // Install hooks
            if (isset($this->configuration['hooks'])) {
                $this->hookInstaller->install($this->configuration['hooks']);
                $this->log('Hook installation completed');
            }

            // Clear caches
            if ($clearCache) {
                $this->clearCaches();
                $this->log('Cache clearing completed');
            }

            $this->log('Module installation completed successfully');

            return true;
        } catch (\Exception $e) {
            $this->log('Installation failed: ' . $e->getMessage(), 'error');
            throw new InstallationException(
                sprintf('Installation failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Uninstall the module completely.
     *
     * @param bool $clearCache Whether to clear Symfony cache after uninstallation
     * @return bool
     * @throws InstallationException
     */
    public function uninstall(bool $clearCache = true): bool
    {
        try {
            $this->log('Starting module uninstallation: ' . $this->module->name);

            // Uninstall hooks first
            if (isset($this->configuration['hooks'])) {
                $this->hookInstaller->uninstall($this->configuration['hooks']);
                $this->log('Hook uninstallation completed');
            }

            // Uninstall tabs
            if (isset($this->configuration['tabs'])) {
                $tabClassNames = array_column($this->configuration['tabs'], 'class_name');
                $this->tabInstaller->uninstall($tabClassNames);
                $this->log('Tab uninstallation completed');
            }

            // Uninstall configurations
            if (isset($this->configuration['configurations'])) {
                $configKeys = array_keys($this->configuration['configurations']);
                $this->configurationInstaller->uninstall($configKeys);
                $this->log('Configuration uninstallation completed');
            }

            // Run custom uninstallers
            $this->runCustomInstallers('uninstall');
            $this->log('Custom uninstallers completed');

            // Uninstall database last
            if (isset($this->configuration['database'])) {
                $this->databaseInstaller->uninstall($this->configuration['database']);
                $this->log('Database uninstallation completed');
            }

            // Clear caches
            if ($clearCache) {
                $this->clearCaches();
                $this->log('Cache clearing completed');
            }

            $this->log('Module uninstallation completed successfully');

            return true;
        } catch (\Exception $e) {
            $this->log('Uninstallation failed: ' . $e->getMessage(), 'error');
            throw new InstallationException(
                sprintf('Uninstallation failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Register all hooks (useful for module enable).
     *
     * @return bool
     */
    public function registerHooks(): bool
    {
        if (!isset($this->configuration['hooks'])) {
            return true;
        }

        try {
            $this->hookInstaller->install($this->configuration['hooks']);
            $this->log('Hooks registered successfully');
            return true;
        } catch (\Exception $e) {
            $this->log('Hook registration failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Reset the module (uninstall + install).
     *
     * @return bool
     * @throws InstallationException
     */
    public function reset(): bool
    {
        $this->ensureComposerAutoload();

        return $this->uninstall() && $this->install();
    }

    /**
     * Run a specific installer component.
     *
     * @param string $component Component name (database, configurations, tabs, hooks)
     * @param string $action Action to perform (install, uninstall)
     * @return bool
     * @throws InstallationException
     */
    public function runComponent(string $component, string $action): bool
    {
        $method = $action . 'Component';

        if (!method_exists($this, $method)) {
            throw new InstallationException(sprintf('Unknown action: %s', $action));
        }

        return $this->{$method}($component);
    }

    /**
     * Run custom installers.
     *
     * @param string $action Action name (install/uninstall)
     * @return void
     * @throws InstallationException
     */
    private function runCustomInstallers(string $action): void
    {
        $customInstallers = $this->configuration['custom_installers'] ?? $this->customInstallers;

        foreach ($customInstallers as $installer) {
            if (is_callable($installer)) {
                $result = $installer($action, $this);
                if ($result === false) {
                    throw new InstallationException("Custom installer failed for action: {$action}");
                }
            }
        }
    }

    /**
     * Ensure Composer autoload is available.
     *
     * @return void
     * @throws InstallationException
     */
    private function ensureComposerAutoload(): void
    {
        $modulePath = _PS_MODULE_DIR_ . $this->module->name;
        $vendorAutoload = $modulePath . '/vendor/autoload.php';
        $composerJson = $modulePath . '/composer.json';

        if (file_exists($vendorAutoload)) {
            return;
        }

        if (!file_exists($composerJson)) {
            return;
        }

        $this->runComposerDumpAutoload($modulePath);
    }

    /**
     * Run composer dump-autoload to generate autoload files.
     *
     * @param string $modulePath
     * @return void
     * @throws InstallationException
     */
    private function runComposerDumpAutoload(string $modulePath): void
    {
        $composerPaths = [
            'composer',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            'composer.phar',
            $modulePath . '/composer.phar',
        ];

        $composerCmd = null;
        foreach ($composerPaths as $path) {
            if ($this->isExecutable($path)) {
                $composerCmd = $path;
                break;
            }
        }

        if ($composerCmd === null) {
            $this->useFallbackAutoloader($modulePath);
            return;
        }

        $command = sprintf(
            'cd %s && %s dump-autoload --no-dev --optimize 2>&1',
            escapeshellarg($modulePath),
            escapeshellcmd($composerCmd)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->useFallbackAutoloader($modulePath);
        }
    }

    /**
     * Check if a command is executable.
     *
     * @param string $command
     * @return bool
     */
    private function isExecutable(string $command): bool
    {
        if (strpos($command, '/') === 0) {
            return is_executable($command);
        }

        $output = [];
        $returnCode = 0;
        exec('which ' . escapeshellarg($command) . ' 2>/dev/null', $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Use the fallback autoloader when Composer is not available.
     *
     * @param string $modulePath
     * @return void
     */
    private function useFallbackAutoloader(string $modulePath): void
    {
        $fallbackAutoload = $modulePath . '/autoload.php';
        if (file_exists($fallbackAutoload)) {
            require_once $fallbackAutoload;
        }
    }

    /**
     * Clear Symfony cache and reset Tab cache.
     *
     * @return void
     */
    private function clearCaches(): void
    {
        try {
            // Clear Symfony cache
            if (class_exists(\Tools::class) && method_exists(\Tools::class, 'clearSf2Cache')) {
                \Tools::clearSf2Cache();
            }

            // Reset Tab static cache
            if (class_exists(Tab::class) && method_exists(Tab::class, 'resetStaticCache')) {
                Tab::resetStaticCache();
            }
        } catch (\Throwable $e) {
            // Log but don't fail installation
            $this->log('Cache clear warning: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * Log a message.
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    private function log(string $message, string $level = 'info'): void
    {
        $fullMessage = '[' . $this->module->name . '] ' . $message;

        if (class_exists(PrestaShopLogger::class)) {
            $logLevel = $this->mapLogLevel($level);
            PrestaShopLogger::addLog($fullMessage, $logLevel);
        }

        // Also log to PHP error log in development
        if (_PS_MODE_DEV_) {
            error_log('[' . strtoupper($level) . '] ' . $fullMessage);
        }
    }

    /**
     * Map log level string to PrestaShop log level.
     *
     * @param string $level
     * @return int
     */
    private function mapLogLevel(string $level): int
    {
        $levels = [
            'debug' => 1,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
        ];

        return $levels[$level] ?? 1;
    }

    /**
     * Get the tab installer.
     *
     * @return TabInstaller
     */
    public function getTabInstaller(): TabInstaller
    {
        return $this->tabInstaller;
    }

    /**
     * Get the hook installer.
     *
     * @return HookInstaller
     */
    public function getHookInstaller(): HookInstaller
    {
        return $this->hookInstaller;
    }

    /**
     * Get the database installer.
     *
     * @return DatabaseInstaller
     */
    public function getDatabaseInstaller(): DatabaseInstaller
    {
        return $this->databaseInstaller;
    }

    /**
     * Get the configuration installer.
     *
     * @return ConfigurationInstaller
     */
    public function getConfigurationInstaller(): ConfigurationInstaller
    {
        return $this->configurationInstaller;
    }

    /**
     * Check if the module is properly installed.
     *
     * @return array<string, bool>
     */
    public function checkInstallationStatus(): array
    {
        $status = [];

        // Check database
        if (isset($this->configuration['database']['tables'])) {
            foreach (array_keys($this->configuration['database']['tables']) as $tableName) {
                $status[sprintf('database_%s', $tableName)] = $this->databaseInstaller->tableExists($tableName);
            }
        }

        // Check configurations
        if (isset($this->configuration['configurations'])) {
            foreach (array_keys($this->configuration['configurations']) as $configKey) {
                $status[sprintf('config_%s', $configKey)] = $this->configurationInstaller->configurationExists($configKey);
            }
        }

        // Check tabs
        if (isset($this->configuration['tabs'])) {
            foreach ($this->configuration['tabs'] as $tab) {
                $className = $tab['class_name'] ?? '';
                if (!empty($className)) {
                    $status[sprintf('tab_%s', $className)] = $this->tabInstaller->tabExists($className);
                }
            }
        }

        // Check hooks
        if (isset($this->configuration['hooks'])) {
            foreach ($this->configuration['hooks'] as $hook) {
                $hookName = is_array($hook) ? ($hook['name'] ?? '') : $hook;
                if (!empty($hookName)) {
                    $status[sprintf('hook_%s', $hookName)] = $this->hookInstaller->isHookRegistered($hookName);
                }
            }
        }

        return $status;
    }
}
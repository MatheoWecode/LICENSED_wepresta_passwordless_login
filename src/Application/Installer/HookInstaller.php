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

use Hook;
use Module;

/**
 * Handles registration and unregistration of hooks.
 * Supports both simple hooks and hooks with exceptions.
 */
class HookInstaller
{
    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Register hooks from configuration.
     *
     * @param array<int, string|array<string, mixed>> $hooks Array of hook names or hook configurations
     * @return bool
     * @throws InstallationException
     */
    public function install(array $hooks): bool
    {
        foreach ($hooks as $hook) {
            $hookName = is_array($hook) ? ($hook['name'] ?? '') : $hook;
            $exceptions = is_array($hook) ? ($hook['exceptions'] ?? []) : [];

            if (!$this->registerHook($hookName, $exceptions)) {
                throw InstallationException::hookRegistrationFailed($hookName);
            }
        }

        return true;
    }

    /**
     * Unregister hooks.
     *
     * @param array<int, string|array<string, mixed>> $hooks Array of hook names or hook configurations
     * @return bool
     * @throws InstallationException
     */
    public function uninstall(array $hooks): bool
    {
        foreach ($hooks as $hook) {
            $hookName = is_array($hook) ? ($hook['name'] ?? '') : $hook;

            if (!$this->unregisterHook($hookName)) {
                throw InstallationException::forComponent('hooks', sprintf('Cannot unregister hook: %s', $hookName));
            }
        }

        return true;
    }

    /**
     * Register a single hook with optional exceptions.
     *
     * @param string $hookName The hook name to register
     * @param array<int, string> $exceptions Array of controller class names where this hook should not be displayed
     * @return bool
     */
    public function registerHook(string $hookName, array $exceptions = []): bool
    {
        if (empty($hookName)) {
            return false;
        }

        if ($this->isHookRegistered($hookName)) {
            // Hook already registered, update exceptions if needed
            if (!empty($exceptions)) {
                return $this->updateHookExceptions($hookName, $exceptions);
            }
            return true;
        }

        // Register the hook
        $result = $this->module->registerHook($hookName);

        // Add exceptions if provided
        if ($result && !empty($exceptions)) {
            $result = $result && $this->updateHookExceptions($hookName, $exceptions);
        }

        return $result;
    }

    /**
     * Unregister a single hook.
     *
     * @param string $hookName The hook name to unregister
     * @return bool
     */
    public function unregisterHook(string $hookName): bool
    {
        if (!$this->isHookRegistered($hookName)) {
            return true; // Hook not registered, nothing to do
        }

        return $this->module->unregisterHook($hookName);
    }

    /**
     * Check if a hook is already registered for this module.
     *
     * @param string $hookName The hook name to check
     * @return bool
     */
    public function isHookRegistered(string $hookName): bool
    {
        $hookId = Hook::getIdByName($hookName);

        if (!$hookId) {
            return false;
        }

        return Hook::isModuleRegisteredOnHook(
            $this->module,
            $hookName,
            (int) $this->module->id
        );
    }

    /**
     * Get all hooks registered for this module.
     *
     * @return array<int, string>
     */
    public function getRegisteredHooks(): array
    {
        $hooks = Hook::getModulesFromHook(null, $this->module->id);

        if (!is_array($hooks)) {
            return [];
        }

        return array_column($hooks, 'name');
    }

    /**
     * Update hook exceptions for a specific hook.
     *
     * @param string $hookName The hook name
     * @param array<int, string> $exceptions Array of controller class names
     * @return bool
     */
    private function updateHookExceptions(string $hookName, array $exceptions): bool
    {
        $hookId = Hook::getIdByName($hookName);

        if (!$hookId) {
            return false;
        }

        // Remove existing exceptions
        $this->removeHookExceptions($hookId);

        // Add new exceptions
        foreach ($exceptions as $exception) {
            if (!$this->addHookException($hookId, $exception)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add an exception for a hook.
     *
     * @param int $hookId The hook ID
     * @param string $exception The controller class name
     * @return bool
     */
    private function addHookException(int $hookId, string $exception): bool
    {
        return (bool) \Db::getInstance()->insert('hook_module_exceptions', [
            'id_shop' => 0,
            'id_module' => (int) $this->module->id,
            'id_hook' => $hookId,
            'file_name' => pSQL($exception),
        ]);
    }

    /**
     * Remove all exceptions for a hook.
     *
     * @param int $hookId The hook ID
     * @return bool
     */
    private function removeHookExceptions(int $hookId): bool
    {
        return (bool) \Db::getInstance()->delete(
            'hook_module_exceptions',
            'id_module = ' . (int) $this->module->id . ' AND id_hook = ' . $hookId
        );
    }

    /**
     * Get exceptions for a specific hook.
     *
     * @param string $hookName The hook name
     * @return array<int, string>
     */
    public function getHookExceptions(string $hookName): array
    {
        $hookId = Hook::getIdByName($hookName);

        if (!$hookId) {
            return [];
        }

        $exceptions = \Db::getInstance()->executeS(
            'SELECT file_name FROM ' . _DB_PREFIX_ . 'hook_module_exceptions
             WHERE id_module = ' . (int) $this->module->id . ' AND id_hook = ' . $hookId
        );

        if (!is_array($exceptions)) {
            return [];
        }

        return array_column($exceptions, 'file_name');
    }
}

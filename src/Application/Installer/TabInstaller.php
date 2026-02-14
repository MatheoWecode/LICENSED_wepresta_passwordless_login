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

use Language;
use Module;
use Tab;

/**
 * Handles installation and uninstallation of admin tabs.
 * Supports PrestaShop 8 and 9 compatibility, routes, and multi-language names.
 */
class TabInstaller
{
    /**
     * Shared parent tab class name for all WePresta modules
     */
    public const WEPRESTA_PARENT_TAB = 'AdminWePresta';

    /**
     * Parent of the WePresta tab (PrestaShop's modules menu)
     */
    private const WEPRESTA_GRANDPARENT_TAB = 'AdminParentModulesSf';

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Check if we're running on PrestaShop 9+
     */
    private function isPS9(): bool
    {
        return version_compare(_PS_VERSION_, '8.0.0', '>=');
    }

    /**
     * Install tabs from configuration.
     *
     * @param array<int, array<string, mixed>> $tabs Tab configurations
     * @return bool
     * @throws InstallationException
     */
    public function install(array $tabs): bool
    {
        // Sort tabs by parent hierarchy (parents first)
        usort($tabs, function ($a, $b) {
            $parentA = $a['parent_class_name'] ?? '';
            $parentB = $b['parent_class_name'] ?? '';
            if (empty($parentA) && !empty($parentB)) {
                return -1;
            }
            if (!empty($parentA) && empty($parentB)) {
                return 1;
            }

            return 0;
        });

        foreach ($tabs as $tabConfig) {
            $this->installTab($tabConfig);
        }

        return true;
    }

    /**
     * Uninstall tabs by class names.
     *
     * @param array<int, string> $tabClassNames Array of tab class names
     * @return bool
     * @throws InstallationException
     */
    public function uninstall(array $tabClassNames): bool
    {
        foreach ($tabClassNames as $className) {
            $this->uninstallTab($className);
        }

        return true;
    }

    /**
     * Install a single tab.
     *
     * Tab configuration format:
     * [
     *     'class_name' => 'AdminMyModuleController',
     *     'name' => 'My Module', // or array of translations
     *     'parent_class_name' => 'AdminParentModulesSf',
     *     'icon' => 'cogs',
     *     'visible' => true,
     *     'route_name' => 'admin_my_module_index' // PS9 only
     * ]
     *
     * @param array<string, mixed> $tabConfig Tab configuration
     * @return Tab
     * @throws InstallationException
     */
    public function installTab(array $tabConfig): Tab
    {
        $className = $tabConfig['class_name'] ?? '';
        $name = $tabConfig['name'] ?? $className;
        $parentClassName = $tabConfig['parent_class_name'] ?? 'AdminParentModulesSf';
        $icon = $tabConfig['icon'] ?? '';
        $visible = $tabConfig['visible'] ?? true;
        $routeName = $tabConfig['route_name'] ?? null;

        if (empty($className)) {
            throw InstallationException::missingConfig('tab.class_name');
        }

        $parentId = $this->getParentTabId($parentClassName);
        $existingTabId = (int) Tab::getIdFromClassName($className);

        if ($existingTabId > 0) {
            return $this->updateExistingTab($existingTabId, $tabConfig, $parentId);
        }

        return $this->createNewTab($tabConfig, $parentId);
    }

    /**
     * Update an existing tab.
     *
     * @param int $tabId Existing tab ID
     * @param array<string, mixed> $tabConfig Tab configuration
     * @param int $parentId Parent tab ID
     * @return Tab
     * @throws InstallationException
     */
    private function updateExistingTab(int $tabId, array $tabConfig, int $parentId): Tab
    {
        $existingTab = new Tab($tabId);
        $needsUpdate = false;

        // Check if parent changed
        if ($existingTab->id_parent != $parentId) {
            $needsUpdate = true;
            $existingTab->id_parent = $parentId;
        }

        // Check if icon changed
        $expectedIcon = $tabConfig['icon'] ?? '';
        if ($existingTab->icon != $expectedIcon) {
            $needsUpdate = true;
            $existingTab->icon = $expectedIcon;
        }

        // Check if visibility changed
        $expectedActive = (bool) ($tabConfig['visible'] ?? true);
        if ((bool) $existingTab->active !== $expectedActive) {
            $needsUpdate = true;
            $existingTab->active = $expectedActive;
        }

        // Check if route name changed (PS9+ only)
        if ($this->isPS9() && property_exists($existingTab, 'route_name')) {
            $expectedRouteName = is_string($tabConfig['route_name'] ?? null) ? $tabConfig['route_name'] : '';
            if ((string) $existingTab->route_name !== $expectedRouteName) {
                $needsUpdate = true;
                $existingTab->route_name = $expectedRouteName;
            }
        }

        // Update names for all languages
        $languages = Language::getLanguages(false);
        $nameConfig = $tabConfig['name'] ?? $tabConfig['class_name'];

        foreach ($languages as $lang) {
            $expectedName = $this->getTranslatedName($nameConfig, (int) $lang['id_lang']);
            if (!isset($existingTab->name[$lang['id_lang']]) || $existingTab->name[$lang['id_lang']] != $expectedName) {
                $needsUpdate = true;
                $existingTab->name[$lang['id_lang']] = $expectedName;
            }
        }

        if ($needsUpdate && !$existingTab->save()) {
            throw InstallationException::tabInstallationFailed($tabConfig['class_name']);
        }

        return $existingTab;
    }

    /**
     * Create a new tab.
     *
     * @param array<string, mixed> $tabConfig Tab configuration
     * @param int $parentId Parent tab ID
     * @return Tab
     * @throws InstallationException
     */
    private function createNewTab(array $tabConfig, int $parentId): Tab
    {
        $tab = new Tab();
        $tab->class_name = $tabConfig['class_name'];
        $tab->module = $this->module->name;
        $tab->id_parent = $parentId;
        $tab->icon = $tabConfig['icon'] ?? '';
        $tab->active = (bool) ($tabConfig['visible'] ?? true);

        // Set route name for PS9+
        if ($this->isPS9() && property_exists($tab, 'route_name')) {
            $tab->route_name = is_string($tabConfig['route_name'] ?? null) ? $tabConfig['route_name'] : '';
        }

        // Set names for all languages
        $languages = Language::getLanguages(false);
        $nameConfig = $tabConfig['name'] ?? $tabConfig['class_name'];

        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = $this->getTranslatedName($nameConfig, (int) $lang['id_lang']);
        }

        if (!$tab->save()) {
            throw InstallationException::tabInstallationFailed($tabConfig['class_name']);
        }

        return $tab;
    }

    /**
     * Uninstall a single tab.
     *
     * @param string $className Tab class name
     * @return bool
     * @throws InstallationException
     */
    public function uninstallTab(string $className): bool
    {
        $tabId = (int) Tab::getIdFromClassName($className);

        if ($tabId <= 0) {
            return true; // Tab doesn't exist, nothing to do
        }

        $tab = new Tab($tabId);

        if (!$tab->delete()) {
            throw InstallationException::forComponent('tabs', sprintf('Cannot uninstall tab: %s', $className));
        }


        return true;
    }

    /**
     * Get parent tab ID from class name.
     * If the parent is AdminWePresta and doesn't exist, create it automatically.
     *
     * @param string $parentClassName Parent tab class name
     * @return int
     */
    private function getParentTabId(string $parentClassName): int
    {
        if (empty($parentClassName)) {
            return 0;
        }

        $parentId = (int) Tab::getIdFromClassName($parentClassName);

        // If parent exists, return its ID
        if ($parentId > 0) {
            return $parentId;
        }

        // If parent is WePresta and doesn't exist, create it
        if ($parentClassName === self::WEPRESTA_PARENT_TAB) {
            $weprestTab = $this->createWePrestaParent();
            return (int) $weprestTab->id;
        }

        return 0;
    }

    /**
     * Create the shared WePresta parent tab.
     * This tab is shared between all WePresta modules and has no module owner.
     *
     * @return Tab
     * @throws InstallationException
     */
    private function createWePrestaParent(): Tab
    {
        // Double-check to prevent race conditions (another module might have created it)
        $existingTabId = (int) Tab::getIdFromClassName(self::WEPRESTA_PARENT_TAB);
        if ($existingTabId > 0) {
            return new Tab($existingTabId);
        }

        // Get the grandparent ID (AdminParentModulesSf)
        $grandparentId = (int) Tab::getIdFromClassName(self::WEPRESTA_GRANDPARENT_TAB);
        if ($grandparentId <= 0) {
            // Fallback: try to find any valid parent for modules
            $grandparentId = 0;
        }

        $tab = new Tab();
        $tab->class_name = self::WEPRESTA_PARENT_TAB;
        $tab->module = ''; // Empty: shared between modules, no owner
        $tab->id_parent = $grandparentId;
        $tab->icon = 'extension'; // Material Design icon
        $tab->active = true;

        // Set route_name for PS9+ (empty for parent tabs with children)
        if ($this->isPS9() && property_exists($tab, 'route_name')) {
            $tab->route_name = '';
        }

        // Set name "WePresta" for all languages
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = 'WePresta';
        }

        if (!$tab->save()) {
            throw InstallationException::tabInstallationFailed(self::WEPRESTA_PARENT_TAB);
        }

        // Final race condition check: if another process created it while we were saving,
        // delete our duplicate and return the existing one
        $allTabs = Tab::getCollectionFromModule('');
        $weprestaTabs = [];
        foreach ($allTabs as $existingTab) {
            if ($existingTab->class_name === self::WEPRESTA_PARENT_TAB) {
                $weprestaTabs[] = $existingTab;
            }
        }

        // If we have duplicates, keep only the one with the lowest ID
        if (count($weprestaTabs) > 1) {
            usort($weprestaTabs, fn($a, $b) => $a->id <=> $b->id);
            for ($i = 1; $i < count($weprestaTabs); $i++) {
                $weprestaTabs[$i]->delete();
            }
            return $weprestaTabs[0];
        }

        return $tab;
    }

    /**
     * Get translated name for a language.
     *
     * @param string|array<int, string> $nameConfig Name configuration
     * @param int $langId Language ID
     * @return string
     */
    private function getTranslatedName($nameConfig, int $langId): string
    {
        if (is_array($nameConfig) && isset($nameConfig[$langId])) {
            return $nameConfig[$langId];
        }

        if (is_string($nameConfig)) {
            return $this->module->l($nameConfig);
        }

        return (string) $nameConfig;
    }

    /**
     * Check if a tab exists.
     *
     * @param string $className Tab class name
     * @return bool
     */
    public function tabExists(string $className): bool
    {
        return (int) Tab::getIdFromClassName($className) > 0;
    }

    /**
     * Get tab information.
     *
     * @param string $className Tab class name
     * @return array<string, mixed>|null
     */
    public function getTabInfo(string $className): ?array
    {
        $tabId = (int) Tab::getIdFromClassName($className);

        if ($tabId <= 0) {
            return null;
        }

        $tab = new Tab($tabId);

        return [
            'id' => $tab->id,
            'class_name' => $tab->class_name,
            'name' => $tab->name,
            'id_parent' => $tab->id_parent,
            'icon' => $tab->icon,
            'active' => (bool) $tab->active,
            'route_name' => property_exists($tab, 'route_name') ? $tab->route_name : null,
        ];
    }

    /**
     * Get all tabs for this module.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleTabs(): array
    {
        $tabs = Tab::getTabs((int) $this->module->context->language->id, 0);

        $moduleTabs = [];
        foreach ($tabs as $tab) {
            if ($tab['module'] === $this->module->name) {
                $moduleTabs[] = $tab;
            }
        }

        return $moduleTabs;
    }

    /**
     * Reset tab positions for this module's tabs.
     *
     * @return bool
     */
    public function resetTabPositions(): bool
    {
        $moduleTabs = $this->getModuleTabs();

        $position = 1;
        foreach ($moduleTabs as $tab) {
            $tabObj = new Tab((int) $tab['id_tab']);
            $tabObj->position = $position++;
            $tabObj->save();
        }

        return true;
    }
}

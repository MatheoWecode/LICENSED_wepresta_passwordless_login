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

use Configuration;
use Shop;

/**
 * Handles installation and uninstallation of module configurations.
 * Supports multi-shop configurations, encrypted values, and HTML content.
 */
class ConfigurationInstaller
{
    /**
     * Install configurations from configuration array.
     *
     * @param array<string, array<string, mixed>> $configurations Configuration array
     * @return bool
     * @throws InstallationException
     */
    public function install(array $configurations): bool
    {
        foreach ($configurations as $key => $config) {
            $this->installConfiguration($key, $config);
        }

        return true;
    }

    /**
     * Uninstall configurations by keys.
     *
     * @param array<int, string> $configKeys Array of configuration keys
     * @return bool
     * @throws InstallationException
     */
    public function uninstall(array $configKeys): bool
    {
        foreach ($configKeys as $key) {
            $this->deleteConfiguration($key);
        }

        return true;
    }

    /**
     * Install a single configuration.
     *
     * Configuration format:
     * [
     *     'value' => mixed,        // The configuration value
     *     'global' => bool,        // Whether it's a global config (default: false)
     *     'html' => bool,          // Whether the value contains HTML (default: false)
     *     'encrypted' => bool,     // Whether to encrypt the value (default: false)
     *     'shops' => array,        // Specific shops for multi-shop (optional)
     *     'default' => mixed       // Default value if not set (optional)
     * ]
     *
     * @param string $key Configuration key
     * @param array<string, mixed> $config Configuration options
     * @return bool
     * @throws InstallationException
     */
    public function installConfiguration(string $key, array $config): bool
    {
        $value = $config['value'] ?? $config['default'] ?? '';
        $isGlobal = $config['global'] ?? false;
        $isHtml = $config['html'] ?? false;
        $isEncrypted = $config['encrypted'] ?? false;
        $specificShops = $config['shops'] ?? null;

        // Check if already exists
        if ($this->configurationExists($key)) {
            return true; // Already configured
        }

        // Handle encrypted values
        if ($isEncrypted && !empty($value)) {
            $value = $this->encryptValue($value);
        }

        // Handle multi-shop configuration
        if ($specificShops !== null && Shop::isFeatureActive()) {
            return $this->installMultiShopConfiguration($key, $value, $isHtml, $specificShops);
        }

        // Standard configuration
        if ($isGlobal) {
            $result = Configuration::updateGlobalValue($key, $value, $isHtml);
        } else {
            $result = Configuration::updateValue($key, $value, $isHtml);
        }

        if (!$result) {
            throw InstallationException::configurationFailed($key);
        }

        return true;
    }

    /**
     * Install configuration for specific shops.
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param bool $isHtml Whether value contains HTML
     * @param array<int> $shopIds Array of shop IDs
     * @return bool
     * @throws InstallationException
     */
    private function installMultiShopConfiguration(string $key, $value, bool $isHtml, array $shopIds): bool
    {
        foreach ($shopIds as $shopId) {
            $result = Configuration::updateValue($key, $value, $isHtml, null, (int) $shopId);
            if (!$result) {
                throw InstallationException::configurationFailed(sprintf('%s (shop %s)', $key, $shopId));
            }
        }

        return true;
    }

    /**
     * Delete a configuration.
     *
     * @param string $key Configuration key
     * @return bool
     */
    public function deleteConfiguration(string $key): bool
    {
        if (Shop::isFeatureActive()) {
            $shops = Shop::getShops(true, null, true);

            foreach ($shops as $shopId) {
                Configuration::deleteByName($key, (int) $shopId);
            }
        }

        return Configuration::deleteByName($key);
    }

    /**
     * Check if a configuration exists.
     *
     * @param string $key Configuration key
     * @return bool
     */
    public function configurationExists(string $key): bool
    {
        return Configuration::hasKey($key);
    }

    /**
     * Get configuration value.
     *
     * @param string $key Configuration key
     * @param int|null $shopId Shop ID for multi-shop
     * @param mixed $default Default value
     * @return mixed
     */
    public function getConfiguration(string $key, ?int $shopId = null, $default = null)
    {
        if (null !== $shopId) {
            $value = Configuration::get($key, null, null, $shopId);
        } else {
            $value = Configuration::get($key);
        }

        return $value !== false ? $value : $default;
    }

    /**
     * Update configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value New value
     * @param bool $isHtml Whether value contains HTML
     * @param int|null $shopId Shop ID for multi-shop
     * @param bool $isGlobal Whether it's a global configuration
     * @return bool
     */
    public function updateConfiguration(string $key, $value, bool $isHtml = false, ?int $shopId = null, bool $isGlobal = false): bool
    {
        if ($isGlobal) {
            return Configuration::updateGlobalValue($key, $value, $isHtml);
        }

        if (null !== $shopId) {
            return Configuration::updateValue($key, $value, $isHtml, null, $shopId);
        }

        return Configuration::updateValue($key, $value, $isHtml);
    }

    /**
     * Get all configurations for a module prefix.
     *
     * @param string $prefix Configuration key prefix
     * @return array<string, mixed>
     */
    public function getConfigurationsByPrefix(string $prefix): array
    {
        $configs = [];

        // Get all configuration keys
        $allKeys = Configuration::getMultiple([]);

        foreach ($allKeys as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $configs[$key] = $value;
            }
        }

        return $configs;
    }

    /**
     * Encrypt a configuration value.
     *
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    private function encryptValue(string $value): string
    {
        // Use PrestaShop's encryption method if available
        if (method_exists(Configuration::class, 'encrypt')) {
            return Configuration::encrypt($value);
        }

        // Fallback to basic encryption
        return base64_encode($value);
    }

    /**
     * Decrypt a configuration value.
     *
     * @param string $encryptedValue Encrypted value
     * @return string Decrypted value
     */
    public function decryptValue(string $encryptedValue): string
    {
        // Use PrestaShop's decryption method if available
        if (method_exists(Configuration::class, 'decrypt')) {
            return Configuration::decrypt($encryptedValue);
        }

        // Fallback to basic decryption
        return base64_decode($encryptedValue);
    }

    /**
     * Validate configuration value against constraints.
     *
     * @param mixed $value Value to validate
     * @param array<string, mixed> $constraints Validation constraints
     * @return bool
     * @throws InstallationException
     */
    public function validateConfigurationValue($value, array $constraints): bool
    {
        if (isset($constraints['required']) && $constraints['required'] && empty($value)) {
            throw new InstallationException('Required configuration value is empty');
        }

        if (isset($constraints['type'])) {
            $type = $constraints['type'];
            if ($type === 'int' && !is_numeric($value)) {
                throw new InstallationException('Configuration value must be numeric');
            }
            if ($type === 'bool' && !is_bool($value) && !in_array(strtolower((string) $value), ['0', '1', 'true', 'false'])) {
                throw new InstallationException('Configuration value must be boolean');
            }
        }

        if (isset($constraints['min']) && is_numeric($value) && $value < $constraints['min']) {
            throw new InstallationException(sprintf('Configuration value must be at least %s', $constraints['min']));
        }

        if (isset($constraints['max']) && is_numeric($value) && $value > $constraints['max']) {
            throw new InstallationException(sprintf('Configuration value must be at most %s', $constraints['max']));
        }

        if (isset($constraints['options']) && !in_array($value, $constraints['options'])) {
            throw new InstallationException('Configuration value not in allowed options');
        }

        return true;
    }
}

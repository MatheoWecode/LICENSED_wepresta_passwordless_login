<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Plugin;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\PluginInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Exception\DependencyException;

/**
 * Registry for managing loaded plugins.
 *
 * Handles plugin instantiation, dependency resolution, and lifecycle.
 *
 * @example
 * $registry = new PluginRegistry(new PluginDiscovery());
 * $registry->loadAll();
 *
 * // Get a specific plugin
 * $plugin = $registry->get('MyPlugin');
 *
 * // Get all field types from all plugins
 * $fieldTypes = $registry->collectFieldTypes();
 */
final class PluginRegistry
{
    /**
     * Loaded plugin instances.
     *
     * @var array<string, PluginInterface>
     */
    private array $plugins = [];

    /**
     * Plugin metadata.
     *
     * @var array<string, PluginInfo>
     */
    private array $pluginInfos = [];

    /**
     * Whether plugins have been booted.
     */
    private bool $booted = false;

    public function __construct(
        private readonly PluginDiscovery $discovery
    ) {
    }

    /**
     * Loads all discovered plugins.
     *
     * @throws DependencyException If dependencies cannot be resolved
     */
    public function loadAll(): void
    {
        $discovered = $this->discovery->discover();

        // Resolve load order based on dependencies
        $ordered = $this->resolveDependencies($discovered);

        foreach ($ordered as $pluginInfo) {
            $this->load($pluginInfo);
        }
    }

    /**
     * Loads a specific plugin by name.
     *
     * @throws DependencyException If plugin not found or dependencies missing
     */
    public function loadByName(string $name): void
    {
        $pluginInfo = $this->discovery->get($name);

        if ($pluginInfo === null) {
            throw DependencyException::pluginNotFound($name);
        }

        // Check dependencies
        foreach ($pluginInfo->dependencies as $dependency) {
            if (str_starts_with($dependency, 'ext:')) {
                // Extension dependency - handled elsewhere
                continue;
            }

            if (!$this->has($dependency) && !$this->discovery->has($dependency)) {
                throw DependencyException::missingPluginDependency($name, $dependency);
            }
        }

        $this->load($pluginInfo);
    }

    /**
     * Boots all loaded plugins.
     *
     * Call this after all plugins are loaded to trigger their boot() method.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }

        $this->booted = true;
    }

    /**
     * Gets a loaded plugin by name.
     */
    public function get(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Checks if a plugin is loaded.
     */
    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Gets all loaded plugins.
     *
     * @return array<string, PluginInterface>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    /**
     * Gets plugin info by name.
     */
    public function getInfo(string $name): ?PluginInfo
    {
        return $this->pluginInfos[$name] ?? null;
    }

    /**
     * Collects all field types from all plugins.
     *
     * @return array<string, class-string> Map of field type key => class name
     */
    public function collectFieldTypes(): array
    {
        $fieldTypes = [];

        foreach ($this->plugins as $plugin) {
            $fieldTypes = array_merge($fieldTypes, $plugin->getFieldTypes());
        }

        return $fieldTypes;
    }

    /**
     * Collects all services from all plugins.
     *
     * @return array<class-string>
     */
    public function collectServices(): array
    {
        $services = [];

        foreach ($this->plugins as $plugin) {
            $services = array_merge($services, $plugin->getServices());
        }

        return array_unique($services);
    }

    /**
     * Loads a plugin from its info.
     */
    private function load(PluginInfo $pluginInfo): void
    {
        if ($this->has($pluginInfo->name)) {
            return; // Already loaded
        }

        /** @var PluginInterface $instance */
        $instance = $pluginInfo->createInstance();

        $this->plugins[$pluginInfo->name] = $instance;
        $this->pluginInfos[$pluginInfo->name] = $pluginInfo;
    }

    /**
     * Resolves dependencies and returns plugins in load order.
     *
     * @param array<string, PluginInfo> $plugins
     * @return array<PluginInfo>
     * @throws DependencyException If circular dependency detected
     */
    private function resolveDependencies(array $plugins): array
    {
        $resolved = [];
        $resolving = [];

        foreach ($plugins as $plugin) {
            $this->resolvePlugin($plugin, $plugins, $resolved, $resolving);
        }

        return $resolved;
    }

    /**
     * Recursively resolves a plugin's dependencies.
     *
     * @param array<string, PluginInfo> $plugins
     * @param array<PluginInfo> $resolved
     * @param array<string, bool> $resolving
     * @throws DependencyException If circular dependency detected
     */
    private function resolvePlugin(
        PluginInfo $plugin,
        array $plugins,
        array &$resolved,
        array &$resolving
    ): void {
        // Check if already resolved
        foreach ($resolved as $r) {
            if ($r->name === $plugin->name) {
                return;
            }
        }

        // Check for circular dependency
        if (isset($resolving[$plugin->name])) {
            throw DependencyException::circularDependency($plugin->name);
        }

        $resolving[$plugin->name] = true;

        // Resolve dependencies first
        foreach ($plugin->dependencies as $dependency) {
            if (str_starts_with($dependency, 'ext:')) {
                continue; // Extension dependencies are checked elsewhere
            }

            if (isset($plugins[$dependency])) {
                $this->resolvePlugin($plugins[$dependency], $plugins, $resolved, $resolving);
            }
            // If dependency is not a plugin, assume it's a module (checked at boot time)
        }

        unset($resolving[$plugin->name]);
        $resolved[] = $plugin;
    }
}


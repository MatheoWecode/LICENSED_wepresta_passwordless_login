<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Plugin;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\PluginInterface;

/**
 * Discovers plugins from various sources.
 *
 * Scans directories for classes implementing PluginInterface:
 * - modules/[name]/src/Plugin/*.php
 * - themes/[name]/modules/[name]/Plugin/*.php
 * - Custom paths registered via addPath()
 *
 * @example
 * $discovery = new PluginDiscovery();
 * $discovery->addPath('/custom/plugins/');
 * $plugins = $discovery->discover();
 * // Returns: [PluginInfo, PluginInfo, ...]
 */
final class PluginDiscovery
{
    /**
     * Default discovery paths relative to PrestaShop root.
     */
    private const DEFAULT_PATHS = [
        'modules/[name]/src/Plugin',
        'themes/[name]/modules/[name]/Plugin',
    ];

    /**
     * Custom discovery paths.
     *
     * @var array<string>
     */
    private array $customPaths = [];

    /**
     * PrestaShop root directory.
     */
    private string $rootDir;

    /**
     * Cache of discovered plugins.
     *
     * @var array<string, PluginInfo>|null
     */
    private ?array $cache = null;

    public function __construct(?string $rootDir = null)
    {
        $this->rootDir = $rootDir ?? $this->detectRootDir();
    }

    /**
     * Adds a custom path to scan for plugins.
     */
    public function addPath(string $path): self
    {
        $this->customPaths[] = $path;
        $this->cache = null; // Invalidate cache

        return $this;
    }

    /**
     * Discovers all plugins from registered paths.
     *
     * @return array<string, PluginInfo> Map of plugin name => PluginInfo
     */
    public function discover(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = [];

        // Scan default paths
        foreach (self::DEFAULT_PATHS as $pattern) {
            $this->scanPattern($pattern);
        }

        // Scan custom paths
        foreach ($this->customPaths as $path) {
            $this->scanDirectory($path);
        }

        return $this->cache;
    }

    /**
     * Gets a specific plugin by name.
     */
    public function get(string $name): ?PluginInfo
    {
        $plugins = $this->discover();

        return $plugins[$name] ?? null;
    }

    /**
     * Checks if a plugin exists.
     */
    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * Clears the discovery cache.
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }

    /**
     * Scans a glob pattern for plugins.
     */
    private function scanPattern(string $pattern): void
    {
        $fullPattern = $this->rootDir . '/' . $pattern;
        $directories = glob($fullPattern, GLOB_ONLYDIR);

        if ($directories === false) {
            return;
        }

        foreach ($directories as $dir) {
            $this->scanDirectory($dir);
        }
    }

    /**
     * Scans a directory for plugin classes.
     */
    private function scanDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $this->processFile($file);
        }
    }

    /**
     * Processes a PHP file to find plugin classes.
     */
    private function processFile(string $file): void
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return;
        }

        // Extract namespace and class name
        $namespace = $this->extractNamespace($content);
        $className = $this->extractClassName($content);

        if ($namespace === null || $className === null) {
            return;
        }

        $fullClassName = $namespace . '\\' . $className;

        // Check if class exists and implements PluginInterface
        if (!class_exists($fullClassName)) {
            // Try to autoload
            require_once $file;
        }

        if (!class_exists($fullClassName)) {
            return;
        }

        $reflection = new \ReflectionClass($fullClassName);

        if (!$reflection->implementsInterface(PluginInterface::class)) {
            return;
        }

        if ($reflection->isAbstract() || $reflection->isInterface()) {
            return;
        }

        // Create PluginInfo
        $pluginInfo = new PluginInfo(
            name: $fullClassName::getName(),
            version: $fullClassName::getVersion(),
            dependencies: $fullClassName::getDependencies(),
            className: $fullClassName,
            filePath: $file
        );

        $this->cache[$pluginInfo->name] = $pluginInfo;
    }

    /**
     * Extracts namespace from PHP content.
     */
    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extracts class name from PHP content.
     */
    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Detects PrestaShop root directory.
     */
    private function detectRootDir(): string
    {
        // Try to find root from current file location
        $dir = __DIR__;

        while ($dir !== '/') {
            if (file_exists($dir . '/config/config.inc.php')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        // Fallback to _PS_ROOT_DIR_ constant
        if (defined('_PS_ROOT_DIR_')) {
            return _PS_ROOT_DIR_;
        }

        return getcwd() ?: '/';
    }
}


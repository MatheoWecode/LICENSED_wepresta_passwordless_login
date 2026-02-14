<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Plugin;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Value object containing plugin metadata.
 *
 * Immutable object returned by PluginDiscovery with all plugin information.
 */
final readonly class PluginInfo
{
    /**
     * @param string $name Plugin unique name
     * @param string $version Plugin version (SemVer)
     * @param array<string> $dependencies Required dependencies
     * @param class-string $className Full class name
     * @param string $filePath Absolute path to plugin file
     */
    public function __construct(
        public string $name,
        public string $version,
        public array $dependencies,
        public string $className,
        public string $filePath
    ) {
    }

    /**
     * Creates a new plugin instance.
     */
    public function createInstance(): object
    {
        return new ($this->className)();
    }

    /**
     * Checks if this plugin depends on another.
     */
    public function dependsOn(string $dependency): bool
    {
        return in_array($dependency, $this->dependencies, true);
    }

    /**
     * Checks if this plugin requires a WEDEV extension.
     */
    public function requiresExtension(string $extension): bool
    {
        return in_array('ext:' . $extension, $this->dependencies, true);
    }

    /**
     * Converts to array for serialization.
     *
     * @return array{name: string, version: string, dependencies: array<string>, className: string, filePath: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'dependencies' => $this->dependencies,
            'className' => $this->className,
            'filePath' => $this->filePath,
        ];
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Contract;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface for plugins that provide frontend/admin assets.
 *
 * Implement this interface when your plugin needs to register
 * JavaScript or CSS files in the back-office or front-office.
 *
 * Assets are returned as paths relative to the plugin's directory.
 *
 * @example
 * final class MyPlugin implements PluginInterface, AssetProviderInterface
 * {
 *     public function getAdminJsAssets(): array
 *     {
 *         return [
 *             'views/js/admin/my-plugin.js',
 *             'views/js/admin/components.js',
 *         ];
 *     }
 *
 *     public function getAdminCssAssets(): array
 *     {
 *         return ['views/css/admin/my-plugin.css'];
 *     }
 *
 *     public function getFrontJsAssets(): array
 *     {
 *         return ['views/js/front/my-plugin.js'];
 *     }
 *
 *     public function getFrontCssAssets(): array
 *     {
 *         return ['views/css/front/my-plugin.css'];
 *     }
 * }
 */
interface AssetProviderInterface
{
    /**
     * Returns admin JavaScript asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of JS file paths
     */
    public function getAdminJsAssets(): array;

    /**
     * Returns admin CSS asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of CSS file paths
     */
    public function getAdminCssAssets(): array;

    /**
     * Returns front-office JavaScript asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of JS file paths
     */
    public function getFrontJsAssets(): array;

    /**
     * Returns front-office CSS asset paths.
     *
     * Paths should be relative to the plugin's root directory.
     *
     * @return array<string> List of CSS file paths
     */
    public function getFrontCssAssets(): array;
}


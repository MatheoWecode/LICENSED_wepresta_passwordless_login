<?php
/**
 * Module autoloader - PrestaShop compatible
 *
 * This autoloader handles two contexts:
 * 1. PrestaShop context: Registers only the module's PSR-4 autoloader (no dev dependencies)
 * 2. CLI context (PHPStan, PHP-CS-Fixer): Loads full Composer autoloader
 *
 * IMPORTANT: PrestaShop loads vendor/autoload.php from modules during kernel boot.
 * After running `composer install`, execute `composer run patch-autoload` to make
 * vendor/autoload.php PrestaShop-safe (avoids Symfony version conflicts).
 */

declare(strict_types=1);

// In PrestaShop context, register only the module's PSR-4 autoloader
// This avoids conflicts with dev dependencies that have different Symfony versions
if (defined('_PS_VERSION_')) {
    if (!isset($GLOBALS['wepresta_passwordless_login_autoloader_registered'])) {
        $GLOBALS['wepresta_passwordless_login_autoloader_registered'] = true;
        $moduleDir = __DIR__;

        spl_autoload_register(function ($class) use ($moduleDir) {
            $prefix = 'Wepresta_Passwordless_Login' . '\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return false;
            }

            $baseDir = $moduleDir . '/src/';
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return true;
            }

            return false;
        }, true, true);
    }
    return;
}

// Outside PrestaShop (CLI for dev tools): load full Composer autoloader
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}



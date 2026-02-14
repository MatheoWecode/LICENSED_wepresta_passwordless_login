<?php
/**
 * WEDEV Core - ModuleAwareTrait
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Trait;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Module;

/**
 * Trait pour les classes qui ont besoin d'accéder au module.
 */
trait ModuleAwareTrait
{
    protected ?Module $module = null;

    /**
     * Définit l'instance du module.
     */
    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    /**
     * Récupère l'instance du module.
     */
    public function getModule(): ?Module
    {
        return $this->module;
    }

    /**
     * Récupère le nom du module.
     */
    public function getModuleName(): string
    {
        return $this->module?->name ?? '';
    }

    /**
     * Récupère la version du module.
     */
    public function getModuleVersion(): string
    {
        return $this->module?->version ?? '0.0.0';
    }

    /**
     * Récupère le chemin du module.
     */
    public function getModulePath(): string
    {
        if ($this->module === null) {
            return '';
        }

        return _PS_MODULE_DIR_ . $this->module->name . '/';
    }

    /**
     * Récupère un service du container Symfony.
     *
     * @template T of object
     * @param class-string<T> $serviceId
     * @return T|null
     */
    public function getService(string $serviceId): ?object
    {
        if ($this->module === null) {
            return null;
        }

        try {
            $container = $this->module->get('service_container');

            if ($container === null || !$container->has($serviceId)) {
                return null;
            }

            return $container->get($serviceId);
        } catch (\Exception $e) {
            return null;
        }
    }
}


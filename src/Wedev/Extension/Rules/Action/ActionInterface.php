<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Interface pour les actions de règles.
 */
interface ActionInterface
{
    /**
     * Exécute l'action dans le contexte donné.
     */
    public function execute(RuleContext $context): void;
}


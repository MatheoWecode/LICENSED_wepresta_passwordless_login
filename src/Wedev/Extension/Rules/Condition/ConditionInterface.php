<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Interface pour les conditions de règles.
 */
interface ConditionInterface
{
    /**
     * Évalue la condition dans le contexte donné.
     */
    public function evaluate(RuleContext $context): bool;
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition NOT (négation).
 *
 * @example
 * // Client NON connecté
 * $condition = new NotCondition(
 *     new CustomerCondition('is_logged', '=', true)
 * );
 */
final class NotCondition implements ConditionInterface
{
    public function __construct(
        private readonly ConditionInterface $condition
    ) {
    }

    public function evaluate(RuleContext $context): bool
    {
        return !$this->condition->evaluate($context);
    }
}


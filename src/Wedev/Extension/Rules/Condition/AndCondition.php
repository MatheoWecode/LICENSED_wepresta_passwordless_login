<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition composite AND.
 *
 * Toutes les conditions doivent être vraies.
 *
 * @example
 * $condition = new AndCondition([
 *     new CartCondition('total', '>=', 50),
 *     new CustomerCondition('is_logged', '=', true),
 * ]);
 */
final class AndCondition implements ConditionInterface
{
    /** @var array<ConditionInterface> */
    private array $conditions;

    /**
     * @param array<ConditionInterface> $conditions
     */
    public function __construct(array $conditions)
    {
        if (empty($conditions)) {
            throw new \InvalidArgumentException('AndCondition requires at least one condition.');
        }

        $this->conditions = $conditions;
    }

    public function evaluate(RuleContext $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->evaluate($context)) {
                return false;
            }
        }

        return true;
    }
}


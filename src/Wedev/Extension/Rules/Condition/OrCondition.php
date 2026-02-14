<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition composite OR.
 *
 * Au moins une condition doit être vraie.
 *
 * @example
 * $condition = new OrCondition([
 *     new DateCondition('is_weekend', '=', true),
 *     new CustomerCondition('is_new', '=', true),
 * ]);
 */
final class OrCondition implements ConditionInterface
{
    /** @var array<ConditionInterface> */
    private array $conditions;

    /**
     * @param array<ConditionInterface> $conditions
     */
    public function __construct(array $conditions)
    {
        if (empty($conditions)) {
            throw new \InvalidArgumentException('OrCondition requires at least one condition.');
        }

        $this->conditions = $conditions;
    }

    public function evaluate(RuleContext $context): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($context)) {
                return true;
            }
        }

        return false;
    }
}


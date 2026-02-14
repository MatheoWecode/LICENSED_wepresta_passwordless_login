<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action\ActionInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition\ConditionInterface;

/**
 * Implémentation d'une règle métier.
 *
 * @example
 * $rule = new Rule(
 *     name: 'upsell_vip',
 *     condition: new CartCondition('total', '>=', 100),
 *     action: new ShowUpsellAction($productId),
 *     enabled: true,
 *     priority: 10
 * );
 */
final class Rule implements RuleInterface
{
    public function __construct(
        private readonly string $name,
        private readonly ConditionInterface $condition,
        private readonly ?ActionInterface $action = null,
        private readonly bool $enabled = true,
        private readonly int $priority = 0
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCondition(): ConditionInterface
    {
        return $this->condition;
    }

    public function getAction(): ?ActionInterface
    {
        return $this->action;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}


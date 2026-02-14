<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action\ActionInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition\AndCondition;
use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition\ConditionInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition\OrCondition;

/**
 * Builder fluent pour créer des règles.
 *
 * @example
 * // Règle simple
 * $rule = RuleBuilder::create('free_shipping')
 *     ->when(new CartCondition('total', '>=', 50))
 *     ->then(new EnableFreeShippingAction())
 *     ->build();
 *
 * // Règle avec conditions combinées
 * $rule = RuleBuilder::create('vip_upsell')
 *     ->when(new CustomerCondition('group', '=', 3))
 *     ->and(new CartCondition('total', '>=', 100))
 *     ->then(new ShowUpsellAction($productId))
 *     ->priority(10)
 *     ->build();
 *
 * // Conditions OR
 * $rule = RuleBuilder::create('promo')
 *     ->when(new DateCondition('day_of_week', 'in', [0, 6]))  // Weekend
 *     ->or(new CustomerCondition('is_new', '=', true))        // Ou nouveau client
 *     ->then(new ApplyDiscountAction(5))
 *     ->build();
 */
final class RuleBuilder
{
    private string $name;
    private ?ConditionInterface $condition = null;
    private ?ActionInterface $action = null;
    private bool $enabled = true;
    private int $priority = 0;

    /** @var array<ConditionInterface> */
    private array $andConditions = [];

    /** @var array<ConditionInterface> */
    private array $orConditions = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Crée un nouveau builder.
     */
    public static function create(string $name): self
    {
        return new self($name);
    }

    /**
     * Définit la condition principale.
     */
    public function when(ConditionInterface $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Ajoute une condition AND.
     */
    public function and(ConditionInterface $condition): self
    {
        $this->andConditions[] = $condition;

        return $this;
    }

    /**
     * Ajoute une condition OR.
     */
    public function or(ConditionInterface $condition): self
    {
        $this->orConditions[] = $condition;

        return $this;
    }

    /**
     * Définit l'action à exécuter.
     */
    public function then(ActionInterface $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Active ou désactive la règle.
     */
    public function enabled(bool $enabled = true): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Désactive la règle.
     */
    public function disabled(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Définit la priorité (plus haut = évalué en premier).
     */
    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Construit la règle.
     *
     * @throws \InvalidArgumentException Si aucune condition n'est définie
     */
    public function build(): Rule
    {
        if ($this->condition === null) {
            throw new \InvalidArgumentException('Rule must have at least one condition. Use when() to add one.');
        }

        $finalCondition = $this->buildCondition();

        return new Rule(
            name: $this->name,
            condition: $finalCondition,
            action: $this->action,
            enabled: $this->enabled,
            priority: $this->priority
        );
    }

    /**
     * Construit la condition finale en combinant AND et OR.
     */
    private function buildCondition(): ConditionInterface
    {
        $condition = $this->condition;

        // Combiner avec AND
        if (!empty($this->andConditions)) {
            $allConditions = array_merge([$condition], $this->andConditions);
            $condition = new AndCondition($allConditions);
        }

        // Combiner avec OR
        if (!empty($this->orConditions)) {
            $allConditions = array_merge([$condition], $this->orConditions);
            $condition = new OrCondition($allConditions);
        }

        return $condition;
    }
}


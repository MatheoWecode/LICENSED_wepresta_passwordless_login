<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;

/**
 * Moteur d'évaluation de règles métier.
 *
 * Évalue des règles conditionnelles et exécute les actions associées.
 *
 * @example
 * $engine = new RuleEngine();
 *
 * // Créer des règles
 * $rules = [
 *     RuleBuilder::create('vip_discount')
 *         ->when(new CustomerCondition('group', 'in', [3, 4]))
 *         ->then(new ApplyDiscountAction(10))
 *         ->priority(10)
 *         ->build(),
 *
 *     RuleBuilder::create('cart_upsell')
 *         ->when(new CartCondition('total', '>=', 50))
 *         ->then(new ShowUpsellAction($productId))
 *         ->build(),
 * ];
 *
 * // Évaluer la première règle qui match
 * $context = RuleContext::fromCurrentCart();
 * $matchedRule = $engine->evaluateFirst($rules, $context);
 *
 * if ($matchedRule) {
 *     $matchedRule->getAction()?->execute($context);
 * }
 *
 * // Ou directement exécuter
 * $engine->executeFirst($rules, $context);
 */
final class RuleEngine implements ExtensionInterface
{
    use LoggerTrait;

    public static function getName(): string
    {
        return 'Rules';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Évalue une règle unique.
     */
    public function evaluate(RuleInterface $rule, RuleContext $context): bool
    {
        if (!$rule->isEnabled()) {
            return false;
        }

        try {
            $condition = $rule->getCondition();
            $result = $condition->evaluate($context);

            $this->log('debug', sprintf(
                'Rule "%s" evaluated to %s',
                $rule->getName(),
                $result ? 'TRUE' : 'FALSE'
            ));

            return $result;
        } catch (\Throwable $e) {
            $this->log('error', sprintf(
                'Rule "%s" evaluation failed: %s',
                $rule->getName(),
                $e->getMessage()
            ));

            return false;
        }
    }

    /**
     * Retourne la première règle qui match.
     *
     * Les règles sont triées par priorité décroissante.
     *
     * @param iterable<RuleInterface> $rules
     */
    public function evaluateFirst(iterable $rules, RuleContext $context): ?RuleInterface
    {
        $sortedRules = $this->sortByPriority($rules);

        foreach ($sortedRules as $rule) {
            if ($this->evaluate($rule, $context)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Retourne toutes les règles qui matchent.
     *
     * @param iterable<RuleInterface> $rules
     *
     * @return array<RuleInterface>
     */
    public function evaluateAll(iterable $rules, RuleContext $context): array
    {
        $matched = [];

        foreach ($rules as $rule) {
            if ($this->evaluate($rule, $context)) {
                $matched[] = $rule;
            }
        }

        return $matched;
    }

    /**
     * Évalue et exécute l'action de la première règle qui match.
     *
     * @param iterable<RuleInterface> $rules
     *
     * @return bool True si une règle a été exécutée
     */
    public function executeFirst(iterable $rules, RuleContext $context): bool
    {
        $rule = $this->evaluateFirst($rules, $context);

        if ($rule === null) {
            return false;
        }

        $action = $rule->getAction();
        if ($action !== null) {
            $this->log('info', sprintf('Executing action for rule "%s"', $rule->getName()));
            $action->execute($context);
        }

        return true;
    }

    /**
     * Évalue et exécute les actions de toutes les règles qui matchent.
     *
     * @param iterable<RuleInterface> $rules
     *
     * @return int Nombre de règles exécutées
     */
    public function executeAll(iterable $rules, RuleContext $context): int
    {
        $executed = 0;

        foreach ($this->evaluateAll($rules, $context) as $rule) {
            $action = $rule->getAction();
            if ($action !== null) {
                $this->log('info', sprintf('Executing action for rule "%s"', $rule->getName()));
                $action->execute($context);
                $executed++;
            }
        }

        return $executed;
    }

    /**
     * Trie les règles par priorité décroissante.
     *
     * @param iterable<RuleInterface> $rules
     *
     * @return array<RuleInterface>
     */
    private function sortByPriority(iterable $rules): array
    {
        $rulesArray = $rules instanceof \Traversable
            ? iterator_to_array($rules)
            : (array) $rules;

        usort($rulesArray, static fn (RuleInterface $a, RuleInterface $b): int => $b->getPriority() <=> $a->getPriority());

        return $rulesArray;
    }
}


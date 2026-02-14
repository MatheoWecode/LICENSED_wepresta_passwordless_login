<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Action composite qui exécute plusieurs actions.
 *
 * @example
 * $action = new CompositeAction([
 *     new LogAction('Promo applied'),
 *     new SetVariableAction('discount', 10),
 *     new NotifyAction($adminEmail),
 * ]);
 */
final class CompositeAction implements ActionInterface
{
    /** @var array<ActionInterface> */
    private array $actions;

    /**
     * @param array<ActionInterface> $actions
     */
    public function __construct(array $actions)
    {
        $this->actions = $actions;
    }

    public function execute(RuleContext $context): void
    {
        foreach ($this->actions as $action) {
            $action->execute($context);
        }
    }
}


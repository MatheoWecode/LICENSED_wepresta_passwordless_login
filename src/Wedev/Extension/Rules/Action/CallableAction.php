<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Action basée sur une callback.
 *
 * Permet d'exécuter une fonction personnalisée.
 *
 * @example
 * $action = new CallableAction(function(RuleContext $context) {
 *     $cart = $context->getCart();
 *     // Logique personnalisée...
 * });
 */
final class CallableAction implements ActionInterface
{
    /** @var callable(RuleContext): void */
    private $callback;

    /**
     * @param callable(RuleContext): void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function execute(RuleContext $context): void
    {
        ($this->callback)($context);
    }
}


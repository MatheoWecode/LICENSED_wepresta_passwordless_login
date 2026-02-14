<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Action qui définit une variable dans le contexte Smarty.
 *
 * Utilisée pour passer des données aux templates.
 *
 * @example
 * // Afficher un message de promotion
 * $action = new SetContextAction('promo_message', '10% de réduction pour les VIP !');
 *
 * // Dans le template Smarty:
 * // {if $promo_message}{$promo_message}{/if}
 */
final class SetContextAction implements ActionInterface
{
    public function __construct(
        private readonly string $key,
        private readonly mixed $value
    ) {
    }

    public function execute(RuleContext $context): void
    {
        $psContext = \Context::getContext();

        if (isset($psContext->smarty)) {
            $psContext->smarty->assign($this->key, $this->value);
        }
    }
}


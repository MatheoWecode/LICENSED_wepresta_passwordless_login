<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Action;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;
use PrestaShopLogger;

/**
 * Action qui log un message.
 *
 * @example
 * $action = new LogAction('VIP discount applied for customer', 1);
 */
final class LogAction implements ActionInterface
{
    /**
     * @param string $message  Message à logger
     * @param int    $severity Niveau de sévérité (1=info, 2=warning, 3=error)
     */
    public function __construct(
        private readonly string $message,
        private readonly int $severity = 1
    ) {
    }

    public function execute(RuleContext $context): void
    {
        $finalMessage = $this->interpolate($this->message, $context);

        PrestaShopLogger::addLog(
            $finalMessage,
            $this->severity,
            null,
            'RuleEngine',
            0,
            true
        );
    }

    /**
     * Interpole les variables du contexte dans le message.
     */
    private function interpolate(string $message, RuleContext $context): string
    {
        $replacements = [
            '{customer_id}' => (string) ($context->getCustomer()?->id ?? 'guest'),
            '{cart_total}' => number_format($context->getCartTotal(), 2),
            '{shop_id}' => (string) $context->getShopId(),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }
}


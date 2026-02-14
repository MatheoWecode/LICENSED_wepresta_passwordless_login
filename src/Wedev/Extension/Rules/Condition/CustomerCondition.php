<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition basée sur le client.
 *
 * @example
 * // Client dans un groupe VIP
 * new CustomerCondition('group', 'in', [3, 4])
 *
 * // Client connecté
 * new CustomerCondition('is_logged', '=', true)
 *
 * // Client avec plus de 5 commandes
 * new CustomerCondition('orders_count', '>=', 5)
 *
 * // Client ayant dépensé plus de 500€
 * new CustomerCondition('total_spent', '>', 500)
 *
 * // Nouveau client (inscrit il y a moins de 30 jours)
 * new CustomerCondition('is_new', '=', true)
 */
final class CustomerCondition extends AbstractCondition
{
    private const SUPPORTED_FIELDS = [
        'is_logged',
        'is_guest',
        'group',
        'groups',
        'orders_count',
        'total_spent',
        'newsletter',
        'is_new',
        'days_since_registration',
        'days_since_last_order',
        'email_domain',
    ];

    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed $value
    ) {
        if (!in_array($this->field, self::SUPPORTED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported customer field: "%s". Supported: %s',
                $this->field,
                implode(', ', self::SUPPORTED_FIELDS)
            ));
        }
    }

    public function evaluate(RuleContext $context): bool
    {
        $customer = $context->getCustomer();
        $customerValue = $this->getCustomerValue($customer);

        return $this->compare($customerValue, $this->operator, $this->value);
    }

    /**
     * Extrait la valeur du client pour le champ demandé.
     */
    private function getCustomerValue(?\Customer $customer): mixed
    {
        return match ($this->field) {
            'is_logged' => $customer !== null && $customer->isLogged(),
            'is_guest' => $customer === null || !$customer->isLogged(),
            'group' => $customer !== null ? (int) $customer->id_default_group : 0,
            'groups' => $customer !== null ? $customer->getGroups() : [],
            'orders_count' => $this->getOrdersCount($customer),
            'total_spent' => $this->getTotalSpent($customer),
            'newsletter' => $customer?->newsletter ?? false,
            'is_new' => $this->isNewCustomer($customer),
            'days_since_registration' => $this->getDaysSinceRegistration($customer),
            'days_since_last_order' => $this->getDaysSinceLastOrder($customer),
            'email_domain' => $this->getEmailDomain($customer),
            default => null,
        };
    }

    private function getOrdersCount(?\Customer $customer): int
    {
        if ($customer === null || !$customer->id) {
            return 0;
        }

        return (int) \Order::getCustomerNbOrders($customer->id);
    }

    private function getTotalSpent(?\Customer $customer): float
    {
        if ($customer === null || !$customer->id) {
            return 0.0;
        }

        $spent = \Customer::$definition['fields']['id_customer'] ?? null
            ? \Db::getInstance()->getValue(sprintf(
                'SELECT SUM(o.total_paid_real) FROM %sorders o WHERE o.id_customer = %d AND o.valid = 1',
                _DB_PREFIX_,
                (int) $customer->id
            ))
            : 0;

        return (float) $spent;
    }

    private function isNewCustomer(?\Customer $customer): bool
    {
        return $this->getDaysSinceRegistration($customer) <= 30;
    }

    private function getDaysSinceRegistration(?\Customer $customer): int
    {
        if ($customer === null || empty($customer->date_add)) {
            return 999999;
        }

        $registrationDate = strtotime($customer->date_add);
        if ($registrationDate === false) {
            return 999999;
        }

        return (int) ((time() - $registrationDate) / 86400);
    }

    private function getDaysSinceLastOrder(?\Customer $customer): int
    {
        if ($customer === null || !$customer->id) {
            return 999999;
        }

        $lastOrderDate = \Db::getInstance()->getValue(sprintf(
            'SELECT MAX(o.date_add) FROM %sorders o WHERE o.id_customer = %d AND o.valid = 1',
            _DB_PREFIX_,
            (int) $customer->id
        ));

        if (empty($lastOrderDate)) {
            return 999999;
        }

        $timestamp = strtotime($lastOrderDate);
        if ($timestamp === false) {
            return 999999;
        }

        return (int) ((time() - $timestamp) / 86400);
    }

    private function getEmailDomain(?\Customer $customer): string
    {
        if ($customer === null || empty($customer->email)) {
            return '';
        }

        $parts = explode('@', $customer->email);

        return $parts[1] ?? '';
    }
}


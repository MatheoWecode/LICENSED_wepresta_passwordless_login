<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use Customer;
use Validate;

class CustomerCreator
{
    /**
     * Find an existing customer by email.
     */
    public function findByEmail(string $email): ?Customer
    {
        $email = strtolower(trim($email));
        $customer = new Customer();
        $customer = $customer->getByEmail($email);

        if ($customer && Validate::isLoadedObject($customer)) {
            return $customer;
        }

        return null;
    }

    /**
     * Create a new customer with a random password.
     */
    public function createCustomer(string $email, string $firstName, string $lastName, int $shopId): Customer
    {
        $customer = new Customer();
        $customer->email = strtolower(trim($email));
        $customer->firstname = trim($firstName);
        $customer->lastname = trim($lastName);
        $customer->passwd = $this->generateRandomPassword();
        $customer->active = true;
        $customer->is_guest = false;
        $customer->id_shop = $shopId;
        $customer->id_default_group = (int) \Configuration::get('PS_CUSTOMER_GROUP');
        $customer->id_lang = (int) Context::getContext()->language->id;
        $customer->newsletter = false;
        $customer->optin = false;

        $customer->add();

        // Add customer to default group
        $customer->addGroups([(int) \Configuration::get('PS_CUSTOMER_GROUP')]);

        return $customer;
    }

    /**
     * Log a customer into the current session.
     */
    public function loginCustomer(Customer $customer, Context $context): void
    {
        $context->updateCustomer($customer);
    }

    /**
     * Generate a random strong password that the customer will never know.
     */
    private function generateRandomPassword(): string
    {
        return bin2hex(random_bytes(16));
    }
}

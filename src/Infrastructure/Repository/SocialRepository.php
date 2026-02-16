<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Infrastructure\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

use DbQuery;
use Wepresta_Passwordless_Login\Wedev\Core\Repository\AbstractRepository;

class SocialRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'passwordless_social';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_social';
    }

    /**
     * Find a social link by provider and provider user ID.
     *
     * @return array<string, mixed>|null
     */
    public function findByProviderAndId(string $provider, string $providerId, int $shopId): ?array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where('`provider` = \'' . pSQL($provider) . '\'')
            ->where('`provider_id` = \'' . pSQL($providerId) . '\'')
            ->where('`id_shop` = ' . (int) $shopId);

        $result = $this->db->getRow($query);

        return $result ?: null;
    }

    /**
     * Find all social links for a customer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCustomer(int $customerId): array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where('`id_customer` = ' . (int) $customerId);

        return $this->db->executeS($query) ?: [];
    }

    /**
     * Link a social account to a customer.
     */
    public function linkAccount(int $customerId, string $provider, string $providerId, int $shopId): int
    {
        $existing = $this->findByProviderAndId($provider, $providerId, $shopId);
        if ($existing !== null) {
            return (int) $existing['id_social'];
        }

        $this->db->insert($this->getTableName(), [
            'id_customer' => (int) $customerId,
            'provider' => pSQL($provider),
            'provider_id' => pSQL($providerId),
            'id_shop' => (int) $shopId,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->Insert_ID();
    }

    /**
     * Find a social link by customer and provider.
     *
     * @return array<string, mixed>|null
     */
    public function findByCustomerAndProvider(int $customerId, string $provider): ?array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where('`id_customer` = ' . (int) $customerId)
            ->where('`provider` = \'' . pSQL($provider) . '\'');

        $result = $this->db->getRow($query);

        return $result ?: null;
    }
}

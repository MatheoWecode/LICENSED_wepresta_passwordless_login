<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Infrastructure\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use DbQuery;
use Wepresta_Passwordless_Login\Wedev\Core\Repository\AbstractRepository;

class CodeRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'passwordless_code';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_code';
    }

    /**
     * Find the latest valid (unused, non-expired) code for an email + shop.
     *
     * @return array<string, mixed>|null
     */
    public function findValidCode(string $email, int $shopId): ?array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where('`email` = \'' . pSQL(strtolower($email)) . '\'')
            ->where('`id_shop` = ' . (int) $shopId)
            ->where('`used` = 0')
            ->where('`expires_at` > NOW()')
            ->orderBy('`id_code` DESC');

        $result = $this->db->getRow($query);

        return $result ?: null;
    }

    /**
     * Count codes sent to an email within the last N minutes.
     */
    public function countRecentByEmail(string $email, int $shopId, int $minutes = 60): int
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('`email` = \'' . pSQL(strtolower($email)) . '\'')
            ->where('`id_shop` = ' . (int) $shopId)
            ->where('`date_add` > DATE_SUB(NOW(), INTERVAL ' . (int) $minutes . ' MINUTE)');

        return (int) $this->db->getValue($query);
    }

    /**
     * Store a new verification code.
     */
    public function storeCode(string $email, string $codeHash, int $shopId, ?int $customerId = null): int
    {
        $expirationMinutes = (int) Configuration::get('WEPRESTA_PL_CODE_EXPIRATION') ?: 10;

        $data = [
            'email' => pSQL(strtolower($email)),
            'code_hash' => pSQL($codeHash),
            'id_shop' => (int) $shopId,
            'expires_at' => date('Y-m-d H:i:s', time() + ($expirationMinutes * 60)),
            'attempts' => 0,
            'used' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        if ($customerId !== null) {
            $data['id_customer'] = (int) $customerId;
        }

        $this->db->insert($this->getTableName(), $data);

        return (int) $this->db->Insert_ID();
    }

    /**
     * Mark a code as used.
     */
    public function markAsUsed(int $idCode): bool
    {
        return $this->db->update(
            $this->getTableName(),
            ['used' => 1],
            '`id_code` = ' . (int) $idCode
        );
    }

    /**
     * Increment the attempt counter for a code.
     */
    public function incrementAttempts(int $idCode): bool
    {
        return $this->db->execute(
            'UPDATE `' . $this->getTable() . '` SET `attempts` = `attempts` + 1 WHERE `id_code` = ' . (int) $idCode
        );
    }

    /**
     * Invalidate all previous unused codes for an email + shop.
     */
    public function invalidatePreviousCodes(string $email, int $shopId): bool
    {
        return $this->db->update(
            $this->getTableName(),
            ['used' => 1],
            '`email` = \'' . pSQL(strtolower($email)) . '\' AND `id_shop` = ' . (int) $shopId . ' AND `used` = 0'
        );
    }

    /**
     * Check if a recently verified code exists for this email (within last N minutes).
     * Used to validate profile completion requests.
     */
    public function hasRecentlyVerifiedCode(string $email, int $shopId, int $withinMinutes = 15): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('`email` = \'' . pSQL(strtolower($email)) . '\'')
            ->where('`id_shop` = ' . (int) $shopId)
            ->where('`used` = 1')
            ->where('`date_add` > DATE_SUB(NOW(), INTERVAL ' . (int) $withinMinutes . ' MINUTE)');

        return (int) $this->db->getValue($query) > 0;
    }

    /**
     * Clean up expired codes older than 24 hours.
     */
    public function cleanupExpired(): int
    {
        $this->db->delete(
            $this->getTableName(),
            '`expires_at` < DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );

        return (int) $this->db->Affected_Rows();
    }
}

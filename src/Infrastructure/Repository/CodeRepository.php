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
    private static bool $schemaChecked = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function getTableName(): string
    {
        return 'passwordless_code';
    }

    protected function getPrimaryKey(): string
    {
        return 'id_code';
    }

    /**
     * One-time schema migration for existing installations.
     * Adds `verified` and `ip_address` columns if missing.
     */
    private function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        if (Configuration::get('WEPRESTA_PL_DB_V2')) {
            return;
        }

        $table = $this->getTable();

        // Check which columns already exist to avoid duplicate column errors
        $columns = $this->db->executeS("SHOW COLUMNS FROM `{$table}`");
        $existingColumns = array_column($columns, 'Field');

        if (!in_array('verified', $existingColumns, true)) {
            $this->db->execute(
                "ALTER TABLE `{$table}` ADD COLUMN `verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `used`"
            );
        }

        if (!in_array('ip_address', $existingColumns, true)) {
            $this->db->execute(
                "ALTER TABLE `{$table}` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `id_shop`"
            );
        }

        // Check if index exists before adding
        $indexes = $this->db->executeS("SHOW INDEX FROM `{$table}` WHERE Key_name = 'idx_ip_date'");
        if (empty($indexes)) {
            $this->db->execute(
                "ALTER TABLE `{$table}` ADD KEY `idx_ip_date` (`ip_address`, `date_add`)"
            );
        }

        Configuration::updateValue('WEPRESTA_PL_DB_V2', 1);
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
     * Count codes sent from an IP address within the last N minutes.
     */
    public function countRecentByIp(string $ipAddress, int $minutes = 60): int
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('`ip_address` = \'' . pSQL($ipAddress) . '\'')
            ->where('`date_add` > DATE_SUB(NOW(), INTERVAL ' . (int) $minutes . ' MINUTE)');

        return (int) $this->db->getValue($query);
    }

    /**
     * Store a new verification code.
     */
    public function storeCode(string $email, string $codeHash, int $shopId, ?int $customerId = null, ?string $ipAddress = null): int
    {
        $expirationMinutes = (int) Configuration::get('WEPRESTA_PL_CODE_EXPIRATION') ?: 10;

        $data = [
            'email' => pSQL(strtolower($email)),
            'code_hash' => pSQL($codeHash),
            'id_shop' => (int) $shopId,
            'expires_at' => date('Y-m-d H:i:s', time() + ($expirationMinutes * 60)),
            'attempts' => 0,
            'used' => 0,
            'verified' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        if ($customerId !== null) {
            $data['id_customer'] = (int) $customerId;
        }

        if ($ipAddress !== null) {
            $data['ip_address'] = pSQL($ipAddress);
        }

        $this->db->insert($this->getTableName(), $data);

        return (int) $this->db->Insert_ID();
    }

    /**
     * Mark a code as used (does NOT mark as verified).
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
     * Mark a code as used AND verified (only on successful code verification).
     */
    public function markAsVerified(int $idCode): bool
    {
        return $this->db->update(
            $this->getTableName(),
            ['used' => 1, 'verified' => 1],
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
     * Check if a recently VERIFIED code exists for this email (within last N minutes).
     * Only codes that were successfully verified (not just exhausted) qualify.
     */
    public function hasRecentlyVerifiedCode(string $email, int $shopId, int $withinMinutes = 15): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from($this->getTableName())
            ->where('`email` = \'' . pSQL(strtolower($email)) . '\'')
            ->where('`id_shop` = ' . (int) $shopId)
            ->where('`verified` = 1')
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

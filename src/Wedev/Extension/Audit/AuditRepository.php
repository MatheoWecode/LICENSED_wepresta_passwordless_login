<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Audit;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Db;

/**
 * Repository pour la gestion des entrées d'audit.
 */
final class AuditRepository
{
    private const TABLE_NAME = 'wedev_audit';

    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . self::TABLE_NAME;
    }

    /**
     * Crée la table d'audit.
     */
    public function createTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id_audit` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `action` VARCHAR(50) NOT NULL,
            `entity_type` VARCHAR(100) NOT NULL,
            `entity_id` INT UNSIGNED NULL,
            `user_id` INT UNSIGNED NULL,
            `user_name` VARCHAR(150) NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `old_values` LONGTEXT NULL,
            `new_values` LONGTEXT NULL,
            `context` TEXT NULL,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_audit`),
            INDEX `idx_entity` (`entity_type`, `entity_id`),
            INDEX `idx_user` (`user_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_created` (`created_at`),
            INDEX `idx_shop` (`id_shop`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Supprime la table d'audit.
     */
    public function dropTable(): bool
    {
        return (bool) Db::getInstance()->execute("DROP TABLE IF EXISTS `{$this->table}`");
    }

    /**
     * Sauvegarde une entrée d'audit.
     */
    public function save(AuditEntry $entry): int
    {
        $data = [
            'action' => pSQL($entry->getAction()),
            'entity_type' => pSQL($entry->getEntityType()),
            'entity_id' => $entry->getEntityId(),
            'user_id' => $entry->getUserId(),
            'user_name' => $entry->getUserName() !== null ? pSQL($entry->getUserName()) : null,
            'ip_address' => pSQL($entry->getIpAddress()),
            'old_values' => !empty($entry->getOldValues()) ? pSQL(json_encode($entry->getOldValues())) : null,
            'new_values' => !empty($entry->getNewValues()) ? pSQL(json_encode($entry->getNewValues())) : null,
            'context' => !empty($entry->getContext()) ? pSQL(json_encode($entry->getContext())) : null,
            'id_shop' => (int) $entry->getShopId(),
            'created_at' => pSQL($entry->getCreatedAt()->format('Y-m-d H:i:s')),
        ];

        Db::getInstance()->insert(self::TABLE_NAME, $data);

        return (int) Db::getInstance()->Insert_ID();
    }

    /**
     * Recherche des entrées d'audit.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<AuditEntry>
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1 = 1'];

        if (!empty($filters['action'])) {
            $where[] = "`action` = '" . pSQL($filters['action']) . "'";
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "`entity_type` = '" . pSQL($filters['entity_type']) . "'";
        }

        if (!empty($filters['entity_id'])) {
            $where[] = '`entity_id` = ' . (int) $filters['entity_id'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = '`user_id` = ' . (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "`created_at` >= '" . pSQL($filters['date_from']) . "'";
        }

        if (!empty($filters['date_to'])) {
            $where[] = "`created_at` <= '" . pSQL($filters['date_to']) . "'";
        }

        if (!empty($filters['shop_id'])) {
            $where[] = '`id_shop` = ' . (int) $filters['shop_id'];
        }

        $sql = sprintf(
            "SELECT * FROM `%s` WHERE %s ORDER BY `created_at` DESC LIMIT %d OFFSET %d",
            $this->table,
            implode(' AND ', $where),
            (int) $limit,
            (int) $offset
        );

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return [];
        }

        return array_map([$this, 'hydrate'], $results);
    }

    /**
     * Trouve les entrées pour une entité.
     *
     * @return array<AuditEntry>
     */
    public function findByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        return $this->search([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ], $limit);
    }

    /**
     * Trouve les entrées pour un utilisateur.
     *
     * @return array<AuditEntry>
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->search(['user_id' => $userId], $limit);
    }

    /**
     * Compte les entrées.
     *
     * @param array<string, mixed> $filters
     */
    public function count(array $filters = []): int
    {
        $where = ['1 = 1'];

        if (!empty($filters['action'])) {
            $where[] = "`action` = '" . pSQL($filters['action']) . "'";
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "`entity_type` = '" . pSQL($filters['entity_type']) . "'";
        }

        $sql = sprintf(
            "SELECT COUNT(*) FROM `%s` WHERE %s",
            $this->table,
            implode(' AND ', $where)
        );

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Supprime les anciennes entrées.
     */
    public function deleteOldEntries(int $daysToKeep): int
    {
        $sql = sprintf(
            "DELETE FROM `%s` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $this->table,
            (int) $daysToKeep
        );

        Db::getInstance()->execute($sql);

        return (int) Db::getInstance()->Affected_Rows();
    }

    /**
     * Hydrate une AuditEntry depuis un tableau de données.
     *
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): AuditEntry
    {
        $entry = new AuditEntry(
            action: $data['action'],
            entityType: $data['entity_type'],
            entityId: $data['entity_id'] !== null ? (int) $data['entity_id'] : null,
            userId: $data['user_id'] !== null ? (int) $data['user_id'] : null,
            userName: $data['user_name'],
            ipAddress: $data['ip_address'],
            oldValues: !empty($data['old_values']) ? json_decode($data['old_values'], true) ?: [] : [],
            newValues: !empty($data['new_values']) ? json_decode($data['new_values'], true) ?: [] : [],
            context: !empty($data['context']) ? json_decode($data['context'], true) ?: [] : [],
            shopId: (int) $data['id_shop']
        );

        $entry->setId((int) $data['id_audit']);
        $entry->setCreatedAt(new \DateTimeImmutable($data['created_at']));

        return $entry;
    }
}


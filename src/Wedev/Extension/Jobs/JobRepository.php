<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Jobs;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Db;

/**
 * Repository pour la gestion des jobs en base de données.
 */
final class JobRepository
{
    private const TABLE_NAME = 'wedev_jobs';

    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . self::TABLE_NAME;
    }

    /**
     * Crée la table des jobs.
     */
    public function createTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id_job` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `job_class` VARCHAR(255) NOT NULL,
            `payload` TEXT NOT NULL,
            `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
            `retry_delay` INT UNSIGNED NOT NULL DEFAULT 60,
            `timeout` INT UNSIGNED NOT NULL DEFAULT 300,
            `last_error` TEXT NULL,
            `scheduled_at` DATETIME NOT NULL,
            `started_at` DATETIME NULL,
            `completed_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_job`),
            INDEX `idx_status_scheduled` (`status`, `scheduled_at`),
            INDEX `idx_job_class` (`job_class`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Supprime la table des jobs.
     */
    public function dropTable(): bool
    {
        return (bool) Db::getInstance()->execute("DROP TABLE IF EXISTS `{$this->table}`");
    }

    /**
     * Sauvegarde un job.
     */
    public function save(JobEntry $entry): int
    {
        $data = [
            'job_class' => pSQL($entry->getJobClass()),
            'payload' => pSQL(json_encode($entry->getPayload())),
            'status' => pSQL($entry->getStatus()),
            'attempts' => (int) $entry->getAttempts(),
            'max_attempts' => (int) $entry->getMaxAttempts(),
            'retry_delay' => (int) $entry->getRetryDelay(),
            'timeout' => (int) $entry->getTimeout(),
            'scheduled_at' => pSQL($entry->getScheduledAt()->format('Y-m-d H:i:s')),
            'created_at' => pSQL($entry->getCreatedAt()->format('Y-m-d H:i:s')),
        ];

        Db::getInstance()->insert(self::TABLE_NAME, $data);

        return (int) Db::getInstance()->Insert_ID();
    }

    /**
     * Met à jour un job.
     */
    public function update(JobEntry $entry): bool
    {
        if ($entry->getId() === null) {
            return false;
        }

        $data = [
            'status' => pSQL($entry->getStatus()),
            'attempts' => (int) $entry->getAttempts(),
            'last_error' => $entry->getLastError() !== null ? pSQL($entry->getLastError()) : null,
            'scheduled_at' => pSQL($entry->getScheduledAt()->format('Y-m-d H:i:s')),
            'started_at' => $entry->getStartedAt()?->format('Y-m-d H:i:s'),
            'completed_at' => $entry->getCompletedAt()?->format('Y-m-d H:i:s'),
        ];

        return (bool) Db::getInstance()->update(
            self::TABLE_NAME,
            $data,
            'id_job = ' . (int) $entry->getId()
        );
    }

    /**
     * Récupère les jobs en attente prêts à être exécutés.
     *
     * @return array<JobEntry>
     */
    public function getPendingJobs(int $limit = 10): array
    {
        $sql = "SELECT * FROM `{$this->table}`
                WHERE `status` = 'pending'
                AND `scheduled_at` <= NOW()
                ORDER BY `scheduled_at` ASC
                LIMIT " . (int) $limit;

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return [];
        }

        return array_map([$this, 'hydrate'], $results);
    }

    /**
     * Récupère un job par son ID.
     */
    public function findById(int $id): ?JobEntry
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `id_job` = " . (int) $id;
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        return $this->hydrate($result);
    }

    /**
     * Récupère les statistiques de la queue.
     *
     * @return array{pending: int, running: int, completed: int, failed: int}
     */
    public function getStats(): array
    {
        $sql = "SELECT `status`, COUNT(*) as count FROM `{$this->table}` GROUP BY `status`";
        $results = Db::getInstance()->executeS($sql);

        $stats = [
            'pending' => 0,
            'running' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        foreach ($results ?: [] as $row) {
            $stats[$row['status']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Supprime les jobs terminés anciens.
     */
    public function deleteOldCompletedJobs(int $daysToKeep): int
    {
        $sql = "DELETE FROM `{$this->table}`
                WHERE `status` IN ('completed', 'failed')
                AND `completed_at` < DATE_SUB(NOW(), INTERVAL " . (int) $daysToKeep . " DAY)";

        Db::getInstance()->execute($sql);

        return (int) Db::getInstance()->Affected_Rows();
    }

    /**
     * Remet les jobs bloqués en attente.
     *
     * Jobs "running" depuis plus longtemps que leur timeout.
     */
    public function resetStuckJobs(): int
    {
        $sql = "UPDATE `{$this->table}`
                SET `status` = 'pending',
                    `started_at` = NULL,
                    `attempts` = `attempts` + 1
                WHERE `status` = 'running'
                AND `started_at` < DATE_SUB(NOW(), INTERVAL `timeout` SECOND)";

        Db::getInstance()->execute($sql);

        return (int) Db::getInstance()->Affected_Rows();
    }

    /**
     * Hydrate un JobEntry depuis un tableau de données.
     *
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): JobEntry
    {
        $entry = new JobEntry(
            jobClass: $data['job_class'],
            payload: json_decode($data['payload'], true) ?: [],
            maxAttempts: (int) $data['max_attempts'],
            retryDelay: (int) $data['retry_delay'],
            timeout: (int) $data['timeout'],
            scheduledAt: new \DateTimeImmutable($data['scheduled_at'])
        );

        $entry->setId((int) $data['id_job']);
        $entry->setStatus($data['status']);
        $entry->setAttempts((int) $data['attempts']);
        $entry->setLastError($data['last_error']);
        $entry->setCreatedAt(new \DateTimeImmutable($data['created_at']));

        if (!empty($data['started_at'])) {
            $entry->setStartedAt(new \DateTimeImmutable($data['started_at']));
        }

        if (!empty($data['completed_at'])) {
            $entry->setCompletedAt(new \DateTimeImmutable($data['completed_at']));
        }

        return $entry;
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Audit;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;

/**
 * Service de journalisation d'audit.
 *
 * Enregistre les actions utilisateur pour traçabilité et conformité.
 *
 * @example
 * $logger = new AuditLogger($repository);
 *
 * // Logger une création
 * $logger->logCreate('Product', $product->id, [
 *     'name' => $product->name,
 *     'price' => $product->price,
 * ]);
 *
 * // Logger une modification
 * $logger->logUpdate('Product', $product->id, $oldData, $newData);
 *
 * // Logger une suppression
 * $logger->logDelete('Product', $product->id, $deletedData);
 *
 * // Logger une action personnalisée
 * $logger->log(AuditEntry::ACTION_EXPORT, 'Order', null, context: [
 *     'count' => 150,
 *     'format' => 'csv',
 * ]);
 */
final class AuditLogger implements ExtensionInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly AuditRepository $repository
    ) {
    }

    public static function getName(): string
    {
        return 'Audit';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Enregistre une action d'audit.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $oldValues = [],
        array $newValues = [],
        array $context = []
    ): int {
        $entry = new AuditEntry(
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            userId: $this->getCurrentUserId(),
            userName: $this->getCurrentUserName(),
            ipAddress: \Tools::getRemoteAddr() ?: 'unknown',
            oldValues: $oldValues,
            newValues: $newValues,
            context: $context,
            shopId: $this->getCurrentShopId()
        );

        $id = $this->repository->save($entry);

        $this->logInternal('debug', sprintf(
            'Audit: %s %s #%s by %s',
            $action,
            $entityType,
            $entityId ?? 'N/A',
            $entry->getUserName() ?? 'system'
        ));

        return $id;
    }

    /**
     * Logger une création.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $context
     */
    public function logCreate(
        string $entityType,
        int $entityId,
        array $values,
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_CREATE,
            $entityType,
            $entityId,
            [],
            $values,
            $context
        );
    }

    /**
     * Logger une modification.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    public function logUpdate(
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_UPDATE,
            $entityType,
            $entityId,
            $oldValues,
            $newValues,
            $context
        );
    }

    /**
     * Logger une suppression.
     *
     * @param array<string, mixed> $values  Valeurs avant suppression
     * @param array<string, mixed> $context
     */
    public function logDelete(
        string $entityType,
        int $entityId,
        array $values = [],
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_DELETE,
            $entityType,
            $entityId,
            $values,
            [],
            $context
        );
    }

    /**
     * Logger une consultation.
     *
     * @param array<string, mixed> $context
     */
    public function logView(
        string $entityType,
        int $entityId,
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_VIEW,
            $entityType,
            $entityId,
            [],
            [],
            $context
        );
    }

    /**
     * Logger un export.
     *
     * @param array<string, mixed> $context (count, format, filters...)
     */
    public function logExport(
        string $entityType,
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_EXPORT,
            $entityType,
            null,
            [],
            [],
            $context
        );
    }

    /**
     * Logger un import.
     *
     * @param array<string, mixed> $context (count, file, errors...)
     */
    public function logImport(
        string $entityType,
        array $context = []
    ): int {
        return $this->log(
            AuditEntry::ACTION_IMPORT,
            $entityType,
            null,
            [],
            [],
            $context
        );
    }

    /**
     * Recherche dans l'historique d'audit.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<AuditEntry>
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        return $this->repository->search($filters, $limit, $offset);
    }

    /**
     * Retourne l'historique d'une entité.
     *
     * @return array<AuditEntry>
     */
    public function getEntityHistory(string $entityType, int $entityId, int $limit = 50): array
    {
        return $this->repository->findByEntity($entityType, $entityId, $limit);
    }

    /**
     * Retourne l'historique d'un utilisateur.
     *
     * @return array<AuditEntry>
     */
    public function getUserHistory(int $userId, int $limit = 50): array
    {
        return $this->repository->findByUser($userId, $limit);
    }

    /**
     * Nettoie les anciennes entrées.
     */
    public function cleanup(int $daysToKeep = 365): int
    {
        return $this->repository->deleteOldEntries($daysToKeep);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function getCurrentUserId(): ?int
    {
        $context = \Context::getContext();

        if (isset($context->employee) && $context->employee->id) {
            return (int) $context->employee->id;
        }

        if (isset($context->customer) && $context->customer->id) {
            return (int) $context->customer->id;
        }

        return null;
    }

    private function getCurrentUserName(): ?string
    {
        $context = \Context::getContext();

        if (isset($context->employee) && $context->employee->id) {
            return sprintf(
                '%s %s',
                $context->employee->firstname,
                $context->employee->lastname
            );
        }

        if (isset($context->customer) && $context->customer->id) {
            return sprintf(
                '%s %s',
                $context->customer->firstname,
                $context->customer->lastname
            );
        }

        return null;
    }

    private function getCurrentShopId(): int
    {
        $context = \Context::getContext();

        return isset($context->shop) ? (int) $context->shop->id : 0;
    }

    private function logInternal(string $level, string $message): void
    {
        // Éviter la récursion infinie si LoggerTrait utilise l'audit
        \PrestaShopLogger::addLog(
            $message,
            $level === 'error' ? 3 : 1,
            null,
            'AuditLogger',
            0,
            true
        );
    }
}


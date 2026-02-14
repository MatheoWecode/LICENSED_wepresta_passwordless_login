<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Audit;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Trait pour ajouter l'audit automatique aux services.
 *
 * @example
 * class ProductService
 * {
 *     use AuditableTrait;
 *
 *     public function updateProduct(int $id, array $data): void
 *     {
 *         $product = new Product($id);
 *         $oldData = $this->extractAuditData($product);
 *
 *         // Mise à jour...
 *         $product->name = $data['name'];
 *         $product->save();
 *
 *         $newData = $this->extractAuditData($product);
 *         $this->auditUpdate('Product', $id, $oldData, $newData);
 *     }
 *
 *     private function extractAuditData(Product $product): array
 *     {
 *         return [
 *             'name' => $product->name,
 *             'price' => $product->price,
 *             'active' => $product->active,
 *         ];
 *     }
 * }
 */
trait AuditableTrait
{
    private ?AuditLogger $auditLogger = null;

    /**
     * Retourne le logger d'audit.
     */
    protected function getAuditLogger(): AuditLogger
    {
        if ($this->auditLogger === null) {
            $this->auditLogger = new AuditLogger(new AuditRepository());
        }

        return $this->auditLogger;
    }

    /**
     * Log une création.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $context
     */
    protected function auditCreate(
        string $entityType,
        int $entityId,
        array $values,
        array $context = []
    ): int {
        return $this->getAuditLogger()->logCreate($entityType, $entityId, $values, $context);
    }

    /**
     * Log une modification.
     *
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    protected function auditUpdate(
        string $entityType,
        int $entityId,
        array $oldValues,
        array $newValues,
        array $context = []
    ): int {
        return $this->getAuditLogger()->logUpdate($entityType, $entityId, $oldValues, $newValues, $context);
    }

    /**
     * Log une suppression.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $context
     */
    protected function auditDelete(
        string $entityType,
        int $entityId,
        array $values = [],
        array $context = []
    ): int {
        return $this->getAuditLogger()->logDelete($entityType, $entityId, $values, $context);
    }

    /**
     * Log une consultation.
     *
     * @param array<string, mixed> $context
     */
    protected function auditView(
        string $entityType,
        int $entityId,
        array $context = []
    ): int {
        return $this->getAuditLogger()->logView($entityType, $entityId, $context);
    }

    /**
     * Log un export.
     *
     * @param array<string, mixed> $context
     */
    protected function auditExport(
        string $entityType,
        array $context = []
    ): int {
        return $this->getAuditLogger()->logExport($entityType, $context);
    }

    /**
     * Log un import.
     *
     * @param array<string, mixed> $context
     */
    protected function auditImport(
        string $entityType,
        array $context = []
    ): int {
        return $this->getAuditLogger()->logImport($entityType, $context);
    }
}


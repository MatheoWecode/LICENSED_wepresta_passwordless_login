<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Events\Generic;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Generic event for entity deletion.
 *
 * Includes the deleted entity's data for audit/recovery purposes.
 *
 * @example
 * // When a customer is deleted
 * $dispatcher->dispatch(new EntityDeletedEvent(
 *     entityType: 'customer',
 *     entityId: $customerId,
 *     data: ['email' => $email, 'name' => $name]
 * ));
 *
 * // Cleanup related data
 * $dispatcher->addListener('entity.deleted', function (EntityDeletedEvent $event) {
 *     if ($event->entityType === 'customer') {
 *         $this->cleanupCustomerData($event->entityId);
 *     }
 * });
 */
final class EntityDeletedEvent extends AbstractDomainEvent
{
    /**
     * @param string              $entityType The type of entity that was deleted
     * @param int|string          $entityId   The entity's identifier
     * @param array<string,mixed> $data       Snapshot of the entity's data before deletion
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int|string $entityId,
        public readonly array $data = []
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'entity.deleted';
    }

    /**
     * Returns a more specific event name including the entity type.
     */
    public function getSpecificEventName(): string
    {
        return sprintf('%s.deleted', $this->entityType);
    }

    /**
     * Checks if data was captured before deletion.
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Gets a specific value from the deleted entity's data.
     */
    public function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }
}


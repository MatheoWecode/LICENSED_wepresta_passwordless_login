<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Events\Generic;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Events\AbstractDomainEvent;

/**
 * Generic event for entity creation.
 *
 * Use this for simple CRUD events instead of creating a dedicated event class.
 * For complex events with specific data, extend AbstractDomainEvent instead.
 *
 * @example
 * // When a group is created
 * $dispatcher->dispatch(new EntityCreatedEvent(
 *     entityType: 'group',
 *     entityId: $groupId,
 *     data: ['title' => $title, 'status' => 'active']
 * ));
 *
 * // Listening to all creation events
 * $dispatcher->addListener('entity.created', function (EntityCreatedEvent $event) {
 *     $this->auditLog->log("Created {$event->entityType} #{$event->entityId}");
 * });
 *
 * // Listening to specific entity type
 * $dispatcher->addListener('entity.created', function (EntityCreatedEvent $event) {
 *     if ($event->entityType === 'order') {
 *         // Handle order creation
 *     }
 * });
 */
final class EntityCreatedEvent extends AbstractDomainEvent
{
    /**
     * @param string              $entityType The type of entity (e.g., 'group', 'product', 'order')
     * @param int|string          $entityId   The entity's identifier
     * @param array<string,mixed> $data       Additional data about the created entity
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
        return 'entity.created';
    }

    /**
     * Returns a more specific event name including the entity type.
     *
     * Useful for filtering: 'group.created', 'order.created'
     */
    public function getSpecificEventName(): string
    {
        return sprintf('%s.created', $this->entityType);
    }
}


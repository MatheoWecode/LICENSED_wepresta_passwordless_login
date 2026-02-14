# WEDEV Events Extension

Event-driven architecture for WEDEV-based PrestaShop modules.

## Overview

The Events extension provides a lightweight domain event system for decoupling
components and enabling reactive programming patterns.

## Components

| Class | Description |
|-------|-------------|
| `AbstractDomainEvent` | Base class for all domain events |
| `DomainEventDispatcher` | Dispatches events to registered subscribers |
| `EventSubscriberInterface` | Contract for event listeners |
| `EntityCreatedEvent` | Generic entity creation event |
| `EntityUpdatedEvent` | Generic entity update event |
| `EntityDeletedEvent` | Generic entity deletion event |

## Quick Start

### 1. Register the Dispatcher

```yaml
# config/services.yml
imports:
    - { resource: '../src/Wedev/Extension/Events/config/services_events.yml' }
```

### 2. Dispatch Events

```php
use Wepresta_Passwordless_Login\Wedev\Extension\Events\DomainEventDispatcher;
use Wepresta_Passwordless_Login\Wedev\Extension\Events\Generic\EntityCreatedEvent;

class GroupService
{
    public function __construct(
        private readonly DomainEventDispatcher $dispatcher
    ) {}

    public function create(array $data): int
    {
        $groupId = $this->repository->create($data);

        $this->dispatcher->dispatch(new EntityCreatedEvent(
            entityType: 'group',
            entityId: $groupId,
            data: $data
        ));

        return $groupId;
    }
}
```

### 3. Create Subscribers

```php
use Wepresta_Passwordless_Login\Wedev\Extension\Events\AbstractDomainEvent;
use Wepresta_Passwordless_Login\Wedev\Extension\Events\EventSubscriberInterface;

final class AuditSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'entity.created' => 'onEntityCreated',
            'entity.updated' => 'onEntityUpdated',
            'entity.deleted' => 'onEntityDeleted',
            'order.*' => ['onAnyOrderEvent', 10], // High priority
        ];
    }

    public function onEntityCreated(AbstractDomainEvent $event): void
    {
        $this->auditLog->log('Created: ' . json_encode($event->toArray()));
    }

    // ...
}
```

### 4. Register Subscribers

```yaml
# config/services.yml
services:
    App\EventSubscriber\AuditSubscriber:
        tags: ['wedev.event_subscriber']
```

Or programmatically:

```php
$dispatcher->addSubscriber(new AuditSubscriber());
```

## Custom Events

For complex events, extend `AbstractDomainEvent`:

```php
final class OrderShippedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $trackingNumber,
        public readonly string $carrier,
        public readonly \DateTimeImmutable $shippedAt
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'order.shipped';
    }
}
```

## Wildcard Listeners

Listen to all events of a type using wildcards:

```php
public static function getSubscribedEvents(): array
{
    return [
        'order.*' => 'onAnyOrderEvent',    // order.created, order.updated, etc.
        'entity.*' => 'onAnyEntityEvent',  // All entity events
    ];
}
```

## Priority

Higher priority = called first:

```php
public static function getSubscribedEvents(): array
{
    return [
        'order.created' => ['onOrderCreated', 100], // Called first
        'order.created' => ['logOrder', 0],         // Called second
    ];
}
```

## EntityUpdatedEvent Helpers

Detect specific changes:

```php
public function onEntityUpdated(EntityUpdatedEvent $event): void
{
    // Check if a field changed
    if ($event->hasChanged('price')) {
        $oldPrice = $event->getOldValue('price');
        $newPrice = $event->getNewValue('price');
    }

    // Get all changed fields
    $changedFields = $event->getChangedFields();

    // Get full diff
    $diff = $event->getDiff();
    // ['price' => ['old' => 19.99, 'new' => 24.99]]
}
```

## Best Practices

1. **Use Generic Events for CRUD**: `EntityCreatedEvent`, `EntityUpdatedEvent`, `EntityDeletedEvent`
2. **Create Custom Events for Business Logic**: `OrderShippedEvent`, `PaymentReceivedEvent`
3. **Keep Handlers Fast**: Offload heavy work to Jobs extension
4. **Use Wildcards Sparingly**: Specific listeners are easier to debug
5. **Include Enough Context**: Events should be self-contained

## Integration with Other Extensions

### With Audit Extension

```php
$dispatcher->addListener('entity.*', function (AbstractDomainEvent $event) use ($auditLogger) {
    $auditLogger->log(
        action: $event->getEventName(),
        entityType: $event->entityType ?? 'unknown',
        entityId: $event->entityId ?? 0,
        data: $event->toArray()
    );
});
```

### With Jobs Extension

```php
$dispatcher->addListener('order.created', function (EntityCreatedEvent $event) use ($jobDispatcher) {
    // Dispatch async job
    $jobDispatcher->dispatch(new SendOrderConfirmationJob($event->entityId));
});
```

### With Notifications Extension

```php
$dispatcher->addListener('order.shipped', function (OrderShippedEvent $event) use ($notificationService) {
    $notificationService->send(new Notification(
        channel: 'email',
        template: 'order_shipped',
        data: ['tracking' => $event->trackingNumber]
    ));
});
```


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Events;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Dispatches domain events to registered subscribers.
 *
 * The dispatcher collects subscribers and routes events to the
 * appropriate handlers based on event names. Supports wildcards
 * and priority-based ordering.
 *
 * @example
 * $dispatcher = new DomainEventDispatcher();
 *
 * // Register subscribers
 * $dispatcher->addSubscriber(new OrderNotificationSubscriber());
 * $dispatcher->addSubscriber(new AuditLogSubscriber());
 *
 * // Or register individual listeners
 * $dispatcher->addListener('order.created', function (AbstractDomainEvent $event) {
 *     // Handle event
 * });
 *
 * // Dispatch an event
 * $dispatcher->dispatch(new OrderCreatedEvent($orderId));
 */
final class DomainEventDispatcher
{
    /**
     * Registered listeners: event name => [[callable, priority], ...]
     *
     * @var array<string, array<array{0: callable, 1: int}>>
     */
    private array $listeners = [];

    /**
     * Whether listeners are sorted by priority.
     *
     * @var array<string, bool>
     */
    private array $sorted = [];

    /**
     * Dispatches an event to all registered listeners.
     *
     * @return AbstractDomainEvent The same event (for chaining)
     */
    public function dispatch(AbstractDomainEvent $event): AbstractDomainEvent
    {
        $eventName = $event->getEventName();

        // Get listeners for this specific event
        foreach ($this->getListenersForEvent($eventName) as $listener) {
            $listener($event);
        }

        return $event;
    }

    /**
     * Adds a listener for a specific event.
     *
     * @param string   $eventName The event name (supports wildcards like 'order.*')
     * @param callable $listener  The listener callback
     * @param int      $priority  Higher priority = called first (default: 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = [$listener, $priority];
        $this->sorted[$eventName] = false;
    }

    /**
     * Adds a subscriber that listens to multiple events.
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            if (\is_string($params)) {
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif (\is_array($params)) {
                $this->addListener($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
            }
        }
    }

    /**
     * Removes a listener for a specific event.
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $index => $registered) {
            if ($registered[0] === $listener) {
                unset($this->listeners[$eventName][$index]);
                $this->listeners[$eventName] = array_values($this->listeners[$eventName]);
                break;
            }
        }
    }

    /**
     * Checks if an event has any listeners.
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->getListenersForEvent($eventName));
    }

    /**
     * Gets all listeners for an event (including wildcards).
     *
     * @return array<callable>
     */
    public function getListenersForEvent(string $eventName): array
    {
        $listeners = [];

        // Exact match
        if (isset($this->listeners[$eventName])) {
            $this->sortListeners($eventName);
            foreach ($this->listeners[$eventName] as [$listener, $priority]) {
                $listeners[] = [$listener, $priority];
            }
        }

        // Wildcard matches (e.g., 'order.*' matches 'order.created')
        foreach ($this->listeners as $pattern => $patternListeners) {
            if ($pattern === $eventName) {
                continue; // Already handled above
            }

            if ($this->matchesWildcard($pattern, $eventName)) {
                $this->sortListeners($pattern);
                foreach ($patternListeners as [$listener, $priority]) {
                    $listeners[] = [$listener, $priority];
                }
            }
        }

        // Sort all collected listeners by priority
        usort($listeners, fn($a, $b) => $b[1] <=> $a[1]);

        return array_column($listeners, 0);
    }

    /**
     * Gets all registered event names.
     *
     * @return array<string>
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Clears all listeners.
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
        $this->sorted = [];
    }

    /**
     * Checks if a pattern matches an event name.
     *
     * Supports wildcard '*' at the end: 'order.*' matches 'order.created'
     */
    private function matchesWildcard(string $pattern, string $eventName): bool
    {
        if (!str_ends_with($pattern, '*')) {
            return false;
        }

        $prefix = substr($pattern, 0, -1); // Remove '*'

        return str_starts_with($eventName, $prefix);
    }

    /**
     * Sorts listeners by priority (higher first).
     */
    private function sortListeners(string $eventName): void
    {
        if ($this->sorted[$eventName] ?? true) {
            return;
        }

        usort(
            $this->listeners[$eventName],
            fn($a, $b) => $b[1] <=> $a[1]
        );

        $this->sorted[$eventName] = true;
    }
}


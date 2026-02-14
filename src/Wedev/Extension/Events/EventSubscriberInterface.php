<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Events;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface for event subscribers.
 *
 * Subscribers listen to domain events and react to them.
 * They declare which events they're interested in via getSubscribedEvents().
 *
 * @example
 * final class OrderNotificationSubscriber implements EventSubscriberInterface
 * {
 *     public static function getSubscribedEvents(): array
 *     {
 *         return [
 *             'order.created' => 'onOrderCreated',
 *             'order.shipped' => ['onOrderShipped', 10], // with priority
 *             'order.*' => 'onAnyOrderEvent', // wildcard support
 *         ];
 *     }
 *
 *     public function onOrderCreated(AbstractDomainEvent $event): void
 *     {
 *         // Send confirmation email
 *     }
 *
 *     public function onOrderShipped(AbstractDomainEvent $event): void
 *     {
 *         // Send shipping notification
 *     }
 *
 *     public function onAnyOrderEvent(AbstractDomainEvent $event): void
 *     {
 *         // Log all order events
 *     }
 * }
 */
interface EventSubscriberInterface
{
    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * Format:
     * - 'event.name' => 'methodName'
     * - 'event.name' => ['methodName', priority] (higher priority = called first)
     * - 'entity.*' => 'methodName' (wildcard: matches all events starting with 'entity.')
     *
     * @return array<string, string|array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array;
}


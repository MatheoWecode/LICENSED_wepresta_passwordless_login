<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Notification basée sur un template PrestaShop.
 *
 * @example
 * $notification = new TemplateNotification(
 *     templateName: 'order_conf',
 *     recipients: ['customer@example.com'],
 *     data: [
 *         '{order_id}' => $order->id,
 *         '{order_reference}' => $order->reference,
 *         '{customer_firstname}' => $customer->firstname,
 *     ]
 * );
 */
final class TemplateNotification implements NotificationInterface
{
    /**
     * @param array<string>        $recipients
     * @param array<string, mixed> $data
     * @param array<string>        $channels
     */
    public function __construct(
        private readonly string $templateName,
        private readonly array $recipients,
        private readonly array $data = [],
        private readonly array $channels = ['email'],
        private readonly ?string $subject = null
    ) {
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject ?? $this->templateName;
    }

    public function getContent(): string
    {
        // Le contenu vient du template
        return '';
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Retourne le nom du template.
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }
}


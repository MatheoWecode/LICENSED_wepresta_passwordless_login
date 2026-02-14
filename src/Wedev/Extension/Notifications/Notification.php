<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Implémentation d'une notification.
 *
 * @example
 * $notification = new Notification(
 *     recipients: ['customer@example.com'],
 *     subject: 'Your order has been shipped',
 *     content: 'Your order #123 is on its way!',
 *     data: ['order_id' => 123, 'tracking' => 'XXX'],
 *     channels: ['email']
 * );
 */
final class Notification implements NotificationInterface
{
    /**
     * @param array<string>        $recipients
     * @param array<string, mixed> $data
     * @param array<string>        $channels
     */
    public function __construct(
        private readonly array $recipients,
        private readonly string $subject,
        private readonly string $content,
        private readonly array $data = [],
        private readonly array $channels = ['email']
    ) {
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getContent(): string
    {
        return $this->content;
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
     * Crée une notification pour un seul destinataire.
     *
     * @param array<string, mixed> $data
     * @param array<string>        $channels
     */
    public static function to(
        string $recipient,
        string $subject,
        string $content,
        array $data = [],
        array $channels = ['email']
    ): self {
        return new self([$recipient], $subject, $content, $data, $channels);
    }

    /**
     * Crée une notification pour plusieurs destinataires.
     *
     * @param array<string>        $recipients
     * @param array<string, mixed> $data
     * @param array<string>        $channels
     */
    public static function toMany(
        array $recipients,
        string $subject,
        string $content,
        array $data = [],
        array $channels = ['email']
    ): self {
        return new self($recipients, $subject, $content, $data, $channels);
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications\Channel;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Http\HttpClient;
use Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth\BearerAuth;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\NotificationInterface;

/**
 * Canal de notification Push (Web Push / Firebase).
 *
 * @example
 * $channel = new PushChannel(
 *     provider: 'firebase',
 *     config: ['server_key' => 'XXX']
 * );
 *
 * $channel->send($notification);
 */
final class PushChannel implements ChannelInterface
{
    private const PROVIDERS = ['firebase', 'onesignal'];

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $provider = 'firebase',
        array $config = []
    ) {
        if (!in_array($this->provider, self::PROVIDERS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported push provider: %s. Supported: %s',
                $this->provider,
                implode(', ', self::PROVIDERS)
            ));
        }

        $this->config = $config;
    }

    public function send(NotificationInterface $notification): int
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Push channel not configured');
        }

        // Les recipients sont des tokens push
        $tokens = $notification->getRecipients();

        if (empty($tokens)) {
            return 0;
        }

        return match ($this->provider) {
            'firebase' => $this->sendViaFirebase($tokens, $notification),
            'onesignal' => $this->sendViaOneSignal($tokens, $notification),
            default => 0,
        };
    }

    public function isConfigured(): bool
    {
        return match ($this->provider) {
            'firebase' => !empty($this->config['server_key']),
            'onesignal' => !empty($this->config['app_id'])
                && !empty($this->config['api_key']),
            default => false,
        };
    }

    /**
     * Envoi via Firebase Cloud Messaging.
     *
     * @param array<string> $tokens
     */
    private function sendViaFirebase(array $tokens, NotificationInterface $notification): int
    {
        $client = new HttpClient();

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $notification->getSubject(),
                'body' => $notification->getContent(),
            ],
            'data' => $notification->getData(),
        ];

        $response = $client
            ->withAuth(new BearerAuth($this->config['server_key']))
            ->withHeader('Content-Type', 'application/json')
            ->postJson('https://fcm.googleapis.com/fcm/send', $payload);

        if (!$response->isSuccess()) {
            return 0;
        }

        $data = $response->json();

        return $data['success'] ?? 0;
    }

    /**
     * Envoi via OneSignal.
     *
     * @param array<string> $tokens
     */
    private function sendViaOneSignal(array $tokens, NotificationInterface $notification): int
    {
        $client = new HttpClient();

        $payload = [
            'app_id' => $this->config['app_id'],
            'include_player_ids' => $tokens,
            'headings' => ['en' => $notification->getSubject()],
            'contents' => ['en' => $notification->getContent()],
            'data' => $notification->getData(),
        ];

        $response = $client
            ->withAuth(new BearerAuth($this->config['api_key']))
            ->postJson('https://onesignal.com/api/v1/notifications', $payload);

        if (!$response->isSuccess()) {
            return 0;
        }

        $data = $response->json();

        return $data['recipients'] ?? 0;
    }
}


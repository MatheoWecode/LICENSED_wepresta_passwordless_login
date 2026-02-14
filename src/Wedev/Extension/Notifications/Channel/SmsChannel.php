<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications\Channel;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Adapter\ConfigurationAdapter;
use Wepresta_Passwordless_Login\Wedev\Extension\Http\HttpClient;
use Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth\BearerAuth;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\NotificationInterface;

/**
 * Canal de notification par SMS.
 *
 * Supporte différents providers SMS (Twilio, OVH, etc.).
 *
 * @example
 * // Avec Twilio
 * $channel = new SmsChannel(
 *     provider: 'twilio',
 *     config: [
 *         'account_sid' => 'XXX',
 *         'auth_token' => 'XXX',
 *         'from' => '+33123456789',
 *     ]
 * );
 *
 * $channel->send($notification);
 */
final class SmsChannel implements ChannelInterface
{
    private const PROVIDERS = ['twilio', 'ovh', 'vonage'];

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $provider = 'twilio',
        array $config = []
    ) {
        if (!in_array($this->provider, self::PROVIDERS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported SMS provider: %s. Supported: %s',
                $this->provider,
                implode(', ', self::PROVIDERS)
            ));
        }

        $this->config = $config;
    }

    public function send(NotificationInterface $notification): int
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('SMS channel not configured');
        }

        $sent = 0;

        foreach ($notification->getRecipients() as $recipient) {
            // Ignorer si ce n'est pas un numéro de téléphone
            if (!$this->isPhoneNumber($recipient)) {
                continue;
            }

            $result = $this->sendSms($recipient, $notification->getContent());

            if ($result) {
                $sent++;
            }
        }

        return $sent;
    }

    public function isConfigured(): bool
    {
        return match ($this->provider) {
            'twilio' => !empty($this->config['account_sid'])
                && !empty($this->config['auth_token'])
                && !empty($this->config['from']),
            'ovh' => !empty($this->config['application_key'])
                && !empty($this->config['application_secret'])
                && !empty($this->config['consumer_key']),
            'vonage' => !empty($this->config['api_key'])
                && !empty($this->config['api_secret']),
            default => false,
        };
    }

    /**
     * Vérifie si c'est un numéro de téléphone.
     */
    private function isPhoneNumber(string $value): bool
    {
        // Format international: +33612345678
        return (bool) preg_match('/^\+[1-9]\d{6,14}$/', $value);
    }

    /**
     * Envoie un SMS via le provider configuré.
     */
    private function sendSms(string $to, string $message): bool
    {
        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'ovh' => $this->sendViaOvh($to, $message),
            'vonage' => $this->sendViaVonage($to, $message),
            default => false,
        };
    }

    /**
     * Envoi via Twilio.
     */
    private function sendViaTwilio(string $to, string $message): bool
    {
        $client = new HttpClient();

        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
            $this->config['account_sid']
        );

        $response = $client
            ->withAuth(new \Wepresta_Passwordless_Login\Extension\Http\Auth\BasicAuth(
                $this->config['account_sid'],
                $this->config['auth_token']
            ))
            ->post($url, [
                'To' => $to,
                'From' => $this->config['from'],
                'Body' => $message,
            ]);

        return $response->isSuccess();
    }

    /**
     * Envoi via OVH.
     */
    private function sendViaOvh(string $to, string $message): bool
    {
        // Implémentation OVH SMS
        // Nécessite la signature des requêtes
        return false;
    }

    /**
     * Envoi via Vonage (ex-Nexmo).
     */
    private function sendViaVonage(string $to, string $message): bool
    {
        $client = new HttpClient();

        $response = $client->postJson('https://rest.nexmo.com/sms/json', [
            'api_key' => $this->config['api_key'],
            'api_secret' => $this->config['api_secret'],
            'to' => $to,
            'from' => $this->config['from'] ?? 'WEDEV',
            'text' => $message,
        ]);

        if (!$response->isSuccess()) {
            return false;
        }

        $data = $response->json();

        return isset($data['messages'][0]['status'])
            && $data['messages'][0]['status'] === '0';
    }
}


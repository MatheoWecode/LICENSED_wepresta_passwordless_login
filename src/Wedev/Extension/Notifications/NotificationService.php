<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\Channel\ChannelInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\Channel\EmailChannel;

/**
 * Service d'envoi de notifications multi-canal.
 *
 * Gère l'envoi de notifications via différents canaux (email, SMS, push).
 *
 * @example
 * $service = new NotificationService();
 *
 * // Notification simple par email
 * $service->send(Notification::to(
 *     'customer@example.com',
 *     'Order Shipped',
 *     'Your order #123 has been shipped!'
 * ));
 *
 * // Notification avec template
 * $service->sendTemplate(
 *     'order_shipped',
 *     ['customer@example.com'],
 *     [
 *         'order_id' => 123,
 *         'tracking_number' => 'XXX',
 *         'customer_name' => 'John',
 *     ]
 * );
 *
 * // Multi-canal
 * $service->send(new Notification(
 *     recipients: ['customer@example.com', '+33612345678'],
 *     subject: 'Urgent: Action Required',
 *     content: 'Please confirm your order...',
 *     channels: ['email', 'sms']
 * ));
 */
final class NotificationService implements ExtensionInterface
{
    use LoggerTrait;

    /** @var array<string, ChannelInterface> */
    private array $channels = [];

    public function __construct()
    {
        // Enregistrer le canal email par défaut
        $this->registerChannel('email', new EmailChannel());
    }

    public static function getName(): string
    {
        return 'Notifications';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Enregistre un canal de notification.
     */
    public function registerChannel(string $name, ChannelInterface $channel): self
    {
        $this->channels[$name] = $channel;

        return $this;
    }

    /**
     * Vérifie si un canal est disponible.
     */
    public function hasChannel(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * Envoie une notification.
     *
     * @return array{success: int, failed: int, errors: array<string>}
     */
    public function send(NotificationInterface $notification): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($notification->getChannels() as $channelName) {
            if (!$this->hasChannel($channelName)) {
                $results['errors'][] = "Channel '{$channelName}' not registered";
                $results['failed']++;

                continue;
            }

            $channel = $this->channels[$channelName];

            try {
                $sent = $channel->send($notification);
                $results['success'] += $sent;

                $this->log('info', sprintf(
                    'Notification sent via %s to %d recipients',
                    $channelName,
                    $sent
                ));
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    '%s: %s',
                    $channelName,
                    $e->getMessage()
                );

                $this->log('error', sprintf(
                    'Notification failed via %s: %s',
                    $channelName,
                    $e->getMessage()
                ));
            }
        }

        return $results;
    }

    /**
     * Envoie une notification basée sur un template.
     *
     * @param string               $templateName Nom du template PrestaShop
     * @param array<string>        $recipients   Destinataires
     * @param array<string, mixed> $data         Données pour le template
     * @param array<string>        $channels     Canaux à utiliser
     *
     * @return array{success: int, failed: int, errors: array<string>}
     */
    public function sendTemplate(
        string $templateName,
        array $recipients,
        array $data,
        array $channels = ['email']
    ): array {
        $notification = new TemplateNotification(
            templateName: $templateName,
            recipients: $recipients,
            data: $data,
            channels: $channels
        );

        return $this->send($notification);
    }

    /**
     * Envoie une notification à un client.
     *
     * @param array<string, mixed> $data
     */
    public function notifyCustomer(
        int $customerId,
        string $subject,
        string $content,
        array $data = []
    ): bool {
        $customer = new \Customer($customerId);

        if (!$customer->id || empty($customer->email)) {
            return false;
        }

        $notification = Notification::to(
            $customer->email,
            $subject,
            $content,
            array_merge($data, [
                'customer_firstname' => $customer->firstname,
                'customer_lastname' => $customer->lastname,
            ])
        );

        $result = $this->send($notification);

        return $result['success'] > 0;
    }

    /**
     * Envoie une notification aux administrateurs.
     *
     * @param array<string, mixed> $data
     */
    public function notifyAdmins(
        string $subject,
        string $content,
        array $data = []
    ): int {
        // Récupérer les emails des employés admins
        $sql = 'SELECT e.email
                FROM ' . _DB_PREFIX_ . 'employee e
                INNER JOIN ' . _DB_PREFIX_ . 'profile p ON e.id_profile = p.id_profile
                WHERE p.id_profile = 1 AND e.active = 1';

        $employees = \Db::getInstance()->executeS($sql);

        if (empty($employees)) {
            return 0;
        }

        $emails = array_column($employees, 'email');

        $notification = Notification::toMany($emails, $subject, $content, $data);
        $result = $this->send($notification);

        return $result['success'];
    }
}


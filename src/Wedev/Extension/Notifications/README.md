# Extension Notifications WEDEV

Service de notifications multi-canal pour modules PrestaShop.

## Installation

```bash
wedev ps module new mymodule --ext notifications
```

### Configuration Symfony

```yaml
imports:
    - { resource: '../src/Extension/Notifications/config/services_notifications.yml' }
```

---

## Utilisation

### Notification Simple

```php
use Wepresta_Passwordless_Login\Extension\Notifications\NotificationService;
use Wepresta_Passwordless_Login\Extension\Notifications\Notification;

$service = new NotificationService();

// Email simple
$service->send(Notification::to(
    'customer@example.com',
    'Order Confirmed',
    'Thank you for your order #123!'
));
```

### Notification avec Données

```php
$service->send(new Notification(
    recipients: ['customer@example.com'],
    subject: 'Your order has been shipped',
    content: 'Track your package: XXX-123',
    data: [
        'order_id' => 123,
        'tracking_number' => 'XXX-123',
        'carrier' => 'DHL',
    ]
));
```

### Notification avec Template PrestaShop

```php
$service->sendTemplate(
    'order_conf',
    ['customer@example.com'],
    [
        '{order_reference}' => $order->reference,
        '{firstname}' => $customer->firstname,
        '{total_paid}' => Tools::displayPrice($order->total_paid),
    ]
);
```

### Notification Multi-Destinataires

```php
$service->send(Notification::toMany(
    ['admin1@shop.com', 'admin2@shop.com'],
    'Stock Alert',
    'Product #123 is running low (5 units left)'
));
```

---

## Canaux de Notification

### Email (par défaut)

```php
use Wepresta_Passwordless_Login\Extension\Notifications\Channel\EmailChannel;

$channel = new EmailChannel(
    langId: 1,
    shopId: 1,
    moduleName: 'mymodule'
);

$service->registerChannel('email', $channel);
```

### SMS

```php
use Wepresta_Passwordless_Login\Extension\Notifications\Channel\SmsChannel;

// Twilio
$channel = new SmsChannel('twilio', [
    'account_sid' => Configuration::get('MYMODULE_TWILIO_SID'),
    'auth_token' => Configuration::get('MYMODULE_TWILIO_TOKEN'),
    'from' => '+33123456789',
]);

$service->registerChannel('sms', $channel);

// Utilisation
$service->send(new Notification(
    recipients: ['+33612345678'],
    subject: 'Order Ready',
    content: 'Your order is ready for pickup!',
    channels: ['sms']
));
```

### Push

```php
use Wepresta_Passwordless_Login\Extension\Notifications\Channel\PushChannel;

// Firebase
$channel = new PushChannel('firebase', [
    'server_key' => Configuration::get('MYMODULE_FCM_KEY'),
]);

$service->registerChannel('push', $channel);

// OneSignal
$channel = new PushChannel('onesignal', [
    'app_id' => 'xxx',
    'api_key' => 'xxx',
]);

$service->registerChannel('push', $channel);
```

### Multi-Canal

```php
$service->send(new Notification(
    recipients: ['user@example.com', '+33612345678', 'push_token_xxx'],
    subject: 'Urgent: Action Required',
    content: 'Please confirm your order within 24h',
    channels: ['email', 'sms', 'push']
));
```

---

## Helpers

### Notifier un Client

```php
$service->notifyCustomer(
    customerId: 123,
    subject: 'Your order is ready',
    content: 'Come pick it up at our store!',
    data: ['store_address' => '123 Main St']
);
```

### Notifier les Admins

```php
$service->notifyAdmins(
    subject: 'New Order #' . $order->reference,
    content: 'A new order of ' . Tools::displayPrice($order->total_paid),
    data: ['order_id' => $order->id]
);
```

---

## Créer un Canal Personnalisé

```php
use Wepresta_Passwordless_Login\Extension\Notifications\Channel\ChannelInterface;
use Wepresta_Passwordless_Login\Extension\Notifications\NotificationInterface;

final class SlackChannel implements ChannelInterface
{
    public function __construct(
        private readonly string $webhookUrl
    ) {}

    public function send(NotificationInterface $notification): int
    {
        $client = new HttpClient();

        $response = $client->postJson($this->webhookUrl, [
            'text' => sprintf(
                "*%s*\n%s",
                $notification->getSubject(),
                $notification->getContent()
            ),
        ]);

        return $response->isSuccess() ? 1 : 0;
    }

    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl);
    }
}

// Enregistrement
$service->registerChannel('slack', new SlackChannel($webhookUrl));
```

---

## Gestion des Erreurs

```php
$result = $service->send($notification);

// {
//     'success' => 2,
//     'failed' => 1,
//     'errors' => ['sms: Rate limit exceeded']
// }

if ($result['failed'] > 0) {
    foreach ($result['errors'] as $error) {
        $this->log('warning', 'Notification error: ' . $error);
    }
}
```

---

## Structure des Fichiers

```
Extension/Notifications/
├── README.md
├── config/
│   └── services_notifications.yml
├── Channel/
│   ├── ChannelInterface.php
│   ├── EmailChannel.php
│   ├── PushChannel.php
│   └── SmsChannel.php
├── Notification.php
├── NotificationInterface.php
├── NotificationService.php
└── TemplateNotification.php
```


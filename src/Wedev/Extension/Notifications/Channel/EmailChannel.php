<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications\Channel;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Mail;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\NotificationInterface;
use Wepresta_Passwordless_Login\Wedev\Extension\Notifications\TemplateNotification;

/**
 * Canal de notification par email via PrestaShop Mail.
 *
 * @example
 * $channel = new EmailChannel();
 * $channel->send($notification);
 */
final class EmailChannel implements ChannelInterface
{
    private ?int $langId = null;
    private ?int $shopId = null;
    private ?string $moduleName = null;

    public function __construct(
        ?int $langId = null,
        ?int $shopId = null,
        ?string $moduleName = null
    ) {
        $this->langId = $langId;
        $this->shopId = $shopId;
        $this->moduleName = $moduleName;
    }

    public function send(NotificationInterface $notification): int
    {
        $sent = 0;
        $langId = $this->langId ?? (int) \Configuration::get('PS_LANG_DEFAULT');
        $shopId = $this->shopId ?? (int) \Context::getContext()->shop->id;

        foreach ($notification->getRecipients() as $recipient) {
            // Ignorer si ce n'est pas un email
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $result = $this->sendMail($notification, $recipient, $langId, $shopId);

            if ($result) {
                $sent++;
            }
        }

        return $sent;
    }

    public function isConfigured(): bool
    {
        return (bool) \Configuration::get('PS_MAIL_METHOD');
    }

    /**
     * Envoie un email.
     */
    private function sendMail(
        NotificationInterface $notification,
        string $to,
        int $langId,
        int $shopId
    ): bool {
        // Préparer les variables du template
        $templateVars = $notification->getData();

        // Ajouter le contenu si c'est une notification simple
        if (!$notification instanceof TemplateNotification && !empty($notification->getContent())) {
            $templateVars['{content}'] = $notification->getContent();
        }

        // Déterminer le template
        $template = $notification instanceof TemplateNotification
            ? $notification->getTemplateName()
            : 'wedev_notification';

        // Chemin du template
        $templatePath = $this->moduleName !== null
            ? _PS_MODULE_DIR_ . $this->moduleName . '/mails/'
            : null;

        try {
            $result = Mail::Send(
                $langId,
                $template,
                $notification->getSubject(),
                $templateVars,
                $to,
                null,
                null,
                null,
                null,
                null,
                $templatePath,
                false,
                $shopId
            );

            return (bool) $result;
        } catch (\Throwable) {
            return false;
        }
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Notifications;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les notifications.
 */
interface NotificationInterface
{
    /**
     * Retourne les destinataires.
     *
     * @return array<string> Liste d'identifiants (emails, phones, tokens)
     */
    public function getRecipients(): array;

    /**
     * Retourne le sujet de la notification.
     */
    public function getSubject(): string;

    /**
     * Retourne le contenu de la notification.
     */
    public function getContent(): string;

    /**
     * Retourne les données pour le template.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Retourne les canaux sur lesquels envoyer.
     *
     * @return array<string> ex: ['email', 'sms', 'push']
     */
    public function getChannels(): array;
}


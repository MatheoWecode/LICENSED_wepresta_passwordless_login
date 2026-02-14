<?php
/**
 * WEDEV Core - TranslatorTrait
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Trait;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait pour les traductions.
 */
trait TranslatorTrait
{
    protected ?TranslatorInterface $translator = null;
    protected string $translationDomain = 'Modules.Wepresta\Passwordless\Login.Admin';

    /**
     * Définit le translator.
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Définit le domaine de traduction.
     */
    public function setTranslationDomain(string $domain): void
    {
        $this->translationDomain = $domain;
    }

    /**
     * Traduit un message.
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        if ($this->translator === null) {
            // Fallback: retourner l'ID avec les paramètres remplacés
            $translation = $id;
            foreach ($parameters as $key => $value) {
                $translation = str_replace($key, (string) $value, $translation);
            }

            return $translation;
        }

        return $this->translator->trans(
            $id,
            $parameters,
            $domain ?? $this->translationDomain
        );
    }

    /**
     * Traduit un message pour le front-office.
     */
    protected function transFront(string $id, array $parameters = []): string
    {
        $domain = str_replace('.Admin', '.Shop', $this->translationDomain);

        return $this->trans($id, $parameters, $domain);
    }

    /**
     * Traduit un message global PrestaShop.
     */
    protected function transGlobal(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Global');
    }

    /**
     * Traduit un message d'action PrestaShop.
     */
    protected function transAction(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Actions');
    }

    /**
     * Traduit un message de notification PrestaShop.
     */
    protected function transNotification(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'Admin.Notifications.Success');
    }
}


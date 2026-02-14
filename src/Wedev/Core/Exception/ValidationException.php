<?php
/**
 * WEDEV Core - ValidationException
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Exception;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Exception levée lors d'erreurs de validation.
 */
class ValidationException extends ModuleException
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422, null, ['errors' => $errors]);
        $this->errors = $errors;
    }

    /**
     * Récupère les erreurs de validation.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère les erreurs pour un champ.
     *
     * @return string[]
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Vérifie si un champ a des erreurs.
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Crée une exception pour un seul champ.
     */
    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * Crée une exception pour plusieurs champs.
     */
    public static function forFields(array $errors): self
    {
        return new self($errors);
    }
}


<?php
/**
 * WEDEV Core - ModuleException
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


use RuntimeException;
use Throwable;

/**
 * Exception de base pour le module.
 *
 * Toutes les exceptions du module doivent étendre cette classe.
 */
class ModuleException extends RuntimeException
{
    /**
     * Code d'erreur pour les erreurs de configuration.
     */
    protected const CODE_CONFIGURATION = 1000;

    /**
     * Code d'erreur pour les erreurs de validation.
     */
    protected const CODE_VALIDATION = 1100;

    /**
     * Code d'erreur pour les entités non trouvées.
     */
    protected const CODE_NOT_FOUND = 1200;

    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Contexte additionnel de l'exception.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Crée une exception avec contexte.
     */
    public static function withContext(string $message, array $context = []): self
    {
        return new self($message, 0, null, $context);
    }
}


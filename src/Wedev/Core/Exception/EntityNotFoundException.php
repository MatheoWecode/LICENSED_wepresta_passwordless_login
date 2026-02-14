<?php
/**
 * WEDEV Core - EntityNotFoundException
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
 * Exception levée quand une entité n'est pas trouvée.
 */
class EntityNotFoundException extends ModuleException
{
    public static function withId(string $entityName, int|string $id): self
    {
        return new self(
            sprintf('%s with ID "%s" not found.', $entityName, $id),
            404,
            null,
            ['entity' => $entityName, 'id' => $id]
        );
    }

    public static function withCriteria(string $entityName, array $criteria): self
    {
        return new self(
            sprintf('%s not found with given criteria.', $entityName),
            404,
            null,
            ['entity' => $entityName, 'criteria' => $criteria]
        );
    }
}


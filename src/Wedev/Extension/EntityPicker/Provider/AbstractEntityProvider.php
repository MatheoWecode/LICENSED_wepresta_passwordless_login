<?php
/**
 * WEDEV Extension - EntityPicker
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityPicker\Provider;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Db;
use PrestaShop\PrestaShop\Adapter\LegacyContext;

/**
 * Classe de base pour les providers d'entités.
 */
abstract class AbstractEntityProvider implements EntityProviderInterface
{
    protected Db $db;
    protected int $langId;
    protected int $shopId;
    protected string $dbPrefix;

    public function __construct(
        ?Db $db = null,
        ?LegacyContext $legacyContext = null
    ) {
        $this->db = $db ?? Db::getInstance();

        // LegacyContext::getContext() retourne le Context PrestaShop
        $context = $legacyContext?->getContext() ?? \Context::getContext();

        $this->langId = (int) ($context->language->id ?? 1);
        $this->shopId = (int) ($context->shop->id ?? 1);
        $this->dbPrefix = _DB_PREFIX_;
    }

    /**
     * Retourne l'URL d'une image par défaut quand aucune image n'est disponible.
     */
    protected function getDefaultImageUrl(): string
    {
        return '';
    }

    /**
     * Formate un résultat d'entité.
     *
     * @param int $id ID de l'entité
     * @param string $name Nom de l'entité
     * @param string $image URL de l'image (optionnel)
     * @param array<string, mixed> $extra Données supplémentaires optionnelles
     *
     * @return array{id: int, name: string, image: string}
     */
    protected function formatResult(int $id, string $name, string $image = '', array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'name' => $name,
            'image' => $image ?: $this->getDefaultImageUrl(),
        ], $extra);
    }

    /**
     * Construit une clause WHERE pour la recherche textuelle.
     */
    protected function buildSearchWhere(string $term, array $fields): string
    {
        $conditions = [];
        $escapedTerm = pSQL($term);

        foreach ($fields as $field) {
            $conditions[] = $field . " LIKE '%" . $escapedTerm . "%'";
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Construit une clause WHERE pour les IDs.
     *
     * @param int[] $ids
     */
    protected function buildIdsWhere(array $ids, string $field): string
    {
        if (empty($ids)) {
            return '1 = 0';
        }

        $safeIds = array_map('intval', $ids);

        return $field . ' IN (' . implode(',', $safeIds) . ')';
    }
}


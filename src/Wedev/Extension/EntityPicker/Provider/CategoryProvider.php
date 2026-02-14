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


use DbQuery;

/**
 * Provider de recherche pour les catégories PrestaShop.
 */
final class CategoryProvider extends AbstractEntityProvider
{
    public function getEntityType(): string
    {
        return 'category';
    }

    public function getEntityLabel(): string
    {
        return 'Catégories';
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $term, int $limit = 20): array
    {
        if (strlen($term) < 2) {
            return [];
        }

        $query = new DbQuery();
        $query->select('c.`id_category`, cl.`name`, c.`level_depth`')
            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . $this->langId)
            ->leftJoin('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . $this->shopId)
            ->where($this->buildSearchWhere($term, ['cl.`name`']))
            ->where('c.`active` = 1')
            ->where('c.`level_depth` > 0') // Exclure la catégorie racine
            ->orderBy('c.`level_depth` ASC, cl.`name` ASC')
            ->limit($limit);

        // Recherche par ID si le terme est numérique
        if (is_numeric($term)) {
            $query->where('c.`id_category` = ' . (int) $term, 'OR');
        }

        $rows = $this->db->executeS($query);

        if (!$rows) {
            return [];
        }

        return $this->formatRows($rows);
    }

    /**
     * {@inheritDoc}
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $query = new DbQuery();
        $query->select('c.`id_category`, cl.`name`, c.`level_depth`')
            ->from('category', 'c')
            ->leftJoin('category_lang', 'cl', 'c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . $this->langId)
            ->leftJoin('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . $this->shopId)
            ->where($this->buildIdsWhere($ids, 'c.`id_category`'))
            ->orderBy('c.`level_depth` ASC, cl.`name` ASC');

        $rows = $this->db->executeS($query);

        if (!$rows) {
            return [];
        }

        return $this->formatRows($rows);
    }

    /**
     * Formate les lignes de résultats.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{id: int, name: string, image: string}>
     */
    private function formatRows(array $rows): array
    {
        $results = [];

        foreach ($rows as $row) {
            $categoryId = (int) $row['id_category'];
            $depth = (int) $row['level_depth'];

            // Indentation visuelle selon la profondeur
            $prefix = str_repeat('— ', max(0, $depth - 1));

            $results[] = $this->formatResult(
                $categoryId,
                $prefix . $row['name'] . ' (ID: ' . $categoryId . ')',
                $this->getCategoryImageUrl($categoryId)
            );
        }

        return $results;
    }

    /**
     * Récupère l'URL de l'image de la catégorie.
     */
    private function getCategoryImageUrl(int $categoryId): string
    {
        $imagePath = _PS_CAT_IMG_DIR_ . $categoryId . '.jpg';

        if (file_exists($imagePath)) {
            return _PS_BASE_URL_ . __PS_BASE_URI__ . 'img/c/' . $categoryId . '.jpg';
        }

        return $this->getDefaultImageUrl();
    }
}


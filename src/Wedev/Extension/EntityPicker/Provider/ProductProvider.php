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
use Link;

/**
 * Provider de recherche pour les produits PrestaShop.
 */
final class ProductProvider extends AbstractEntityProvider
{
    private ?Link $link = null;

    public function getEntityType(): string
    {
        return 'product';
    }

    public function getEntityLabel(): string
    {
        return 'Produits';
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
        $query->select('p.`id_product`, pl.`name`, cl.`link_rewrite` AS cat_link, pi.`id_image`')
            ->from('product', 'p')
            ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . $this->langId)
            ->leftJoin('product_shop', 'ps', 'p.`id_product` = ps.`id_product` AND ps.`id_shop` = ' . $this->shopId)
            ->leftJoin('category_lang', 'cl', 'p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . $this->langId)
            ->leftJoin('image_shop', 'pi', 'pi.`id_product` = p.`id_product` AND pi.`id_shop` = ' . $this->shopId . ' AND pi.`cover` = 1')
            ->where($this->buildSearchWhere($term, ['pl.`name`', 'p.`reference`']))
            ->where('ps.`active` = 1')
            ->groupBy('p.`id_product`')
            ->orderBy('pl.`name` ASC')
            ->limit($limit);

        // Recherche par ID si le terme est numérique
        if (is_numeric($term)) {
            $query->where('p.`id_product` = ' . (int) $term, 'OR');
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
        $query->select('p.`id_product`, pl.`name`, cl.`link_rewrite` AS cat_link, pi.`id_image`')
            ->from('product', 'p')
            ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . $this->langId)
            ->leftJoin('product_shop', 'ps', 'p.`id_product` = ps.`id_product` AND ps.`id_shop` = ' . $this->shopId)
            ->leftJoin('category_lang', 'cl', 'p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . $this->langId)
            ->leftJoin('image_shop', 'pi', 'pi.`id_product` = p.`id_product` AND pi.`id_shop` = ' . $this->shopId . ' AND pi.`cover` = 1')
            ->where($this->buildIdsWhere($ids, 'p.`id_product`'))
            ->groupBy('p.`id_product`')
            ->orderBy('pl.`name` ASC');

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
            $productId = (int) $row['id_product'];
            $imageId = (int) ($row['id_image'] ?? 0);

            $results[] = $this->formatResult(
                $productId,
                $row['name'] . ' (ID: ' . $productId . ')',
                $this->getProductImageUrl($productId, $imageId)
            );
        }

        return $results;
    }

    /**
     * Récupère l'URL de l'image du produit.
     */
    private function getProductImageUrl(int $productId, int $imageId): string
    {
        if ($imageId <= 0) {
            return $this->getDefaultImageUrl();
        }

        if ($this->link === null) {
            $this->link = \Context::getContext()->link ?? new Link();
        }

        try {
            return $this->link->getImageLink('product', (string) $imageId, 'small_default');
        } catch (\Exception $e) {
            return $this->getDefaultImageUrl();
        }
    }
}


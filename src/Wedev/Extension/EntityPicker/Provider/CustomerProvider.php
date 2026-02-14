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
 * Provider de recherche pour les clients PrestaShop.
 */
final class CustomerProvider extends AbstractEntityProvider
{
    public function getEntityType(): string
    {
        return 'customer';
    }

    public function getEntityLabel(): string
    {
        return 'Clients';
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
        $query->select('c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`, c.`company`')
            ->from('customer', 'c')
            ->where($this->buildSearchWhere($term, [
                'c.`firstname`',
                'c.`lastname`',
                'c.`email`',
                'c.`company`',
                'CONCAT(c.`firstname`, " ", c.`lastname`)',
            ]))
            ->where('c.`active` = 1')
            ->where('c.`deleted` = 0')
            ->where('c.`id_shop` = ' . $this->shopId)
            ->orderBy('c.`lastname` ASC, c.`firstname` ASC')
            ->limit($limit);

        // Recherche par ID si le terme est numérique
        if (is_numeric($term)) {
            $query->where('c.`id_customer` = ' . (int) $term, 'OR');
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
        $query->select('c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`, c.`company`')
            ->from('customer', 'c')
            ->where($this->buildIdsWhere($ids, 'c.`id_customer`'))
            ->orderBy('c.`lastname` ASC, c.`firstname` ASC');

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
            $customerId = (int) $row['id_customer'];
            $name = $row['firstname'] . ' ' . $row['lastname'];

            if (!empty($row['company'])) {
                $name .= ' (' . $row['company'] . ')';
            }

            $name .= ' - ' . $row['email'];

            $results[] = $this->formatResult(
                $customerId,
                $name,
                $this->getGravatarUrl($row['email'])
            );
        }

        return $results;
    }

    /**
     * Récupère l'URL Gravatar pour l'email.
     */
    private function getGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));

        return 'https://www.gravatar.com/avatar/' . $hash . '?d=mp&s=50';
    }
}


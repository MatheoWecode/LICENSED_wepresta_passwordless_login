<?php
/**
 * WEDEV Core - AbstractRepository
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Core\Repository;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Db;
use DbQuery;

/**
 * Classe de base pour les repositories utilisant l'API Db PrestaShop.
 */
abstract class AbstractRepository
{
    protected Db $db;
    protected string $dbPrefix;

    public function __construct(?Db $db = null, ?string $dbPrefix = null)
    {
        $this->db = $db ?? Db::getInstance();
        $this->dbPrefix = $dbPrefix ?? _DB_PREFIX_;
    }

    /**
     * Nom de la table (sans préfixe).
     */
    abstract protected function getTableName(): string;

    /**
     * Nom de la table avec préfixe.
     */
    protected function getTable(): string
    {
        return $this->dbPrefix . $this->getTableName();
    }

    /**
     * Nom de la clé primaire.
     */
    protected function getPrimaryKey(): string
    {
        return 'id_' . $this->getTableName();
    }

    /**
     * Trouve un enregistrement par ID.
     */
    public function findById(int $id): ?array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where($this->getPrimaryKey() . ' = ' . $id);

        $result = $this->db->getRow($query);

        return $result ?: null;
    }

    /**
     * Trouve tous les enregistrements.
     *
     * @return array[]
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->orderBy($this->getPrimaryKey() . ' DESC');

        if ($limit !== null) {
            $query->limit($limit, $offset ?? 0);
        }

        return $this->db->executeS($query) ?: [];
    }

    /**
     * Trouve les enregistrements actifs.
     *
     * @return array[]
     */
    public function findActive(): array
    {
        $query = new DbQuery();
        $query->select('*')
            ->from($this->getTableName())
            ->where('active = 1')
            ->orderBy('position ASC');

        return $this->db->executeS($query) ?: [];
    }

    /**
     * Validate and sanitize field names to prevent SQL injection.
     * Only allows alphanumeric characters, underscores, and dot (for table.field).
     *
     * @param string $field Field name to validate
     * @return string Validated field name
     * @throws \InvalidArgumentException
     */
    protected function validateFieldName(string $field): string
    {
        if (!preg_match('/^[a-zA-Z0-9_`\.]+$/', $field)) {
            throw new \InvalidArgumentException(
                'Invalid field name: ' . $field . '. Only alphanumeric, underscore, and dot allowed.'
            );
        }

        // Prevent SQL keywords used as field names without proper escaping
        $reservedKeywords = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'UNION', 'WHERE', 'FROM'];
        $upperField = strtoupper($field);

        foreach ($reservedKeywords as $keyword) {
            if (strpos($upperField, $keyword) === 0) {
                throw new \InvalidArgumentException(
                    'Invalid field name: field name cannot start with SQL keyword: ' . $keyword
                );
            }
        }

        return $field;
    }

    /**
     * Validate and sanitize ORDER BY clause to prevent SQL injection.
     * Only allows field names, ASC, DESC, and comma separation.
     *
     * @param string $orderBy ORDER BY clause to validate
     * @return string Validated ORDER BY clause
     * @throws \InvalidArgumentException
     */
    protected function validateOrderBy(string $orderBy): string
    {
        if (empty($orderBy)) {
            throw new \InvalidArgumentException('ORDER BY clause cannot be empty');
        }

        // Split by comma for multiple fields
        $parts = array_map('trim', explode(',', $orderBy));

        foreach ($parts as $part) {
            // Each part should be: fieldname [ASC|DESC]
            if (!preg_match('/^[a-zA-Z0-9_`\.]+(\s+(ASC|DESC))?$/i', $part)) {
                throw new \InvalidArgumentException(
                    'Invalid ORDER BY clause: ' . $part . '. Only field names and ASC/DESC allowed.'
                );
            }
        }

        return $orderBy;
    }

    /**
     * Trouve par critères.
     *
     * @param array<string, mixed> $criteria
     * @return array[]
     */
    public function findBy(array $criteria, ?string $orderBy = null, ?int $limit = null): array
    {
        $query = new DbQuery();
        $query->select('*')->from($this->getTableName());

        foreach ($criteria as $field => $value) {
            // Validate field name to prevent SQL injection
            $safeField = $this->validateFieldName($field);

            if ($value === null) {
                $query->where('`' . $safeField . '` IS NULL');
            } elseif (is_bool($value)) {
                $query->where('`' . $safeField . '` = ' . ($value ? '1' : '0'));
            } elseif (is_int($value)) {
                $query->where('`' . $safeField . '` = ' . $value);
            } else {
                $query->where('`' . $safeField . "` = '" . pSQL((string) $value) . "'");
            }
        }

        if ($orderBy !== null) {
            $safeOrderBy = $this->validateOrderBy($orderBy);
            $query->orderBy($safeOrderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $this->db->executeS($query) ?: [];
    }

    /**
     * Trouve un seul enregistrement par critères.
     */
    public function findOneBy(array $criteria): ?array
    {
        $results = $this->findBy($criteria, null, 1);

        return $results[0] ?? null;
    }

    /**
     * Compte les enregistrements.
     */
    public function count(?array $criteria = null): int
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
            ->from($this->getTableName());

        if ($criteria !== null) {
            foreach ($criteria as $field => $value) {
                // Validate field name to prevent SQL injection
                $safeField = $this->validateFieldName($field);

                if (is_int($value)) {
                    $query->where('`' . $safeField . '` = ' . $value);
                } else {
                    $query->where('`' . $safeField . "` = '" . pSQL((string) $value) . "'");
                }
            }
        }

        return (int) $this->db->getValue($query);
    }

    /**
     * Insère un enregistrement.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $data['date_add'] = $data['date_add'] ?? date('Y-m-d H:i:s');
        $data['date_upd'] = $data['date_upd'] ?? date('Y-m-d H:i:s');

        $this->db->insert($this->getTableName(), $this->escapeData($data));

        return (int) $this->db->Insert_ID();
    }

    /**
     * Met à jour un enregistrement.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $data['date_upd'] = date('Y-m-d H:i:s');

        return $this->db->update(
            $this->getTableName(),
            $this->escapeData($data),
            $this->getPrimaryKey() . ' = ' . $id
        );
    }

    /**
     * Supprime un enregistrement.
     */
    public function delete(int $id): bool
    {
        return $this->db->delete(
            $this->getTableName(),
            $this->getPrimaryKey() . ' = ' . $id
        );
    }

    /**
     * Supprime par critères.
     */
    public function deleteBy(array $criteria): int
    {
        $where = [];
        foreach ($criteria as $field => $value) {
            // Validate field name to prevent SQL injection
            $safeField = $this->validateFieldName($field);

            if (is_int($value)) {
                $where[] = '`' . $safeField . '` = ' . $value;
            } else {
                $where[] = '`' . $safeField . "` = '" . pSQL((string) $value) . "'";
            }
        }

        $this->db->delete($this->getTableName(), implode(' AND ', $where));

        return (int) $this->db->Affected_Rows();
    }

    /**
     * Récupère la position maximale.
     */
    public function getMaxPosition(): int
    {
        $query = new DbQuery();
        $query->select('MAX(position)')
            ->from($this->getTableName());

        return (int) $this->db->getValue($query);
    }

    /**
     * Met à jour la position.
     */
    public function updatePosition(int $id, int $position): bool
    {
        return $this->update($id, ['position' => $position]);
    }

    /**
     * Active/Désactive un enregistrement.
     */
    public function toggleActive(int $id): bool
    {
        $current = $this->findById($id);
        if ($current === null) {
            return false;
        }

        $newStatus = !((bool) ($current['active'] ?? false));

        return $this->update($id, ['active' => $newStatus ? 1 : 0]);
    }

    /**
     * Échappe les données pour l'insertion.
     *
     * @return array<string, string|int|float|null>
     */
    protected function escapeData(array $data): array
    {
        $escaped = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $escaped[pSQL($key)] = null;
            } elseif (is_bool($value)) {
                $escaped[pSQL($key)] = $value ? 1 : 0;
            } elseif (is_int($value) || is_float($value)) {
                $escaped[pSQL($key)] = $value;
            } else {
                $escaped[pSQL($key)] = pSQL((string) $value, true);
            }
        }

        return $escaped;
    }

    /**
     * Exécute une requête brute.
     *
     * @return array[]|bool
     */
    protected function query(string $sql): array|bool
    {
        return $this->db->executeS($sql) ?: [];
    }

    /**
     * Exécute une commande brute.
     */
    protected function execute(string $sql): bool
    {
        return $this->db->execute($sql);
    }

    // =========================================================================
    // Relations Many-to-Many (tables pivot)
    // =========================================================================

    /**
     * Attache une entité liée via une table pivot.
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param string $foreignKey Nom de la clé étrangère (ex: 'id_product')
     * @param int $entityId ID de l'entité principale
     * @param int $foreignId ID de l'entité à attacher
     */
    public function attachTo(string $pivotTable, string $foreignKey, int $entityId, int $foreignId): bool
    {
        // Vérifier si la relation existe déjà
        $existing = $this->db->getValue(
            'SELECT 1 FROM `' . $this->dbPrefix . pSQL($pivotTable) . '`
             WHERE `' . pSQL($this->getPrimaryKey()) . '` = ' . $entityId . '
             AND `' . pSQL($foreignKey) . '` = ' . $foreignId
        );

        if ($existing) {
            return true; // Déjà attaché
        }

        return $this->db->insert(pSQL($pivotTable), [
            pSQL($this->getPrimaryKey()) => $entityId,
            pSQL($foreignKey) => $foreignId,
        ]);
    }

    /**
     * Détache une entité liée via une table pivot.
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param string $foreignKey Nom de la clé étrangère
     * @param int $entityId ID de l'entité principale
     * @param int $foreignId ID de l'entité à détacher
     */
    public function detachFrom(string $pivotTable, string $foreignKey, int $entityId, int $foreignId): bool
    {
        return $this->db->delete(
            pSQL($pivotTable),
            '`' . pSQL($this->getPrimaryKey()) . '` = ' . $entityId . '
             AND `' . pSQL($foreignKey) . '` = ' . $foreignId
        );
    }

    /**
     * Détache toutes les entités liées d'une table pivot.
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param int $entityId ID de l'entité principale
     */
    public function detachAll(string $pivotTable, int $entityId): bool
    {
        return $this->db->delete(
            pSQL($pivotTable),
            '`' . pSQL($this->getPrimaryKey()) . '` = ' . $entityId
        );
    }

    /**
     * Attache plusieurs entités en une seule opération.
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param string $foreignKey Nom de la clé étrangère
     * @param int $entityId ID de l'entité principale
     * @param int[] $foreignIds Liste des IDs à attacher
     */
    public function attachMany(string $pivotTable, string $foreignKey, int $entityId, array $foreignIds): bool
    {
        if (empty($foreignIds)) {
            return true;
        }

        $values = [];
        $pk = pSQL($this->getPrimaryKey());
        $fk = pSQL($foreignKey);

        foreach ($foreignIds as $foreignId) {
            $values[] = '(' . $entityId . ', ' . (int) $foreignId . ')';
        }

        $sql = 'INSERT IGNORE INTO `' . $this->dbPrefix . pSQL($pivotTable) . '`
                (`' . $pk . '`, `' . $fk . '`)
                VALUES ' . implode(', ', $values);

        return $this->db->execute($sql);
    }

    /**
     * Récupère les IDs des entités attachées.
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param string $foreignKey Nom de la clé étrangère
     * @param int $entityId ID de l'entité principale
     *
     * @return int[]
     */
    public function getAttachedIds(string $pivotTable, string $foreignKey, int $entityId): array
    {
        $query = new DbQuery();
        $query->select(pSQL($foreignKey))
            ->from(pSQL($pivotTable))
            ->where('`' . pSQL($this->getPrimaryKey()) . '` = ' . $entityId);

        $results = $this->db->executeS($query);

        if (!$results) {
            return [];
        }

        return array_map(fn($row) => (int) $row[$foreignKey], $results);
    }

    /**
     * Synchronise les entités attachées (supprime les anciennes, ajoute les nouvelles).
     *
     * @param string $pivotTable Nom de la table pivot (sans préfixe)
     * @param string $foreignKey Nom de la clé étrangère
     * @param int $entityId ID de l'entité principale
     * @param int[] $foreignIds Liste des IDs finaux souhaités
     */
    public function syncAttached(string $pivotTable, string $foreignKey, int $entityId, array $foreignIds): void
    {
        // Supprimer toutes les anciennes associations
        $this->detachAll($pivotTable, $entityId);

        // Ajouter les nouvelles
        if (!empty($foreignIds)) {
            $this->attachMany($pivotTable, $foreignKey, $entityId, $foreignIds);
        }
    }
}


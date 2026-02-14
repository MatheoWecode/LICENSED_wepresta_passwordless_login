<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Application\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use Tools;

/**
 * Handles database installation and uninstallation.
 * Supports SQL files, migrations, and advanced database operations.
 */
class DatabaseInstaller
{
    private string $moduleName;
    private string $modulePath;
    private string $dbPrefix;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->modulePath = _PS_MODULE_DIR_ . $moduleName . '/';
        $this->dbPrefix = $this->validateDbPrefix(_DB_PREFIX_);
    }

    /**
     * Validate and sanitize database prefix to prevent SQL injection.
     *
     * @param string $prefix Raw database prefix
     * @return string Sanitized database prefix
     * @throws InstallationException
     */
    private function validateDbPrefix(string $prefix): string
    {
        // Database prefix should only contain alphanumeric characters and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            throw InstallationException::forSecurityViolation(
                'Invalid database prefix format: ' . $prefix
            );
        }

        // Ensure prefix doesn't exceed reasonable length
        if (strlen($prefix) > 50) {
            throw InstallationException::forSecurityViolation(
                'Database prefix too long: ' . $prefix
            );
        }

        return $prefix;
    }

    /**
     * Validate MySQL engine type.
     *
     * @param string $engine Raw MySQL engine
     * @return string Validated MySQL engine
     * @throws InstallationException
     */
    private function validateMysqlEngine(string $engine): string
    {
        // Allow only known safe MySQL engines
        $allowedEngines = ['InnoDB', 'MyISAM', 'MEMORY', 'MERGE', 'ARCHIVE'];

        if (!in_array($engine, $allowedEngines, true)) {
            throw InstallationException::forSecurityViolation(
                'Invalid MySQL engine: ' . $engine
            );
        }

        return $engine;
    }

    /**
     * Install database tables from SQL file or configuration.
     *
     * @param array<string, mixed> $config Database configuration
     * @return bool
     * @throws InstallationException
     */
    public function install(array $config = []): bool
    {
        // Execute install.sql if it exists
        $this->executeSqlFile('install.sql');

        // Execute additional SQL files if specified
        if (isset($config['sql_files']) && is_array($config['sql_files'])) {
            foreach ($config['sql_files'] as $sqlFile) {
                $this->executeSqlFile($sqlFile);
            }
        }

        // Create tables from configuration if specified
        if (isset($config['tables']) && is_array($config['tables'])) {
            $this->createTablesFromConfig($config['tables']);
        }

        // Run migrations if specified
        if (isset($config['migrate_to'])) {
            $this->migrate($config['migrate_to']);
        }

        return true;
    }

    /**
     * Uninstall database tables from SQL file or configuration.
     *
     * @param array<string, mixed> $config Database configuration
     * @return bool
     * @throws InstallationException
     */
    public function uninstall(array $config = []): bool
    {
        // Execute uninstall.sql if it exists
        $this->executeSqlFile('uninstall.sql');

        // Execute additional uninstall SQL files if specified
        if (isset($config['uninstall_sql_files']) && is_array($config['uninstall_sql_files'])) {
            foreach ($config['uninstall_sql_files'] as $sqlFile) {
                $this->executeSqlFile($sqlFile);
            }
        }

        // Drop tables from configuration if specified
        if (isset($config['tables']) && is_array($config['tables'])) {
            $this->dropTablesFromConfig($config['tables']);
        }

        return true;
    }

    /**
     * Execute a SQL file.
     *
     * @param string $filename SQL file name
     * @return bool
     * @throws InstallationException
     */
    public function executeSqlFile(string $filename): bool
    {
        $filePath = $this->modulePath . 'sql/' . $filename;

        if (!file_exists($filePath)) {
            return true; // File doesn't exist, skip silently
        }

        $sql = Tools::file_get_contents($filePath);

        if (empty($sql)) {
            return true;
        }

        return $this->executeSql($sql);
    }

    /**
     * Execute SQL queries.
     *
     * @param string $sql SQL content
     * @return bool
     * @throws InstallationException
     */
    public function executeSql(string $sql): bool
    {
        // Validate MySQL engine
        $mysqlEngine = $this->validateMysqlEngine(_MYSQL_ENGINE_);

        $sql = str_replace(
            ['PREFIX_', 'ENGINE_TYPE'],
            [$this->dbPrefix, $mysqlEngine],
            $sql
        );

        // Remove comments
        $sql = preg_replace('/^--.*$/m', '', $sql);
        // Split on semicolons
        $queries = explode(';', $sql);

        foreach ($queries as $query) {
            $query = trim($query);
            // Skip empty queries
            if (empty($query)) {
                continue;
            }

            if (!Db::getInstance()->execute($query)) {
                throw InstallationException::databaseError(
                    'execute_sql',
                    Db::getInstance()->getMsgError()
                );
            }
        }

        return true;
    }

    /**
     * Create tables from configuration array.
     *
     * @param array<array<string, mixed>> $tables Table configurations
     * @return bool
     * @throws InstallationException
     */
    public function createTablesFromConfig(array $tables): bool
    {
        foreach ($tables as $tableName => $tableConfig) {
            $this->createTable($tableName, $tableConfig);
        }

        return true;
    }

    /**
     * Create a single table from configuration.
     *
     * @param string $tableName Table name (without prefix)
     * @param array<string, mixed> $config Table configuration
     * @return bool
     * @throws InstallationException
     */
    public function createTable(string $tableName, array $config): bool
    {
        if ($this->tableExists($tableName)) {
            // Check if table structure needs updating
            if (isset($config['update_if_exists']) && $config['update_if_exists']) {
                return $this->updateTableStructure($tableName, $config);
            }
            return true;
        }

        $columns = $config['columns'] ?? [];
        $indexes = $config['indexes'] ?? [];
        $engine = $config['engine'] ?? _MYSQL_ENGINE_;
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $sql = "CREATE TABLE `" . $this->dbPrefix . pSQL($tableName) . "` (";

        $columnDefinitions = [];
        foreach ($columns as $columnName => $columnConfig) {
            $columnDefinitions[] = $this->buildColumnDefinition($columnName, $columnConfig);
        }

        $sql .= implode(', ', $columnDefinitions);

        // Add indexes
        if (!empty($indexes)) {
            $sql .= ', ';
            $indexDefinitions = [];
            foreach ($indexes as $indexName => $indexConfig) {
                $indexDefinitions[] = $this->buildIndexDefinition($indexName, $indexConfig);
            }
            $sql .= implode(', ', $indexDefinitions);
        }

        $sql .= ") ENGINE=" . pSQL($engine) . " DEFAULT CHARSET=" . pSQL($charset) . " COLLATE=" . pSQL($collation) . ";";

        return $this->executeSql($sql);
    }

    /**
     * Build column definition for CREATE TABLE.
     *
     * @param string $columnName Column name
     * @param array<string, mixed> $config Column configuration
     * @return string
     */
    private function buildColumnDefinition(string $columnName, array $config): string
    {
        $type = strtoupper($config['type'] ?? 'VARCHAR(255)');
        $null = isset($config['null']) && $config['null'] ? 'NULL' : 'NOT NULL';
        $default = isset($config['default']) ? 'DEFAULT ' . $this->formatDefaultValue($config['default']) : '';
        $autoIncrement = isset($config['auto_increment']) && $config['auto_increment'] ? 'AUTO_INCREMENT' : '';
        $primary = isset($config['primary']) && $config['primary'] ? 'PRIMARY KEY' : '';

        $parts = array_filter([
            "`" . pSQL($columnName) . "`",
            $type,
            $null,
            $default,
            $autoIncrement,
            $primary
        ]);

        return implode(' ', $parts);
    }

    /**
     * Build index definition for CREATE TABLE.
     *
     * @param string $indexName Index name
     * @param array<string, mixed> $config Index configuration
     * @return string
     */
    private function buildIndexDefinition(string $indexName, array $config): string
    {
        $type = strtoupper($config['type'] ?? 'INDEX');
        $columns = $config['columns'] ?? [];

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $columnList = implode(',', array_map(function ($col) {
            return "`" . pSQL($col) . "`";
        }, $columns));

        return $type . " `" . pSQL($indexName) . "` (" . $columnList . ")";
    }

    /**
     * Format default value for SQL.
     *
     * @param mixed $value Default value
     * @return string
     */
    private function formatDefaultValue($value): string
    {
        if (is_string($value)) {
            return "'" . pSQL($value) . "'";
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        return (string) $value;
    }

    /**
     * Drop tables from configuration.
     *
     * @param array<array<string, mixed>> $tables Table configurations
     * @return bool
     */
    public function dropTablesFromConfig(array $tables): bool
    {
        foreach (array_keys($tables) as $tableName) {
            $this->dropTable($tableName);
        }

        return true;
    }

    /**
     * Drop a single table.
     *
     * @param string $tableName Table name (without prefix)
     * @return bool
     */
    public function dropTable(string $tableName): bool
    {
        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tableName)) {
            return false;
        }

        if (!$this->tableExists($tableName)) {
            return true;
        }

        $sql = "DROP TABLE `" . $this->dbPrefix . pSQL($tableName) . "`;";

        return Db::getInstance()->execute($sql);
    }

    /**
     * Update table structure if needed.
     *
     * @param string $tableName Table name (without prefix)
     * @param array<string, mixed> $config Table configuration
     * @return bool
     */
    private function updateTableStructure(string $tableName, array $config): bool
    {
        $columns = $config['columns'] ?? [];

        foreach ($columns as $columnName => $columnConfig) {
            if (!$this->columnExists($tableName, $columnName)) {
                $this->addColumn($tableName, $columnName, $columnConfig);
            }
        }

        return true;
    }

    /**
     * Add a column to an existing table.
     *
     * @param string $tableName Table name (without prefix)
     * @param string $columnName Column name
     * @param array<string, mixed> $config Column configuration
     * @return bool
     */
    public function addColumn(string $tableName, string $columnName, array $config): bool
    {
        // Validate table and column names
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tableName) ||
            !preg_match('/^[a-zA-Z0-9_-]+$/', $columnName)) {
            return false;
        }

        $definition = $this->buildColumnDefinition($columnName, $config);
        $sql = "ALTER TABLE `" . $this->dbPrefix . pSQL($tableName) . "` ADD COLUMN " . $definition . ";";

        return Db::getInstance()->execute($sql);
    }

    /**
     * Run migrations up to a specific version.
     *
     * @param string $targetVersion Target version
     * @return bool
     * @throws InstallationException
     */
    public function migrate(string $targetVersion): bool
    {
        $migrationsPath = $this->modulePath . 'sql/migrations/';

        if (!is_dir($migrationsPath)) {
            return true;
        }

        $migrationFiles = glob($migrationsPath . '*.php');

        if (empty($migrationFiles)) {
            return true;
        }

        sort($migrationFiles);

        foreach ($migrationFiles as $migrationFile) {
            $version = $this->extractVersionFromFilename($migrationFile);

            if (version_compare($version, $targetVersion, '>')) {
                break;
            }

            $this->executeMigration($migrationFile);
        }

        return true;
    }

    /**
     * Execute a single migration file.
     *
     * @param string $migrationFile Migration file path
     * @return bool
     * @throws InstallationException
     */
    private function executeMigration(string $migrationFile): bool
    {
        require_once $migrationFile;

        $className = $this->getClassNameFromFile($migrationFile);

        if (!class_exists($className)) {
            throw InstallationException::databaseError('migration', sprintf('Migration class not found: %s', $className));
        }

        $migration = new $className();

        if (!method_exists($migration, 'up')) {
            throw InstallationException::databaseError('migration', sprintf('Migration class %s must have an up() method', $className));
        }

        $migration->up();

        return true;
    }

    /**
     * Extract version from migration filename.
     *
     * @param string $filename Migration filename
     * @return string
     */
    private function extractVersionFromFilename(string $filename): string
    {
        $basename = basename($filename, '.php');
        $parts = explode('_', $basename, 2);

        return $parts[0] ?? '0.0.0';
    }

    /**
     * Get class name from migration file.
     *
     * @param string $filename Migration filename
     * @return string
     */
    private function getClassNameFromFile(string $filename): string
    {
        return basename($filename, '.php');
    }

    /**
     * Check if a table exists.
     *
     * @param string $tableName Table name (without prefix)
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tableName)) {
            return false;
        }

        $result = Db::getInstance()->executeS(
            'SHOW TABLES LIKE "' . $this->dbPrefix . pSQL($tableName) . '"'
        );

        return !empty($result);
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $tableName Table name (without prefix)
     * @param string $columnName Column name
     * @return bool
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        // Validate table and column names
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tableName) ||
            !preg_match('/^[a-zA-Z0-9_-]+$/', $columnName)) {
            return false;
        }

        $result = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . $this->dbPrefix . pSQL($tableName) . '` LIKE "' . pSQL($columnName) . '"'
        );

        return !empty($result);
    }

    /**
     * Get database version information.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseInfo(): array
    {
        $version = Db::getInstance()->getVersion();
        $engine = Db::getInstance()->executeS('SELECT ENGINE, VERSION() as version');

        return [
            'version' => $version,
            'engine' => $engine[0]['ENGINE'] ?? 'Unknown',
            'mysql_version' => $engine[0]['version'] ?? 'Unknown',
            'prefix' => $this->dbPrefix,
        ];
    }

    /**
     * Backup a table structure and data.
     *
     * @param string $tableName Table name (without prefix)
     * @param string $backupSuffix Suffix for backup table name
     * @return bool
     */
    public function backupTable(string $tableName, string $backupSuffix = '_backup'): bool
    {
        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tableName)) {
            return false;
        }

        $backupTableName = $tableName . $backupSuffix;

        // Validate backup suffix
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $backupSuffix)) {
            return false;
        }

        // Drop backup table if exists
        $this->dropTable($backupTableName);

        // Create backup table using secure table names
        $sql = "CREATE TABLE `" . $this->dbPrefix . pSQL($backupTableName) . "` AS SELECT * FROM `" . $this->dbPrefix . pSQL($tableName) . "`;";

        return Db::getInstance()->execute($sql);
    }
}


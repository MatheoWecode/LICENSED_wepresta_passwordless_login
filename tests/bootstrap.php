<?php

declare(strict_types=1);

/**
 * Bootstrap PHPUnit pour tests WEDEV
 */

// Autoloader Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Définir les constantes PrestaShop simulées
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '9.0.0');
}

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', dirname(__DIR__) . '/');
}

// Classe Mock pour Configuration PrestaShop
if (!class_exists('Configuration')) {
    class Configuration
    {
        private static array $data = [];

        public static function get(string $key, ?int $langId = null, ?int $shopGroupId = null, ?int $shopId = null): mixed
        {
            return self::$data[$key] ?? null;
        }

        public static function set(string $key, mixed $value, bool $html = false): void
        {
            self::$data[$key] = $value;
        }

        public static function updateValue(string $key, mixed $value, bool $html = false): bool
        {
            self::$data[$key] = $value;
            return true;
        }

        public static function deleteByName(string $key): bool
        {
            unset(self::$data[$key]);
            return true;
        }

        public static function hasKey(string $key): bool
        {
            return array_key_exists($key, self::$data);
        }

        public static function reset(): void
        {
            self::$data = [];
        }
    }
}

// Classe Mock pour Context
if (!class_exists('Context')) {
    class Context
    {
        public ?object $shop = null;
        public ?object $language = null;
        public ?object $customer = null;
        public ?object $cart = null;
        public ?object $smarty = null;
        public ?object $controller = null;

        private static ?Context $instance = null;

        public static function getContext(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
                self::$instance->shop = (object) ['id' => 1, 'id_shop_group' => 1];
                self::$instance->language = (object) ['id' => 1];
                self::$instance->customer = (object) ['id' => 0, 'id_default_group' => 1];
                self::$instance->cart = (object) [
                    'id' => 0,
                    'id_customer' => 0,
                    'getOrderTotal' => fn() => 0.0,
                    'nbProducts' => fn() => 0,
                ];
            }
            return self::$instance;
        }

        public static function reset(): void
        {
            self::$instance = null;
        }
    }
}

// Classe Mock pour Shop
if (!class_exists('Shop')) {
    class Shop
    {
        public static function isFeatureActive(): bool
        {
            return false;
        }

        public static function getShops(bool $active = true): array
        {
            return [
                ['id_shop' => 1, 'name' => 'Default Shop', 'id_shop_group' => 1],
            ];
        }
    }
}

// Classe Mock pour Db
if (!class_exists('Db')) {
    class Db
    {
        private static ?Db $instance = null;

        public static function getInstance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function execute(string $query): bool
        {
            return true;
        }

        public function executeS(string $query): array
        {
            return [];
        }

        public function getRow(string $query): ?array
        {
            return null;
        }

        public function getValue(string $query): mixed
        {
            return null;
        }

        public function insert(string $table, array $data): bool
        {
            return true;
        }

        public function update(string $table, array $data, string $where): bool
        {
            return true;
        }

        public function delete(string $table, string $where): bool
        {
            return true;
        }

        public function Insert_ID(): int
        {
            return 1;
        }

        public function Affected_Rows(): int
        {
            return 1;
        }
    }
}

// Classe Mock pour DbQuery
if (!class_exists('DbQuery')) {
    class DbQuery
    {
        private string $query = '';

        public function select(string $fields): self
        {
            $this->query = "SELECT {$fields}";
            return $this;
        }

        public function from(string $table, ?string $alias = null): self
        {
            $this->query .= " FROM " . _DB_PREFIX_ . $table;
            if ($alias) {
                $this->query .= " {$alias}";
            }
            return $this;
        }

        public function where(string $condition): self
        {
            $this->query .= " WHERE {$condition}";
            return $this;
        }

        public function orderBy(string $order): self
        {
            $this->query .= " ORDER BY {$order}";
            return $this;
        }

        public function limit(int $limit, int $offset = 0): self
        {
            $this->query .= " LIMIT {$offset}, {$limit}";
            return $this;
        }

        public function build(): string
        {
            return $this->query;
        }

        public function __toString(): string
        {
            return $this->build();
        }
    }
}

// Fonction pSQL
if (!function_exists('pSQL')) {
    function pSQL(string $string): string
    {
        return addslashes($string);
    }
}

// Classe PrestaShopLogger
if (!class_exists('PrestaShopLogger')) {
    class PrestaShopLogger
    {
        public const LOG_SEVERITY_LEVEL_INFORMATIVE = 1;
        public const LOG_SEVERITY_LEVEL_WARNING = 2;
        public const LOG_SEVERITY_LEVEL_ERROR = 3;
        public const LOG_SEVERITY_LEVEL_MAJOR = 4;

        public static function addLog(
            string $message,
            int $severity = 1,
            ?int $errorCode = null,
            ?string $objectType = null,
            ?int $objectId = null,
            bool $allowDuplicate = false
        ): bool {
            return true;
        }
    }
}

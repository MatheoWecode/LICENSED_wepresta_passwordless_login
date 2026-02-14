# Migrations base de données

> Référence technique détaillée : [.cursor/rules/006-module-database.mdc](../../.cursor/rules/006-module-database.mdc)

Gérer les tables personnalisées et les mises à jour de schéma.

## Structure des fichiers SQL

```
sql/
├── install.sql       # Création des tables
└── uninstall.sql     # Suppression des tables
```

---

## Installation initiale

### Fichier install.sql

```sql
CREATE TABLE IF NOT EXISTS `PREFIX_monmodule_item` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_monmodule_item`),
    KEY `active` (`active`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> **Note** : `PREFIX_` et `ENGINE_TYPE` sont remplacés automatiquement par PrestaShop.

### Exécution à l'installation

```php
public function installDatabase(): bool
{
    $sql = file_get_contents($this->getLocalPath() . 'sql/install.sql');
    $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
    
    return Db::getInstance()->execute($sql);
}
```

---

## Désinstallation

### Fichier uninstall.sql

```sql
DROP TABLE IF EXISTS `PREFIX_monmodule_item`;
DROP TABLE IF EXISTS `PREFIX_monmodule_item_lang`;
DROP TABLE IF EXISTS `PREFIX_monmodule_item_shop`;
```

### Exécution

```php
public function uninstallDatabase(): bool
{
    $sql = file_get_contents($this->getLocalPath() . 'sql/uninstall.sql');
    $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
    
    return Db::getInstance()->execute($sql);
}
```

---

## Tables multilingues

Pour stocker des données traduisibles :

```sql
-- Table principale
CREATE TABLE IF NOT EXISTS `PREFIX_monmodule_item` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_monmodule_item`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

-- Table de traductions
CREATE TABLE IF NOT EXISTS `PREFIX_monmodule_item_lang` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    PRIMARY KEY (`id_monmodule_item`, `id_lang`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
```

---

## Tables multi-boutique

Pour le support multi-shop :

```sql
CREATE TABLE IF NOT EXISTS `PREFIX_monmodule_item_shop` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_monmodule_item`, `id_shop`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;
```

---

## Scripts de mise à jour

Quand vous modifiez le schéma sur un module déjà installé, créez des scripts d'upgrade.

### Structure

```
upgrade/
├── upgrade-1.1.0.php
├── upgrade-1.2.0.php
└── upgrade-2.0.0.php
```

### Format d'un script

```php
<?php
// upgrade/upgrade-1.1.0.php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade vers la version 1.1.0
 */
function upgrade_module_1_1_0($module): bool
{
    $sql = [];
    
    // Ajouter une colonne
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'monmodule_item` 
              ADD `priority` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `position`';
    
    // Créer une nouvelle table
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'monmodule_log` (
        `id_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `message` TEXT NOT NULL,
        `date_add` DATETIME NOT NULL,
        PRIMARY KEY (`id_log`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';
    
    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }
    
    return true;
}
```

### Déclenchement

PrestaShop exécute automatiquement les scripts d'upgrade quand :
1. La version du fichier module est supérieure à celle en BDD
2. L'utilisateur accède au back-office

---

## Bonnes pratiques

### Nommage des tables

```
ps_monmodule_item           ✅ Préfixe du module
ps_monmodule_item_lang      ✅ Suffixe _lang pour traductions
ps_monmodule_item_shop      ✅ Suffixe _shop pour multi-boutique

ps_items                    ❌ Trop générique, risque de collision
```

### Scripts idempotents

Les scripts doivent pouvoir s'exécuter plusieurs fois sans erreur :

```sql
-- ✅ BON
CREATE TABLE IF NOT EXISTS ...
ALTER TABLE ... ADD COLUMN IF NOT EXISTS ...

-- ❌ MAUVAIS
CREATE TABLE ...  -- Erreur si existe déjà
```

### Vérifications avant modification

```php
function upgrade_module_1_1_0($module): bool
{
    // Vérifier si la colonne existe déjà
    $columns = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'monmodule_item` LIKE "priority"'
    );
    
    if (empty($columns)) {
        // Ajouter la colonne
        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'monmodule_item` ADD `priority` INT(11)'
        );
    }
    
    return true;
}
```

---

## Déboguer les migrations

### Voir la version en BDD

```sql
SELECT version FROM ps_module WHERE name = 'monmodule';
```

### Forcer une ré-exécution

```sql
UPDATE ps_module SET version = '1.0.0' WHERE name = 'monmodule';
```

Puis accédez au back-office pour déclencher les upgrades.

### Exécuter manuellement

```bash
ddev mysql -e "ALTER TABLE ps_monmodule_item ADD priority INT(11);"
```

---

## Sauvegarde avant modification

Toujours sauvegarder avant une migration risquée :

```bash
# Exporter la table
ddev mysqldump ps_monmodule_item > backup_item.sql

# Restaurer si problème
ddev mysql < backup_item.sql
```

---

**Prochaine étape** : [Mise à jour du Core partagé](./update-shared-code.md)


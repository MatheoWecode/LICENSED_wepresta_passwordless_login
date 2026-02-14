# Clés de Configuration

Référence des clés de configuration du module.

## Convention de nommage

```
NOMDUMODULE_SECTION_PARAMETRE
```

Exemples :
- `MONMODULE_ACTIVE`
- `MONMODULE_API_KEY`
- `MONMODULE_DISPLAY_LIMIT`

---

## Clés du module

### Générales

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `MONMODULE_ACTIVE` | bool | `true` | Module actif |
| `MONMODULE_DEBUG` | bool | `false` | Mode debug |
| `MONMODULE_VERSION` | string | - | Version installée |

### Affichage

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `MONMODULE_DISPLAY_TITLE` | string (ml) | - | Titre affiché (multilingue) |
| `MONMODULE_DISPLAY_LIMIT` | int | `10` | Nombre d'items à afficher |
| `MONMODULE_DISPLAY_TEMPLATE` | string | `grid` | Template: grid, list, carousel |

### API

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `MONMODULE_API_ENABLED` | bool | `false` | API activée |
| `MONMODULE_API_KEY` | string | - | Clé API (générée) |
| `MONMODULE_API_RATE_LIMIT` | int | `60` | Requêtes/minute |

### Cache

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `MONMODULE_CACHE_ENABLED` | bool | `true` | Cache activé |
| `MONMODULE_CACHE_TTL` | int | `3600` | Durée du cache (secondes) |

---

## Accès via ConfigurationAdapter

```php
// Lecture
$active = $this->config->getBool('MONMODULE_ACTIVE');
$limit = $this->config->getInt('MONMODULE_DISPLAY_LIMIT');
$apiKey = $this->config->getString('MONMODULE_API_KEY');

// Écriture
$this->config->set('MONMODULE_ACTIVE', true);
$this->config->set('MONMODULE_DISPLAY_LIMIT', 20);
```

---

## Configuration multilingue

### Lire

```php
// Langue courante
$title = Configuration::get('MONMODULE_DISPLAY_TITLE', $this->context->language->id);

// Toutes les langues
$titles = [];
foreach (Language::getLanguages() as $lang) {
    $titles[$lang['id_lang']] = Configuration::get('MONMODULE_DISPLAY_TITLE', $lang['id_lang']);
}
```

### Écrire

```php
// Tableau indexé par id_lang
Configuration::updateValue('MONMODULE_DISPLAY_TITLE', [
    1 => 'Welcome',      // Anglais
    2 => 'Bienvenue',    // Français
]);
```

---

## Configuration multi-boutique

### Portée

| Type | Méthode | Usage |
|------|---------|-------|
| Globale | `getGlobalValue()` | Clés API, paramètres techniques |
| Par boutique | `get()` avec shop_id | Paramètres d'affichage |

### Exemples

```php
// Globale (toutes boutiques)
$apiKey = Configuration::getGlobalValue('MONMODULE_API_KEY');
Configuration::updateGlobalValue('MONMODULE_API_KEY', $newKey);

// Par boutique
$shopId = Context::getContext()->shop->id;
$title = Configuration::get('MONMODULE_DISPLAY_TITLE', null, null, $shopId);
Configuration::updateValue('MONMODULE_DISPLAY_TITLE', $value, false, null, $shopId);
```

---

## Définir les valeurs par défaut

```php
// Dans le module
private const DEFAULT_CONFIG = [
    'MONMODULE_ACTIVE' => true,
    'MONMODULE_DEBUG' => false,
    'MONMODULE_DISPLAY_LIMIT' => 10,
    'MONMODULE_DISPLAY_TEMPLATE' => 'grid',
    'MONMODULE_API_ENABLED' => false,
    'MONMODULE_API_RATE_LIMIT' => 60,
    'MONMODULE_CACHE_ENABLED' => true,
    'MONMODULE_CACHE_TTL' => 3600,
];
```

---

## Centraliser les clés

```php
// src/Domain/ConfigurationKeys.php

namespace MonModule\Domain;

final class ConfigurationKeys
{
    // Générales
    public const ACTIVE = 'MONMODULE_ACTIVE';
    public const DEBUG = 'MONMODULE_DEBUG';
    
    // Affichage
    public const DISPLAY_TITLE = 'MONMODULE_DISPLAY_TITLE';
    public const DISPLAY_LIMIT = 'MONMODULE_DISPLAY_LIMIT';
    public const DISPLAY_TEMPLATE = 'MONMODULE_DISPLAY_TEMPLATE';
    
    // API
    public const API_ENABLED = 'MONMODULE_API_ENABLED';
    public const API_KEY = 'MONMODULE_API_KEY';
    public const API_RATE_LIMIT = 'MONMODULE_API_RATE_LIMIT';
    
    // Cache
    public const CACHE_ENABLED = 'MONMODULE_CACHE_ENABLED';
    public const CACHE_TTL = 'MONMODULE_CACHE_TTL';
    
    /**
     * Toutes les clés (pour cleanup).
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::DEBUG,
            self::DISPLAY_TITLE,
            self::DISPLAY_LIMIT,
            self::DISPLAY_TEMPLATE,
            self::API_ENABLED,
            self::API_KEY,
            self::API_RATE_LIMIT,
            self::CACHE_ENABLED,
            self::CACHE_TTL,
        ];
    }
}
```

### Utilisation

```php
use MonModule\Domain\ConfigurationKeys;

// Lecture
$active = $this->config->getBool(ConfigurationKeys::ACTIVE);

// Cleanup à la désinstallation
foreach (ConfigurationKeys::all() as $key) {
    Configuration::deleteByName($key);
}
```

---

## Debug de la configuration

### Voir toutes les clés du module

```sql
SELECT * FROM ps_configuration 
WHERE name LIKE 'MONMODULE_%';
```

### Valeurs multilingues

```sql
SELECT c.name, cl.id_lang, cl.value 
FROM ps_configuration c
JOIN ps_configuration_lang cl ON c.id_configuration = cl.id_configuration
WHERE c.name LIKE 'MONMODULE_%';
```

### Valeurs par boutique

```sql
SELECT c.name, cs.id_shop, COALESCE(cs.value, c.value) as value
FROM ps_configuration c
LEFT JOIN ps_configuration_shop cs ON c.id_configuration = cs.id_configuration
WHERE c.name LIKE 'MONMODULE_%';
```

---

**Prochaine étape** : [Référence des Hooks](./hooks-reference.md)


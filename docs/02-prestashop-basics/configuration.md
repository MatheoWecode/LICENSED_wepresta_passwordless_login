# Configuration

> Référence technique détaillée : [.cursor/rules/006-module-database.mdc](../../.cursor/rules/006-module-database.mdc)

Comment stocker et récupérer la configuration de votre module.

## Configuration vs Base de données

| Besoin | Solution | Table |
|--------|----------|-------|
| Paramètres simples (on/off, texte) | `Configuration` | `ps_configuration` |
| Données structurées (listes, entités) | Table personnalisée | `ps_monmodule_*` |
| Données multilingues simples | `Configuration` avec lang | `ps_configuration_lang` |
| Données complexes multilingues | Table personnalisée | `ps_monmodule_*_lang` |

---

## Utiliser Configuration

### Lire une valeur

```php
// Valeur simple
$active = Configuration::get('MONMODULE_ACTIVE');

// Valeur multilingue
$title = Configuration::get('MONMODULE_TITLE', $idLang);

// Valeur d'une boutique spécifique
$value = Configuration::get('MONMODULE_VALUE', null, $idShopGroup, $idShop);
```

### Écrire une valeur

```php
// Valeur simple
Configuration::updateValue('MONMODULE_ACTIVE', true);

// Valeur multilingue (tableau indexé par id_lang)
Configuration::updateValue('MONMODULE_TITLE', [
    1 => 'Hello',    // Anglais
    2 => 'Bonjour',  // Français
]);

// Valeur HTML (nécessite le flag $html = true)
Configuration::updateValue('MONMODULE_CONTENT', '<p>HTML</p>', true);
```

### Supprimer une valeur

```php
Configuration::deleteByName('MONMODULE_ACTIVE');
```

---

## Module Starter PRO : ConfigurationAdapter

Ce module utilise un **adapter** pour un accès typé et testable :

```php
// Au lieu de
$active = (bool) Configuration::get('MONMODULE_ACTIVE');

// Utilisez
$active = $this->config->getBool('MONMODULE_ACTIVE');
```

### Méthodes disponibles

| Méthode | Retour | Usage |
|---------|--------|-------|
| `get($key)` | mixed | Valeur brute |
| `getString($key)` | string | Chaîne ('' si null) |
| `getInt($key)` | int | Entier |
| `getBool($key)` | bool | Booléen |
| `getFloat($key)` | float | Décimal |
| `getJson($key)` | ?array | Tableau JSON |
| `set($key, $value)` | bool | Définir |
| `setJson($key, $array)` | bool | Stocker en JSON |
| `delete($key)` | bool | Supprimer |

---

## Initialiser la configuration

### À l'installation

```php
private const DEFAULT_CONFIG = [
    'MONMODULE_ACTIVE' => true,
    'MONMODULE_TITLE' => 'Mon Module',
    'MONMODULE_LIMIT' => 10,
];

public function installConfiguration(): bool
{
    foreach (self::DEFAULT_CONFIG as $key => $value) {
        if (!Configuration::updateValue($key, $value)) {
            return false;
        }
    }
    return true;
}
```

### À la désinstallation

```php
public function uninstallConfiguration(): bool
{
    foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
        Configuration::deleteByName($key);
    }
    return true;
}
```

---

## Bonnes pratiques

### Conventions de nommage

```php
// Format: NOMDUMODULE_NOM_PARAMETRE
'MONMODULE_ACTIVE'           // ✅
'MONMODULE_API_KEY'          // ✅
'MONMODULE_CACHE_TTL'        // ✅

'MY_MODULE_ACTIVE'           // ❌ Tiret bas dans le nom
'monmodule_active'           // ❌ Minuscules
'ACTIVE'                     // ❌ Risque de collision
```

### Centraliser les clés

```php
final class ConfigurationKeys
{
    public const ACTIVE = 'MONMODULE_ACTIVE';
    public const TITLE = 'MONMODULE_TITLE';
    public const API_KEY = 'MONMODULE_API_KEY';
    public const CACHE_TTL = 'MONMODULE_CACHE_TTL';
    
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::TITLE,
            self::API_KEY,
            self::CACHE_TTL,
        ];
    }
}
```

### Valeurs par défaut

```php
public function getLimit(): int
{
    $limit = $this->config->getInt('MONMODULE_LIMIT');
    return $limit > 0 ? $limit : 10; // Défaut si non défini
}
```

---

## Multi-boutique

En contexte multi-boutique, la configuration peut être :

| Portée | Description |
|--------|-------------|
| Globale | Même valeur pour toutes les boutiques |
| Par groupe | Valeur par groupe de boutiques |
| Par boutique | Valeur spécifique à chaque boutique |

### Récupérer selon le contexte

```php
// Valeur de la boutique courante (automatique)
$value = Configuration::get('MONMODULE_KEY');

// Valeur globale
$value = Configuration::getGlobalValue('MONMODULE_KEY');

// Valeur d'une boutique spécifique
$value = Configuration::get('MONMODULE_KEY', null, null, $shopId);
```

### Enregistrer selon le contexte

```php
// Boutique courante
Configuration::updateValue('MONMODULE_KEY', $value);

// Valeur globale
Configuration::updateGlobalValue('MONMODULE_KEY', $value);
```

---

## Données sensibles

Pour les clés API et mots de passe :

### Ne jamais afficher en clair

```php
// Dans le formulaire de configuration
$apiKey = Configuration::get('MONMODULE_API_KEY');
$maskedKey = $apiKey ? '••••' . substr($apiKey, -4) : '';
```

### Envisager le chiffrement

```php
// Stocker chiffré (exemple simple)
$encrypted = base64_encode($apiKey);
Configuration::updateValue('MONMODULE_API_KEY', $encrypted);

// Lire et déchiffrer
$apiKey = base64_decode(Configuration::get('MONMODULE_API_KEY'));
```

> ⚠️ Pour une vraie sécurité, utilisez le composant Secrets de Symfony ou une variable d'environnement.

---

<details>
<summary>💡 Déboguer la configuration</summary>

```sql
-- Voir la configuration d'un module
SELECT * FROM ps_configuration 
WHERE name LIKE 'MONMODULE_%';

-- Voir les valeurs multilingues
SELECT c.name, cl.id_lang, cl.value 
FROM ps_configuration c
JOIN ps_configuration_lang cl ON c.id_configuration = cl.id_configuration
WHERE c.name LIKE 'MONMODULE_%';
```

</details>

---

**Prochaine étape** : [Front vs Admin](./front-vs-admin.md)


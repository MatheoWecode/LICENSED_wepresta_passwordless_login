# Architecture PrestaShop

Comprendre l'architecture de PrestaShop pour mieux développer vos modules.

## Vue d'ensemble

PrestaShop est un CMS e-commerce basé sur un pattern **MVC modifié** avec des spécificités :

```
prestashop/
├── admin-xxx/          # Back-office (admin)
├── classes/            # Modèles (ObjectModel)
├── controllers/        # Contrôleurs front et admin
├── modules/            # Vos modules
├── override/           # Surcharges (à éviter)
├── src/                # Code Symfony (PS 1.7+)
├── themes/             # Thèmes front-office
├── translations/       # Traductions
└── var/                # Cache, logs
```

## Les couches de PrestaShop

### 1. Couche Présentation (Vue)

| Type | Emplacement | Moteur |
|------|-------------|--------|
| Front-office | `themes/` | Smarty |
| Back-office | `src/PrestaShopBundle/Resources/views/` | Twig |
| Modules | `modules/xxx/views/templates/` | Smarty + Twig |

### 2. Couche Métier (Modèle)

PrestaShop utilise deux systèmes :

**Legacy : ObjectModel**
```
classes/
├── Product.php
├── Customer.php
├── Order.php
└── ...
```

**Moderne : Doctrine (PS 1.7+)**
```
src/PrestaShopBundle/Entity/
```

### 3. Couche Contrôleur

**Front-office** : Contrôleurs dans `controllers/front/`
**Back-office** : Contrôleurs Symfony dans `src/PrestaShopBundle/Controller/`

---

## Le dossier `src/` (Symfony)

Depuis PrestaShop 1.7, le core utilise Symfony :

```
src/
├── Adapter/            # Adapters vers le code legacy
├── Core/               # Logique métier moderne
│   ├── Domain/         # Commands/Queries (CQRS)
│   ├── Grid/           # Framework de grilles
│   └── Form/           # Form Types
└── PrestaShopBundle/   # Bundle Symfony
    ├── Controller/     # Contrôleurs admin
    ├── Entity/         # Entités Doctrine
    └── Resources/      # Vues Twig, config
```

---

## ObjectModel : le modèle legacy

`ObjectModel` est la classe de base pour les entités PrestaShop.

### Caractéristiques

- Mapping objet-relationnel simple
- Validation intégrée
- Support multilingue
- Support multi-boutique

### Exemple simplifié

```php
class Product extends ObjectModel
{
    public $id_product;
    public $name;         // Multilingue
    public $price;
    public $active;
    
    // Définition de la table
    public static $definition = [
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'lang' => true],
            'price' => ['type' => self::TYPE_FLOAT],
            'active' => ['type' => self::TYPE_BOOL],
        ],
    ];
}
```

> 💡 **Dans vos modules**, préférez Doctrine ou des repositories personnalisés plutôt qu'ObjectModel.

---

## Autoloading

PrestaShop utilise plusieurs autoloaders :

### 1. Autoloader Composer (recommandé)

```json
// composer.json du module
{
    "autoload": {
        "psr-4": {
            "MonModule\\": "src/"
        }
    }
}
```

### 2. Autoloader legacy PrestaShop

Classes dans `classes/` et `controllers/` sont auto-chargées.

### 3. Autoloader Symfony

Pour les classes dans `src/PrestaShopBundle/`.

---

## Le Context

Le `Context` est un singleton contenant l'état de la requête :

| Propriété | Description |
|-----------|-------------|
| `$context->shop` | Boutique courante |
| `$context->language` | Langue courante |
| `$context->currency` | Devise courante |
| `$context->customer` | Client connecté |
| `$context->cart` | Panier en cours |
| `$context->employee` | Employé admin |
| `$context->controller` | Contrôleur courant |
| `$context->smarty` | Instance Smarty |
| `$context->link` | Générateur de liens |

### Accès

```php
// Legacy
$context = Context::getContext();
$langId = $context->language->id;

// Module Starter PRO (via adapter)
$langId = $this->contextAdapter->getLanguageId();
```

---

## Configuration

PrestaShop stocke la configuration dans la table `ps_configuration` :

```php
// Lire
$value = Configuration::get('PS_SHOP_NAME');

// Écrire
Configuration::updateValue('MA_CLE', 'valeur');

// Supprimer
Configuration::deleteByName('MA_CLE');
```

> Voir [Configuration](./configuration.md) pour plus de détails.

---

## Différences PS8 vs PS9

| Aspect | PrestaShop 8 | PrestaShop 9 |
|--------|--------------|--------------|
| PHP | 7.4 - 8.1 | 8.1 - 8.3 |
| Symfony | 4.4 | 6.4 |
| Bootstrap | 4 | 5 |
| jQuery | Inclus | Optionnel |
| Smarty | Par défaut | Déprécié (Twig) |

---

<details>
<summary>💡 En savoir plus sur le pattern MVC de PrestaShop</summary>

PrestaShop n'est pas un MVC pur. Il utilise :
- **Modèle** : ObjectModel + Doctrine
- **Vue** : Smarty + Twig (hybride)
- **Contrôleur** : Legacy + Symfony (hybride)

Le code est en transition progressive vers Symfony depuis PS 1.7. Les modules modernes doivent privilégier les composants Symfony.

</details>

---

**Prochaine étape** : [Cycle de vie d'un module](./module-lifecycle.md)


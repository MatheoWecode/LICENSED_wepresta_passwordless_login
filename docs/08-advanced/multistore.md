# Multi-boutique (Multistore)

Support du mode multi-boutique de PrestaShop.

## Qu'est-ce que le multistore ?

PrestaShop permet de gérer plusieurs boutiques depuis une seule installation :

```
┌─────────────────────────────────────────────────────────────┐
│                    Installation PrestaShop                  │
├─────────────────────────────────────────────────────────────┤
│  Groupe 1: France                                           │
│  ├── Boutique FR (shop_id=1)                               │
│  └── Boutique BE (shop_id=2)                               │
│                                                             │
│  Groupe 2: International                                    │
│  └── Boutique EN (shop_id=3)                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Activer le support multistore

### Dans le module

```php
public function __construct()
{
    // ...
    $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];
    
    // Déclarer le support multistore
    if (Shop::isFeatureActive()) {
        Shop::addTableAssociation('monmodule_item', ['type' => 'shop']);
    }
}
```

### Vérifier si multistore actif

```php
if (Shop::isFeatureActive()) {
    // Mode multistore activé
    $shopId = Context::getContext()->shop->id;
}
```

---

## Tables multistore

### Structure

```sql
-- Table principale
CREATE TABLE `PREFIX_monmodule_item` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_monmodule_item`)
);

-- Table d'association shop
CREATE TABLE `PREFIX_monmodule_item_shop` (
    `id_monmodule_item` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_monmodule_item`, `id_shop`)
);
```

### Requêtes

```php
// Récupérer les items de la boutique courante
$shopId = (int) Context::getContext()->shop->id;

$sql = new DbQuery();
$sql->select('i.*, is.active')
    ->from('monmodule_item', 'i')
    ->innerJoin('monmodule_item_shop', 'is', 'i.id_monmodule_item = is.id_monmodule_item')
    ->where('is.id_shop = ' . $shopId)
    ->where('is.active = 1');

$items = Db::getInstance()->executeS($sql);
```

---

## Configuration par boutique

### Lire la configuration

```php
// Boutique courante (automatique)
$value = Configuration::get('MONMODULE_SETTING');

// Boutique spécifique
$value = Configuration::get('MONMODULE_SETTING', null, null, $shopId);

// Valeur globale (toutes boutiques)
$value = Configuration::getGlobalValue('MONMODULE_SETTING');
```

### Écrire la configuration

```php
// Boutique courante
Configuration::updateValue('MONMODULE_SETTING', $value);

// Boutique spécifique
Configuration::updateValue('MONMODULE_SETTING', $value, false, null, $shopId);

// Valeur globale
Configuration::updateGlobalValue('MONMODULE_SETTING', $value);
```

---

## Contexte de boutique

### Changer le contexte

```php
// Sauvegarder le contexte actuel
$originalContext = Shop::getContext();
$originalShopId = Shop::getContextShopID();

// Changer pour une boutique spécifique
Shop::setContext(Shop::CONTEXT_SHOP, $targetShopId);

// Faire les opérations...
$items = $this->repository->findActive();

// Restaurer le contexte
Shop::setContext($originalContext, $originalShopId);
```

### Contextes disponibles

| Contexte | Description |
|----------|-------------|
| `Shop::CONTEXT_ALL` | Toutes les boutiques |
| `Shop::CONTEXT_GROUP` | Un groupe de boutiques |
| `Shop::CONTEXT_SHOP` | Une boutique spécifique |

---

## Installation multistore

### À l'installation

```php
public function install(): bool
{
    // Installer pour toutes les boutiques
    if (Shop::isFeatureActive()) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }
    
    return parent::install()
        && $this->registerHook(self::HOOKS)
        && $this->installDatabase()
        && $this->installShopData();
}

private function installShopData(): bool
{
    $shops = Shop::getShops(true, null, true);
    
    foreach ($shops as $shopId) {
        Configuration::updateValue('MONMODULE_ACTIVE', true, false, null, $shopId);
    }
    
    return true;
}
```

---

## Formulaire de configuration multistore

### Afficher les options multistore

```php
public function renderForm(): string
{
    $helper = new HelperForm();
    
    // Afficher le sélecteur de boutique
    $helper->show_toolbar = true;
    $helper->table = $this->table;
    $helper->module = $this;
    $helper->identifier = $this->identifier;
    $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
    
    // Permettre la sélection multistore
    $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
    
    return $helper->generateForm($this->getConfigForm());
}
```

### Sauvegarder par boutique

```php
protected function postProcess(): void
{
    $shops = Shop::getContextListShopID();
    
    foreach ($shops as $shopId) {
        Configuration::updateValue(
            'MONMODULE_SETTING',
            Tools::getValue('MONMODULE_SETTING'),
            false,
            null,
            $shopId
        );
    }
}
```

---

## Bonnes pratiques

### Toujours vérifier le multistore

```php
public function getActiveItems(): array
{
    $shopId = null;
    
    if (Shop::isFeatureActive()) {
        $shopId = (int) Context::getContext()->shop->id;
    }
    
    return $this->repository->findActiveByShop($shopId);
}
```

### Configuration globale vs locale

| Type | Exemple | Portée |
|------|---------|--------|
| Globale | Clé API | Toutes boutiques |
| Par boutique | Titre affiché | Chaque boutique |
| Par groupe | Logo | Groupe de boutiques |

### Repository multistore

```php
class ItemRepository extends AbstractRepository
{
    public function findActiveByShop(?int $shopId = null): array
    {
        $query = new DbQuery();
        $query->select('i.*')
              ->from($this->getTableName(), 'i');
        
        if ($shopId !== null && Shop::isFeatureActive()) {
            $query->innerJoin(
                $this->getTableName() . '_shop',
                'is',
                'i.id_monmodule_item = is.id_monmodule_item'
            )
            ->where('is.id_shop = ' . $shopId)
            ->where('is.active = 1');
        } else {
            $query->where('i.active = 1');
        }
        
        return $this->db->executeS($query) ?: [];
    }
}
```

---

## Debug multistore

```php
// Afficher le contexte actuel
echo 'Context: ' . Shop::getContext();
echo 'Shop ID: ' . Shop::getContextShopID();
echo 'Group ID: ' . Shop::getContextShopGroupID();

// Lister toutes les boutiques
$shops = Shop::getShops();
foreach ($shops as $shop) {
    echo $shop['id_shop'] . ': ' . $shop['name'];
}
```

---

**Prochaine étape** : [Performance](./performance.md)


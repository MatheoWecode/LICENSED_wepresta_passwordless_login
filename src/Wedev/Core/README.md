# Core WEDEV

⚠️ **NE PAS MODIFIER CE DOSSIER MANUELLEMENT**

Ce dossier contient le code partagé géré par WEDEV CLI.
Il est mis à jour automatiquement via `wedev ps module update-core`.

## Structure

```
Core/
├── Adapter/              # Adapters PrestaShop
│   ├── ConfigurationAdapter.php    # Configuration typée
│   ├── ContextAdapter.php          # Contexte (shop, lang, customer)
│   └── ShopAdapter.php             # Multi-shop
│
├── Contract/             # Interfaces stables
│   ├── ConfigurableInterface.php   # Pour classes avec config
│   ├── ExtensionInterface.php      # Pour les extensions WEDEV
│   ├── InstallableInterface.php    # Pour composants installables
│   ├── RepositoryInterface.php     # CRUD de base
│   └── ServiceInterface.php        # Marqueur services injectables
│
├── Exception/            # Exceptions typées
│   ├── ModuleException.php         # Exception de base
│   ├── EntityNotFoundException.php # Entité non trouvée
│   ├── ValidationException.php     # Erreur de validation
│   ├── ConfigurationException.php  # Erreur de configuration
│   └── DependencyException.php     # Extension manquante
│
├── Extension/            # Chargeur d'extensions
│   └── ExtensionLoader.php         # Détecte les extensions disponibles
│
├── Repository/           # Classes de base Repository
│   └── AbstractRepository.php      # CRUD avec PrestaShop Db
│
├── Service/              # Services utilitaires
│   └── CacheService.php            # Cache unifié
│
└── Trait/                # Traits réutilisables
    ├── LoggerTrait.php             # Logging PrestaShop
    ├── ModuleAwareTrait.php        # Accès au module
    ├── MultiShopTrait.php          # Helpers multi-shop
    └── TranslatorTrait.php         # Traductions
```

## Utilisation

### Adapters

```php
use Wepresta_Passwordless_Login\Core\Adapter\ConfigurationAdapter;
use Wepresta_Passwordless_Login\Core\Adapter\ContextAdapter;
use Wepresta_Passwordless_Login\Core\Adapter\ShopAdapter;

// Configuration typée
$config = new ConfigurationAdapter();
$value = $config->get('MY_CONFIG', 'default');
$config->set('MY_CONFIG', 'new_value');

// Contexte
$context = new ContextAdapter();
$shopId = $context->getShopId();
$langId = $context->getLanguageId();
$customerId = $context->getCustomerId();

// Multi-shop
$shop = new ShopAdapter();
if ($shop->isMultiShopActive()) {
    $shop->forEachShop(function(array $shopData) {
        // Exécuté pour chaque boutique
    });
}
```

### Traits

```php
use Wepresta_Passwordless_Login\Core\Trait\LoggerTrait;
use Wepresta_Passwordless_Login\Core\Trait\MultiShopTrait;

class MyService
{
    use LoggerTrait;
    use MultiShopTrait;

    public function doSomething(): void
    {
        $this->log('info', 'Processing...');
        
        if ($this->isMultiShopActive()) {
            $this->forEachShop(fn($shop) => $this->process($shop));
        }
    }
}
```

### Extension Loader

```php
use Wepresta_Passwordless_Login\Core\Extension\ExtensionLoader;

// Vérifier si une extension est disponible
if (ExtensionLoader::isAvailable('Http')) {
    $client = new HttpClient();
}

// Exiger une extension (lance exception si absente)
ExtensionLoader::require('Http');

// Lister les extensions disponibles
$extensions = ExtensionLoader::getAvailableExtensions();
// ['UI', 'Http']
```

### Exceptions

```php
use Wepresta_Passwordless_Login\Core\Exception\EntityNotFoundException;
use Wepresta_Passwordless_Login\Core\Exception\ConfigurationException;
use Wepresta_Passwordless_Login\Core\Exception\DependencyException;

// Entité non trouvée
throw EntityNotFoundException::forId(123, 'Product');

// Configuration invalide
throw ConfigurationException::missingKey('API_KEY');

// Extension requise
throw DependencyException::extensionNotFound('Http');
```

## Personnalisation

Pour étendre ces classes, créez vos propres classes dans les dossiers 
`Application/`, `Domain/`, `Infrastructure/` ou `Presentation/`.

```php
// Exemple: Étendre ConfigurationAdapter
namespace MonModule\Infrastructure\Adapter;

use MonModule\Core\Adapter\ConfigurationAdapter;

class MyConfigAdapter extends ConfigurationAdapter
{
    private const PREFIX = 'MYMODULE_';

    public function getApiKey(): string
    {
        return $this->get(self::PREFIX . 'API_KEY', '');
    }
}
```

## Version

Ce Core est à la version définie dans `.wedev-core-version`.

Mettre à jour : `wedev ps module update-core`

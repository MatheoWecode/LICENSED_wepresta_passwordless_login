# Services et Injection de Dépendances

> Référence technique détaillée : [.cursor/rules/003-module-services.mdc](../../.cursor/rules/003-module-services.mdc)

Symfony gère les services et leurs dépendances via l'**injection de dépendances** (DI).

## Qu'est-ce que l'injection de dépendances ?

Au lieu de créer les dépendances manuellement :

```php
// ❌ Sans DI
class ItemService
{
    public function __construct()
    {
        $this->repository = new ItemRepository();  // Couplage fort
        $this->config = new ConfigurationAdapter();
    }
}
```

Les dépendances sont **injectées** :

```php
// ✅ Avec DI
class ItemService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,  // Interface
        private readonly ConfigurationAdapter $config
    ) {}
}
```

---

## Configuration des services

Les services sont déclarés dans `config/services.yml` :

```yaml
services:
  _defaults:
    autowire: true        # Injection automatique
    autoconfigure: true   # Tags automatiques
    public: false         # Services privés par défaut

  # Déclaration explicite des services
  MonModule\Application\Service\ItemService:
    public: true
    arguments:
      $repository: '@MonModule\Infrastructure\Repository\ItemRepository'
      $config: '@MonModule\Core\Adapter\ConfigurationAdapter'
```

---

## Autowiring

Avec **autowiring**, Symfony injecte automatiquement les dépendances basées sur les types :

```yaml
services:
  _defaults:
    autowire: true

  MonModule\:
    resource: '../src/*'
```

```php
// Les dépendances sont injectées automatiquement
class ItemService
{
    public function __construct(
        private readonly ItemRepository $repository,
        // Symfony trouve ItemRepository automatiquement
    ) {}
}
```

---

## Accéder aux services

### Dans un contrôleur Symfony

```php
class ItemController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly ItemService $itemService
    ) {}
    
    public function indexAction(): Response
    {
        $items = $this->itemService->getActiveItems();
        // ...
    }
}
```

### Dans le module principal

```php
// Via la méthode get() du module
public function hookDisplayHome(array $params): string
{
    $service = $this->get(ItemService::class);
    $items = $service->getActiveItems();
    // ...
}
```

### Via le container Symfony

```php
$container = $this->get('service_container');
$service = $container->get(ItemService::class);
```

---

## Services PrestaShop courants

PrestaShop expose de nombreux services réutilisables :

| Service | ID | Usage |
|---------|-----|-------|
| EntityManager | `doctrine.orm.entity_manager` | Doctrine ORM |
| Translator | `translator` | Traductions |
| Router | `router` | Génération d'URLs |
| Request Stack | `request_stack` | Requête courante |
| Logger | `logger` | Logs |

### Injection

```php
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator
    ) {}
}
```

---

## Créer un service

### 1. Créer la classe

```php
// src/Application/Service/PricingService.php

namespace MonModule\Application\Service;

use MonModule\Core\Adapter\ConfigurationAdapter;

class PricingService
{
    public function __construct(
        private readonly ConfigurationAdapter $config
    ) {}
    
    public function calculateWithTax(float $price): float
    {
        $taxRate = $this->config->getFloat('MONMODULE_TAX_RATE') ?: 20.0;
        return $price * (1 + $taxRate / 100);
    }
}
```

### 2. Déclarer le service

```yaml
# config/services.yml
services:
  MonModule\Application\Service\PricingService:
    public: true
```

### 3. Utiliser

```php
$pricingService = $this->get(PricingService::class);
$priceWithTax = $pricingService->calculateWithTax(100);
```

---

## Services avec interfaces

Pour respecter le principe d'inversion de dépendances :

### 1. Définir l'interface

```php
// src/Domain/Repository/ItemRepositoryInterface.php

interface ItemRepositoryInterface
{
    public function findById(int $id): ?Item;
    public function findActive(): array;
    public function save(Item $item): int;
}
```

### 2. Implémenter

```php
// src/Infrastructure/Repository/ItemRepository.php

class ItemRepository implements ItemRepositoryInterface
{
    // Implémentation...
}
```

### 3. Lier interface et implémentation

```yaml
services:
  MonModule\Domain\Repository\ItemRepositoryInterface:
    alias: MonModule\Infrastructure\Repository\ItemRepository
```

### 4. Injecter l'interface

```php
class ItemService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
        // Symfony injecte ItemRepository automatiquement
    ) {}
}
```

---

## Tags et autoconfigure

Les **tags** permettent de regrouper des services :

### Event Subscribers

```yaml
services:
  MonModule\Infrastructure\EventSubscriber\ProductSubscriber:
    tags:
      - { name: kernel.event_subscriber }
```

Avec `autoconfigure: true`, le tag est ajouté automatiquement si la classe implémente `EventSubscriberInterface`.

---

## Scopes et lifecycle

### Par défaut : Shared (singleton)

```yaml
services:
  MonModule\Application\Service\ItemService:
    # Une seule instance pendant toute la requête
```

### Non partagé

```yaml
services:
  MonModule\Application\Service\StatefulService:
    shared: false
    # Nouvelle instance à chaque injection
```

---

## Bonnes pratiques

### Injection par constructeur

```php
// ✅ Préféré
public function __construct(
    private readonly ItemService $service
) {}
```

### Éviter l'injection par setter

```php
// ❌ À éviter
public function setService(ItemService $service): void
{
    $this->service = $service;
}
```

### Services stateless

```php
// ✅ Sans état interne
class PricingService
{
    public function calculate(float $price): float
    {
        return $price * 1.2;
    }
}

// ❌ Avec état mutable
class PricingService
{
    private float $lastPrice;  // État partagé entre appels
}
```

---

<details>
<summary>💡 Déboguer les services</summary>

```bash
# Lister tous les services
ddev exec bin/console debug:container --show-private

# Chercher un service
ddev exec bin/console debug:container ItemService

# Vérifier le wiring
ddev exec bin/console debug:autowiring ItemService
```

</details>

---

**Prochaine étape** : [Grid Framework](./grid-framework.md)


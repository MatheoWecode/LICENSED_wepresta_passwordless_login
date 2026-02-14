# Clean Architecture

> Référence technique détaillée : [.cursor/rules/001-module-architecture.mdc](../../.cursor/rules/001-module-architecture.mdc)

Ce module suit les principes de la **Clean Architecture** pour un code maintenable et testable.

## Qu'est-ce que la Clean Architecture ?

La Clean Architecture sépare le code en **couches concentriques** avec une règle simple : les dépendances pointent toujours vers l'intérieur.

```
┌─────────────────────────────────────────────────────────────────┐
│                        Presentation                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                     Infrastructure                       │   │
│  │  ┌─────────────────────────────────────────────────┐    │   │
│  │  │                  Application                     │    │   │
│  │  │  ┌─────────────────────────────────────────┐    │    │   │
│  │  │  │                Domain                    │    │    │   │
│  │  │  │                                          │    │    │   │
│  │  │  │  Entities, Value Objects, Interfaces    │    │    │   │
│  │  │  │                                          │    │    │   │
│  │  │  └─────────────────────────────────────────┘    │    │   │
│  │  │                                                  │    │   │
│  │  │  Services, Use Cases, Commands, Queries        │    │   │
│  │  └─────────────────────────────────────────────────┘    │   │
│  │                                                          │   │
│  │  Repositories, Adapters, API Clients, Event Subscribers │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Controllers, Grids, Templates, Forms                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## Les 4 couches

### 1. Domain (centre)

**Le cœur métier** — indépendant de tout framework.

```
src/Domain/
├── Entity/              # Entités métier
├── Repository/          # Interfaces de persistance
├── ValueObject/         # Objets valeur immutables
├── Event/               # Événements du domaine
└── Exception/           # Exceptions métier
```

**Caractéristiques :**
- Aucune dépendance externe
- Code PHP pur
- Représente les règles métier

### 2. Application

**Les cas d'utilisation** — orchestration du domaine.

```
src/Application/
├── Command/             # Opérations d'écriture
├── Query/               # Opérations de lecture
├── Service/             # Services applicatifs
├── Form/                # Form Types Symfony
└── Installer/           # Logique d'installation
```

**Caractéristiques :**
- Utilise le Domain
- Indépendant de l'infrastructure
- Contient la logique applicative

### 3. Infrastructure

**Les implémentations concrètes** — dépendances externes.

```
src/Infrastructure/
├── Adapter/             # Adapters (Configuration, Context)
├── Repository/          # Implémentations des repositories
├── EventSubscriber/     # Subscribers Symfony
└── Api/                 # Clients API externes
```

**Caractéristiques :**
- Implémente les interfaces du Domain
- Dépend de frameworks/bibliothèques
- Facilement remplaçable

### 4. Presentation

**L'interface utilisateur** — contrôleurs et vues.

```
src/Presentation/
├── Controller/          # Contrôleurs admin
└── Grid/                # Grilles PrestaShop
```

**Caractéristiques :**
- Gère les requêtes HTTP
- Délègue à l'Application
- Retourne des réponses

---

## La règle des dépendances

Les dépendances ne peuvent pointer que **vers l'intérieur** :

| Couche | Peut dépendre de |
|--------|------------------|
| **Domain** | Rien (le centre) |
| **Application** | Domain |
| **Infrastructure** | Domain, Application |
| **Presentation** | Domain, Application, Infrastructure |

### Exemple concret

```php
// Domain définit l'interface
namespace MonModule\Domain\Repository;

interface ItemRepositoryInterface
{
    public function findById(int $id): ?Item;
}

// Infrastructure implémente
namespace MonModule\Infrastructure\Repository;

use MonModule\Domain\Repository\ItemRepositoryInterface;

class ItemRepository implements ItemRepositoryInterface
{
    public function findById(int $id): ?Item
    {
        // Utilise Doctrine ou Db::getInstance()
    }
}

// Application utilise l'interface (pas l'implémentation)
namespace MonModule\Application\Service;

use MonModule\Domain\Repository\ItemRepositoryInterface;

class ItemService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
    ) {}
}
```

---

## Avantages

### Testabilité

Chaque couche peut être testée isolément :

```php
// Test avec un mock du repository
$mockRepo = $this->createMock(ItemRepositoryInterface::class);
$mockRepo->method('findById')->willReturn(new Item(...));

$service = new ItemService($mockRepo);
$result = $service->getItem(1);
```

### Maintenabilité

- Changement de base de données ? Modifiez uniquement l'Infrastructure
- Nouveau framework ? Seule la Presentation change
- Évolution métier ? Modifiez le Domain

### Indépendance

Le code métier (Domain) ne dépend pas de :
- PrestaShop
- Symfony
- MySQL
- Aucune bibliothèque externe

---

## Quand utiliser quelle couche ?

| Besoin | Couche | Exemple |
|--------|--------|---------|
| Définir une entité | Domain | `Item`, `Order` |
| Définir une interface | Domain | `ItemRepositoryInterface` |
| Implémenter un use case | Application | `CreateItemCommand` |
| Créer un formulaire | Application | `ItemFormType` |
| Accéder à la BDD | Infrastructure | `ItemRepository` |
| Appeler une API externe | Infrastructure | `PaymentGateway` |
| Gérer une requête HTTP | Presentation | `ItemController` |

---

## Anti-patterns

### ❌ Domain qui dépend de l'infrastructure

```php
// MAUVAIS : Entity qui utilise Db directement
class Item
{
    public function save(): void
    {
        Db::getInstance()->insert(...);  // ❌
    }
}
```

### ❌ Logique métier dans le contrôleur

```php
// MAUVAIS : Calculs dans le contrôleur
public function listAction(): Response
{
    $items = $this->repository->findAll();
    $total = 0;
    foreach ($items as $item) {
        $total += $item->getPrice() * 1.2;  // ❌ TVA calculée ici
    }
}
```

### ✅ Bonne séparation

```php
// BON : Service qui calcule
class PricingService
{
    public function calculateWithTax(Item $item): float
    {
        return $item->getPrice() * 1.2;
    }
}

// Contrôleur qui délègue
public function listAction(): Response
{
    $items = $this->itemService->getItemsWithPricing();
}
```

---

<details>
<summary>💡 Pour aller plus loin</summary>

Ressources recommandées :
- "Clean Architecture" de Robert C. Martin
- "Domain-Driven Design" d'Eric Evans
- [Architecture hexagonale](https://alistair.cockburn.us/hexagonal-architecture/)

</details>

---

**Prochaine étape** : [Pattern CQRS](./cqrs-pattern.md)


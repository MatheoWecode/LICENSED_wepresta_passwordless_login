# Pattern CQRS

> Référence technique détaillée : [.cursor/rules/001-module-architecture.mdc](../../.cursor/rules/001-module-architecture.mdc)

CQRS (Command Query Responsibility Segregation) sépare les opérations de lecture et d'écriture.

## Qu'est-ce que CQRS ?

**CQRS** divise les opérations en deux catégories :

| Type | Description | Retour |
|------|-------------|--------|
| **Command** | Modifie l'état (écriture) | Void ou ID créé |
| **Query** | Lit l'état (lecture) | Données |

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│   Utilisateur                                               │
│       │                                                     │
│       ├── "Créer un item" ──► Command ──► Base de données  │
│       │                                                     │
│       └── "Voir les items" ──► Query ──► Données affichées │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Pourquoi CQRS ?

### Avantages

1. **Clarté** : Chaque classe a une responsabilité unique
2. **Optimisation** : Queries optimisées pour la lecture
3. **Scalabilité** : Read et Write peuvent évoluer séparément
4. **Testabilité** : Chaque opération est testable isolément

### Dans ce module

Nous utilisons une version **simplifiée** de CQRS adaptée à PrestaShop :

```
src/Application/
├── Command/              # Opérations d'écriture
│   ├── CreateItemCommand.php
│   ├── UpdateItemCommand.php
│   └── DeleteItemCommand.php
│
└── Query/                # Opérations de lecture
    ├── GetItemQuery.php
    └── GetItemsListQuery.php
```

---

## Commands (écriture)

Une **Command** représente une intention de modifier l'état.

### Structure d'une Command

```php
// src/Application/Command/CreateItemCommand.php

namespace MonModule\Application\Command;

final class CreateItemCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $active = true
    ) {}
}
```

### Handler de Command

Le **Handler** exécute la Command :

```php
// src/Application/Command/CreateItemCommandHandler.php

namespace MonModule\Application\Command;

use MonModule\Domain\Entity\Item;
use MonModule\Domain\Repository\ItemRepositoryInterface;

final class CreateItemCommandHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
    ) {}
    
    public function handle(CreateItemCommand $command): int
    {
        $item = new Item(
            name: $command->name,
            description: $command->description,
            active: $command->active
        );
        
        return $this->repository->save($item);
    }
}
```

### Utilisation

```php
// Dans un contrôleur ou service
$command = new CreateItemCommand(
    name: 'Mon Item',
    description: 'Description',
    active: true
);

$itemId = $this->createItemHandler->handle($command);
```

---

## Queries (lecture)

Une **Query** représente une demande de données.

### Structure d'une Query

```php
// src/Application/Query/GetItemQuery.php

namespace MonModule\Application\Query;

final class GetItemQuery
{
    public function __construct(
        public readonly int $id
    ) {}
}
```

### Handler de Query

```php
// src/Application/Query/GetItemQueryHandler.php

namespace MonModule\Application\Query;

use MonModule\Domain\Entity\Item;
use MonModule\Domain\Repository\ItemRepositoryInterface;
use MonModule\Domain\Exception\ItemNotFoundException;

final class GetItemQueryHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
    ) {}
    
    public function handle(GetItemQuery $query): Item
    {
        $item = $this->repository->findById($query->id);
        
        if ($item === null) {
            throw ItemNotFoundException::withId($query->id);
        }
        
        return $item;
    }
}
```

### Utilisation

```php
$query = new GetItemQuery(id: 42);
$item = $this->getItemHandler->handle($query);
```

---

## Queries complexes

Pour les listes avec filtres et pagination :

```php
// src/Application/Query/GetItemsListQuery.php

final class GetItemsListQuery
{
    public function __construct(
        public readonly ?bool $active = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly string $orderBy = 'position',
        public readonly string $orderDir = 'ASC'
    ) {}
}
```

```php
// Handler
public function handle(GetItemsListQuery $query): array
{
    return $this->repository->findByFilters(
        active: $query->active,
        offset: ($query->page - 1) * $query->limit,
        limit: $query->limit,
        orderBy: $query->orderBy,
        orderDir: $query->orderDir
    );
}
```

---

## Enregistrer les Handlers

Dans `config/services.yml` :

```yaml
services:
  # Commands
  MonModule\Application\Command\CreateItemCommandHandler:
    arguments:
      $repository: '@MonModule\Infrastructure\Repository\ItemRepository'
  
  # Queries
  MonModule\Application\Query\GetItemQueryHandler:
    arguments:
      $repository: '@MonModule\Infrastructure\Repository\ItemRepository'
```

---

## CQRS dans les contrôleurs

### Contrôleur admin

```php
class ItemController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly CreateItemCommandHandler $createHandler,
        private readonly GetItemsListQueryHandler $listHandler
    ) {}
    
    public function indexAction(): Response
    {
        $query = new GetItemsListQuery(active: true);
        $items = $this->listHandler->handle($query);
        
        return $this->render('@Modules/monmodule/views/templates/admin/list.html.twig', [
            'items' => $items,
        ]);
    }
    
    public function createAction(Request $request): Response
    {
        // Formulaire soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $command = new CreateItemCommand(
                name: $data['name'],
                description: $data['description']
            );
            
            $this->createHandler->handle($command);
            
            return $this->redirectToRoute('monmodule_list');
        }
        
        // ...
    }
}
```

---

## Bonnes pratiques

### Nommage

```
CreateItemCommand      # Intention claire
UpdateItemCommand      # Verbe à l'impératif
DeleteItemCommand

GetItemQuery           # Question claire
GetItemsListQuery      # Pluriel pour les listes
SearchItemsQuery       # Recherche
```

### Commands immutables

```php
// ✅ Properties readonly
final class CreateItemCommand
{
    public function __construct(
        public readonly string $name
    ) {}
}

// ❌ Properties modifiables
class CreateItemCommand
{
    public string $name;  // Peut être modifié après création
}
```

### Un Handler = Une responsabilité

```php
// ✅ Un handler par command
class CreateItemCommandHandler { ... }
class UpdateItemCommandHandler { ... }

// ❌ Handler qui fait tout
class ItemCommandHandler
{
    public function handleCreate(...) { }
    public function handleUpdate(...) { }
    public function handleDelete(...) { }
}
```

---

<details>
<summary>💡 CQRS avancé : Event Sourcing</summary>

Dans une implémentation complète, les Commands génèrent des **Events** stockés :

```
Command: CreateItem
    ↓
Event: ItemCreated
    ↓
Stored in Event Store
    ↓
Projections rebuilt from events
```

Cette approche est plus complexe et rarement nécessaire pour un module PrestaShop.

</details>

---

**Prochaine étape** : [Services et DI](./services-di.md)


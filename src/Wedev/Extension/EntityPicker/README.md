# Extension EntityPicker WEDEV

Composant de recherche et sélection d'entités PrestaShop via AJAX.

## Installation

L'extension est automatiquement copiée lors de la génération d'un module avec WEDEV CLI.

### Configuration Symfony

Ajouter l'import dans `config/services.yml` du module :

```yaml
imports:
    - { resource: '../src/Extension/EntityPicker/config/services_entitypicker.yml' }
```

---

## Providers Disponibles

| Provider | Entité | Recherche par |
|----------|--------|---------------|
| `ProductProvider` | Produits | Nom, référence, ID |
| `CategoryProvider` | Catégories | Nom, ID |
| `CustomerProvider` | Clients | Nom, prénom, email, société, ID |

---

## Utilisation Back-Office

### 1. Créer les Routes

```yaml
# config/routes.yml
my_module_search_products:
    path: /my-module/ajax/search-products
    methods: [GET]
    defaults:
        _controller: 'MyModule\Controller\Admin\MyController::searchProductsAction'

my_module_fetch_products:
    path: /my-module/ajax/fetch-products
    methods: [POST]
    defaults:
        _controller: 'MyModule\Controller\Admin\MyController::fetchProductsAction'
```

### 2. Implémenter le Controller

```php
<?php
declare(strict_types=1);

namespace MyModule\Controller\Admin;

use Wepresta_Passwordless_Login\Extension\EntityPicker\Controller\EntitySearchTrait;
use Wepresta_Passwordless_Login\Extension\EntityPicker\Provider\ProductProvider;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MyController extends FrameworkBundleAdminController
{
    use EntitySearchTrait;

    public function __construct(
        private readonly ProductProvider $productProvider
    ) {}

    public function searchProductsAction(Request $request): JsonResponse
    {
        return $this->handleEntitySearch($request, $this->productProvider);
    }

    public function fetchProductsAction(Request $request): JsonResponse
    {
        return $this->handleEntityFetch($request, $this->productProvider);
    }
}
```

### 3. Utiliser dans un Template Twig

#### Option A : Via la Macro

```twig
{% import '@WedevEntityPicker/admin/_entity_picker.html.twig' as entityPicker %}

{{ entityPicker.render({
    name: 'product_ids',
    label: 'Produits associés',
    search_url: path('my_module_search_products'),
    fetch_url: path('my_module_fetch_products'),
    multiple: true,
    value: existingProductIds,
    placeholder: 'Rechercher un produit...',
    help_text: 'Sélectionnez les produits à associer',
}) }}

{# Inclure le JS une fois #}
{{ entityPicker.assets() }}
```

#### Option B : Directement en HTML

```html
<div class="entity-picker"
     data-search-url="{{ path('my_module_search_products') }}"
     data-fetch-url="{{ path('my_module_fetch_products') }}"
     data-multiple="true"
     data-min-chars="2">
    <input type="text" class="form-control entity-picker-search" placeholder="Rechercher...">
    <div class="entity-picker-results list-group mt-2"></div>
    <ul class="entity-picker-selected list-group mt-3"></ul>
    <input type="hidden" class="entity-picker-ids" name="product_ids" value="{{ existingIds|json_encode }}">
</div>

<script src="{{ asset('modules/mymodule/views/js/entity-picker.js') }}"></script>
```

---

## Créer un Provider Custom

```php
<?php
declare(strict_types=1);

namespace MyModule\Provider;

use Wepresta_Passwordless_Login\Extension\EntityPicker\Provider\AbstractEntityProvider;
use DbQuery;

final class ManufacturerProvider extends AbstractEntityProvider
{
    public function getEntityType(): string
    {
        return 'manufacturer';
    }

    public function getEntityLabel(): string
    {
        return 'Fabricants';
    }

    public function search(string $term, int $limit = 20): array
    {
        if (strlen($term) < 2) {
            return [];
        }

        $query = new DbQuery();
        $query->select('m.`id_manufacturer`, m.`name`')
            ->from('manufacturer', 'm')
            ->where($this->buildSearchWhere($term, ['m.`name`']))
            ->where('m.`active` = 1')
            ->orderBy('m.`name` ASC')
            ->limit($limit);

        $rows = $this->db->executeS($query);

        if (!$rows) {
            return [];
        }

        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->formatResult(
                (int) $row['id_manufacturer'],
                $row['name'],
                $this->getManufacturerImageUrl((int) $row['id_manufacturer'])
            );
        }

        return $results;
    }

    public function getByIds(array $ids): array
    {
        // Similaire à search() mais avec WHERE id IN (...)
    }

    private function getManufacturerImageUrl(int $id): string
    {
        // Retourner l'URL de l'image
        return '';
    }
}
```

---

## API JavaScript

### Méthodes

```javascript
// Initialiser tous les pickers
WedevEntityPicker.init('.entity-picker');

// Récupérer les IDs sélectionnés
const ids = WedevEntityPicker.getSelectedIds(container);

// Récupérer les entités complètes
const entities = WedevEntityPicker.getSelectedEntities(container);

// Effacer la sélection
WedevEntityPicker.clear(container);
```

### Événements

```javascript
// Écouter les changements
document.querySelector('.entity-picker').addEventListener('entitypicker:change', (e) => {
    console.log('IDs sélectionnés:', e.detail.ids);
    console.log('Entités:', e.detail.entities);
});
```

---

## Options du Composant

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `name` | string | 'entities' | Nom du champ hidden |
| `label` | string | 'Entités' | Label du champ |
| `search_url` | string | '' | URL de l'endpoint de recherche |
| `fetch_url` | string | '' | URL pour récupérer les entités par ID |
| `multiple` | bool | true | Sélection multiple |
| `min_chars` | int | 2 | Caractères minimum avant recherche |
| `max_results` | int | 20 | Nombre max de résultats |
| `allow_clear` | bool | true | Permettre de supprimer une sélection |
| `placeholder` | string | 'Rechercher...' | Placeholder du champ |
| `value` | array | [] | IDs pré-sélectionnés |
| `required` | bool | false | Champ obligatoire |
| `disabled` | bool | false | Champ désactivé |

---

## Structure des Fichiers

```
Extension/EntityPicker/
├── README.md                           # Cette documentation
├── config/
│   └── services_entitypicker.yml       # Services Symfony
├── Provider/
│   ├── EntityProviderInterface.php     # Interface provider
│   ├── AbstractEntityProvider.php      # Classe de base
│   ├── ProductProvider.php             # Provider produits
│   ├── CategoryProvider.php            # Provider catégories
│   └── CustomerProvider.php            # Provider clients
├── Form/
│   └── EntityPickerType.php            # FormType Symfony
├── Controller/
│   └── EntitySearchTrait.php           # Trait pour les controllers
├── Assets/
│   └── js/
│       └── entity-picker.js            # Composant JavaScript
└── Templates/
    └── admin/
        └── _entity_picker.html.twig    # Macro Twig
```

---

## Intégration avec AbstractRepository

L'extension EntityPicker s'utilise avec les méthodes ManyToMany de `AbstractRepository` :

```php
// Dans votre repository
class LabelRepository extends AbstractRepository
{
    public function attachProducts(int $labelId, array $productIds): bool
    {
        return $this->attachMany(
            'mymodule_label_product',  // Table pivot
            'id_product',               // Clé étrangère
            $labelId,                   // ID de l'entité
            $productIds                 // IDs à attacher
        );
    }

    public function getProductIds(int $labelId): array
    {
        return $this->getAttachedIds(
            'mymodule_label_product',
            'id_product',
            $labelId
        );
    }

    public function syncProducts(int $labelId, array $productIds): void
    {
        $this->syncAttached(
            'mymodule_label_product',
            'id_product',
            $labelId,
            $productIds
        );
    }
}
```

---

## Bonnes Pratiques

1. **Toujours définir `fetch_url`** pour charger les entités existantes
2. **Limiter `max_results`** pour des performances optimales
3. **Utiliser le trait `EntitySearchTrait`** plutôt que coder manuellement les actions
4. **Copier le JS** dans `views/js/` du module pour le déploiement


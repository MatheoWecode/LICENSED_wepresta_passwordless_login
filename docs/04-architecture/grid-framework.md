# Grid Framework

> Référence technique détaillée : [.cursor/rules/004-module-controllers.mdc](../../.cursor/rules/004-module-controllers.mdc)

Le **Grid Framework** de PrestaShop permet de créer des tableaux admin modernes avec tri, filtres et pagination.

## Qu'est-ce que le Grid Framework ?

C'est un système standardisé pour afficher des listes d'objets dans le back-office :

```
┌─────────────────────────────────────────────────────────────────┐
│  Recherche: [________]     Filtres: [Status ▼] [Date ▼]        │
├─────────────────────────────────────────────────────────────────┤
│  □  ID ▼  │  Nom          │  Status    │  Date       │ Actions │
├───────────┼───────────────┼────────────┼─────────────┼─────────┤
│  □  1     │  Item A       │  ✓ Actif   │  22/12/2024 │ ⋮       │
│  □  2     │  Item B       │  ✗ Inactif │  21/12/2024 │ ⋮       │
│  □  3     │  Item C       │  ✓ Actif   │  20/12/2024 │ ⋮       │
├─────────────────────────────────────────────────────────────────┤
│  ◀ 1 2 3 ▶                              Affichage: 20 par page │
└─────────────────────────────────────────────────────────────────┘
```

---

## Composants du Grid

Le Grid Framework se compose de plusieurs éléments :

| Composant | Rôle |
|-----------|------|
| **GridDefinition** | Structure de la grille (colonnes, filtres, actions) |
| **GridDataFactory** | Fournit les données |
| **SearchCriteria** | Critères de recherche/filtre |
| **GridFactory** | Assemble le tout |

---

## Créer une grille

### 1. GridDefinitionFactory

Définit la structure de la grille :

```php
// src/Presentation/Grid/ItemGridDefinitionFactory.php

namespace MonModule\Presentation\Grid;

use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

final class ItemGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    protected function getId(): string
    {
        return 'monmodule_items';
    }

    protected function getName(): string
    {
        return $this->trans('Items', [], 'Modules.Monmodule.Admin');
    }

    protected function getColumns(): ColumnCollection
    {
        return (new ColumnCollection())
            ->add((new DataColumn('id_item'))
                ->setName($this->trans('ID', [], 'Admin.Global'))
                ->setOptions(['field' => 'id_item']))
            
            ->add((new DataColumn('name'))
                ->setName($this->trans('Name', [], 'Admin.Global'))
                ->setOptions(['field' => 'name']))
            
            ->add((new ToggleColumn('active'))
                ->setName($this->trans('Status', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'active',
                    'primary_field' => 'id_item',
                    'route' => 'monmodule_item_toggle',
                    'route_param_name' => 'itemId',
                ]))
            
            ->add((new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => $this->getRowActions(),
                ]));
    }
}
```

### 2. GridDataFactory

Fournit les données depuis la base :

```php
// src/Presentation/Grid/ItemGridDataFactory.php

namespace MonModule\Presentation\Grid;

use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class ItemGridDataFactory implements GridDataFactoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
    ) {}

    public function getData(SearchCriteriaInterface $searchCriteria): GridData
    {
        $items = $this->repository->findForGrid(
            $searchCriteria->getFilters(),
            $searchCriteria->getOrderBy(),
            $searchCriteria->getOrderWay(),
            $searchCriteria->getOffset(),
            $searchCriteria->getLimit()
        );

        $total = $this->repository->countForGrid($searchCriteria->getFilters());

        return new GridData(
            new RecordCollection($items),
            $total,
            $this->getQuery($searchCriteria)
        );
    }
}
```

### 3. Enregistrer les services

```yaml
# config/services.yml
services:
  MonModule\Presentation\Grid\ItemGridDefinitionFactory:
    parent: 'prestashop.core.grid.definition.factory.abstract_grid_definition'
    public: true

  MonModule\Presentation\Grid\ItemGridDataFactory:
    arguments:
      $repository: '@MonModule\Infrastructure\Repository\ItemRepository'
    public: true

  monmodule.grid.item_grid_factory:
    class: PrestaShop\PrestaShop\Core\Grid\GridFactory
    arguments:
      - '@MonModule\Presentation\Grid\ItemGridDefinitionFactory'
      - '@MonModule\Presentation\Grid\ItemGridDataFactory'
      - '@prestashop.core.grid.filter.form_factory'
      - '@prestashop.core.hook.dispatcher'
    public: true
```

---

## Utiliser la grille

### Dans le contrôleur

```php
class ItemController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request): Response
    {
        $gridFactory = $this->get('monmodule.grid.item_grid_factory');
        
        $searchCriteria = $this->buildSearchCriteriaFromRequest(
            $request,
            'monmodule_items'
        );
        
        $grid = $gridFactory->getGrid($searchCriteria);
        
        return $this->render('@Modules/monmodule/views/templates/admin/items/index.html.twig', [
            'grid' => $this->presentGrid($grid),
        ]);
    }
}
```

### Dans le template Twig

```twig
{% extends '@PrestaShop/Admin/layout.html.twig' %}

{% block content %}
    <div class="card">
        <h2 class="card-header">{{ 'Items'|trans({}, 'Modules.Monmodule.Admin') }}</h2>
        <div class="card-body">
            {% include '@PrestaShop/Admin/Common/Grid/grid.html.twig' with {'grid': grid} %}
        </div>
    </div>
{% endblock %}
```

---

## Types de colonnes

| Type | Usage |
|------|-------|
| `DataColumn` | Données texte |
| `ToggleColumn` | Switch on/off |
| `DateTimeColumn` | Dates |
| `BadgeColumn` | Labels colorés |
| `ImageColumn` | Images |
| `ActionColumn` | Boutons d'action |
| `BulkActionColumn` | Cases à cocher |
| `PositionColumn` | Drag & drop |
| `LinkColumn` | Lien cliquable |

---

## Filtres

Ajouter des filtres à la grille :

```php
protected function getFilters(): FilterCollection
{
    return (new FilterCollection())
        ->add((new Filter('id_item', TextType::class))
            ->setTypeOptions(['required' => false])
            ->setAssociatedColumn('id_item'))
        
        ->add((new Filter('name', TextType::class))
            ->setTypeOptions(['required' => false])
            ->setAssociatedColumn('name'))
        
        ->add((new Filter('active', YesAndNoChoiceType::class))
            ->setTypeOptions(['required' => false])
            ->setAssociatedColumn('active'));
}
```

---

## Actions de masse

```php
protected function getBulkActions(): BulkActionCollection
{
    return (new BulkActionCollection())
        ->add((new SubmitBulkAction('enable_selection'))
            ->setName($this->trans('Enable', [], 'Admin.Actions'))
            ->setOptions([
                'submit_route' => 'monmodule_item_bulk_enable',
            ]))
        
        ->add((new SubmitBulkAction('disable_selection'))
            ->setName($this->trans('Disable', [], 'Admin.Actions'))
            ->setOptions([
                'submit_route' => 'monmodule_item_bulk_disable',
            ]))
        
        ->add((new SubmitBulkAction('delete_selection'))
            ->setName($this->trans('Delete', [], 'Admin.Actions'))
            ->setOptions([
                'submit_route' => 'monmodule_item_bulk_delete',
                'confirm_message' => $this->trans('Delete selected items?', [], 'Modules.Monmodule.Admin'),
            ]));
}
```

---

## Actions par ligne

```php
private function getRowActions(): RowActionCollection
{
    return (new RowActionCollection())
        ->add((new LinkRowAction('edit'))
            ->setName($this->trans('Edit', [], 'Admin.Actions'))
            ->setIcon('edit')
            ->setOptions([
                'route' => 'monmodule_item_edit',
                'route_param_name' => 'itemId',
                'route_param_field' => 'id_item',
            ]))
        
        ->add((new SubmitRowAction('delete'))
            ->setName($this->trans('Delete', [], 'Admin.Actions'))
            ->setIcon('delete')
            ->setOptions([
                'method' => 'POST',
                'route' => 'monmodule_item_delete',
                'route_param_name' => 'itemId',
                'route_param_field' => 'id_item',
                'confirm_message' => $this->trans('Delete this item?', [], 'Modules.Monmodule.Admin'),
            ]));
}
```

---

<details>
<summary>💡 Personnaliser le rendu</summary>

Pour un rendu personnalisé d'une colonne :

```php
->add((new DataColumn('price'))
    ->setOptions([
        'field' => 'price',
        'sortable' => true,
    ])
    ->setModifier(function ($value) {
        return number_format($value, 2, ',', ' ') . ' €';
    }))
```

</details>

---

**Prochaine étape** : [Form Types](./form-types.md)


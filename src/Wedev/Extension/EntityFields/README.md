# EntityFields Extension

Generic infrastructure for attaching custom fields to any PrestaShop entity (core entities + custom post types).

## Overview

The EntityFields extension provides a unified system for:
- Registering entity types that support custom fields
- Managing hooks for field display and saving
- Building context for location rule matching
- Integrating ACF (Advanced Custom Fields) with any entity type

## Architecture

```
EntityFieldRegistry (singleton)
    ├── EntityFieldProviderInterface (implemented by modules)
    │   ├── ProductEntityFieldProvider (wepresta_acf)
    │   ├── CategoryEntityFieldProvider (wepresta_acf)
    │   ├── CptEntityFieldProvider (wepresta_cpt)
    │   └── ...
    │
    └── EntityFieldContext (helper for building context)
```

## Usage

### 1. Register Entity Types (in ACF module)

```php
// In config/services.yml
services:
    Wepresta_Passwordless_Login\Application\Provider\EntityField\ProductEntityFieldProvider:
        tags: ['entity_field_provider']
        arguments:
            $registry: '@wedev.entity_field_registry'

// In a service or event subscriber:
use Wepresta_Passwordless_Login\Wedev\Extension\EntityFields\EntityFieldRegistry;

public function __construct(
    private readonly EntityFieldRegistry $registry,
    private readonly ProductEntityFieldProvider $productProvider
) {
    // Register on initialization
    $this->registry->registerEntityType('product', $this->productProvider);
}
```

### 2. Implement EntityFieldProviderInterface

```php
use Wepresta_Passwordless_Login\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

final class ProductEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'product';
    }

    public function getDisplayHooks(): array
    {
        return ['displayAdminProductsExtra'];
    }

    public function getActionHooks(): array
    {
        return ['actionProductUpdate', 'actionProductAdd'];
    }

    public function buildContext(int $entityId): array
    {
        $product = new Product($entityId);
        return [
            'entity_type' => 'product',
            'entity_id' => $entityId,
            'category_ids' => $product->getCategories(),
            'product_type' => $product->getType(),
        ];
    }

    public function getEntityLabel(int $langId): string
    {
        return 'Product';
    }
}
```

### 3. Register CPT Post Types (in CPT module)

```php
// In wepresta_cpt module
final class CptEntityFieldProvider implements EntityFieldProviderInterface
{
    public function __construct(
        private readonly PostTypeRepositoryInterface $postTypeRepository
    ) {
    }

    public function getEntityType(): string
    {
        // This is a special provider that registers multiple entity types
        // See implementation below
        return 'cpt_dynamic';
    }

    // ... implement interface methods
}

// Register each PostType as an entity type
foreach ($postTypeRepository->findActive() as $postType) {
    $entityType = 'cpt_' . $postType->getSlug()->getValue();
    $provider = new CptPostTypeProvider($postType);
    $registry->registerEntityType($entityType, $provider);
}
```

### 4. Use Registry in ACF Module

```php
// Get all hooks to register
$allHooks = $registry->getAllHooks();
$this->registerHook($allHooks);

// Get hooks for a specific entity type
$productHooks = $registry->getHooksForEntityType('product');

// Get entity types for a hook
$entityTypes = $registry->getEntityTypesForHook('displayAdminProductsExtra');
// Returns: ['product']

// Build context for location rules
$context = EntityFieldContext::buildFromProvider($registry, 'product', 123);
```

## Integration with ACF Location Rules

The EntityFieldRegistry integrates with ACF's LocationProviderRegistry:

```php
// In LocationProviderRegistry
use Wepresta_Passwordless_Login\Wedev\Extension\EntityFields\EntityFieldRegistry;

public function __construct(
    EntityFieldRegistry $entityFieldRegistry
) {
    // Get entity types from registry
    foreach ($entityFieldRegistry->getAllEntityTypes() as $entityType => $provider) {
        $locations[] = [
            'type' => 'entity_type',
            'value' => $entityType,
            'label' => $provider->getEntityLabel($langId),
            'group' => 'Entities',
        ];
    }
}
```

## Entity Type Naming Convention

- **Core entities**: Use PrestaShop naming (`'product'`, `'category'`, `'customer'`)
- **CPT entities**: Use prefix `'cpt_'` + post type slug (`'cpt_event'`, `'cpt_news'`)
- **Custom entities**: Use descriptive, unique identifiers

## Services

### EntityFieldRegistry

Central singleton registry for all entity types.

**Methods:**
- `registerEntityType(string $type, EntityFieldProviderInterface $provider): void`
- `getEntityType(string $type): ?EntityFieldProviderInterface`
- `getAllEntityTypes(): array<string, EntityFieldProviderInterface>`
- `getHooksForEntityType(string $type): array<string>`
- `getAllHooks(): array<string>`
- `getEntityTypesForHook(string $hookName): array<string>`

### EntityFieldContext

Static helper for building context arrays.

**Methods:**
- `build(string $entityType, int $entityId, array $additional = []): array`
- `buildFromProvider(EntityFieldRegistry $registry, string $entityType, int $entityId): array`

## Examples

### Example 1: Register Product Entity

```php
// In wepresta_acf module
$productProvider = new ProductEntityFieldProvider();
$registry->registerEntityType('product', $productProvider);
```

### Example 2: Register CPT Post Type

```php
// In wepresta_cpt module
$postType = $postTypeRepository->getBySlug('event');
$provider = new CptPostTypeProvider($postType);
$registry->registerEntityType('cpt_event', $provider);
```

### Example 3: Use in Hook Handler

```php
// In module main file
public function hookDisplayAdminProductsExtra(array $params): string
{
    $productId = (int)($params['id_product'] ?? 0);
    if ($productId <= 0) {
        return '';
    }

    // Build context for location rules
    $context = EntityFieldContext::buildFromProvider(
        $this->getService(EntityFieldRegistry::class),
        'product',
        $productId
    );

    // Get matching field groups
    $groups = $this->getMatchingGroups($context);

    // Render fields
    return $this->renderFields($groups, $productId);
}
```

## Dependencies

- None (standalone extension)

## Version

1.0.0



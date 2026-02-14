# Performance

Optimiser les performances de votre module.

## Principes généraux

1. **Mesurer avant d'optimiser** — Ne pas deviner
2. **Optimiser les goulots d'étranglement** — Pas tout
3. **Le cache est votre ami** — Utiliser intelligemment
4. **Moins de requêtes SQL** — Grouper les requêtes

---

## Profiling

### Activer le profiling PrestaShop

```php
// config/defines.inc.php
define('_PS_MODE_DEV_', true);
define('_PS_DEBUG_PROFILING_', true);
```

Affiche les temps d'exécution en bas de page.

### Xdebug Profiler

```bash
# Avec DDEV
ddev xdebug on

# Déclencher le profiling
# Ajouter ?XDEBUG_PROFILE=1 à l'URL
```

Analysez avec [KCacheGrind](https://kcachegrind.github.io/) ou [Webgrind](https://github.com/jokkedk/webgrind).

---

## Cache

### Utiliser CacheService

```php
use MonModule\Core\Service\CacheService;

class ItemService
{
    public function __construct(
        private readonly CacheService $cache,
        private readonly ItemRepository $repository
    ) {}
    
    public function getActiveItems(): array
    {
        return $this->cache->get(
            'active_items',
            fn() => $this->repository->findActive(),
            3600  // TTL: 1 heure
        );
    }
    
    public function invalidateCache(): void
    {
        $this->cache->delete('active_items');
    }
}
```

### Invalidation du cache

```php
// À l'ajout/modification/suppression
public function hookActionObjectItemAddAfter(array $params): void
{
    $this->getService(CacheService::class)->delete('active_items');
}

public function hookActionObjectItemUpdateAfter(array $params): void
{
    $this->getService(CacheService::class)->delete('active_items');
}
```

### Cache avec paramètres

```php
public function getItemsByCategory(int $categoryId): array
{
    $cacheKey = 'items_category_' . $categoryId;
    
    return $this->cache->get(
        $cacheKey,
        fn() => $this->repository->findByCategory($categoryId),
        1800
    );
}
```

---

## Requêtes SQL

### Éviter les requêtes N+1

```php
// ❌ Mauvais: N+1 requêtes
$items = $this->repository->findAll();
foreach ($items as $item) {
    $item['category'] = $this->categoryRepo->findById($item['id_category']);
}

// ✅ Bon: 1 requête avec JOIN
$query = new DbQuery();
$query->select('i.*, c.name as category_name')
      ->from('monmodule_item', 'i')
      ->leftJoin('category', 'c', 'i.id_category = c.id_category');
```

### Limiter les résultats

```php
// ❌ Mauvais: récupère tout
$items = $this->repository->findAll();
$firstTen = array_slice($items, 0, 10);

// ✅ Bon: limite en SQL
$query = new DbQuery();
$query->select('*')
      ->from('monmodule_item')
      ->limit(10);
```

### Index de base de données

```sql
-- Ajouter des index sur les colonnes filtrées
ALTER TABLE ps_monmodule_item 
ADD INDEX idx_active (active),
ADD INDEX idx_category (id_category);
```

---

## Hooks performants

### Sortie anticipée

```php
public function hookDisplayHome(array $params): string
{
    // Sortir rapidement si désactivé
    if (!$this->config->getBool('MONMODULE_ACTIVE')) {
        return '';
    }
    
    // Sortir si cache valide
    $cached = $this->getCachedOutput('home');
    if ($cached !== null) {
        return $cached;
    }
    
    // Logique normale...
}
```

### Lazy loading

```php
public function hookDisplayHome(array $params): string
{
    // Charger le service seulement si nécessaire
    if (!$this->shouldDisplay()) {
        return '';
    }
    
    $service = $this->getService(DisplayService::class);
    // ...
}
```

---

## Assets

### Charger conditionnellement

```php
public function hookActionFrontControllerSetMedia(array $params): void
{
    $controller = $this->context->controller->php_self;
    
    // Seulement sur les pages nécessaires
    $allowedPages = ['index', 'product', 'category'];
    
    if (!in_array($controller, $allowedPages, true)) {
        return;
    }
    
    $this->context->controller->registerStylesheet(...);
}
```

### Minification et compression

```javascript
// webpack.config.js
Encore
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    // Minification automatique en production
;
```

### Chargement asynchrone

```php
$this->context->controller->registerJavascript(
    'monmodule-js',
    'modules/' . $this->name . '/views/dist/front.js',
    [
        'position' => 'bottom',
        'attributes' => 'async',  // ou 'defer'
    ]
);
```

---

## Mémoire

### Éviter les gros tableaux

```php
// ❌ Mauvais: charge tout en mémoire
$allProducts = Product::getProducts($langId, 0, 0, 'id_product', 'ASC');

// ✅ Bon: pagination
$page = 0;
$limit = 100;
do {
    $products = Product::getProducts($langId, $page * $limit, $limit, 'id_product', 'ASC');
    foreach ($products as $product) {
        $this->processProduct($product);
    }
    $page++;
} while (count($products) === $limit);
```

### Générateurs

```php
// Pour de très grandes collections
public function getAllItems(): \Generator
{
    $offset = 0;
    $limit = 100;
    
    do {
        $items = $this->repository->findPaginated($offset, $limit);
        foreach ($items as $item) {
            yield $item;
        }
        $offset += $limit;
    } while (count($items) === $limit);
}

// Utilisation
foreach ($this->getAllItems() as $item) {
    // Traite un item à la fois
}
```

---

## Outils de mesure

### Timer simple

```php
$start = microtime(true);

// Code à mesurer
$items = $this->repository->findAll();

$duration = microtime(true) - $start;
PrestaShopLogger::addLog("findAll took {$duration}s");
```

### Query Logger

```sql
-- Activer le slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

### New Relic / Blackfire

Pour une analyse approfondie, utilisez des outils professionnels :
- [Blackfire](https://blackfire.io/)
- [New Relic](https://newrelic.com/)

---

## Checklist performance

- [ ] Cache activé pour les données fréquentes
- [ ] Pas de requêtes N+1
- [ ] Index SQL sur les colonnes filtrées
- [ ] Assets chargés conditionnellement
- [ ] Assets minifiés en production
- [ ] Hooks avec sortie anticipée
- [ ] Pagination pour les grandes collections

---

**Prochaine étape** : [Sécurité](./security.md)


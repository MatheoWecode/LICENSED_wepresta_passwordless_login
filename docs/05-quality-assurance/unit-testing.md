# Tests Unitaires (Unit Testing)

> Référence technique détaillée : [.cursor/rules/008-module-testing.mdc](../../.cursor/rules/008-module-testing.mdc)

Les tests unitaires vérifient que chaque **unité** de code fonctionne correctement de manière isolée.

## Qu'est-ce qu'un test unitaire ?

Un test unitaire :
- Teste **une seule chose** (méthode, classe)
- Est **isolé** (pas de base de données, pas de réseau)
- Est **rapide** (millisecondes)
- Est **répétable** (même résultat à chaque exécution)

```
┌─────────────────────────────────────────────────────────────┐
│  Test: ItemService::calculateTotal()                        │
│                                                             │
│  Entrée: items = [{price: 10}, {price: 20}]                │
│  Attendu: 30                                                │
│  Résultat: 30 ✓                                             │
│                                                             │
│  → Test PASSED                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## PHPUnit

Ce module utilise **PHPUnit**, le framework de test standard pour PHP.

### Lancer les tests

```bash
# Tous les tests
composer phpunit

# Avec DDEV
ddev exec composer phpunit

# Un fichier spécifique
./vendor/bin/phpunit tests/Unit/ItemServiceTest.php

# Une méthode spécifique
./vendor/bin/phpunit --filter=testCalculateTotal
```

---

## Structure des tests

```
tests/
├── Unit/                    # Tests unitaires
│   ├── Domain/
│   │   └── Entity/
│   │       └── ItemTest.php
│   └── Application/
│       └── Service/
│           └── ItemServiceTest.php
│
├── Integration/             # Tests d'intégration
│   └── Repository/
│       └── ItemRepositoryTest.php
│
└── bootstrap.php            # Configuration
```

---

## Écrire un test

### Structure de base

```php
// tests/Unit/Application/Service/ItemServiceTest.php

namespace MonModule\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use MonModule\Application\Service\ItemService;
use MonModule\Domain\Entity\Item;

class ItemServiceTest extends TestCase
{
    private ItemService $service;
    
    protected function setUp(): void
    {
        // Exécuté avant chaque test
        $this->service = new ItemService();
    }
    
    public function testCalculateTotalReturnsSum(): void
    {
        // Arrange (Préparer)
        $items = [
            new Item(name: 'A', price: 10.0),
            new Item(name: 'B', price: 20.0),
        ];
        
        // Act (Agir)
        $total = $this->service->calculateTotal($items);
        
        // Assert (Vérifier)
        $this->assertEquals(30.0, $total);
    }
    
    public function testCalculateTotalWithEmptyArrayReturnsZero(): void
    {
        $total = $this->service->calculateTotal([]);
        
        $this->assertEquals(0.0, $total);
    }
}
```

---

## Assertions courantes

| Assertion | Vérifie que... |
|-----------|----------------|
| `assertEquals($expected, $actual)` | Valeurs égales |
| `assertSame($expected, $actual)` | Identiques (type + valeur) |
| `assertTrue($value)` | Est vrai |
| `assertFalse($value)` | Est faux |
| `assertNull($value)` | Est null |
| `assertNotNull($value)` | N'est pas null |
| `assertCount($count, $array)` | Tableau a N éléments |
| `assertEmpty($array)` | Tableau vide |
| `assertInstanceOf($class, $object)` | Est instance de |
| `assertContains($needle, $haystack)` | Contient |
| `assertArrayHasKey($key, $array)` | Clé existe |

### Exemples

```php
// Égalité
$this->assertEquals('hello', $result);

// Type strict
$this->assertSame(42, $result);  // Pas '42'

// Booléens
$this->assertTrue($item->isActive());
$this->assertFalse($item->isDeleted());

// Null
$this->assertNull($this->service->findById(999));
$this->assertNotNull($this->service->findById(1));

// Collections
$this->assertCount(3, $items);
$this->assertContains($item, $collection);

// Types
$this->assertInstanceOf(Item::class, $result);
```

---

## Mocks

Un **mock** simule un objet pour isoler le code testé.

### Pourquoi mocker ?

```php
// ❌ Sans mock: dépend de la BDD
class ItemServiceTest extends TestCase
{
    public function testGetItem(): void
    {
        $service = new ItemService(new ItemRepository());  // BDD réelle!
        $item = $service->getItem(1);
    }
}

// ✅ Avec mock: isolé
class ItemServiceTest extends TestCase
{
    public function testGetItem(): void
    {
        // Créer un mock du repository
        $mockRepo = $this->createMock(ItemRepositoryInterface::class);
        $mockRepo->method('findById')
                 ->willReturn(new Item(id: 1, name: 'Test'));
        
        $service = new ItemService($mockRepo);
        $item = $service->getItem(1);
        
        $this->assertEquals('Test', $item->getName());
    }
}
```

### Configurer un mock

```php
$mockRepo = $this->createMock(ItemRepositoryInterface::class);

// Retourner une valeur
$mockRepo->method('findById')->willReturn($item);

// Retourner selon l'argument
$mockRepo->method('findById')
         ->willReturnMap([
             [1, $item1],
             [2, $item2],
         ]);

// Lever une exception
$mockRepo->method('save')
         ->willThrowException(new \Exception('DB Error'));

// Vérifier qu'une méthode est appelée
$mockRepo->expects($this->once())
         ->method('save')
         ->with($this->equalTo($item));
```

---

## Tests d'exceptions

```php
public function testGetItemThrowsExceptionWhenNotFound(): void
{
    $mockRepo = $this->createMock(ItemRepositoryInterface::class);
    $mockRepo->method('findById')->willReturn(null);
    
    $service = new ItemService($mockRepo);
    
    $this->expectException(ItemNotFoundException::class);
    $this->expectExceptionMessage('Item with ID 999 not found');
    
    $service->getItem(999);
}
```

---

## Data Providers

Pour tester avec plusieurs jeux de données :

```php
/**
 * @dataProvider priceDataProvider
 */
public function testCalculateTax(float $price, float $expectedTax): void
{
    $tax = $this->service->calculateTax($price);
    $this->assertEquals($expectedTax, $tax);
}

public static function priceDataProvider(): array
{
    return [
        'prix standard' => [100.0, 20.0],
        'prix zéro' => [0.0, 0.0],
        'prix décimal' => [99.99, 19.998],
        'grand prix' => [1000.0, 200.0],
    ];
}
```

---

## Bonnes pratiques

### Nommer clairement

```php
// ✅ Nom descriptif
public function testCalculateTotalWithDiscountAppliesPercentage(): void

// ❌ Nom vague
public function testCalculate(): void
```

### Un assert par test (idéalement)

```php
// ✅ Un concept par test
public function testItemIsActiveByDefault(): void
{
    $item = new Item(name: 'Test');
    $this->assertTrue($item->isActive());
}

public function testItemNameIsStored(): void
{
    $item = new Item(name: 'Test');
    $this->assertEquals('Test', $item->getName());
}
```

### Tester les cas limites

```php
// Cas normal
public function testFindByIdReturnsItem(): void { }

// Cas limite
public function testFindByIdReturnsNullWhenNotFound(): void { }
public function testFindByIdThrowsOnNegativeId(): void { }
```

---

**Prochaine étape** : [Code Coverage](./code-coverage.md)


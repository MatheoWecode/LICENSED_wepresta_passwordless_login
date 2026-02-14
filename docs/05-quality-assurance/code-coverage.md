# Code Coverage

La **couverture de code** (code coverage) mesure quel pourcentage du code est exécuté par les tests.

## Qu'est-ce que le code coverage ?

Le coverage indique quelles lignes de code sont testées :

```php
public function calculateTotal(array $items): float  // ✓ Couvert
{
    if (empty($items)) {                              // ✓ Couvert
        return 0.0;                                   // ✗ Non couvert!
    }
    
    $total = 0.0;                                     // ✓ Couvert
    foreach ($items as $item) {                       // ✓ Couvert
        $total += $item->getPrice();                  // ✓ Couvert
    }
    
    return $total;                                    // ✓ Couvert
}

// Coverage: 7/8 lignes = 87.5%
```

---

## Générer un rapport

### Prérequis

Xdebug ou PCOV doit être installé :

```bash
# Vérifier
php -m | grep -E "(xdebug|pcov)"

# Avec DDEV, Xdebug est disponible
ddev xdebug on
```

### Lancer avec coverage

```bash
# Rapport texte
composer phpunit-coverage

# Ou manuellement
./vendor/bin/phpunit --coverage-text

# Rapport HTML (plus détaillé)
./vendor/bin/phpunit --coverage-html var/coverage
```

### Voir le rapport HTML

```bash
# Ouvrir dans le navigateur
open var/coverage/index.html

# Avec DDEV
ddev launch /modules/monmodule/var/coverage/
```

---

## Lire le rapport

### Rapport texte

```
Code Coverage Report:
  2024-12-22 10:30:00

 Summary:
  Classes: 75.00% (3/4)
  Methods: 82.35% (14/17)
  Lines:   89.42% (85/95)

MonModule\Application\Service
  ItemService .......................... 95.00%
  PricingService ....................... 100.00%
  
MonModule\Domain\Entity
  Item ................................. 88.00%
```

### Rapport HTML

Le rapport HTML montre :
- **Vert** : Ligne couverte
- **Rouge** : Ligne non couverte
- **Jaune** : Partiellement couvert (branches)

---

## Objectifs de coverage

| Niveau | Pourcentage | Signification |
|--------|-------------|---------------|
| Minimum | 60% | Fonctionnalités principales testées |
| Bon | 80% | Bonne couverture |
| Excellent | 90%+ | Couverture élevée |

### Pour ce module

Objectif recommandé : **80%** sur `src/Application/` et `src/Domain/`.

```neon
# phpunit.xml
<coverage>
    <report>
        <html outputDirectory="var/coverage"/>
    </report>
</coverage>
```

---

## Ce qu'il faut tester

### Priorité haute

- **Domain** : Entités, Value Objects
- **Application** : Services, Handlers
- **Logique métier complexe**

### Priorité basse

- **Infrastructure** : Repositories (tests d'intégration)
- **Presentation** : Contrôleurs (tests fonctionnels)
- **Getters/Setters simples**

### Ne pas tester

- **Core** : Géré par WEDEV
- **Code tiers** : PrestaShop, Symfony

---

## Améliorer le coverage

### Identifier les trous

1. Générez le rapport HTML
2. Cliquez sur un fichier avec faible coverage
3. Les lignes rouges indiquent le code non testé

### Ajouter des tests

```php
// Code non couvert
if (empty($items)) {
    return 0.0;  // Cette ligne n'est jamais exécutée
}

// Ajouter un test pour ce cas
public function testCalculateTotalWithEmptyArrayReturnsZero(): void
{
    $result = $this->service->calculateTotal([]);
    $this->assertEquals(0.0, $result);
}
```

### Tester les branches

```php
// Deux branches à tester
public function getStatus(): string
{
    return $this->active ? 'active' : 'inactive';
}

// Tests pour les deux branches
public function testGetStatusReturnsActiveWhenActive(): void { }
public function testGetStatusReturnsInactiveWhenInactive(): void { }
```

---

## Coverage et CI/CD

### Vérification automatique

```yaml
# .github/workflows/tests.yml
jobs:
  coverage:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: ./vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Check coverage
        run: |
          COVERAGE=$(grep -oP 'line-rate="\K[^"]+' coverage.xml)
          if (( $(echo "$COVERAGE < 0.80" | bc -l) )); then
            echo "Coverage is below 80%"
            exit 1
          fi
```

### Badge de coverage

Affichez le coverage dans le README :

```markdown
![Coverage](https://img.shields.io/badge/coverage-85%25-green)
```

---

## Faux positifs

Un coverage élevé ne garantit pas des tests de qualité :

```php
// ✗ Test inutile (100% coverage mais ne vérifie rien)
public function testGetName(): void
{
    $item = new Item(name: 'Test');
    $item->getName();  // Pas d'assertion!
}

// ✓ Test utile
public function testGetNameReturnsSetName(): void
{
    $item = new Item(name: 'Test');
    $this->assertEquals('Test', $item->getName());
}
```

### Qualité > Quantité

- Écrivez des tests qui **vérifient** le comportement
- Testez les **cas limites**
- Testez les **erreurs attendues**

---

## Configuration

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <coverage>
        <include>
            <directory suffix=".php">src/</directory>
        </include>
        <exclude>
            <directory>src/Core/</directory>
        </exclude>
        <report>
            <html outputDirectory="var/coverage"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

**Prochaine étape** : [Style de code](./code-style.md)


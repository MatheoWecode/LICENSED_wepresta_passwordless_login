# Refactoring Automatisé

> Référence technique détaillée : [.cursor/rules/011-module-quality.mdc](../../.cursor/rules/011-module-quality.mdc)

**Rector** automatise le refactoring et la migration du code PHP.

## Qu'est-ce que Rector ?

Rector analyse et transforme le code automatiquement :
- Migration vers PHP 8.1+
- Application des bonnes pratiques
- Refactoring de patterns obsolètes

```
┌─────────────────────────────────────────────────────────────┐
│  Code avant                                                 │
│                                                             │
│  public function getUser() {                                │
│      return $this->user ?? null;                            │
│  }                                                          │
│                                                             │
│       ↓ Rector                                              │
│                                                             │
│  public function getUser(): ?User {                         │
│      return $this->user;                                    │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## Utilisation

### Prévisualiser les changements (dry-run)

```bash
# Voir ce que Rector changerait
composer rector-dry

# Avec DDEV
ddev exec composer rector-dry
```

### Appliquer les changements

```bash
# Appliquer les transformations
composer rector

# Avec DDEV
ddev exec composer rector
```

---

## Configuration

Le fichier `rector.php` définit les règles :

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    
    $rectorConfig->skip([
        __DIR__ . '/src/Core',  // WEDEV Core
    ]);
    
    // PHP 8.1
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
    ]);
    
    // Bonnes pratiques
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
    ]);
};
```

---

## Transformations courantes

### Types de retour

```php
// Avant
public function getItems()
{
    return $this->items;
}

// Après
public function getItems(): array
{
    return $this->items;
}
```

### Constructor Promotion (PHP 8.0+)

```php
// Avant
class ItemService
{
    private ItemRepository $repository;
    
    public function __construct(ItemRepository $repository)
    {
        $this->repository = $repository;
    }
}

// Après
class ItemService
{
    public function __construct(
        private ItemRepository $repository
    ) {
    }
}
```

### Readonly Properties (PHP 8.1+)

```php
// Avant
private ItemRepository $repository;

// Après
private readonly ItemRepository $repository;
```

### Match Expression (PHP 8.0+)

```php
// Avant
switch ($status) {
    case 'active':
        $result = 'Actif';
        break;
    case 'inactive':
        $result = 'Inactif';
        break;
    default:
        $result = 'Inconnu';
}

// Après
$result = match ($status) {
    'active' => 'Actif',
    'inactive' => 'Inactif',
    default => 'Inconnu',
};
```

### Early Return

```php
// Avant
public function process($item)
{
    if ($item !== null) {
        if ($item->isActive()) {
            return $this->doProcess($item);
        }
    }
    return null;
}

// Après
public function process($item)
{
    if ($item === null) {
        return null;
    }
    
    if (!$item->isActive()) {
        return null;
    }
    
    return $this->doProcess($item);
}
```

### Code mort

```php
// Avant
public function save(Item $item): void
{
    $result = $this->repository->save($item);
    $unused = 'never used';  // Supprimé
    
    if (false) {              // Supprimé
        echo 'never executed';
    }
}

// Après
public function save(Item $item): void
{
    $result = $this->repository->save($item);
}
```

---

## Sets disponibles

| Set | Description |
|-----|-------------|
| `LevelSetList::UP_TO_PHP_81` | Migration PHP 8.1 |
| `SetList::CODE_QUALITY` | Amélioration qualité |
| `SetList::DEAD_CODE` | Suppression code mort |
| `SetList::EARLY_RETURN` | Pattern early return |
| `SetList::TYPE_DECLARATION` | Ajout des types |
| `SetList::PRIVATIZATION` | Visibilité réduite |

---

## Workflow recommandé

### 1. Prévisualiser

```bash
composer rector-dry
```

Examinez les changements proposés.

### 2. Appliquer progressivement

```bash
# Sur un fichier spécifique
./vendor/bin/rector process src/Application/Service/ItemService.php
```

### 3. Vérifier

```bash
# Vérifier que les tests passent
composer phpunit

# Vérifier l'analyse statique
composer phpstan
```

### 4. Commiter

```bash
git add .
git commit -m "refactor: apply Rector transformations"
```

---

## Ignorer des règles

### Dans le fichier

```php
/**
 * @rector-suppress-rule Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector
 */
class LegacyService
{
    // Non finalisé pour compatibilité
}
```

### Dans la configuration

```php
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->skip([
        ReadOnlyPropertyRector::class => [
            __DIR__ . '/src/Legacy/*',
        ],
    ]);
};
```

---

## Risques

⚠️ **Rector modifie le code**. Précautions :

1. **Commitez avant** d'exécuter Rector
2. **Utilisez dry-run** d'abord
3. **Exécutez les tests** après
4. **Revoyez les changements** avant de commiter

---

**Prochaine étape** : [Workflow QA](./qa-workflow.md)


# Style de Code

> Référence technique détaillée : [.cursor/rules/011-module-quality.mdc](../../.cursor/rules/011-module-quality.mdc)

Un style de code cohérent améliore la lisibilité et la maintenabilité.

## PHP-CS-Fixer

Ce module utilise **PHP-CS-Fixer** pour appliquer automatiquement les conventions de code.

### Vérifier le style

```bash
# Voir les problèmes sans corriger
composer cs-check

# Avec DDEV
ddev exec composer cs-check
```

### Corriger automatiquement

```bash
# Corriger tous les fichiers
composer cs-fix

# Avec DDEV
ddev exec composer cs-fix
```

---

## Standard utilisé : PSR-12

Le module suit le standard **PSR-12** (PHP Standards Recommendation).

### Principales règles

| Règle | Exemple |
|-------|---------|
| Indentation | 4 espaces (pas de tabs) |
| Accolades | Nouvelle ligne pour classes/méthodes |
| Lignes | Max 120 caractères |
| Imports | Triés alphabétiquement |
| Opérateurs | Espacés |

### Exemples

```php
<?php

declare(strict_types=1);

namespace MonModule\Application\Service;

use MonModule\Domain\Entity\Item;
use MonModule\Domain\Repository\ItemRepositoryInterface;

class ItemService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository
    ) {
    }

    public function findActive(): array
    {
        return $this->repository->findByActive(true);
    }
}
```

---

## Configuration

Le fichier `.php-cs-fixer.php` configure les règles :

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('Core');  // Géré par WEDEV

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        
        // Règles supplémentaires
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'void_return' => true,
    ])
    ->setFinder($finder);
```

---

## Règles courantes

### Syntaxe de tableaux

```php
// ❌ Ancienne syntaxe
$items = array('a', 'b', 'c');

// ✅ Syntaxe courte
$items = ['a', 'b', 'c'];
```

### Imports ordonnés

```php
// ❌ Désordonnés
use Symfony\Component\Form\FormInterface;
use MonModule\Domain\Entity\Item;
use PrestaShop\PrestaShop\Core\Grid\GridFactory;

// ✅ Triés alphabétiquement
use MonModule\Domain\Entity\Item;
use PrestaShop\PrestaShop\Core\Grid\GridFactory;
use Symfony\Component\Form\FormInterface;
```

### Imports non utilisés

```php
// ❌ Import inutilisé
use MonModule\Domain\Entity\Category;  // Non utilisé!
use MonModule\Domain\Entity\Item;

class ItemService
{
    public function getItem(): Item { }
}

// ✅ Import supprimé automatiquement
use MonModule\Domain\Entity\Item;
```

### Virgule finale

```php
// ✅ Virgule après le dernier élément
$config = [
    'name' => 'value',
    'active' => true,
];  // ← Virgule ici
```

### Retour void explicite

```php
// ❌ Retour void implicite
public function save(Item $item)
{
    $this->repository->save($item);
}

// ✅ Retour void explicite
public function save(Item $item): void
{
    $this->repository->save($item);
}
```

---

## Intégration IDE

### VS Code / Cursor

Extension : **PHP CS Fixer**

```json
// .vscode/settings.json
{
    "php-cs-fixer.executablePath": "./vendor/bin/php-cs-fixer",
    "php-cs-fixer.onsave": true,
    "editor.formatOnSave": true
}
```

### PhpStorm

1. **Settings** → **PHP** → **Quality Tools**
2. Configurez le chemin vers PHP-CS-Fixer
3. Activez "Reformat on save"

---

## Ignorer des fichiers

### Fichiers générés

```php
// .php-cs-fixer.php
$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'var',
        'vendor',
        'node_modules',
        'src/Core',  // WEDEV Core
    ]);
```

### Ignorer une ligne

```php
// Pour une ligne spécifique
// @codingStandardsIgnoreLine
$legacyCode = somethingUgly();

// Pour un bloc
// @codingStandardsIgnoreStart
$legacy = ugly_code();
$more = ugly_code_2();
// @codingStandardsIgnoreEnd
```

---

## CI/CD

Le style est vérifié automatiquement :

```yaml
# .github/workflows/tests.yml
jobs:
  cs-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: composer cs-check
```

Une erreur de style **bloque** le merge.

---

## Workflow recommandé

### Avant de commiter

```bash
# 1. Corriger le style
composer cs-fix

# 2. Vérifier qu'il n'y a plus d'erreurs
composer cs-check

# 3. Commiter
git add .
git commit -m "feat: add new feature"
```

### Avec pre-commit hook

```bash
# .git/hooks/pre-commit
#!/bin/bash
composer cs-check
if [ $? -ne 0 ]; then
    echo "❌ Style errors found. Run 'composer cs-fix'"
    exit 1
fi
```

---

**Prochaine étape** : [Refactoring](./refactoring.md)


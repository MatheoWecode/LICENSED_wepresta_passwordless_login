# Analyse Statique (Static Analysis)

> Référence technique détaillée : [.cursor/rules/011-module-quality.mdc](../../.cursor/rules/011-module-quality.mdc)

L'analyse statique examine le code **sans l'exécuter** pour détecter des erreurs potentielles.

## Qu'est-ce que l'analyse statique ?

Contrairement aux tests qui exécutent le code, l'analyse statique :
- Lit le code source
- Analyse les types, les flux, les dépendances
- Détecte des bugs **avant l'exécution**

```
┌─────────────────────────────────────────────────────────────┐
│  Code Source                                                │
│       ↓                                                     │
│  PHPStan analyse                                            │
│       ↓                                                     │
│  ✗ Erreur: Argument 1 de getData() attendu int, reçu string│
│  ✗ Erreur: Variable $user peut être null                   │
│       ↓                                                     │
│  Correction avant exécution                                 │
└─────────────────────────────────────────────────────────────┘
```

---

## PHPStan

Ce module utilise **PHPStan**, l'outil d'analyse statique le plus populaire pour PHP.

### Lancer l'analyse

```bash
# Depuis la racine du module
composer phpstan

# Avec DDEV
ddev exec composer phpstan
```

### Sortie typique

```
 ------ --------------------------------------------------------
  Line   src/Application/Service/ItemService.php
 ------ --------------------------------------------------------
  45     Parameter #1 $id of method findById() expects int,
         string given.
  67     Property $items is never read, only written.
 ------ --------------------------------------------------------

 [ERROR] Found 2 errors
```

---

## Configuration

Le fichier `phpstan.neon` configure PHPStan :

```neon
parameters:
    # Niveau de rigueur (0-8)
    level: 6
    
    # Chemins à analyser
    paths:
        - src/
        - monmodule.php
    
    # Chemins à ignorer
    excludePaths:
        - src/Core/  # Géré par WEDEV
    
    # Bootstrap PrestaShop
    bootstrapFiles:
        - vendor/autoload.php
    
    # Ignorer certaines erreurs
    ignoreErrors:
        - '#Call to an undefined method#'
```

---

## Niveaux de rigueur

PHPStan propose 9 niveaux (0-9) :

| Niveau | Vérifications |
|--------|---------------|
| 0 | Erreurs de base |
| 1 | Variables inconnues |
| 2 | Méthodes inconnues |
| 3 | Types de retour |
| 4 | Types de retour stricts |
| 5 | Arguments typés |
| **6** | Vérification des nullables |
| 7 | Unions de types |
| **8** | Vérifications strictes |

### Niveau recommandé

Ce module est configuré au **niveau 6** (bon équilibre rigueur/praticité).

<details>
<summary>💡 Passer au niveau 8</summary>

Le niveau 8 est le plus strict. Pour y passer :

1. Modifiez `phpstan.neon` :
```neon
parameters:
    level: 8
```

2. Corrigez les nouvelles erreurs (principalement liées aux types mixtes)

3. Utilisez les stubs pour les classes PrestaShop non typées

</details>

---

## Types d'erreurs courantes

### Variable peut être null

```php
// ❌ Erreur: $user peut être null
$user = $this->repository->findById($id);
return $user->getName();  // Erreur si $user est null

// ✅ Solution
$user = $this->repository->findById($id);
if ($user === null) {
    throw new UserNotFoundException($id);
}
return $user->getName();
```

### Mauvais type d'argument

```php
// ❌ Erreur: expects int, string given
$price = Tools::getValue('price');  // Retourne string|false
$this->service->setPrice($price);   // Attend int

// ✅ Solution
$price = (int) Tools::getValue('price');
$this->service->setPrice($price);
```

### Méthode inexistante

```php
// ❌ Erreur: méthode non définie
$product->getCustomField();  // N'existe pas

// ✅ Vérifier que la méthode existe ou utiliser les stubs
```

---

## Stubs PrestaShop

Les classes PrestaShop ne sont pas toujours bien typées. Utilisez des **stubs** :

```
stubs/
├── Configuration.stub.php
├── Context.stub.php
└── Product.stub.php
```

Exemple de stub :

```php
// stubs/Configuration.stub.php
<?php

class Configuration
{
    /**
     * @param string $key
     * @param int|null $idLang
     * @return string|false
     */
    public static function get($key, $idLang = null) {}
}
```

Configuration dans `phpstan.neon` :

```neon
parameters:
    stubFiles:
        - stubs/Configuration.stub.php
```

---

## Ignorer des erreurs

Parfois, certaines erreurs sont des faux positifs :

### Dans le code

```php
/** @phpstan-ignore-next-line */
$result = $legacyMethod();  // Méthode dynamique
```

### Dans la configuration

```neon
parameters:
    ignoreErrors:
        # Ignorer une erreur spécifique
        - '#Call to an undefined method ObjectModel::save#'
        
        # Ignorer dans un fichier
        -
            message: '#Variable \$context might not be defined#'
            path: src/Legacy/*
```

---

## Intégration continue

PHPStan s'exécute automatiquement dans le pipeline CI :

```yaml
# .github/workflows/tests.yml
jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: composer phpstan
```

Une erreur PHPStan **bloque** le merge.

---

## Bonnes pratiques

### Typer tout le code

```php
// ✅ Typé correctement
public function getItem(int $id): ?Item
{
    return $this->repository->findById($id);
}

// ❌ Non typé
public function getItem($id)
{
    return $this->repository->findById($id);
}
```

### Éviter les mixed

```php
// ❌ Type mixed
public function process(mixed $data): mixed

// ✅ Types précis
public function process(array $data): ProcessResult
```

### Utiliser les assertions

```php
use Webmozart\Assert\Assert;

public function process(array $items): void
{
    Assert::allIsInstanceOf($items, Item::class);
    // PHPStan sait maintenant que $items contient des Item
}
```

---

**Prochaine étape** : [Tests unitaires](./unit-testing.md)


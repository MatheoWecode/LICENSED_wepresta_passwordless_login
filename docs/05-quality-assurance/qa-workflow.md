# Workflow QA Complet

Checklist et workflow pour garantir la qualité du code avant chaque commit.

## Commande tout-en-un

```bash
# Exécute: cs-check + phpstan + phpunit
composer test
```

Cette commande doit **passer sans erreur** avant tout commit.

---

## Workflow détaillé

### Avant de coder

```bash
# 1. Mettre à jour les dépendances
composer install
npm install

# 2. S'assurer que tout fonctionne
composer test
```

### Pendant le développement

```bash
# Watch des assets (terminal 1)
npm run watch

# Vérifications rapides pendant le dev
composer phpstan          # Analyse statique
composer cs-check        # Style
```

### Avant de commiter

```bash
# 1. Tout le workflow QA
composer test

# 2. Si erreurs de style
composer cs-fix

# 3. Re-vérifier
composer test

# 4. Commiter
git add .
git commit -m "feat: description"
```

---

## Checklist avant commit

### Code

- [ ] `composer cs-check` passe (style)
- [ ] `composer phpstan` passe (analyse statique)
- [ ] `composer phpunit` passe (tests)
- [ ] Pas de `var_dump()`, `dd()`, `die()`
- [ ] Pas de `console.log()` dans le JS

### Documentation

- [ ] Nouvelles méthodes documentées (PHPDoc)
- [ ] README mis à jour si nouvelle fonctionnalité
- [ ] CHANGELOG mis à jour

### Base de données

- [ ] Script d'upgrade créé si modification de schéma
- [ ] SQL utilise `pSQL()` pour les chaînes

### Traductions

- [ ] Textes utilisent `$this->trans()`
- [ ] Clés de traduction cohérentes

### Sécurité

- [ ] Entrées utilisateur validées
- [ ] Tokens CSRF vérifiés
- [ ] Permissions admin vérifiées

---

## Scripts Composer

Le fichier `composer.json` définit les scripts :

```json
{
    "scripts": {
        "cs-check": "php-cs-fixer fix --dry-run --diff",
        "cs-fix": "php-cs-fixer fix",
        "phpstan": "phpstan analyse -c phpstan.neon",
        "phpunit": "phpunit",
        "phpunit-coverage": "phpunit --coverage-html var/coverage",
        "rector-dry": "rector process --dry-run",
        "rector": "rector process",
        "test": [
            "@cs-check",
            "@phpstan",
            "@phpunit"
        ]
    }
}
```

---

## Automatisation

### Pre-commit Hook

Créez `.git/hooks/pre-commit` :

```bash
#!/bin/bash

echo "🔍 Vérification du code..."

# Style
echo "  → Style (PHP-CS-Fixer)"
composer cs-check --quiet
if [ $? -ne 0 ]; then
    echo "❌ Erreurs de style. Lancez 'composer cs-fix'"
    exit 1
fi

# Analyse statique
echo "  → Analyse statique (PHPStan)"
composer phpstan --quiet
if [ $? -ne 0 ]; then
    echo "❌ Erreurs PHPStan"
    exit 1
fi

# Tests
echo "  → Tests (PHPUnit)"
composer phpunit --quiet
if [ $? -ne 0 ]; then
    echo "❌ Tests échoués"
    exit 1
fi

echo "✅ Toutes les vérifications passent"
exit 0
```

Rendre exécutable :
```bash
chmod +x .git/hooks/pre-commit
```

### Husky (alternative)

```bash
npm install husky --save-dev
npx husky install
npx husky add .husky/pre-commit "composer test"
```

---

## Résolution des erreurs courantes

### PHPStan

| Erreur | Solution |
|--------|----------|
| Variable might not be defined | Initialiser la variable |
| Cannot access property on null | Ajouter une vérification null |
| Return type mismatch | Corriger le type de retour |

### PHP-CS-Fixer

| Erreur | Solution |
|--------|----------|
| Expected 1 blank line | Ajouter/supprimer des lignes vides |
| Trailing whitespace | Supprimer espaces en fin de ligne |
| Missing strict_types | Ajouter `declare(strict_types=1);` |

### PHPUnit

| Erreur | Solution |
|--------|----------|
| Assertion failed | Corriger le code ou le test |
| Class not found | `composer dump-autoload` |
| Mock not configured | Configurer le mock correctement |

---

## Métriques de qualité

### Objectifs recommandés

| Métrique | Objectif | Commande |
|----------|----------|----------|
| Coverage | ≥ 80% | `composer phpunit-coverage` |
| PHPStan | Level 6 | `composer phpstan` |
| CS-Fixer | 0 erreur | `composer cs-check` |

### Tableau de bord (exemple)

```
╔═══════════════════════════════════════════════════════╗
║                   Qualité du Code                     ║
╠═══════════════════════════════════════════════════════╣
║  PHPStan Level 6      ████████████████████  100%     ║
║  Code Coverage        ████████████████░░░░   82%     ║
║  Style PSR-12         ████████████████████  100%     ║
║  Tests Unitaires      ████████████████████   45/45   ║
╚═══════════════════════════════════════════════════════╝
```

---

## Workflow CI/CD

Les mêmes vérifications s'exécutent dans le pipeline :

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install
      
      - name: Code style
        run: composer cs-check
      
      - name: Static analysis
        run: composer phpstan
      
      - name: Unit tests
        run: composer phpunit
```

Toute erreur **bloque le merge**.

---

**Prochaine section** : [CI/CD](../06-ci-cd/)


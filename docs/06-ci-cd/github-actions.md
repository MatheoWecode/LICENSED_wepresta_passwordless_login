# GitHub Actions

Introduction à l'intégration continue avec GitHub Actions.

## Qu'est-ce que CI/CD ?

- **CI** (Continuous Integration) : Tests automatiques à chaque push
- **CD** (Continuous Deployment) : Déploiement automatique

```
┌─────────────────────────────────────────────────────────────┐
│  git push                                                   │
│       ↓                                                     │
│  GitHub Actions déclenché                                   │
│       ↓                                                     │
│  ┌─────────────────────────────────────────────┐           │
│  │ Job 1: Code Style    ✓                      │           │
│  │ Job 2: PHPStan       ✓                      │           │
│  │ Job 3: Tests         ✓                      │           │
│  └─────────────────────────────────────────────┘           │
│       ↓                                                     │
│  ✓ Tous les checks passent → PR mergeable                  │
└─────────────────────────────────────────────────────────────┘
```

---

## Fichier de workflow

Les workflows sont dans `.github/workflows/` :

```yaml
# .github/workflows/tests.yml

name: Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, intl, pdo_mysql
      
      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Code style check
        run: composer cs-check
      
      - name: Static analysis
        run: composer phpstan
      
      - name: Run tests
        run: composer phpunit
```

---

## Anatomie d'un workflow

### Déclencheurs (`on`)

```yaml
on:
  # À chaque push
  push:
    branches: [main, develop]
  
  # Sur les Pull Requests
  pull_request:
    branches: [main]
  
  # Manuellement
  workflow_dispatch:
  
  # Planifié (cron)
  schedule:
    - cron: '0 0 * * 0'  # Chaque dimanche
```

### Jobs

```yaml
jobs:
  # Nom du job
  test:
    # Environnement
    runs-on: ubuntu-latest
    
    # Étapes
    steps:
      - name: Description de l'étape
        run: commande
```

### Steps

```yaml
steps:
  # Action prédéfinie
  - name: Checkout
    uses: actions/checkout@v4
  
  # Commande shell
  - name: Install
    run: composer install
  
  # Plusieurs commandes
  - name: Build
    run: |
      npm install
      npm run build
```

---

## Workflow complet du module

```yaml
# .github/workflows/ci.yml

name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  # =========================================
  # Code Style
  # =========================================
  cs-check:
    name: Code Style
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install --prefer-dist
      
      - name: Check code style
        run: composer cs-check

  # =========================================
  # Static Analysis
  # =========================================
  phpstan:
    name: PHPStan
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install --prefer-dist
      
      - name: Run PHPStan
        run: composer phpstan

  # =========================================
  # Unit Tests
  # =========================================
  phpunit:
    name: PHPUnit
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --prefer-dist
      
      - name: Run tests
        run: composer phpunit
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml

  # =========================================
  # Build Assets
  # =========================================
  build:
    name: Build Assets
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Build
        run: npm run build
```

---

## Status Checks

Les jobs créent des "checks" sur les Pull Requests :

```
┌─────────────────────────────────────────────┐
│  Pull Request #42                           │
├─────────────────────────────────────────────┤
│  ✓ cs-check      passed                     │
│  ✓ phpstan       passed                     │
│  ✗ phpunit       failed                     │
│  ✓ build         passed                     │
├─────────────────────────────────────────────┤
│  [Merge blocked - 1 check failed]           │
└─────────────────────────────────────────────┘
```

### Protéger la branche main

Dans GitHub : **Settings** → **Branches** → **Add rule**

- ✓ Require status checks to pass
- ✓ Require branches to be up to date

---

## Secrets

Pour les variables sensibles :

### Définir un secret

**Settings** → **Secrets and variables** → **Actions** → **New secret**

### Utiliser un secret

```yaml
steps:
  - name: Deploy
    env:
      API_KEY: ${{ secrets.API_KEY }}
    run: deploy --key=$API_KEY
```

---

## Cache

Accélérer les builds avec le cache :

```yaml
- name: Cache Composer
  uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
    restore-keys: |
      ${{ runner.os }}-composer-

- name: Cache npm
  uses: actions/cache@v3
  with:
    path: node_modules
    key: ${{ runner.os }}-node-${{ hashFiles('**/package-lock.json') }}
```

---

## Voir les résultats

1. Allez sur l'onglet **Actions** du repository
2. Cliquez sur le workflow
3. Consultez les logs de chaque job

### En cas d'échec

1. Cliquez sur le job en échec
2. Développez l'étape qui a échoué
3. Lisez les logs d'erreur
4. Corrigez et re-poussez

---

**Prochaine étape** : [Tests automatisés](./automated-tests.md)


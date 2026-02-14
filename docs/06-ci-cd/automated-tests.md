# Tests Automatisés

Comment les tests s'exécutent dans le pipeline CI.

## Matrice de tests

Le module est testé sur plusieurs versions de PHP :

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.1', '8.2', '8.3']
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP ${{ matrix.php-version }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
      
      - name: Run tests
        run: composer phpunit
```

Résultat :

```
┌───────────────────────────────────────────────────────┐
│  test (8.1)   ████████████████████  ✓ passed         │
│  test (8.2)   ████████████████████  ✓ passed         │
│  test (8.3)   ████████████████████  ✓ passed         │
└───────────────────────────────────────────────────────┘
```

---

## Types de tests dans le pipeline

### 1. Tests unitaires

```yaml
- name: Unit tests
  run: ./vendor/bin/phpunit --testsuite=Unit
```

- Rapides (secondes)
- Pas de dépendances externes
- Exécutés à chaque push

### 2. Tests d'intégration

```yaml
- name: Integration tests
  run: ./vendor/bin/phpunit --testsuite=Integration
  env:
    DATABASE_URL: mysql://test:test@localhost/test
```

- Nécessitent une base de données
- Plus lents
- Exécutés sur les PR vers main

### 3. Tests de coverage

```yaml
- name: Tests with coverage
  run: ./vendor/bin/phpunit --coverage-clover coverage.xml

- name: Upload to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage.xml
```

---

## Configuration avec base de données

```yaml
jobs:
  integration:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql
      
      - name: Install dependencies
        run: composer install
      
      - name: Run integration tests
        run: ./vendor/bin/phpunit --testsuite=Integration
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/test
```

---

## Rapport de tests

### Format JUnit

```yaml
- name: Run tests
  run: ./vendor/bin/phpunit --log-junit test-results.xml

- name: Upload test results
  uses: actions/upload-artifact@v3
  if: always()
  with:
    name: test-results
    path: test-results.xml
```

### Affichage dans GitHub

```yaml
- name: Publish Test Report
  uses: dorny/test-reporter@v1
  if: always()
  with:
    name: PHPUnit Tests
    path: test-results.xml
    reporter: java-junit
```

---

## Tests sur différents OS

```yaml
strategy:
  matrix:
    os: [ubuntu-latest, windows-latest, macos-latest]
    php: ['8.1', '8.2']

runs-on: ${{ matrix.os }}
```

> Note : Pour un module PrestaShop, Ubuntu suffit généralement.

---

## Fail-fast

Par défaut, si un test échoue, les autres continuent :

```yaml
strategy:
  fail-fast: false  # Continuer même si un test échoue
  matrix:
    php: ['8.1', '8.2', '8.3']
```

Ou arrêter tout :

```yaml
strategy:
  fail-fast: true  # Arrêter dès le premier échec
```

---

## Tests conditionnels

### Seulement sur main

```yaml
- name: Integration tests
  if: github.ref == 'refs/heads/main'
  run: ./vendor/bin/phpunit --testsuite=Integration
```

### Seulement sur PR

```yaml
- name: Full test suite
  if: github.event_name == 'pull_request'
  run: composer test
```

### Seulement si fichiers modifiés

```yaml
- name: Check changed files
  id: changes
  uses: dorny/paths-filter@v2
  with:
    filters: |
      src:
        - 'src/**'
        - 'tests/**'

- name: Run tests
  if: steps.changes.outputs.src == 'true'
  run: composer phpunit
```

---

## Timeout

Éviter les tests bloqués :

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    timeout-minutes: 10
    
    steps:
      - name: Run tests
        timeout-minutes: 5
        run: composer phpunit
```

---

## Debug

### Activer le debug

```yaml
steps:
  - name: Debug info
    run: |
      php -v
      composer --version
      echo "PHP Extensions:"
      php -m
```

### Conserver les artefacts

```yaml
- name: Upload logs on failure
  if: failure()
  uses: actions/upload-artifact@v3
  with:
    name: logs
    path: |
      var/logs/
      var/cache/
```

---

## Notifications

### Slack

```yaml
- name: Notify Slack
  if: failure()
  uses: slackapi/slack-github-action@v1
  with:
    payload: |
      {
        "text": "Tests failed on ${{ github.repository }}"
      }
  env:
    SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### Email

GitHub envoie automatiquement des emails en cas d'échec (configurable dans les paramètres).

---

**Prochaine étape** : [Release Process](./release-process.md)


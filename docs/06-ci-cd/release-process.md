# Release Process

Comment préparer et publier une nouvelle version du module.

## Versionning sémantique

Ce module suit le **Semantic Versioning** (SemVer) :

```
MAJOR.MINOR.PATCH

Exemples:
  1.0.0 → 1.0.1  (patch: correction de bug)
  1.0.1 → 1.1.0  (minor: nouvelle fonctionnalité)
  1.1.0 → 2.0.0  (major: breaking change)
```

| Type | Quand l'incrémenter |
|------|---------------------|
| MAJOR | Changements incompatibles |
| MINOR | Nouvelle fonctionnalité rétrocompatible |
| PATCH | Correction de bug |

---

## Checklist avant release

### Code

- [ ] Tous les tests passent (`composer test`)
- [ ] Assets compilés en production (`npm run build`)
- [ ] Pas de code de debug (`dd()`, `var_dump()`)
- [ ] CHANGELOG mis à jour

### Documentation

- [ ] README à jour
- [ ] Version mise à jour dans le module
- [ ] Notes de version rédigées

### Fichiers

- [ ] Version dans `monmodule.php`
- [ ] Version dans `composer.json`
- [ ] Version dans `package.json`
- [ ] Script d'upgrade si nécessaire

---

## Mettre à jour la version

### 1. Module principal

```php
// monmodule.php
public function __construct()
{
    $this->name = 'monmodule';
    $this->version = '1.2.0';  // ← Mettre à jour
    // ...
}
```

### 2. Composer

```json
{
    "name": "wecode/monmodule",
    "version": "1.2.0"
}
```

### 3. Package.json

```json
{
    "name": "monmodule",
    "version": "1.2.0"
}
```

---

## Changelog

Maintenez un fichier `CHANGELOG.md` :

```markdown
# Changelog

## [1.2.0] - 2024-12-22

### Added
- Export CSV des items (#42)
- Support multi-boutique (#38)

### Changed
- Amélioration des performances du cache
- Mise à jour de l'interface admin

### Fixed
- Correction du calcul de TVA (#45)
- Fix du hook displayHome sur PS 8.1

### Deprecated
- Méthode `getOldItems()` sera supprimée en 2.0

## [1.1.0] - 2024-11-15
...
```

---

## Créer une release GitHub

### Via l'interface

1. Allez sur **Releases** → **Draft a new release**
2. **Tag** : `v1.2.0`
3. **Title** : `v1.2.0`
4. **Description** : Copiez le changelog
5. **Attach** : Le fichier ZIP du module
6. **Publish release**

### Via CLI

```bash
# Créer le tag
git tag -a v1.2.0 -m "Version 1.2.0"

# Pusher le tag
git push origin v1.2.0
```

---

## Build automatique

Workflow pour créer un ZIP à chaque release :

```yaml
# .github/workflows/release.yml

name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
      
      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm ci
      
      - name: Build assets
        run: npm run build
      
      - name: Create ZIP
        run: |
          mkdir -p dist
          zip -r dist/monmodule-${{ github.ref_name }}.zip . \
            -x "*.git*" \
            -x "node_modules/*" \
            -x "_dev/*" \
            -x "tests/*" \
            -x "var/*" \
            -x "*.md" \
            -x "phpstan.neon" \
            -x "phpunit.xml" \
            -x ".php-cs-fixer.php" \
            -x "rector.php"
      
      - name: Upload to release
        uses: softprops/action-gh-release@v1
        with:
          files: dist/monmodule-${{ github.ref_name }}.zip
```

---

## Publication sur PrestaShop Addons

### Prérequis

1. Compte vendeur sur [addons.prestashop.com](https://addons.prestashop.com)
2. Module validé techniquement
3. Assets marketing (logo, captures d'écran)

### Checklist Addons

| Critère | Requis |
|---------|--------|
| Compatibilité PS 8.0+ | ✓ |
| PHP 8.1+ | ✓ |
| Pas de code malveillant | ✓ |
| Traductions | Anglais minimum |
| Logo 57x57px | ✓ |
| Captures d'écran | 3 minimum |
| Documentation | ✓ |

### Process

1. **Créer le produit** sur Addons
2. **Uploader le ZIP**
3. **Validation technique** (2-5 jours)
4. **Publication**

---

## Contenu du ZIP de release

Le ZIP final doit contenir :

```
monmodule/
├── monmodule.php
├── composer.json
├── config/
├── controllers/
├── src/
├── sql/
├── translations/
├── upgrade/
├── views/
│   ├── css/
│   ├── js/
│   ├── dist/        ← Assets compilés
│   └── templates/
└── logo.png
```

### Exclus du ZIP

- `node_modules/`
- `_dev/`
- `tests/`
- `var/`
- `.git/`
- Fichiers de config (phpstan, phpunit, etc.)
- Fichiers markdown

---

## Script de release

```bash
#!/bin/bash
# scripts/release.sh

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: ./scripts/release.sh 1.2.0"
    exit 1
fi

echo "📦 Préparation de la release $VERSION..."

# 1. Vérifier les tests
composer test || exit 1

# 2. Build des assets
npm run build || exit 1

# 3. Mettre à jour les versions
sed -i "s/\$this->version = '.*'/\$this->version = '$VERSION'/" monmodule.php

# 4. Commit
git add .
git commit -m "release: v$VERSION"

# 5. Tag
git tag -a "v$VERSION" -m "Version $VERSION"

# 6. Push
git push origin main
git push origin "v$VERSION"

echo "✅ Release v$VERSION créée!"
echo "→ Le workflow GitHub va créer le ZIP automatiquement"
```

---

**Prochaine section** : [API Integrations](../07-api-integrations/)


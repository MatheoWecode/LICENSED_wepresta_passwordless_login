# Mise à jour du Core partagé

> Référence technique détaillée : [.cursor/rules/012-module-core.mdc](../../.cursor/rules/012-module-core.mdc)

Ce module utilise un **Core partagé** maintenu par WEDEV CLI. Voici comment le mettre à jour.

## Qu'est-ce que le Core partagé ?

Le dossier `src/Core/` contient des classes utilitaires réutilisables :

```
src/Core/
├── Adapter/
│   ├── ConfigurationAdapter.php    # Accès Configuration
│   └── ContextAdapter.php          # Accès Context
├── Exception/
│   ├── ModuleException.php         # Exception de base
│   ├── EntityNotFoundException.php
│   └── ValidationException.php
├── Repository/
│   └── AbstractRepository.php      # CRUD de base
├── Service/
│   └── CacheService.php            # Cache unifié
└── Trait/
    ├── ModuleAwareTrait.php
    ├── LoggerTrait.php
    └── TranslatorTrait.php
```

> ⚠️ **Ne modifiez jamais** les fichiers dans `src/Core/`. Ils sont écrasés lors des mises à jour.

---

## Vérifier la version actuelle

La version du Core est dans le fichier `.wedev-core-version` :

```bash
cat .wedev-core-version
# 1.0.0
```

---

## Mettre à jour le Core

### Via WEDEV CLI

```bash
# Depuis la racine du projet PrestaShop
cd /chemin/vers/prestashop

# Lancer la mise à jour
wedev ps module
# → 🔄 Mettre à jour le Core
```

Le CLI :
1. Détecte les modules avec Core WEDEV
2. Affiche les versions actuelles
3. Propose la mise à jour

### Options disponibles

```
? Module à mettre à jour:
  ❯ monmodule (v1.0.0)
    autremodule (v1.0.0)
    Tous les modules
```

### Ce qui est mis à jour

- Tous les fichiers dans `src/Core/`
- Le fichier `.wedev-core-version`
- Les namespaces sont adaptés automatiquement

---

## Personnaliser le Core

Si vous avez besoin de fonctionnalités supplémentaires, **n'éditez pas** le Core. Étendez-le :

### Exemple : Étendre ConfigurationAdapter

```php
// src/Infrastructure/Adapter/ExtendedConfigAdapter.php

namespace MonModule\Infrastructure\Adapter;

use MonModule\Core\Adapter\ConfigurationAdapter;

class ExtendedConfigAdapter extends ConfigurationAdapter
{
    private const PREFIX = 'MONMODULE_';
    
    /**
     * Récupère une config avec préfixe automatique.
     */
    public function getModuleConfig(string $key): mixed
    {
        return $this->get(self::PREFIX . $key);
    }
    
    /**
     * Définit une config avec préfixe automatique.
     */
    public function setModuleConfig(string $key, mixed $value): bool
    {
        return $this->set(self::PREFIX . $key, $value);
    }
}
```

### Enregistrer l'extension

Dans `config/services.yml` :

```yaml
services:
  MonModule\Infrastructure\Adapter\ExtendedConfigAdapter:
    public: true
```

---

## Fichiers à ne jamais écraser

Lors d'une mise à jour manuelle depuis un repo Git :

| Fichier/Dossier | Action |
|-----------------|--------|
| `src/Core/` | Peut être écrasé |
| `src/Application/` | **NE PAS écraser** |
| `src/Domain/` | **NE PAS écraser** |
| `src/Infrastructure/` | **NE PAS écraser** |
| `src/Presentation/` | **NE PAS écraser** |
| `config/services.yml` | Merger manuellement |
| `composer.json` | Merger manuellement |
| `sql/` | **NE PAS écraser** |

---

## Résoudre les conflits

Si le Core a été modifié accidentellement :

### 1. Sauvegarder les modifications

```bash
git diff src/Core/ > my-core-changes.patch
```

### 2. Réinitialiser le Core

```bash
# Via WEDEV CLI
wedev ps module
# → 🔄 Mettre à jour le Core

# Ou manuellement
rm -rf src/Core/
# Puis mettre à jour
```

### 3. Appliquer les modifications dans une extension

Créez des classes qui étendent le Core au lieu de le modifier.

---

## Changelog des mises à jour

Les changements du Core sont documentés dans :

- Le [CHANGELOG de WEDEV CLI](https://github.com/wecode/wedev-cli/releases)
- Le fichier `CHANGELOG-CORE.md` (si présent)

### Vérifier les breaking changes

Avant de mettre à jour en production :

1. Lisez le changelog
2. Testez dans un environnement de développement
3. Vérifiez que vos extensions fonctionnent toujours

---

## Automatisation (CI/CD)

Pour automatiser les vérifications de version :

```yaml
# .github/workflows/check-core.yml
name: Check Core Version

on: [push]

jobs:
  check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Check Core version
        run: |
          CURRENT=$(cat .wedev-core-version)
          LATEST=$(curl -s https://api.github.com/repos/wecode/wedev-cli/releases/latest | jq -r '.tag_name')
          if [ "$CURRENT" != "$LATEST" ]; then
            echo "::warning::Core update available: $CURRENT -> $LATEST"
          fi
```

---

**Prochaine section** : [Architecture](../04-architecture/)


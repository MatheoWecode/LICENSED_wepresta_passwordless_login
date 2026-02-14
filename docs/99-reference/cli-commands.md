# Commandes CLI

Référence des commandes pour le développement.

## WEDEV CLI

### Installation

```bash
npm install -g @wecode/wedev-cli
```

### Module

| Commande | Description |
|----------|-------------|
| `wedev ps module` | Menu gestion modules |
| `wedev ps module new` | Créer un nouveau module |
| `wedev ps module --update-core` | Mettre à jour le Core partagé |

### Développement

| Commande | Description |
|----------|-------------|
| `wedev ps dev-mode` | Activer le mode développement |
| `wedev ps cache` | Vider le cache |
| `wedev ps hooks` | Référence des hooks |

---

## PrestaShop Console

### Modules

```bash
# Installer un module
bin/console prestashop:module install monmodule

# Désinstaller
bin/console prestashop:module uninstall monmodule

# Activer
bin/console prestashop:module enable monmodule

# Désactiver
bin/console prestashop:module disable monmodule

# Reset (uninstall + install)
bin/console prestashop:module reset monmodule

# Lister les modules
bin/console prestashop:module list

# Status d'un module
bin/console prestashop:module status monmodule
```

### Cache

```bash
# Vider le cache
bin/console cache:clear

# Warmup du cache
bin/console cache:warmup
```

### Debug

```bash
# Routeur
bin/console debug:router

# Container
bin/console debug:container

# Configuration
bin/console debug:config
```

---

## Composer

### Dépendances

```bash
# Installer les dépendances
composer install

# Installer en production
composer install --no-dev --optimize-autoloader

# Mettre à jour
composer update

# Ajouter une dépendance
composer require vendor/package

# Ajouter en dev
composer require --dev vendor/package
```

### Autoload

```bash
# Regénérer l'autoload
composer dump-autoload

# Optimisé
composer dump-autoload --optimize
```

### Scripts personnalisés

```bash
# Qualité
composer test          # cs-check + phpstan + phpunit
composer cs-check      # Vérifier le style
composer cs-fix        # Corriger le style
composer phpstan       # Analyse statique
composer phpunit       # Tests unitaires
composer phpunit-coverage  # Tests avec coverage

# Refactoring
composer rector-dry    # Voir les changements
composer rector        # Appliquer les changements
```

---

## npm

### Dépendances

```bash
# Installer
npm install

# Installer en production
npm ci

# Ajouter
npm install package

# Ajouter en dev
npm install --save-dev package
```

### Build

```bash
# Développement avec watch
npm run watch

# Build développement
npm run dev

# Build production
npm run build
```

---

## DDEV

### Environnement

```bash
# Démarrer
ddev start

# Arrêter
ddev stop

# Redémarrer
ddev restart

# Status
ddev status

# Ouvrir dans le navigateur
ddev launch
```

### Commandes dans le container

```bash
# Exécuter une commande
ddev exec commande

# Exemples
ddev exec bin/console cache:clear
ddev exec composer install
ddev exec rm -rf var/cache/*
```

### Base de données

```bash
# Client MySQL
ddev mysql

# Requête directe
ddev mysql -e "SELECT * FROM ps_module"

# Import
ddev mysql < dump.sql

# Export
ddev mysqldump > backup.sql
```

### Xdebug

```bash
# Activer
ddev xdebug on

# Désactiver
ddev xdebug off

# Status
ddev xdebug status
```

---

## Git

### Workflow

```bash
# Nouvelle branche
git checkout -b feature/ma-feature

# Commit
git add .
git commit -m "feat: description"

# Push
git push origin feature/ma-feature

# Rebase sur main
git checkout main
git pull origin main
git checkout feature/ma-feature
git rebase main
```

### Tags

```bash
# Créer un tag
git tag -a v1.0.0 -m "Version 1.0.0"

# Pusher le tag
git push origin v1.0.0
```

---

## Raccourcis utiles

Ajoutez dans votre `.bashrc` ou `.zshrc` :

```bash
# PrestaShop
alias pscache="rm -rf var/cache/*"
alias psmod="bin/console prestashop:module"
alias psreset="bin/console prestashop:module reset"

# DDEV
alias de="ddev exec"
alias dm="ddev mysql"
alias dl="ddev launch"

# Module
alias mtest="composer test"
alias mwatch="npm run watch"
alias mbuild="npm run build"
```

---

**Prochaine étape** : [Clés de configuration](./configuration-keys.md)


# Prérequis

Ce module nécessite un environnement de développement moderne. Voici les outils requis.

## Versions minimales

| Outil | Version minimale | Vérification |
|-------|------------------|--------------|
| PHP | 8.1+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20+ | `node -v` |
| npm | 10+ | `npm -v` |
| PrestaShop | 8.0+ | Back-office → Paramètres avancés |

## PHP

### Extensions requises

```bash
# Vérifier les extensions
php -m | grep -E "(curl|gd|intl|json|mbstring|openssl|pdo_mysql|zip)"
```

Extensions nécessaires :
- `curl` — requêtes HTTP
- `gd` ou `imagick` — manipulation d'images
- `intl` — internationalisation
- `json` — encodage/décodage JSON
- `mbstring` — chaînes multi-octets
- `openssl` — chiffrement
- `pdo_mysql` — base de données
- `zip` — compression

### Configuration recommandée

```ini
; php.ini recommandé pour le développement
memory_limit = 2048M
max_execution_time = 300
upload_max_filesize = 20M
post_max_size = 20M
display_errors = On
error_reporting = E_ALL
```

---

## Composer

Composer gère les dépendances PHP du module.

### Installation

```bash
# macOS (Homebrew)
brew install composer

# Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# Télécharger l'installeur sur https://getcomposer.org/download/
```

### Vérification

```bash
composer -V
# Composer version 2.x.x
```

---

## Node.js et npm

Node.js est utilisé pour compiler les assets (SCSS, JavaScript) via Webpack Encore.

### Installation recommandée : nvm

```bash
# Installer nvm (Node Version Manager)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Installer Node.js 20 LTS
nvm install 20
nvm use 20
```

### Vérification

```bash
node -v
# v20.x.x

npm -v
# 10.x.x
```

---

## DDEV (optionnel mais recommandé)

DDEV simplifie la gestion de l'environnement de développement local.

### Installation

```bash
# macOS
brew install ddev/ddev/ddev

# Linux
curl -fsSL https://ddev.com/install.sh | bash

# Windows
choco install ddev
```

### Avantages

- Configuration PHP/MySQL automatique
- Isolation des projets
- Commandes simplifiées (`ddev exec`, `ddev launch`)
- Xdebug intégré

### Configuration pour PrestaShop

```yaml
# .ddev/config.yaml
name: mon-prestashop
type: php
php_version: "8.2"
webserver_type: apache-fpm
database:
  type: mariadb
  version: "10.6"
hooks:
  post-start:
    - exec: chmod -R 777 var/cache var/logs
```

---

## IDE recommandé

### Visual Studio Code + Cursor

Extensions recommandées :
- **PHP Intelephense** — autocomplétion PHP
- **Twig Language** — support Twig
- **Smarty** — support Smarty
- **ESLint** — linting JavaScript
- **Stylelint** — linting CSS/SCSS

### PhpStorm

Configuration recommandée :
- Activer le support Symfony
- Configurer le chemin vers `vendor/`
- Activer PHP CS Fixer en format on save

---

## Vérification complète

Exécutez ce script pour vérifier votre environnement :

```bash
#!/bin/bash
echo "=== Vérification de l'environnement ==="

echo -n "PHP: "
php -v | head -1

echo -n "Composer: "
composer -V | head -1

echo -n "Node.js: "
node -v

echo -n "npm: "
npm -v

echo -n "DDEV: "
ddev version 2>/dev/null || echo "Non installé"

echo "=== Extensions PHP ==="
php -m | grep -E "(curl|gd|intl|json|mbstring|openssl|pdo_mysql|zip)"
```

---

**Prochaine étape** : [Premiers pas](./first-steps.md)


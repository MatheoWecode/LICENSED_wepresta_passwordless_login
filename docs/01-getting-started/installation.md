# Installation

Ce guide couvre les différentes méthodes d'installation du module.

## Méthode recommandée : WEDEV CLI

WEDEV CLI génère un module personnalisé avec votre nom, vos hooks et vos options.

### 1. Installer WEDEV CLI (si pas déjà fait)

```bash
npm install -g @wecode/wedev-cli
```

### 2. Générer votre module

```bash
# Depuis la racine de votre projet PrestaShop
cd /chemin/vers/prestashop

# Lancer le générateur interactif
wedev ps module
```

Le générateur vous demande :
1. **Nom technique** — ex: `mymodule` (lowercase, underscores autorisés)
2. **Nom d'affichage** — ex: `Mon Super Module`
3. **Description** — courte description du module
4. **Auteur** — votre nom ou société
5. **Catégorie** — front_office, administration, etc.
6. **Hooks** — sélection des hooks à enregistrer
7. **Options** — base de données, tests, API, etc.

### 3. Installer le module

```bash
# Avec DDEV
ddev exec bin/console prestashop:module install monmodule

# Sans DDEV
php bin/console prestashop:module install monmodule
```

---

## Méthode alternative : Installation manuelle

Si vous avez cloné le template directement :

### 1. Copier le module

```bash
# Copier dans le dossier modules/
cp -r module-starter modules/monmodule

# Renommer les fichiers
cd modules/monmodule
mv wepresta_passwordless_login.php monmodule.php
```

### 2. Rechercher/Remplacer

Remplacez dans tous les fichiers :
- `wepresta_passwordless_login` → `monmodule`
- `Wepresta_Passwordless_Login` → `MonModule`
- `WEPRESTA_PASSWORDLESS_LOGIN` → `MONMODULE`

### 3. Installer les dépendances

```bash
# PHP
composer install

# Node.js (pour les assets)
npm install
```

### 4. Installer le module

```bash
bin/console prestashop:module install monmodule
```

---

## Installation avec DDEV

Si vous utilisez DDEV pour votre environnement de développement :

### Configuration recommandée

```yaml
# .ddev/config.yaml
name: mon-prestashop
type: php
php_version: "8.2"
webserver_type: apache-fpm
database:
  type: mariadb
  version: "10.6"
```

### Commandes DDEV

```bash
# Démarrer l'environnement
ddev start

# Installer le module
ddev exec bin/console prestashop:module install monmodule

# Vider le cache
ddev exec rm -rf var/cache/*

# Accéder à la boutique
ddev launch
```

---

## Vérification de l'installation

Après installation, vérifiez :

1. **Module visible** dans Back-office → Modules → Gestionnaire de modules
2. **Pas d'erreurs** dans le fichier `var/logs/dev.log`
3. **Configuration accessible** via le bouton "Configurer" du module

### Commandes de vérification

```bash
# Lister les modules installés
ddev exec bin/console prestashop:module list

# Vérifier le statut d'un module
ddev exec bin/console prestashop:module status monmodule
```

---

## Problèmes courants

| Problème | Solution |
|----------|----------|
| Module non visible | Vider le cache : `rm -rf var/cache/*` |
| Erreur d'installation | Vérifier les logs : `tail -f var/logs/dev.log` |
| Erreur de namespace | Vérifier `composer.json` et relancer `composer dump-autoload` |
| Assets non chargés | Lancer `npm run build` |

---

**Prochaine étape** : [Prérequis](./prerequisites.md)


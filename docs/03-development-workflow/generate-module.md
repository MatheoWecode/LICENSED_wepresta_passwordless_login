# Générer un module avec WEDEV CLI

WEDEV CLI permet de créer un module PrestaShop personnalisé en quelques secondes.

## Prérequis

```bash
# Installer WEDEV CLI globalement
npm install -g @wecode/wedev-cli

# Vérifier l'installation
wedev --version
```

---

## Lancer le générateur

### Depuis un projet PrestaShop

```bash
cd /chemin/vers/prestashop
wedev ps module
```

### Directement via le menu

```bash
wedev ps
# → 📦 Gestion des modules
# → ➕ Créer un nouveau module
```

---

## Options interactives

Le générateur pose plusieurs questions :

### 1. Nom technique

```
? Nom technique du module (lowercase, underscores ok): myawesomemodule
```

- Lowercase uniquement
- Underscores autorisés (`my_module`)
- Pas de tirets, pas d'espaces
- Max 64 caractères

### 2. Nom d'affichage

```
? Nom d'affichage: My Awesome Module
```

Affiché dans le back-office PrestaShop.

### 3. Description

```
? Description courte: Un module qui fait des choses incroyables
```

Visible dans la liste des modules.

### 4. Auteur

```
? Auteur: Mon Entreprise
```

### 5. Catégorie

```
? Catégorie:
  ❯ front_office_features
    administration
    analytics_stats
    billing_invoicing
    checkout
    content_management
    ...
```

### 6. Hooks

```
? Hooks à enregistrer:
  ◉ displayHeader
  ◯ displayTop
  ◉ displayHome
  ◉ displayFooter
  ◯ displayProductAdditionalInfo
  ◉ actionFrontControllerSetMedia
  ...
```

Sélectionnez les hooks dont vous avez besoin.

### 7. Options avancées

```
? Options supplémentaires:
  ◉ Base de données (tables personnalisées)
  ◉ Tab admin (menu back-office)
  ◯ Contrôleur front
  ◉ Tests PHPUnit
  ◉ API REST
```

---

## Résultat

Le générateur crée :

```
modules/myawesomemodule/
├── myawesomemodule.php      # Point d'entrée personnalisé
├── composer.json            # Avec namespace correct
├── package.json             # Dépendances Node.js
├── config/
│   ├── routes.yml           # Routes avec préfixe correct
│   └── services.yml         # Services avec namespace
├── src/                     # Code source
├── views/                   # Templates
├── _dev/                    # Sources assets
├── sql/                     # Scripts SQL
├── tests/                   # Tests si activé
└── .cursor/rules/           # Règles Cursor
```

### Remplacements automatiques

| Placeholder | Remplacé par |
|-------------|--------------|
| `wepresta_passwordless_login` | `myawesomemodule` |
| `Wepresta_Passwordless_Login` | `MyAwesomeModule` |
| `WEPRESTA_PASSWORDLESS_LOGIN` | `MYAWESOMEMODULE` |
| `Module Starter` | `My Awesome Module` |

---

## Après la génération

### 1. Installer les dépendances

```bash
cd modules/myawesomemodule

# PHP
composer install

# Node.js
npm install
```

### 2. Compiler les assets

```bash
npm run build
```

### 3. Installer le module

```bash
# Avec DDEV
ddev exec bin/console prestashop:module install myawesomemodule

# Sans DDEV
bin/console prestashop:module install myawesomemodule
```

### 4. Vérifier

1. Allez dans **Modules** → **Gestionnaire de modules**
2. Recherchez votre module
3. Cliquez sur **Configurer**

---

## Commandes WEDEV associées

### Lister les modules

```bash
wedev ps module
# → 📋 Lister les modules
```

### Installer/Désinstaller

```bash
wedev ps module
# → 📥 Installer un module
# → 📤 Désinstaller un module
```

### Référence des hooks

```bash
wedev ps module
# → 🪝 Référence des hooks
```

Affiche une liste des hooks courants avec leur description.

---

## Génération en mode batch (avancé)

Pour automatiser la création de plusieurs modules :

```bash
# À venir dans une future version
wedev ps module new --name=mymodule --hooks=displayHome,displayHeader --no-interactive
```

---

**Prochaine étape** : [Cycle de développement](./development-cycle.md)


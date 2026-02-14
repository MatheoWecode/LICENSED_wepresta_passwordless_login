# Premiers pas

Après l'installation, suivez ces étapes pour vérifier que tout fonctionne.

## Vérifications post-installation

### 1. Le module est visible

1. Accédez au back-office PrestaShop
2. Allez dans **Modules** → **Gestionnaire de modules**
3. Recherchez votre module
4. Vérifiez qu'il est **installé** et **activé**

### 2. Pas d'erreurs dans les logs

```bash
# Vérifier les logs récents
tail -50 var/logs/dev.log

# Suivre les logs en temps réel
tail -f var/logs/dev.log
```

### 3. La configuration est accessible

1. Cliquez sur le bouton **Configurer** du module
2. Une page de configuration doit s'afficher sans erreur

---

## Structure du module installé

Après génération, votre module contient :

```
monmodule/
├── monmodule.php           # Point d'entrée
├── composer.json           # Dépendances PHP
├── package.json            # Dépendances Node.js
├── config/
│   ├── routes.yml          # Routes admin
│   └── services.yml        # Services Symfony
├── src/                    # Code source
├── views/                  # Templates et assets
├── _dev/                   # Sources SCSS/JS
├── sql/                    # Scripts SQL
└── tests/                  # Tests PHPUnit
```

---

## Installer les dépendances

### Dépendances PHP

```bash
cd modules/monmodule
composer install
```

### Dépendances Node.js

```bash
npm install
```

---

## Compiler les assets

Les assets (CSS, JavaScript) doivent être compilés avant utilisation.

### Développement (avec watch)

```bash
# Compile et surveille les changements
npm run watch
```

### Production

```bash
# Build optimisé pour la production
npm run build
```

---

## Activer le mode développement PrestaShop

Pour voir les erreurs détaillées pendant le développement :

### Méthode 1 : Via le fichier de configuration

Éditez `config/defines.inc.php` :

```php
define('_PS_MODE_DEV_', true);
```

### Méthode 2 : Via WEDEV CLI

```bash
wedev ps dev-mode
```

Cette commande :
- Active `_PS_MODE_DEV_`
- Désactive le cache Smarty
- Configure les tokens pour le debug

---

## Tester un hook

Pour vérifier que vos hooks fonctionnent :

### Hook displayHome

1. Allez sur la page d'accueil de la boutique
2. Cherchez le contenu ajouté par votre module
3. Si rien ne s'affiche, vérifiez :
   - Le hook est bien enregistré (`HOOKS` dans le module)
   - La méthode `hookDisplayHome()` retourne du HTML
   - Le cache est vidé

### Hook displayHeader

1. Inspectez le `<head>` de la page (F12)
2. Cherchez vos CSS/JS injectés
3. Vérifiez avec :

```bash
# Chercher les assets du module
curl -s https://votre-boutique.local | grep monmodule
```

---

## Commandes utiles au quotidien

### Vider le cache

```bash
# DDEV
ddev exec rm -rf var/cache/*

# Sans DDEV
rm -rf var/cache/*
```

### Réinstaller le module (reset)

```bash
ddev exec bin/console prestashop:module uninstall monmodule
ddev exec bin/console prestashop:module install monmodule
```

### Voir les hooks enregistrés

```bash
# Dans la base de données
ddev mysql -e "SELECT * FROM ps_hook_module WHERE id_module = (SELECT id_module FROM ps_module WHERE name = 'monmodule')"
```

---

## Workflow de développement recommandé

1. **Terminal 1** : Watch des assets
   ```bash
   npm run watch
   ```

2. **Terminal 2** : Logs en temps réel
   ```bash
   tail -f var/logs/dev.log
   ```

3. **Navigateur** : Boutique ouverte avec DevTools

4. **IDE** : Code source du module

---

## Problèmes courants au démarrage

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| Page blanche | Erreur PHP fatale | Activer `_PS_MODE_DEV_` |
| 500 Internal Server Error | Erreur de syntaxe | Vérifier les logs |
| CSS non appliqué | Assets non compilés | `npm run build` |
| Hook non exécuté | Hook non enregistré | Vérifier `HOOKS` dans le module |
| Modifications non visibles | Cache | Vider `var/cache/` |

---

**Prochaine section** : [PrestaShop Basics](../02-prestashop-basics/)


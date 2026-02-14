# Cycle de développement

Workflow quotidien pour développer votre module efficacement.

## Configuration initiale

### 1. Activer le mode développement

```bash
# Via WEDEV CLI
wedev ps dev-mode
```

Cette commande :
- Active `_PS_MODE_DEV_` (erreurs détaillées)
- Désactive le cache Smarty
- Configure les tokens pour le debug

### 2. Lancer le watch des assets

```bash
cd modules/monmodule
npm run watch
```

Laissez ce terminal ouvert pendant le développement.

### 3. Surveiller les logs

```bash
# Dans un autre terminal
tail -f var/logs/dev.log
```

---

## Workflow de développement

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│   1. Modifier le code                                       │
│         ↓                                                   │
│   2. Sauvegarder (Ctrl+S)                                  │
│         ↓                                                   │
│   3. Watch recompile les assets (automatique)              │
│         ↓                                                   │
│   4. Rafraîchir le navigateur (F5)                         │
│         ↓                                                   │
│   5. Vérifier le résultat                                  │
│         ↓                                                   │
│   6. Répéter                                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Commandes essentielles

### Vider le cache

```bash
# DDEV
ddev exec rm -rf var/cache/*

# Sans DDEV
rm -rf var/cache/*
```

> 💡 **Astuce** : Créez un alias `alias pscache="rm -rf var/cache/*"`

### Réinstaller le module

Utile après modification des hooks ou de l'installation :

```bash
# Reset = uninstall + install
ddev exec bin/console prestashop:module reset monmodule
```

### Recharger les services

Après modification de `services.yml` :

```bash
rm -rf var/cache/*
```

### Regénérer l'autoload

Après ajout d'une nouvelle classe :

```bash
composer dump-autoload
```

---

## Modifications courantes

### Ajouter un hook

1. Ajoutez le hook dans la constante `HOOKS` du module
2. Créez la méthode `hookNomDuHook()`
3. Réinstallez le module

```bash
ddev exec bin/console prestashop:module reset monmodule
```

### Modifier la configuration

1. Modifiez `DEFAULT_CONFIG` dans le module
2. Ajoutez les champs dans le formulaire
3. Videz le cache

### Ajouter une table SQL

1. Modifiez `sql/install.sql`
2. Modifiez `sql/uninstall.sql`
3. Créez un script d'upgrade si déjà installé
4. Réinstallez ou exécutez le script manuellement

---

## Debugging

### Afficher des variables

```php
// Dans un hook ou service
dump($variable);  // Affiche dans la Symfony Debug Bar

// Arrêter l'exécution
dd($variable);    // dump and die
```

> ⚠️ Retirez les `dd()` avant de commiter !

### Logs

```php
PrestaShopLogger::addLog(
    'Mon message',
    1,  // Severity: 1=info, 2=warning, 3=error
    null,
    'Order',  // Object type
    $orderId  // Object ID
);
```

Consultez les logs dans :
- **Back-office** → Paramètres avancés → Logs
- **Fichier** : `var/logs/dev.log`

### Xdebug

```bash
# Activer Xdebug avec DDEV
ddev xdebug on

# Désactiver
ddev xdebug off
```

Configurez votre IDE pour écouter sur le port 9003.

---

## Mode production

Avant de déployer :

### 1. Build des assets

```bash
npm run build
```

### 2. Désactiver le mode dev

```php
// config/defines.inc.php
define('_PS_MODE_DEV_', false);
```

### 3. Vider le cache

```bash
rm -rf var/cache/*
```

### 4. Vérifier les erreurs

```bash
composer phpstan
composer cs-check
```

---

## Raccourcis recommandés

| Action | Commande |
|--------|----------|
| Vider cache | `ddev exec rm -rf var/cache/*` |
| Reset module | `ddev exec bin/console prestashop:module reset monmodule` |
| Watch assets | `npm run watch` |
| Build prod | `npm run build` |
| Lancer tests | `composer phpunit` |
| Vérifier qualité | `composer test` |

### Script personnalisé

Créez un fichier `dev.sh` :

```bash
#!/bin/bash
case "$1" in
  cache)
    ddev exec rm -rf var/cache/*
    echo "Cache vidé"
    ;;
  reset)
    ddev exec bin/console prestashop:module reset monmodule
    ;;
  watch)
    npm run watch
    ;;
  *)
    echo "Usage: ./dev.sh {cache|reset|watch}"
    ;;
esac
```

---

**Prochaine étape** : [Workflow Assets](./assets-workflow.md)


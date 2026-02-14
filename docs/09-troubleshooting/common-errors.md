# Erreurs Fréquentes

Solutions aux problèmes les plus courants.

## Installation

### Module non visible après copie

**Symptôme** : Le module n'apparaît pas dans la liste.

**Solutions** :
1. Vider le cache
   ```bash
   rm -rf var/cache/*
   ```

2. Vérifier le nom du dossier
   ```
   modules/monmodule/monmodule.php  ✅
   modules/MonModule/monmodule.php  ❌
   ```

3. Vérifier les permissions
   ```bash
   chmod -R 755 modules/monmodule
   ```

---

### Erreur à l'installation

**Symptôme** : "Module installation failed"

**Solutions** :

1. **Activer le mode debug**
   ```php
   // config/defines.inc.php
   define('_PS_MODE_DEV_', true);
   ```

2. **Consulter les logs**
   ```bash
   tail -100 var/logs/dev.log
   ```

3. **Vérifier le SQL**
   ```bash
   # Tester manuellement
   mysql -u root -p prestashop < modules/monmodule/sql/install.sql
   ```

---

### Class not found

**Symptôme** : `Class 'MonModule\...' not found`

**Solutions** :

1. **Regénérer l'autoload**
   ```bash
   composer dump-autoload
   ```

2. **Vérifier le namespace**
   ```php
   // Fichier: src/Application/Service/ItemService.php
   namespace MonModule\Application\Service;  // ✅ Correct
   namespace MonModule\Service;              // ❌ Incorrect
   ```

3. **Vérifier composer.json**
   ```json
   {
       "autoload": {
           "psr-4": {
               "MonModule\\": "src/"
           }
       }
   }
   ```

---

## Développement

### Modifications non visibles

**Symptôme** : Les changements ne s'affichent pas.

**Solutions** :

1. **Vider le cache PrestaShop**
   ```bash
   rm -rf var/cache/*
   ```

2. **Vider le cache navigateur**
   - Ctrl + F5 (hard refresh)
   - Ou ouvrir en navigation privée

3. **Recompiler les assets**
   ```bash
   npm run build
   ```

---

### Hook non exécuté

**Symptôme** : La méthode `hookDisplayHome()` n'est pas appelée.

**Solutions** :

1. **Vérifier l'enregistrement**
   ```sql
   SELECT * FROM ps_hook_module hm
   JOIN ps_hook h ON h.id_hook = hm.id_hook
   WHERE hm.id_module = (SELECT id_module FROM ps_module WHERE name = 'monmodule');
   ```

2. **Réenregistrer le hook**
   ```bash
   bin/console prestashop:module reset monmodule
   ```

3. **Vérifier le nom de la méthode**
   ```php
   public function hookDisplayHome(array $params)  // ✅
   public function hookdisplayhome(array $params)  // ❌ Casse incorrecte
   ```

---

### Erreur 500

**Symptôme** : Page blanche ou erreur 500.

**Solutions** :

1. **Activer l'affichage des erreurs**
   ```php
   define('_PS_MODE_DEV_', true);
   ```

2. **Consulter les logs PHP**
   ```bash
   tail -f /var/log/apache2/error.log
   # ou
   tail -f var/logs/dev.log
   ```

3. **Vérifier la syntaxe PHP**
   ```bash
   php -l monmodule.php
   ```

---

## Assets

### CSS/JS non chargés

**Symptôme** : Les styles ne s'appliquent pas.

**Solutions** :

1. **Vérifier le build**
   ```bash
   npm run build
   ls -la views/dist/
   ```

2. **Vérifier le hook**
   ```php
   // Le hook est-il enregistré ?
   public function hookActionFrontControllerSetMedia(array $params): void
   ```

3. **Vérifier le chemin**
   ```php
   // Chemin correct ?
   'modules/' . $this->name . '/views/dist/front.css'
   ```

4. **Vérifier la console navigateur**
   - F12 → Network
   - Chercher les erreurs 404

---

### Erreur Webpack

**Symptôme** : `npm run build` échoue.

**Solutions** :

1. **Réinstaller les dépendances**
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

2. **Vérifier la version Node**
   ```bash
   node -v  # Doit être 20+
   ```

3. **Vérifier la syntaxe SCSS/JS**
   - Regarder le message d'erreur
   - Corriger le fichier mentionné

---

## Base de données

### Table non créée

**Symptôme** : "Table doesn't exist"

**Solutions** :

1. **Vérifier le SQL**
   ```sql
   -- Tester manuellement
   SHOW TABLES LIKE '%monmodule%';
   ```

2. **Réinstaller le module**
   ```bash
   bin/console prestashop:module uninstall monmodule
   bin/console prestashop:module install monmodule
   ```

3. **Vérifier les erreurs SQL**
   ```php
   $result = Db::getInstance()->execute($sql);
   if (!$result) {
       echo Db::getInstance()->getMsgError();
   }
   ```

---

### Données non sauvegardées

**Symptôme** : Les modifications ne persistent pas.

**Solutions** :

1. **Vérifier le retour de save()**
   ```php
   if (!$this->repository->save($item)) {
       PrestaShopLogger::addLog('Save failed: ' . Db::getInstance()->getMsgError());
   }
   ```

2. **Vérifier les types**
   ```sql
   DESCRIBE ps_monmodule_item;
   ```

3. **Vérifier les contraintes**
   - Clés étrangères
   - Valeurs NOT NULL

---

## Performance

### Page lente

**Symptôme** : Chargement > 3 secondes.

**Solutions** :

1. **Profiler**
   ```php
   define('_PS_DEBUG_PROFILING_', true);
   ```

2. **Vérifier les requêtes N+1**
   - Utiliser des JOIN au lieu de boucles

3. **Ajouter du cache**
   ```php
   return $this->cache->get('key', fn() => $this->heavyQuery());
   ```

---

## Multi-boutique

### Configuration non sauvegardée par boutique

**Symptôme** : La config s'applique à toutes les boutiques.

**Solution** :
```php
// Sauvegarder pour la boutique courante
Configuration::updateValue('KEY', $value, false, null, Shop::getContextShopID());
```

---

## Tableau récapitulatif

| Erreur | Première action |
|--------|-----------------|
| Module non visible | `rm -rf var/cache/*` |
| Class not found | `composer dump-autoload` |
| Hook non exécuté | `bin/console prestashop:module reset` |
| 500 / Page blanche | `_PS_MODE_DEV_ = true` |
| CSS non chargé | `npm run build` |
| Table inexistante | Vérifier SQL + réinstaller |

---

**Prochaine étape** : [Debugging](./debugging.md)


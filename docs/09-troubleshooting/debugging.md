# Debugging

Techniques pour déboguer votre module.

## Mode développement PrestaShop

### Activer

```php
// config/defines.inc.php
define('_PS_MODE_DEV_', true);
```

### Effets

- Affichage des erreurs PHP
- Cache Smarty désactivé
- Stacktrace détaillée

### Via WEDEV CLI

```bash
wedev ps dev-mode
```

---

## Afficher des variables

### dump() et dd()

```php
// Afficher sans arrêter
dump($variable);

// Afficher et arrêter (die)
dd($variable);
```

> ⚠️ **Retirer avant de commiter !**

### Dans Smarty

```smarty
{* Afficher toutes les variables *}
{debug}

{* Afficher une variable *}
{$variable|@print_r}
```

### Dans Twig

```twig
{{ dump(variable) }}
```

---

## Xdebug

### Avec DDEV

```bash
# Activer
ddev xdebug on

# Désactiver
ddev xdebug off
```

### Configuration IDE (VS Code)

```json
// .vscode/launch.json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

### Points d'arrêt

1. Placez un point d'arrêt dans l'IDE
2. Activez Xdebug
3. Rafraîchissez la page
4. L'exécution s'arrête au breakpoint

---

## Logs

### PrestaShopLogger

```php
PrestaShopLogger::addLog(
    'Mon message de log',
    1,              // Severity: 1=info, 2=warning, 3=error
    null,           // Error code
    'Order',        // Object type
    $orderId,       // Object ID
    true            // Allow duplicate
);
```

### Consulter les logs

```bash
# Fichier
tail -f var/logs/dev.log

# Back-office
# Paramètres avancés → Logs
```

### Logger PSR-3

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    public function process(): void
    {
        $this->logger->info('Processing started');
        $this->logger->error('Something went wrong', ['context' => $data]);
    }
}
```

---

## Profiling SQL

### Afficher les requêtes

```php
// Avant la requête
$sql = "SELECT * FROM ps_product";
PrestaShopLogger::addLog('SQL: ' . $sql);

// Temps d'exécution
$start = microtime(true);
$result = Db::getInstance()->executeS($sql);
$duration = microtime(true) - $start;
PrestaShopLogger::addLog("Query took {$duration}s");
```

### Slow Query Log MySQL

```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

---

## Console Symfony

### Commandes utiles

```bash
# Debug du routeur
bin/console debug:router | grep monmodule

# Debug du container
bin/console debug:container MonModule

# Debug des événements
bin/console debug:event-dispatcher
```

---

## Debug des hooks

### Vérifier qu'un hook est appelé

```php
public function hookDisplayHome(array $params): string
{
    PrestaShopLogger::addLog('hookDisplayHome called');
    
    // ou temporairement
    file_put_contents('/tmp/hook_debug.log', date('Y-m-d H:i:s') . " hookDisplayHome\n", FILE_APPEND);
    
    // ...
}
```

### Lister les hooks d'un module

```sql
SELECT h.name, hm.position
FROM ps_hook_module hm
JOIN ps_hook h ON h.id_hook = hm.id_hook
JOIN ps_module m ON m.id_module = hm.id_module
WHERE m.name = 'monmodule';
```

---

## Debug AJAX

### Console navigateur

1. F12 → Network
2. Filtrer par XHR
3. Cliquer sur la requête
4. Onglets Headers, Response, Preview

### Retourner des infos de debug

```php
// Temporairement
return json_encode([
    'success' => false,
    'debug' => [
        'input' => $request->request->all(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ],
]);
```

---

## Debug JavaScript

### Console

```javascript
console.log('Variable:', variable);
console.table(array);
console.trace(); // Stacktrace
```

### Breakpoints navigateur

1. F12 → Sources
2. Naviguer jusqu'au fichier JS
3. Cliquer sur le numéro de ligne
4. Rafraîchir la page

### Source maps

En mode développement, les source maps permettent de déboguer le code original :

```bash
npm run dev  # Génère les source maps
```

---

## Debug des services

### Vérifier qu'un service est chargé

```php
try {
    $service = $this->get(ItemService::class);
    dump($service);
} catch (\Exception $e) {
    dump('Service not found: ' . $e->getMessage());
}
```

### Debug du container

```bash
bin/console debug:container --show-private | grep monmodule
```

---

## Outils externes

| Outil | Usage |
|-------|-------|
| [Blackfire](https://blackfire.io/) | Profiling avancé |
| [New Relic](https://newrelic.com/) | Monitoring production |
| [Ray](https://myray.app/) | Debug moderne |
| [Clockwork](https://underground.works/clockwork/) | Debug bar |

---

## Checklist de debug

1. [ ] Mode dev activé (`_PS_MODE_DEV_`)
2. [ ] Vérifier les logs (`var/logs/dev.log`)
3. [ ] Vérifier la console navigateur (F12)
4. [ ] Utiliser `dump()` / `dd()`
5. [ ] Xdebug si complexe
6. [ ] Isoler le problème (commentage progressif)

---

**Prochaine étape** : [Logs](./logs.md)


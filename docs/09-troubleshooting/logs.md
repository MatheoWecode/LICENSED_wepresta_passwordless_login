# Logs

Configurer et utiliser les logs dans votre module.

## Logs PrestaShop

### Emplacement

```
var/logs/
├── dev.log      # Logs de développement
├── prod.log     # Logs de production
└── admin/       # Logs admin
```

### Consulter

```bash
# En temps réel
tail -f var/logs/dev.log

# Dernières lignes
tail -100 var/logs/dev.log

# Rechercher
grep "monmodule" var/logs/dev.log
```

---

## PrestaShopLogger

### Utilisation de base

```php
PrestaShopLogger::addLog(
    'Message de log',      // Message
    1,                     // Severity (1-4)
    null,                  // Error code
    'ObjectType',          // Objet concerné
    $objectId,             // ID de l'objet
    true                   // Autoriser les doublons
);
```

### Niveaux de sévérité

| Niveau | Signification | Affichage |
|--------|---------------|-----------|
| 1 | Information | Bleu |
| 2 | Avertissement | Orange |
| 3 | Erreur | Rouge |
| 4 | Critique | Rouge foncé |

### Exemples

```php
// Information
PrestaShopLogger::addLog(
    "Module initialisé pour la commande #$orderId",
    1,
    null,
    'Order',
    $orderId
);

// Avertissement
PrestaShopLogger::addLog(
    'API timeout, retry in progress',
    2
);

// Erreur
PrestaShopLogger::addLog(
    "Failed to process item #$itemId: " . $e->getMessage(),
    3,
    $e->getCode(),
    'Item',
    $itemId
);

// Critique
PrestaShopLogger::addLog(
    'Database connection lost',
    4
);
```

---

## Logger PSR-3

### Injection

```yaml
# config/services.yml
services:
  MonModule\Application\Service\ItemService:
    arguments:
      $logger: '@logger'
```

```php
use Psr\Log\LoggerInterface;

class ItemService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
}
```

### Méthodes disponibles

```php
$this->logger->debug('Debug message');
$this->logger->info('Info message');
$this->logger->notice('Notice message');
$this->logger->warning('Warning message');
$this->logger->error('Error message');
$this->logger->critical('Critical message');
$this->logger->alert('Alert message');
$this->logger->emergency('Emergency message');
```

### Avec contexte

```php
$this->logger->info('Order processed', [
    'order_id' => $orderId,
    'total' => $total,
    'customer' => $customerId,
]);
```

---

## LoggerTrait

Ce module fournit un trait pour simplifier le logging :

```php
use MonModule\Core\Trait\LoggerTrait;

class MyService
{
    use LoggerTrait;
    
    public function process(): void
    {
        $this->logInfo('Processing started');
        
        try {
            // ...
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }
}
```

### Méthodes du trait

| Méthode | Description |
|---------|-------------|
| `logDebug($message, $context)` | Log debug |
| `logInfo($message, $context)` | Log info |
| `logWarning($message, $context)` | Log warning |
| `logError($message, $context)` | Log error |
| `logException($exception)` | Log exception complète |

---

## Logs dans les fichiers

### Logger personnalisé

```php
class FileLogger
{
    private string $logFile;
    
    public function __construct(string $moduleName)
    {
        $this->logFile = _PS_MODULE_DIR_ . $moduleName . '/var/module.log';
    }
    
    public function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context) : ''
        );
        
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
```

### Rotation des logs

```php
public function rotateLog(): void
{
    if (file_exists($this->logFile) && filesize($this->logFile) > 10 * 1024 * 1024) {
        $archiveName = $this->logFile . '.' . date('Y-m-d-H-i-s');
        rename($this->logFile, $archiveName);
    }
}
```

---

## Logs en back-office

### Consulter

**Paramètres avancés** → **Logs**

### Filtrer

- Par sévérité
- Par date
- Par message

### Purger

```sql
-- Supprimer les vieux logs
DELETE FROM ps_log WHERE date_add < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## Logs en production

### Bonnes pratiques

1. **Ne pas logger de données sensibles**
   ```php
   // ❌ MAUVAIS
   $this->logger->info('API called', ['api_key' => $apiKey]);
   
   // ✅ BON
   $this->logger->info('API called', ['api_key' => '***']);
   ```

2. **Limiter les logs debug en production**
   ```php
   if (_PS_MODE_DEV_) {
       $this->logDebug('Detailed info', $data);
   }
   ```

3. **Structurer les logs**
   ```php
   $this->logger->info('order.processed', [
       'order_id' => $orderId,
       'duration_ms' => $duration,
   ]);
   ```

---

## Monitorage

### Alertes sur erreurs

```php
public function logCritical(string $message, array $context = []): void
{
    parent::logCritical($message, $context);
    
    // Envoyer une alerte
    if ($this->shouldAlert()) {
        $this->notificationService->sendAlert($message, $context);
    }
}
```

### Métriques

```php
$this->logger->info('metric.api_call', [
    'endpoint' => $endpoint,
    'response_time_ms' => $responseTime,
    'status_code' => $statusCode,
]);
```

---

## Nettoyage des logs

### Script de maintenance

```php
// scripts/clean-logs.php
$maxAge = 30; // jours

// Logs PrestaShop
Db::getInstance()->execute(
    'DELETE FROM `' . _DB_PREFIX_ . "log` 
     WHERE date_add < DATE_SUB(NOW(), INTERVAL $maxAge DAY)"
);

// Logs fichiers
$logDir = _PS_MODULE_DIR_ . 'monmodule/var/';
$files = glob($logDir . '*.log.*');
foreach ($files as $file) {
    if (filemtime($file) < strtotime("-$maxAge days")) {
        unlink($file);
    }
}
```

### Cron

```bash
# Exécuter chaque semaine
0 3 * * 0 php /var/www/html/modules/monmodule/scripts/clean-logs.php
```

---

**Prochaine section** : [Reference](../99-reference/)


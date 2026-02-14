# Webhooks

Les webhooks permettent d'envoyer des notifications en temps réel vers des systèmes externes.

## Qu'est-ce qu'un webhook ?

Un webhook est une requête HTTP envoyée **automatiquement** quand un événement se produit :

```
┌─────────────────────────────────────────────────────────────┐
│  PrestaShop                                                 │
│       │                                                     │
│  Événement: Nouvelle commande                               │
│       ↓                                                     │
│  Module envoie POST → https://api.externe.com/webhook      │
│       ↓                                                     │
│  Service externe reçoit les données                         │
└─────────────────────────────────────────────────────────────┘
```

---

## Configurer un webhook

### Dans le back-office

1. Module → Configuration
2. Section "Webhooks"
3. Ajouter une URL de webhook
4. Sélectionner les événements

### Configuration

| Paramètre | Description |
|-----------|-------------|
| URL | Endpoint HTTPS à appeler |
| Événements | Quels événements déclenchent le webhook |
| Secret | Clé pour signer les requêtes |
| Actif | Activer/désactiver |

---

## Événements disponibles

| Événement | Déclencheur |
|-----------|-------------|
| `item.created` | Nouvel item créé |
| `item.updated` | Item modifié |
| `item.deleted` | Item supprimé |
| `order.created` | Nouvelle commande |
| `order.status_changed` | Statut commande modifié |

---

## Format des requêtes

### Headers

```
Content-Type: application/json
X-Webhook-Event: item.created
X-Webhook-Signature: sha256=xxxxx
X-Webhook-Timestamp: 1703250000
```

### Body

```json
{
    "event": "item.created",
    "timestamp": "2024-12-22T10:30:00+01:00",
    "data": {
        "id": 42,
        "name": "Nouvel item",
        "active": true,
        "created_at": "2024-12-22T10:30:00+01:00"
    },
    "shop": {
        "id": 1,
        "name": "Ma Boutique",
        "url": "https://shop.local"
    }
}
```

---

## Vérifier la signature

Le webhook est signé avec un secret partagé :

### PHP

```php
function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
{
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Utilisation
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'votre_secret_webhook';

if (!verifyWebhookSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$data = json_decode($payload, true);
// Traiter l'événement...
```

### Node.js

```javascript
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
    const expected = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(expected),
        Buffer.from(signature)
    );
}
```

---

## Implémenter les webhooks

### Service d'envoi

```php
// src/Infrastructure/Service/WebhookService.php

class WebhookService
{
    public function send(string $event, array $data): void
    {
        $webhooks = $this->getActiveWebhooks($event);
        
        foreach ($webhooks as $webhook) {
            $this->dispatch($webhook, $event, $data);
        }
    }
    
    private function dispatch(Webhook $webhook, string $event, array $data): void
    {
        $payload = json_encode([
            'event' => $event,
            'timestamp' => date('c'),
            'data' => $data,
        ]);
        
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook->getSecret());
        
        $response = $this->httpClient->post($webhook->getUrl(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $event,
                'X-Webhook-Signature' => $signature,
            ],
            'body' => $payload,
            'timeout' => 10,
        ]);
        
        $this->logDelivery($webhook, $event, $response);
    }
}
```

### Déclencher depuis un hook

```php
public function hookActionObjectItemAddAfter(array $params): void
{
    $item = $params['object'];
    
    $this->getService(WebhookService::class)->send('item.created', [
        'id' => $item->id,
        'name' => $item->name,
        'active' => $item->active,
    ]);
}
```

---

## Retry et fiabilité

### Politique de retry

Si le webhook échoue, le module retente :

| Tentative | Délai |
|-----------|-------|
| 1 | Immédiat |
| 2 | 1 minute |
| 3 | 5 minutes |
| 4 | 30 minutes |
| 5 | 2 heures |

Après 5 échecs, le webhook est marqué comme "failed".

### Réponses attendues

| Code | Signification |
|------|---------------|
| 2xx | Succès |
| 4xx | Erreur client (ne pas réessayer) |
| 5xx | Erreur serveur (réessayer) |

---

## Logs des webhooks

### Voir les logs

Back-office → Module → Webhooks → Logs

### Informations loguées

- URL appelée
- Événement
- Payload envoyé
- Code de réponse
- Temps de réponse
- Erreurs éventuelles

---

## Bonnes pratiques

### Côté émetteur (module)

- ✅ Signer les requêtes
- ✅ Inclure un timestamp
- ✅ Implémenter les retries
- ✅ Loguer les livraisons
- ✅ Timeout raisonnable (10s)

### Côté récepteur (votre service)

- ✅ Vérifier la signature
- ✅ Répondre rapidement (< 5s)
- ✅ Traiter de manière asynchrone si long
- ✅ Être idempotent (même résultat si reçu 2 fois)
- ✅ Retourner 200 dès réception

```php
// Récepteur idéal
http_response_code(200);
echo 'OK';

// Traitement asynchrone
$this->queue->push('process_webhook', $data);
```

---

## Tester les webhooks

### Avec webhook.site

1. Allez sur [webhook.site](https://webhook.site)
2. Copiez l'URL unique
3. Configurez cette URL dans le module
4. Déclenchez un événement
5. Vérifiez la requête reçue

### En local avec ngrok

```bash
# Exposer le port local
ngrok http 80

# Utilisez l'URL ngrok comme endpoint
# https://abc123.ngrok.io/webhook-receiver.php
```

---

**Prochaine étape** : [Services externes](./external-services.md)


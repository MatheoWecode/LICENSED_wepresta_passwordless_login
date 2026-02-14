# Extension Http WEDEV

Client HTTP unifié pour modules PrestaShop avec retry automatique, rate limiting et authentification.

## Installation

L'extension est automatiquement copiée lors de la génération d'un module :

```bash
wedev ps module new mymodule --ext http
```

### Configuration Symfony

Ajouter l'import dans `config/services.yml` du module :

```yaml
imports:
    - { resource: '../src/Extension/Http/config/services_http.yml' }
```

---

## Utilisation

### Requêtes Simples

```php
use Wepresta_Passwordless_Login\Extension\Http\HttpClient;

$client = new HttpClient();

// GET
$response = $client->get('https://api.example.com/users');

// GET avec paramètres
$response = $client->get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 10
]);

// POST avec FormData
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// POST avec JSON
$response = $client->postJson('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// PUT, PATCH, DELETE
$response = $client->put('https://api.example.com/users/1', ['name' => 'Jane']);
$response = $client->patch('https://api.example.com/users/1', ['name' => 'Jane']);
$response = $client->delete('https://api.example.com/users/1');
```

### Traitement de la Réponse

```php
$response = $client->get('https://api.example.com/users');

// Vérifier le statut
if ($response->isSuccess()) {
    $users = $response->json();
}

// Accéder aux données
$statusCode = $response->getStatusCode();  // 200
$body = $response->getBody();              // Raw string
$data = $response->json();                 // Array

// Headers
$contentType = $response->getHeader('content-type');
$rateLimit = $response->getHeader('x-rate-limit-remaining');

// Helpers de statut
$response->isSuccess();      // 2xx
$response->isRedirect();     // 3xx
$response->isClientError();  // 4xx
$response->isServerError();  // 5xx
$response->isNotFound();     // 404
$response->isUnauthorized(); // 401
$response->isRateLimited();  // 429

// Lancer une exception si erreur
$response->throw();
```

---

## Authentification

### Bearer Token

```php
use Wepresta_Passwordless_Login\Extension\Http\Auth\BearerAuth;

$auth = new BearerAuth('your-api-token');

$response = $client
    ->withAuth($auth)
    ->get('https://api.example.com/protected');
```

### API Key

```php
use Wepresta_Passwordless_Login\Extension\Http\Auth\ApiKeyAuth;

// Header par défaut: X-API-Key
$auth = new ApiKeyAuth('your-api-key');

// Header personnalisé
$auth = new ApiKeyAuth('your-api-key', 'X-Custom-Header');

$response = $client
    ->withAuth($auth)
    ->get('https://api.example.com/data');
```

### HTTP Basic

```php
use Wepresta_Passwordless_Login\Extension\Http\Auth\BasicAuth;

$auth = new BasicAuth('username', 'password');

$response = $client
    ->withAuth($auth)
    ->get('https://api.example.com/data');
```

### OAuth2

```php
use Wepresta_Passwordless_Login\Extension\Http\Auth\OAuth2Auth;

$auth = new OAuth2Auth(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    tokenUrl: 'https://auth.example.com/oauth/token',
    scopes: ['read', 'write']
);

// Le token est obtenu et renouvelé automatiquement
$response = $client
    ->withAuth($auth)
    ->get('https://api.example.com/protected');
```

---

## Retry Automatique

```php
// Retry 3 fois avec backoff exponentiel
$response = $client
    ->withRetry(3)
    ->get('https://api.example.com/data');

// Retry 5 fois sans backoff
$response = $client
    ->withRetry(5, exponentialBackoff: false)
    ->get('https://api.example.com/data');
```

Le retry est déclenché automatiquement pour les codes :
- 408 Request Timeout
- 429 Too Many Requests
- 500 Internal Server Error
- 502 Bad Gateway
- 503 Service Unavailable
- 504 Gateway Timeout

### Backoff Exponentiel

| Tentative | Délai |
|-----------|-------|
| 1 | ~1 seconde |
| 2 | ~2 secondes |
| 3 | ~4 secondes |
| 4 | ~8 secondes |
| 5 | ~16 secondes |

Un jitter aléatoire (±25%) est ajouté pour éviter les "thundering herds".

---

## Rate Limiting

```php
// Limiter à 10 requêtes par seconde
$client = $client->withRateLimit(10, 1);

// Limiter à 100 requêtes par minute
$client = $client->withRateLimit(100, 60);

// Les requêtes sont automatiquement retardées si nécessaire
for ($i = 0; $i < 100; $i++) {
    $response = $client->get('https://api.example.com/data');
}
```

---

## Timeout

```php
// Timeout de 60 secondes (par défaut: 30s)
$response = $client
    ->withTimeout(60)
    ->get('https://slow-api.example.com/data');
```

---

## Headers Personnalisés

```php
// Un seul header
$response = $client
    ->withHeader('X-Custom-Header', 'value')
    ->get('https://api.example.com/data');

// Plusieurs headers
$response = $client
    ->withHeaders([
        'X-Request-ID' => uniqid(),
        'X-Client-Version' => '1.0.0'
    ])
    ->get('https://api.example.com/data');
```

---

## Exemples Complets

### OpenAI API

```php
use Wepresta_Passwordless_Login\Extension\Http\HttpClient;
use Wepresta_Passwordless_Login\Extension\Http\Auth\BearerAuth;

$client = new HttpClient();
$auth = new BearerAuth($_ENV['OPENAI_API_KEY']);

$response = $client
    ->withAuth($auth)
    ->withTimeout(120)  // GPT peut être lent
    ->withRetry(3)
    ->postJson('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Write a product description.']
        ],
        'max_tokens' => 500
    ]);

if ($response->isSuccess()) {
    $content = $response->json()['choices'][0]['message']['content'];
}
```

### Stripe API

```php
$client = new HttpClient();
$auth = new BearerAuth($_ENV['STRIPE_SECRET_KEY']);

$response = $client
    ->withAuth($auth)
    ->post('https://api.stripe.com/v1/charges', [
        'amount' => 2000,
        'currency' => 'eur',
        'source' => 'tok_visa',
        'description' => 'Order #123'
    ]);

if ($response->isSuccess()) {
    $chargeId = $response->json()['id'];
}
```

### VIES VAT Validation

```php
$client = new HttpClient();

$response = $client
    ->withTimeout(10)
    ->withRetry(3)
    ->get('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number', [
        'countryCode' => 'FR',
        'vatNumber' => '12345678901'
    ]);

if ($response->isSuccess()) {
    $data = $response->json();
    $isValid = $data['valid'] ?? false;
}
```

---

## Gestion des Erreurs

```php
use Wepresta_Passwordless_Login\Extension\Http\HttpException;

try {
    $response = $client->get('https://api.example.com/data');
    $response->throw(); // Lance une exception si erreur HTTP
    
    $data = $response->json();
} catch (HttpException $e) {
    if ($e->isTimeout()) {
        // Gérer le timeout
    } elseif ($e->isRateLimit()) {
        // Attendre et réessayer
    } elseif ($e->isConnectionError()) {
        // Problème de connexion
    } else {
        // Autre erreur
        $this->log('error', 'API error: ' . $e->getMessage());
    }
}
```

---

## Dans un Service PrestaShop

```php
<?php
declare(strict_types=1);

namespace MyModule\Service;

use Wepresta_Passwordless_Login\Extension\Http\HttpClient;
use Wepresta_Passwordless_Login\Extension\Http\Auth\BearerAuth;
use Wepresta_Passwordless_Login\Core\Adapter\ConfigurationAdapter;
use Wepresta_Passwordless_Login\Core\Trait\LoggerTrait;

class ExternalApiService
{
    use LoggerTrait;

    private HttpClient $client;

    public function __construct(
        private readonly ConfigurationAdapter $config
    ) {
        $this->client = (new HttpClient())
            ->withAuth(new BearerAuth($this->config->get('API_KEY')))
            ->withRetry(3)
            ->withTimeout(30);
    }

    public function fetchData(int $productId): ?array
    {
        try {
            $response = $this->client->get(
                $this->config->get('API_ENDPOINT') . '/products/' . $productId
            );

            if ($response->isSuccess()) {
                return $response->json();
            }

            $this->log('warning', 'API returned ' . $response->getStatusCode());
            return null;

        } catch (HttpException $e) {
            $this->log('error', 'API error: ' . $e->getMessage());
            return null;
        }
    }
}
```

---

## Structure des Fichiers

```
Extension/Http/
├── README.md
├── config/
│   └── services_http.yml
├── HttpClient.php
├── HttpResponse.php
├── HttpException.php
├── RetryStrategy.php
├── RateLimitHandler.php
└── Auth/
    ├── AuthInterface.php
    ├── ApiKeyAuth.php
    ├── BasicAuth.php
    ├── BearerAuth.php
    └── OAuth2Auth.php
```

---

## Dépendances

- PHP 8.1+
- `allow_url_fopen` activé en PHP
- Core WEDEV (LoggerTrait)

Aucune dépendance externe (pas de Guzzle, pas de Symfony HttpClient).
L'extension utilise les fonctions natives PHP (`file_get_contents` avec stream context).


# Services Externes

Intégrer des APIs tierces dans votre module.

## Architecture recommandée

Les appels à des services externes sont dans `src/Infrastructure/` :

```
src/Infrastructure/
├── Api/
│   ├── Client/
│   │   └── ExternalApiClient.php
│   └── Dto/
│       ├── Request/
│       └── Response/
└── Service/
    └── ExternalSyncService.php
```

---

## Client HTTP

### Avec Symfony HttpClient

```php
// src/Infrastructure/Api/Client/PaymentApiClient.php

namespace MonModule\Infrastructure\Api\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymentApiClient
{
    private const BASE_URL = 'https://api.payment.com/v1';
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}
    
    public function createPayment(array $data): array
    {
        $response = $this->httpClient->request('POST', self::BASE_URL . '/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);
        
        return $response->toArray();
    }
    
    public function getPayment(string $id): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . '/payments/' . $id, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
        
        return $response->toArray();
    }
}
```

### Avec cURL natif

```php
class SimpleApiClient
{
    public function post(string $url, array $data, array $headers = []): array
    {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
            ], $headers),
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new ApiException(curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new ApiException("HTTP Error: $httpCode");
        }
        
        return json_decode($response, true);
    }
}
```

---

## Gestion des erreurs

### Exception personnalisée

```php
// src/Infrastructure/Api/Exception/ApiException.php

namespace MonModule\Infrastructure\Api\Exception;

use MonModule\Core\Exception\ModuleException;

class ApiException extends ModuleException
{
    private int $httpCode;
    private ?array $responseBody;
    
    public function __construct(
        string $message,
        int $httpCode = 0,
        ?array $responseBody = null
    ) {
        parent::__construct($message, $httpCode);
        $this->httpCode = $httpCode;
        $this->responseBody = $responseBody;
    }
    
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
    
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
```

### Gestion des erreurs

```php
try {
    $result = $this->apiClient->createPayment($data);
} catch (ApiException $e) {
    $this->logger->error('Payment API error', [
        'http_code' => $e->getHttpCode(),
        'message' => $e->getMessage(),
    ]);
    
    if ($e->getHttpCode() === 401) {
        throw new ConfigurationException('Invalid API key');
    }
    
    throw $e;
}
```

---

## Configuration des clés API

### Stocker les clés

```php
// Dans la configuration du module
Configuration::updateValue('MONMODULE_EXTERNAL_API_KEY', $apiKey);
```

### Variables d'environnement (recommandé)

```php
// .env.local (ne pas commiter)
EXTERNAL_API_KEY=sk_live_xxxxx

// Lecture
$apiKey = $_ENV['EXTERNAL_API_KEY'] ?? Configuration::get('MONMODULE_EXTERNAL_API_KEY');
```

### Service avec injection

```yaml
# config/services.yml
services:
  MonModule\Infrastructure\Api\Client\PaymentApiClient:
    arguments:
      $apiKey: '%env(EXTERNAL_API_KEY)%'
```

---

## Cache des réponses

Pour éviter les appels répétitifs :

```php
class CachedApiClient
{
    public function __construct(
        private readonly PaymentApiClient $client,
        private readonly CacheService $cache
    ) {}
    
    public function getPayment(string $id): array
    {
        return $this->cache->get(
            'payment_' . $id,
            fn() => $this->client->getPayment($id),
            300  // 5 minutes
        );
    }
}
```

---

## Retry automatique

```php
class ResilientApiClient
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;
    
    public function request(string $method, string $url, array $options = []): array
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $this->doRequest($method, $url, $options);
            } catch (ApiException $e) {
                $lastException = $e;
                
                // Ne pas réessayer les erreurs client (4xx)
                if ($e->getHttpCode() >= 400 && $e->getHttpCode() < 500) {
                    throw $e;
                }
                
                // Attendre avant de réessayer
                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                }
            }
        }
        
        throw $lastException;
    }
}
```

---

## Exemples d'intégrations

### Envoi d'email (SendGrid)

```php
class SendGridClient
{
    public function sendEmail(string $to, string $subject, string $content): void
    {
        $response = $this->httpClient->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => [
                'personalizations' => [['to' => [['email' => $to]]]],
                'from' => ['email' => 'noreply@shop.com'],
                'subject' => $subject,
                'content' => [['type' => 'text/html', 'value' => $content]],
            ],
        ]);
    }
}
```

### Stockage cloud (AWS S3)

```php
class S3StorageClient
{
    public function upload(string $key, string $content): string
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $content,
            'ACL' => 'private',
        ]);
        
        return $this->s3Client->getObjectUrl($this->bucket, $key);
    }
}
```

### CRM (HubSpot)

```php
class HubSpotClient
{
    public function createContact(Customer $customer): void
    {
        $this->httpClient->request('POST', 'https://api.hubapi.com/crm/v3/objects/contacts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => [
                'properties' => [
                    'email' => $customer->email,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,
                ],
            ],
        ]);
    }
}
```

---

## Tests

### Mock du client HTTP

```php
class PaymentApiClientTest extends TestCase
{
    public function testCreatePaymentReturnsId(): void
    {
        $mockHttp = $this->createMock(HttpClientInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        
        $mockResponse->method('toArray')->willReturn(['id' => 'pay_123']);
        $mockHttp->method('request')->willReturn($mockResponse);
        
        $client = new PaymentApiClient($mockHttp, 'fake_key');
        $result = $client->createPayment(['amount' => 100]);
        
        $this->assertEquals('pay_123', $result['id']);
    }
}
```

---

**Prochaine section** : [Advanced](../08-advanced/)


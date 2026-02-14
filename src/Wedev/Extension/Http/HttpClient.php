<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Http;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;
use Wepresta_Passwordless_Login\Wedev\Extension\Http\Auth\AuthInterface;

/**
 * Client HTTP unifié avec retry, rate limiting et authentification.
 *
 * Fournit une API fluide pour effectuer des requêtes HTTP
 * avec gestion automatique des erreurs, retries et rate limiting.
 *
 * @example
 * $client = new HttpClient();
 *
 * // Requête simple GET
 * $response = $client->get('https://api.example.com/data');
 *
 * // POST avec JSON
 * $response = $client->postJson('https://api.example.com/users', [
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com'
 * ]);
 *
 * // Avec authentification Bearer
 * $response = $client
 *     ->withAuth(new BearerAuth($token))
 *     ->get('https://api.example.com/protected');
 *
 * // Avec retry automatique
 * $response = $client
 *     ->withAuth(new BearerAuth($apiKey))
 *     ->withRetry(3)
 *     ->withTimeout(60)
 *     ->postJson('https://api.openai.com/v1/chat/completions', [
 *         'model' => 'gpt-4',
 *         'messages' => [['role' => 'user', 'content' => 'Hello!']]
 *     ]);
 *
 * if ($response->isSuccess()) {
 *     $data = $response->json();
 *     echo $data['choices'][0]['message']['content'];
 * }
 */
final class HttpClient implements ExtensionInterface
{
    use LoggerTrait;

    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_USER_AGENT = 'WEDEV-Module/1.0';

    private ?AuthInterface $auth = null;
    private ?RetryStrategy $retryStrategy = null;
    private ?RateLimitHandler $rateLimitHandler = null;
    private int $timeout = self::DEFAULT_TIMEOUT;

    /** @var array<string, string> */
    private array $defaultHeaders = [];

    public static function getName(): string
    {
        return 'Http';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Builder methods (fluent interface)
    // -------------------------------------------------------------------------

    /**
     * Configure l'authentification pour les requêtes.
     */
    public function withAuth(AuthInterface $auth): self
    {
        $clone = clone $this;
        $clone->auth = $auth;

        return $clone;
    }

    /**
     * Active le retry automatique avec backoff exponentiel.
     *
     * @param int  $maxAttempts       Nombre maximum de tentatives (1-10)
     * @param bool $exponentialBackoff Utiliser le backoff exponentiel
     */
    public function withRetry(int $maxAttempts = 3, bool $exponentialBackoff = true): self
    {
        $clone = clone $this;
        $clone->retryStrategy = new RetryStrategy(
            min(max(1, $maxAttempts), 10),
            $exponentialBackoff
        );

        return $clone;
    }

    /**
     * Configure le rate limiting côté client.
     *
     * @param int $maxRequests Nombre maximum de requêtes
     * @param int $perSeconds  Période en secondes
     */
    public function withRateLimit(int $maxRequests, int $perSeconds): self
    {
        $clone = clone $this;
        $clone->rateLimitHandler = new RateLimitHandler($maxRequests, $perSeconds);

        return $clone;
    }

    /**
     * Configure le timeout des requêtes.
     *
     * @param int $seconds Timeout en secondes (1-300)
     */
    public function withTimeout(int $seconds): self
    {
        $clone = clone $this;
        $clone->timeout = min(max(1, $seconds), 300);

        return $clone;
    }

    /**
     * Ajoute un header par défaut.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->defaultHeaders[$name] = $value;

        return $clone;
    }

    /**
     * Ajoute plusieurs headers par défaut.
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->defaultHeaders = array_merge($clone->defaultHeaders, $headers);

        return $clone;
    }

    // -------------------------------------------------------------------------
    // HTTP Methods
    // -------------------------------------------------------------------------

    /**
     * Effectue une requête GET.
     *
     * @param array<string, mixed> $queryParams Paramètres de query string
     */
    public function get(string $url, array $queryParams = []): HttpResponse
    {
        if (!empty($queryParams)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($queryParams);
        }

        return $this->request('GET', $url);
    }

    /**
     * Effectue une requête POST avec FormData.
     *
     * @param array<string, mixed> $data Données du formulaire
     */
    public function post(string $url, array $data = []): HttpResponse
    {
        return $this->request('POST', $url, $data, false);
    }

    /**
     * Effectue une requête POST avec JSON.
     *
     * @param array<string, mixed> $data Données JSON
     */
    public function postJson(string $url, array $data = []): HttpResponse
    {
        return $this->request('POST', $url, $data, true);
    }

    /**
     * Effectue une requête PUT avec JSON.
     *
     * @param array<string, mixed> $data Données JSON
     */
    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->request('PUT', $url, $data, true);
    }

    /**
     * Effectue une requête PATCH avec JSON.
     *
     * @param array<string, mixed> $data Données JSON
     */
    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->request('PATCH', $url, $data, true);
    }

    /**
     * Effectue une requête DELETE.
     */
    public function delete(string $url): HttpResponse
    {
        return $this->request('DELETE', $url);
    }

    // -------------------------------------------------------------------------
    // Core request method
    // -------------------------------------------------------------------------

    /**
     * Effectue une requête HTTP.
     *
     * @param array<string, mixed> $data Données à envoyer
     * @param bool                 $json Envoyer en JSON
     */
    private function request(
        string $method,
        string $url,
        array $data = [],
        bool $json = false
    ): HttpResponse {
        // Rate limiting
        $this->rateLimitHandler?->wait();

        $attempt = 0;
        $maxAttempts = $this->retryStrategy?->getMaxAttempts() ?? 1;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->doRequest($method, $url, $data, $json);

                // Retry on retriable errors
                if ($this->retryStrategy?->shouldRetry($response->getStatusCode()) && $attempt < $maxAttempts) {
                    $delay = (int) $this->retryStrategy->getDelay($attempt);
                    $this->logInfo(sprintf(
                        'HTTP %s %s returned %d, retrying in %dms (%d/%d)',
                        $method,
                        $url,
                        $response->getStatusCode(),
                        $delay,
                        $attempt,
                        $maxAttempts
                    ));
                    usleep($delay * 1000);

                    continue;
                }

                return $response;
            } catch (HttpException $e) {
                $lastException = $e;

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $delay = (int) ($this->retryStrategy?->getDelay($attempt) ?? 1000);
                $this->logWarning(sprintf(
                    'HTTP %s %s failed: %s, retrying in %dms (%d/%d)',
                    $method,
                    $url,
                    $e->getMessage(),
                    $delay,
                    $attempt,
                    $maxAttempts
                ));
                usleep($delay * 1000);
            }
        }

        throw $lastException ?? HttpException::requestFailed('Max retries exceeded');
    }

    /**
     * Effectue réellement la requête HTTP.
     */
    private function doRequest(
        string $method,
        string $url,
        array $data,
        bool $json
    ): HttpResponse {
        $headers = $this->buildHeaders($json);

        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->formatHeaders($headers),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ];

        if (!empty($data)) {
            $options['http']['content'] = $json
                ? json_encode($data, JSON_THROW_ON_ERROR)
                : http_build_query($data);
        }

        $context = stream_context_create($options);

        $this->logDebug(sprintf('HTTP %s %s', $method, $url));

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $error = error_get_last();
            throw HttpException::requestFailed($error['message'] ?? 'Unknown error');
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        $responseHeaders = $this->parseHeaders($http_response_header ?? []);

        $this->logDebug(sprintf('HTTP %s %s -> %d', $method, $url, $statusCode));

        return new HttpResponse($statusCode, $body, $responseHeaders);
    }

    /**
     * Construit les headers de la requête.
     *
     * @return array<string, string>
     */
    private function buildHeaders(bool $json): array
    {
        $headers = array_merge([
            'User-Agent' => self::DEFAULT_USER_AGENT,
            'Accept' => 'application/json',
        ], $this->defaultHeaders);

        if ($json) {
            $headers['Content-Type'] = 'application/json';
        } else {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if ($this->auth !== null) {
            $headers = array_merge($headers, $this->auth->getHeaders());
        }

        return $headers;
    }

    /**
     * Formate les headers pour stream_context.
     */
    private function formatHeaders(array $headers): string
    {
        return implode("\r\n", array_map(
            static fn (string $name, string $value): string => "{$name}: {$value}",
            array_keys($headers),
            array_values($headers)
        ));
    }

    /**
     * Extrait le code HTTP de la réponse.
     *
     * @param array<string> $headers
     */
    private function extractStatusCode(array $headers): int
    {
        if (empty($headers)) {
            return 0;
        }

        // Prendre le dernier status (en cas de redirections)
        foreach (array_reverse($headers) as $header) {
            if (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    /**
     * Parse les headers de la réponse.
     *
     * @param array<string> $rawHeaders
     *
     * @return array<string, string>
     */
    private function parseHeaders(array $rawHeaders): array
    {
        $headers = [];

        foreach ($rawHeaders as $header) {
            if (str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return $headers;
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Jobs;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;

/**
 * Classe de base pour les jobs asynchrones.
 *
 * @example
 * class SendEmailJob extends AbstractJob
 * {
 *     public function __construct(
 *         private readonly string $email,
 *         private readonly string $subject,
 *         private readonly string $content
 *     ) {
 *         parent::__construct();
 *     }
 *
 *     public function handle(): void
 *     {
 *         $this->log('info', "Sending email to {$this->email}");
 *         Mail::Send(
 *             $this->context->language->id,
 *             'custom',
 *             $this->subject,
 *             ['content' => $this->content],
 *             $this->email
 *         );
 *     }
 *
 *     // Sérialisation pour la file d'attente
 *     public function serialize(): array
 *     {
 *         return [
 *             'email' => $this->email,
 *             'subject' => $this->subject,
 *             'content' => $this->content,
 *         ];
 *     }
 *
 *     public static function deserialize(array $data): self
 *     {
 *         return new self($data['email'], $data['subject'], $data['content']);
 *     }
 * }
 */
abstract class AbstractJob implements JobInterface
{
    use LoggerTrait;

    protected int $maxAttempts = 3;
    protected int $retryDelay = 60;  // 1 minute
    protected int $timeout = 300;    // 5 minutes

    /**
     * Retourne le nombre maximum de tentatives.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Retourne le délai entre les tentatives (en secondes).
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * Retourne le timeout du job (en secondes).
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Callback appelé en cas d'échec définitif.
     */
    public function onFailed(\Throwable $exception): void
    {
        $this->log('error', sprintf(
            'Job %s failed after %d attempts: %s',
            static::class,
            $this->maxAttempts,
            $exception->getMessage()
        ));
    }

    /**
     * Sérialise le job pour le stockage.
     *
     * @return array<string, mixed>
     */
    abstract public function serialize(): array;

    /**
     * Désérialise un job depuis les données stockées.
     *
     * @param array<string, mixed> $data
     */
    abstract public static function deserialize(array $data): self;
}


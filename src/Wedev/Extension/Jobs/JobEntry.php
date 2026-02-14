<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Jobs;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Représente une entrée de job dans la file d'attente.
 */
final class JobEntry
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    private ?int $id = null;
    private string $status = self::STATUS_PENDING;
    private int $attempts = 0;
    private ?string $lastError = null;
    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $completedAt = null;
    private \DateTimeImmutable $createdAt;

    /**
     * @param class-string<AbstractJob> $jobClass
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $jobClass,
        private readonly array $payload,
        private readonly int $maxAttempts,
        private readonly int $retryDelay,
        private readonly int $timeout,
        private \DateTimeImmutable $scheduledAt
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return class-string<AbstractJob>
     */
    public function getJobClass(): string
    {
        return $this->jobClass;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // -------------------------------------------------------------------------
    // Setters (pour hydratation depuis DB)
    // -------------------------------------------------------------------------

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function setLastError(?string $error): self
    {
        $this->lastError = $error;

        return $this;
    }

    public function setStartedAt(?\DateTimeImmutable $date): self
    {
        $this->startedAt = $date;

        return $this;
    }

    public function setCompletedAt(?\DateTimeImmutable $date): self
    {
        $this->completedAt = $date;

        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $date): self
    {
        $this->createdAt = $date;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function markAsRunning(): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function incrementAttempt(): void
    {
        $this->attempts++;
    }

    public function reschedule(\DateTimeImmutable $scheduledAt): void
    {
        $this->status = self::STATUS_PENDING;
        $this->scheduledAt = $scheduledAt;
        $this->startedAt = null;
    }

    /**
     * Vérifie si le job peut être exécuté maintenant.
     */
    public function isReady(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->scheduledAt <= new \DateTimeImmutable();
    }
}


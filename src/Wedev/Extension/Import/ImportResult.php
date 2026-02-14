<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Résultat d'un import.
 */
final class ImportResult
{
    /** @var array<array{line: int, message: string}> */
    private array $errors = [];

    /** @var array<array{line: int, message: string}> */
    private array $warnings = [];

    public function __construct(
        private int $processed = 0,
        private int $created = 0,
        private int $updated = 0,
        private int $skipped = 0
    ) {
    }

    // -------------------------------------------------------------------------
    // Incréments
    // -------------------------------------------------------------------------

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function incrementCreated(): void
    {
        $this->created++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
    }

    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    public function addError(int $line, string $message): void
    {
        $this->errors[] = ['line' => $line, 'message' => $message];
    }

    public function addWarning(int $line, string $message): void
    {
        $this->warnings[] = ['line' => $line, 'message' => $message];
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * @return array<array{line: int, message: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<array{line: int, message: string}>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    public function hasErrors(): bool
    {
        return $this->getErrorCount() > 0;
    }

    public function isSuccess(): bool
    {
        return !$this->hasErrors();
    }

    /**
     * Retourne un résumé du résultat.
     *
     * @return array<string, int|array<mixed>>
     */
    public function toArray(): array
    {
        return [
            'processed' => $this->processed,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->getErrorCount(),
            'warnings' => $this->getWarningCount(),
        ];
    }

    /**
     * Résumé textuel.
     */
    public function getSummary(): string
    {
        return sprintf(
            'Processed: %d | Created: %d | Updated: %d | Skipped: %d | Errors: %d',
            $this->processed,
            $this->created,
            $this->updated,
            $this->skipped,
            $this->getErrorCount()
        );
    }
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Jobs;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;

/**
 * Dispatcher de jobs asynchrones.
 *
 * Gère la mise en file d'attente et l'exécution des jobs.
 *
 * @example
 * $dispatcher = new JobDispatcher($repository);
 *
 * // Dispatcher un job
 * $dispatcher->dispatch(new SendEmailJob($email, $subject, $content));
 *
 * // Dispatcher avec délai
 * $dispatcher->dispatch(
 *     new ProcessOrderJob($orderId),
 *     delay: 60  // Exécuter dans 1 minute
 * );
 *
 * // Exécuter les jobs en attente (appelé par CRON)
 * $processed = $dispatcher->processQueue(limit: 10);
 */
final class JobDispatcher implements ExtensionInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly JobRepository $repository
    ) {
    }

    public static function getName(): string
    {
        return 'Jobs';
    }

    public static function getVersion(): string
    {
        return '1.0.0';
    }

    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Met un job en file d'attente.
     *
     * @param int $delay Délai avant exécution (en secondes)
     */
    public function dispatch(AbstractJob $job, int $delay = 0): int
    {
        $scheduledAt = new \DateTimeImmutable('+' . $delay . ' seconds');

        $entry = new JobEntry(
            jobClass: $job::class,
            payload: $job->serialize(),
            maxAttempts: $job->getMaxAttempts(),
            retryDelay: $job->getRetryDelay(),
            timeout: $job->getTimeout(),
            scheduledAt: $scheduledAt
        );

        $id = $this->repository->save($entry);

        $this->log('info', sprintf(
            'Job %s dispatched (ID: %d, scheduled: %s)',
            $job::class,
            $id,
            $scheduledAt->format('Y-m-d H:i:s')
        ));

        return $id;
    }

    /**
     * Traite les jobs en attente.
     *
     * @return int Nombre de jobs traités
     */
    public function processQueue(int $limit = 10): int
    {
        $jobs = $this->repository->getPendingJobs($limit);
        $processed = 0;

        foreach ($jobs as $entry) {
            $this->processJob($entry);
            $processed++;
        }

        return $processed;
    }

    /**
     * Traite un job spécifique.
     */
    private function processJob(JobEntry $entry): void
    {
        $this->log('debug', sprintf('Processing job %s (ID: %d)', $entry->getJobClass(), $entry->getId()));

        // Marquer comme en cours
        $entry->markAsRunning();
        $this->repository->update($entry);

        try {
            $job = $this->instantiateJob($entry);
            $job->handle();

            // Succès
            $entry->markAsCompleted();
            $this->repository->update($entry);

            $this->log('info', sprintf('Job %s completed (ID: %d)', $entry->getJobClass(), $entry->getId()));
        } catch (\Throwable $e) {
            $this->handleJobFailure($entry, $e);
        }
    }

    /**
     * Instancie un job depuis son entrée.
     */
    private function instantiateJob(JobEntry $entry): AbstractJob
    {
        $class = $entry->getJobClass();

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Job class %s not found', $class));
        }

        if (!is_subclass_of($class, AbstractJob::class)) {
            throw new \RuntimeException(sprintf('Job class %s must extend AbstractJob', $class));
        }

        return $class::deserialize($entry->getPayload());
    }

    /**
     * Gère l'échec d'un job.
     */
    private function handleJobFailure(JobEntry $entry, \Throwable $exception): void
    {
        $entry->incrementAttempt();
        $entry->setLastError($exception->getMessage());

        if ($entry->getAttempts() >= $entry->getMaxAttempts()) {
            // Échec définitif
            $entry->markAsFailed();
            $this->repository->update($entry);

            $this->log('error', sprintf(
                'Job %s failed permanently (ID: %d): %s',
                $entry->getJobClass(),
                $entry->getId(),
                $exception->getMessage()
            ));

            // Appeler le callback onFailed
            try {
                $job = $this->instantiateJob($entry);
                $job->onFailed($exception);
            } catch (\Throwable) {
                // Ignorer les erreurs du callback
            }
        } else {
            // Replanifier
            $nextRun = new \DateTimeImmutable('+' . $entry->getRetryDelay() . ' seconds');
            $entry->reschedule($nextRun);
            $this->repository->update($entry);

            $this->log('warning', sprintf(
                'Job %s failed (ID: %d, attempt %d/%d), rescheduled to %s: %s',
                $entry->getJobClass(),
                $entry->getId(),
                $entry->getAttempts(),
                $entry->getMaxAttempts(),
                $nextRun->format('Y-m-d H:i:s'),
                $exception->getMessage()
            ));
        }
    }

    /**
     * Retourne les statistiques de la queue.
     *
     * @return array{pending: int, running: int, completed: int, failed: int}
     */
    public function getQueueStats(): array
    {
        return $this->repository->getStats();
    }

    /**
     * Nettoie les jobs terminés anciens.
     */
    public function cleanup(int $daysToKeep = 7): int
    {
        return $this->repository->deleteOldCompletedJobs($daysToKeep);
    }
}


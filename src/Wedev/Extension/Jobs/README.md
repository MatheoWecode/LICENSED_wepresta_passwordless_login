# Extension Jobs WEDEV

File d'attente de jobs asynchrones pour modules PrestaShop.

## Installation

```bash
wedev ps module new mymodule --ext jobs
```

### Configuration Symfony

```yaml
imports:
    - { resource: '../src/Extension/Jobs/config/services_jobs.yml' }
```

### Installation de la table

Dans le `ModuleInstaller` :

```php
public function installDatabase(): bool
{
    $repository = new JobRepository();
    return $repository->createTable();
}
```

---

## Créer un Job

```php
<?php
declare(strict_types=1);

namespace MyModule\Jobs;

use Wepresta_Passwordless_Login\Extension\Jobs\AbstractJob;

final class SendEmailJob extends AbstractJob
{
    protected int $maxAttempts = 3;
    protected int $retryDelay = 120;  // 2 minutes
    protected int $timeout = 60;       // 1 minute

    public function __construct(
        private readonly string $email,
        private readonly string $subject,
        private readonly string $content
    ) {
    }

    public function handle(): void
    {
        $this->log('info', "Sending email to {$this->email}");

        // Votre logique d'envoi d'email
        $result = \Mail::Send(
            \Configuration::get('PS_LANG_DEFAULT'),
            'custom_template',
            $this->subject,
            ['content' => $this->content],
            $this->email
        );

        if (!$result) {
            throw new \RuntimeException('Failed to send email');
        }
    }

    public function serialize(): array
    {
        return [
            'email' => $this->email,
            'subject' => $this->subject,
            'content' => $this->content,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['email'],
            $data['subject'],
            $data['content']
        );
    }

    public function onFailed(\Throwable $exception): void
    {
        // Notification admin, log spécial, etc.
        parent::onFailed($exception);
    }
}
```

---

## Dispatcher un Job

```php
use Wepresta_Passwordless_Login\Extension\Jobs\JobDispatcher;
use Wepresta_Passwordless_Login\Extension\Jobs\JobRepository;
use MyModule\Jobs\SendEmailJob;

$dispatcher = new JobDispatcher(new JobRepository());

// Exécution immédiate (dès le prochain CRON)
$jobId = $dispatcher->dispatch(
    new SendEmailJob('user@example.com', 'Welcome!', 'Welcome to our store.')
);

// Exécution différée (dans 5 minutes)
$jobId = $dispatcher->dispatch(
    new SendEmailJob('user@example.com', 'Reminder', 'Don\'t forget your cart!'),
    delay: 300
);
```

---

## Traiter la Queue (CRON)

Créer un controller ou script CRON :

```php
// controllers/front/cron.php
class MyModuleCronModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Vérifier le token de sécurité
        $token = Tools::getValue('token');
        if ($token !== Configuration::get('MYMODULE_CRON_TOKEN')) {
            die('Invalid token');
        }

        $dispatcher = new JobDispatcher(new JobRepository());

        // Traiter jusqu'à 20 jobs
        $processed = $dispatcher->processQueue(limit: 20);

        // Réinitialiser les jobs bloqués
        $repository = new JobRepository();
        $reset = $repository->resetStuckJobs();

        die(json_encode([
            'processed' => $processed,
            'reset' => $reset
        ]));
    }
}
```

### Configuration CRON

```bash
# Toutes les minutes
* * * * * curl -s "https://yourshop.com/module/mymodule/cron?token=YOUR_TOKEN" > /dev/null
```

---

## Exemples de Jobs

### Import de produits

```php
final class ImportProductsJob extends AbstractJob
{
    protected int $maxAttempts = 1;  // Pas de retry
    protected int $timeout = 3600;   // 1 heure

    public function __construct(
        private readonly string $filePath
    ) {}

    public function handle(): void
    {
        $importer = new ProductImporter();
        $importer->import($this->filePath);
    }

    public function serialize(): array
    {
        return ['filePath' => $this->filePath];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['filePath']);
    }
}
```

### Génération de rapport

```php
final class GenerateReportJob extends AbstractJob
{
    protected int $timeout = 600;  // 10 minutes

    public function __construct(
        private readonly string $reportType,
        private readonly \DateTimeImmutable $from,
        private readonly \DateTimeImmutable $to,
        private readonly string $recipientEmail
    ) {}

    public function handle(): void
    {
        $report = $this->generateReport();
        $this->sendEmail($report);
    }

    private function generateReport(): string
    {
        // Logique de génération...
        return '/tmp/report.pdf';
    }

    private function sendEmail(string $filePath): void
    {
        // Envoi par email...
    }

    public function serialize(): array
    {
        return [
            'reportType' => $this->reportType,
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'recipientEmail' => $this->recipientEmail,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['reportType'],
            new \DateTimeImmutable($data['from']),
            new \DateTimeImmutable($data['to']),
            $data['recipientEmail']
        );
    }
}
```

---

## Statistiques

```php
$dispatcher = new JobDispatcher(new JobRepository());
$stats = $dispatcher->getQueueStats();

// [
//     'pending' => 15,
//     'running' => 2,
//     'completed' => 1234,
//     'failed' => 3
// ]
```

---

## Maintenance

### Nettoyage automatique

```php
// Supprimer les jobs terminés de plus de 7 jours
$deleted = $dispatcher->cleanup(daysToKeep: 7);
```

### Jobs bloqués

```php
// Réinitialiser les jobs "running" qui ont dépassé leur timeout
$repository = new JobRepository();
$reset = $repository->resetStuckJobs();
```

---

## Structure des Fichiers

```
Extension/Jobs/
├── README.md
├── config/
│   └── services_jobs.yml
├── sql/
│   ├── install.sql
│   └── uninstall.sql
├── JobInterface.php
├── AbstractJob.php
├── JobEntry.php
├── JobDispatcher.php
└── JobRepository.php
```


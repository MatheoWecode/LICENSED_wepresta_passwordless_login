<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Contract\ExtensionInterface;
use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;
use Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser\ParserInterface;

/**
 * Classe de base pour les importeurs.
 *
 * @example
 * class ProductImporter extends AbstractImporter
 * {
 *     protected function getRequiredColumns(): array
 *     {
 *         return ['reference', 'name', 'price'];
 *     }
 *
 *     protected function processRow(array $row, int $lineNumber): void
 *     {
 *         $this->validateRow($row, $lineNumber);
 *
 *         $product = $this->findByReference($row['reference']);
 *
 *         if ($product) {
 *             $this->updateProduct($product, $row);
 *             $this->result->incrementUpdated();
 *         } else {
 *             $this->createProduct($row);
 *             $this->result->incrementCreated();
 *         }
 *     }
 * }
 *
 * // Utilisation
 * $importer = new ProductImporter(new CsvParser());
 * $result = $importer->import('/path/to/products.csv');
 */
abstract class AbstractImporter implements ExtensionInterface
{
    use LoggerTrait;

    protected ImportResult $result;
    protected int $batchSize = 100;
    protected bool $dryRun = false;

    /** @var callable|null */
    protected $progressCallback = null;

    public function __construct(
        protected readonly ParserInterface $parser
    ) {
        $this->result = new ImportResult();
    }

    public static function getName(): string
    {
        return 'Import';
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
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * Définit la taille des batches.
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = max(1, $size);

        return $this;
    }

    /**
     * Active le mode dry-run (simulation sans modification).
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    /**
     * Définit le callback de progression.
     *
     * @param callable(int $current, int $total): void $callback
     */
    public function onProgress(callable $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Import
    // -------------------------------------------------------------------------

    /**
     * Importe un fichier.
     */
    public function import(string $filePath): ImportResult
    {
        $this->result = new ImportResult();

        if (!file_exists($filePath)) {
            $this->result->addError(0, 'File not found: ' . $filePath);

            return $this->result;
        }

        $this->log('info', 'Starting import: ' . basename($filePath));

        // Parser le fichier
        $rows = $this->parser->parse($filePath);
        $total = count($rows);

        // Valider les colonnes
        if (!$this->validateColumns($rows)) {
            return $this->result;
        }

        // Traiter chaque ligne
        $lineNumber = 1; // 1-based pour les messages (header = 0)

        foreach ($rows as $row) {
            $lineNumber++;

            try {
                $this->result->incrementProcessed();
                $this->processRow($row, $lineNumber);
                $this->reportProgress($this->result->getProcessed(), $total);
            } catch (\Throwable $e) {
                $this->result->addError($lineNumber, $e->getMessage());
            }
        }

        $this->log('info', 'Import completed: ' . $this->result->getSummary());

        return $this->result;
    }

    /**
     * Importe depuis un string (contenu du fichier).
     */
    public function importFromString(string $content): ImportResult
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'import_');
        file_put_contents($tempFile, $content);

        try {
            return $this->import($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    // -------------------------------------------------------------------------
    // Abstract methods
    // -------------------------------------------------------------------------

    /**
     * Retourne les colonnes obligatoires.
     *
     * @return array<string>
     */
    abstract protected function getRequiredColumns(): array;

    /**
     * Traite une ligne du fichier.
     *
     * @param array<string, mixed> $row
     */
    abstract protected function processRow(array $row, int $lineNumber): void;

    // -------------------------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------------------------

    /**
     * Valide les colonnes du fichier.
     *
     * @param array<array<string, mixed>> $rows
     */
    protected function validateColumns(array $rows): bool
    {
        if (empty($rows)) {
            $this->result->addError(0, 'File is empty');

            return false;
        }

        $columns = array_keys($rows[0]);
        $required = $this->getRequiredColumns();
        $missing = array_diff($required, $columns);

        if (!empty($missing)) {
            $this->result->addError(0, 'Missing columns: ' . implode(', ', $missing));

            return false;
        }

        return true;
    }

    /**
     * Valide une ligne.
     *
     * @param array<string, mixed> $row
     * @param array<string>        $requiredFields
     *
     * @throws \InvalidArgumentException
     */
    protected function validateRow(array $row, int $lineNumber, array $requiredFields = []): void
    {
        $fields = $requiredFields ?: $this->getRequiredColumns();

        foreach ($fields as $field) {
            if (!isset($row[$field]) || $row[$field] === '') {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }

    // -------------------------------------------------------------------------
    // Progress
    // -------------------------------------------------------------------------

    protected function reportProgress(int $current, int $total): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)($current, $total);
        }
    }
}


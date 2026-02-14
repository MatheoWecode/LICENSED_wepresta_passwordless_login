<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Parser CSV.
 *
 * @example
 * $parser = new CsvParser(delimiter: ';', enclosure: '"');
 * $rows = $parser->parse('/path/to/file.csv');
 *
 * // [
 * //     ['id' => '1', 'name' => 'Product A', 'price' => '19.99'],
 * //     ['id' => '2', 'name' => 'Product B', 'price' => '29.99'],
 * // ]
 */
final class CsvParser implements ParserInterface
{
    public function __construct(
        private readonly string $delimiter = ';',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
        private readonly bool $skipEmptyLines = true
    ) {
    }

    public function parse(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Cannot open file: ' . $filePath);
        }

        $rows = [];
        $headers = null;
        $lineNumber = 0;

        try {
            while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                $lineNumber++;

                // Ignorer les lignes vides
                if ($this->skipEmptyLines && $this->isEmptyRow($row)) {
                    continue;
                }

                // Première ligne = headers
                if ($headers === null) {
                    $headers = array_map('trim', $row);
                    // Normaliser les headers (retirer BOM UTF-8)
                    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");

                    continue;
                }

                // Ignorer si pas assez de colonnes
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }

                // Combiner avec les headers
                $rows[] = array_combine($headers, array_slice($row, 0, count($headers)));
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    public function write(string $filePath, array $data, array $columns): void
    {
        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException('Cannot create file: ' . $filePath);
        }

        try {
            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Headers
            fputcsv($handle, $columns, $this->delimiter, $this->enclosure, $this->escape);

            // Data
            foreach ($data as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $line[] = $row[$column] ?? '';
                }
                fputcsv($handle, $line, $this->delimiter, $this->enclosure, $this->escape);
            }
        } finally {
            fclose($handle);
        }
    }

    public function getContentType(): string
    {
        return 'text/csv; charset=utf-8';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }

    /**
     * Vérifie si une ligne est vide.
     *
     * @param array<mixed> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}


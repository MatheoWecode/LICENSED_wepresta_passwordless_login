<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Parser JSON.
 *
 * Supporte:
 * - Tableau de lignes: [{"id": 1, "name": "A"}, {"id": 2, "name": "B"}]
 * - Objet avec clé: {"products": [{"id": 1}, {"id": 2}]}
 *
 * @example
 * $parser = new JsonParser();
 * $rows = $parser->parse('/path/to/file.json');
 *
 * // Avec clé d'extraction
 * $parser = new JsonParser(dataKey: 'products');
 * $rows = $parser->parse('/path/to/file.json');
 */
final class JsonParser implements ParserInterface
{
    public function __construct(
        private readonly ?string $dataKey = null,
        private readonly bool $prettyPrint = true
    ) {
    }

    public function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException('Cannot read file: ' . $filePath);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Invalid JSON: ' . $e->getMessage());
        }

        // Extraire les données si clé spécifiée
        if ($this->dataKey !== null) {
            if (!isset($data[$this->dataKey])) {
                throw new \RuntimeException("Key '{$this->dataKey}' not found in JSON");
            }
            $data = $data[$this->dataKey];
        }

        if (!is_array($data)) {
            throw new \RuntimeException('JSON must contain an array of objects');
        }

        // Vérifier que c'est un tableau de tableaux
        if (empty($data) || !is_array(reset($data))) {
            return [];
        }

        return $data;
    }

    public function write(string $filePath, array $data, array $columns): void
    {
        // Filtrer les colonnes
        $filteredData = array_map(function (array $row) use ($columns): array {
            $filtered = [];
            foreach ($columns as $column) {
                $filtered[$column] = $row[$column] ?? null;
            }

            return $filtered;
        }, $data);

        // Wrapper si clé spécifiée
        $output = $this->dataKey !== null
            ? [$this->dataKey => $filteredData]
            : $filteredData;

        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($output, $flags);

        if (file_put_contents($filePath, $json) === false) {
            throw new \RuntimeException('Cannot write file: ' . $filePath);
        }
    }

    public function getContentType(): string
    {
        return 'application/json; charset=utf-8';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }
}


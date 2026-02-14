<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Parser XML.
 *
 * @example
 * $parser = new XmlParser(
 *     rootElement: 'products',
 *     rowElement: 'product'
 * );
 *
 * $rows = $parser->parse('/path/to/file.xml');
 *
 * // XML attendu:
 * // <products>
 * //     <product><id>1</id><name>A</name></product>
 * //     <product><id>2</id><name>B</name></product>
 * // </products>
 */
final class XmlParser implements ParserInterface
{
    public function __construct(
        private readonly string $rootElement = 'data',
        private readonly string $rowElement = 'row'
    ) {
    }

    public function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException('Cannot read file: ' . $filePath);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $message = !empty($errors) ? $errors[0]->message : 'Unknown error';

            throw new \RuntimeException('Invalid XML: ' . trim($message));
        }

        $rows = [];

        // Trouver les éléments de ligne
        $elements = $xml->xpath('//' . $this->rowElement);

        if ($elements === false) {
            return [];
        }

        foreach ($elements as $element) {
            $row = [];
            foreach ($element->children() as $child) {
                $row[$child->getName()] = (string) $child;
            }

            // Ajouter les attributs aussi
            foreach ($element->attributes() as $name => $value) {
                $row['@' . $name] = (string) $value;
            }

            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function write(string $filePath, array $data, array $columns): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $this->rootElement . '/>');

        foreach ($data as $row) {
            $element = $xml->addChild($this->rowElement);

            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                // Gérer les caractères spéciaux
                $element->addChild($column, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
            }
        }

        $result = $xml->asXML($filePath);

        if ($result === false) {
            throw new \RuntimeException('Cannot write XML file: ' . $filePath);
        }
    }

    public function getContentType(): string
    {
        return 'application/xml; charset=utf-8';
    }

    public function getFileExtension(): string
    {
        return 'xml';
    }
}


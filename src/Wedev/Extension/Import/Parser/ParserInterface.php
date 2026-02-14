<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser;
if (!defined('_PS_VERSION_')) {
    exit;
}


/**
 * Interface pour les parsers de fichiers.
 */
interface ParserInterface
{
    /**
     * Parse un fichier et retourne les lignes.
     *
     * @return array<array<string, mixed>> Tableau de lignes avec colonnes en clés
     */
    public function parse(string $filePath): array;

    /**
     * Écrit des données dans un fichier.
     *
     * @param array<array<string, mixed>> $data
     * @param array<string>               $columns
     */
    public function write(string $filePath, array $data, array $columns): void;

    /**
     * Retourne le content-type du format.
     */
    public function getContentType(): string;

    /**
     * Retourne l'extension de fichier.
     */
    public function getFileExtension(): string;
}


<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Import;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Core\Trait\LoggerTrait;
use Wepresta_Passwordless_Login\Wedev\Extension\Import\Parser\ParserInterface;

/**
 * Classe de base pour les exporteurs.
 *
 * @example
 * class ProductExporter extends AbstractExporter
 * {
 *     protected function getColumns(): array
 *     {
 *         return ['id', 'reference', 'name', 'price', 'quantity'];
 *     }
 *
 *     protected function getData(): iterable
 *     {
 *         $products = Product::getProducts($this->langId, 0, 0, 'id_product', 'ASC');
 *
 *         foreach ($products as $product) {
 *             yield [
 *                 'id' => $product['id_product'],
 *                 'reference' => $product['reference'],
 *                 'name' => $product['name'],
 *                 'price' => $product['price'],
 *                 'quantity' => StockAvailable::getQuantityAvailableByProduct($product['id_product']),
 *             ];
 *         }
 *     }
 * }
 *
 * // Utilisation
 * $exporter = new ProductExporter(new CsvParser());
 * $exporter->export('/path/to/export.csv');
 */
abstract class AbstractExporter
{
    use LoggerTrait;

    protected int $langId;
    protected int $shopId;

    /** @var callable|null */
    protected $progressCallback = null;

    public function __construct(
        protected readonly ParserInterface $parser
    ) {
        $this->langId = (int) \Configuration::get('PS_LANG_DEFAULT');
        $this->shopId = (int) \Context::getContext()->shop->id;
    }

    /**
     * Définit la langue pour l'export.
     */
    public function setLangId(int $langId): self
    {
        $this->langId = $langId;

        return $this;
    }

    /**
     * Définit la boutique pour l'export.
     */
    public function setShopId(int $shopId): self
    {
        $this->shopId = $shopId;

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

    /**
     * Exporte vers un fichier.
     *
     * @return int Nombre de lignes exportées
     */
    public function export(string $filePath): int
    {
        $this->log('info', 'Starting export to: ' . basename($filePath));

        $data = iterator_to_array($this->getData());
        $count = count($data);

        $this->parser->write($filePath, $data, $this->getColumns());

        $this->log('info', sprintf('Export completed: %d rows', $count));

        return $count;
    }

    /**
     * Exporte vers un string.
     */
    public function exportToString(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');

        try {
            $this->export($tempFile);

            return file_get_contents($tempFile) ?: '';
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Retourne le content-type pour le téléchargement.
     */
    public function getContentType(): string
    {
        return $this->parser->getContentType();
    }

    /**
     * Retourne l'extension de fichier.
     */
    public function getFileExtension(): string
    {
        return $this->parser->getFileExtension();
    }

    // -------------------------------------------------------------------------
    // Abstract methods
    // -------------------------------------------------------------------------

    /**
     * Retourne les colonnes de l'export.
     *
     * @return array<string>
     */
    abstract protected function getColumns(): array;

    /**
     * Retourne les données à exporter.
     *
     * @return iterable<array<string, mixed>>
     */
    abstract protected function getData(): iterable;
}


# Extension Import WEDEV

Framework d'import/export pour modules PrestaShop.

## Installation

```bash
wedev ps module new mymodule --ext import
```

### Configuration Symfony

```yaml
imports:
    - { resource: '../src/Extension/Import/config/services_import.yml' }
```

---

## Créer un Importeur

```php
<?php
declare(strict_types=1);

namespace MyModule\Import;

use Wepresta_Passwordless_Login\Extension\Import\AbstractImporter;
use Wepresta_Passwordless_Login\Extension\Import\Parser\CsvParser;

final class ProductImporter extends AbstractImporter
{
    public function __construct()
    {
        parent::__construct(new CsvParser());
    }

    protected function getRequiredColumns(): array
    {
        return ['reference', 'name', 'price'];
    }

    protected function processRow(array $row, int $lineNumber): void
    {
        $this->validateRow($row, $lineNumber);

        $product = $this->findByReference($row['reference']);

        if ($product) {
            $this->updateProduct($product, $row);
            $this->result->incrementUpdated();
        } else {
            $this->createProduct($row);
            $this->result->incrementCreated();
        }
    }

    private function findByReference(string $reference): ?Product
    {
        $id = Product::getIdByReference($reference);
        return $id ? new Product($id) : null;
    }

    private function updateProduct(Product $product, array $row): void
    {
        if ($this->dryRun) return;

        $product->name[$this->langId] = $row['name'];
        $product->price = (float) $row['price'];
        $product->update();
    }

    private function createProduct(array $row): void
    {
        if ($this->dryRun) return;

        $product = new Product();
        $product->reference = $row['reference'];
        $product->name[$this->langId] = $row['name'];
        $product->price = (float) $row['price'];
        $product->add();
    }
}
```

### Utilisation

```php
$importer = new ProductImporter();

// Mode dry-run (test sans modification)
$importer->setDryRun(true);

// Avec callback de progression
$importer->onProgress(function(int $current, int $total) {
    echo "Progress: {$current}/{$total}\n";
});

// Import
$result = $importer->import('/path/to/products.csv');

echo $result->getSummary();
// "Processed: 100 | Created: 50 | Updated: 45 | Skipped: 5 | Errors: 0"

if ($result->hasErrors()) {
    foreach ($result->getErrors() as $error) {
        echo "Line {$error['line']}: {$error['message']}\n";
    }
}
```

---

## Créer un Exporteur

```php
<?php
declare(strict_types=1);

namespace MyModule\Export;

use Wepresta_Passwordless_Login\Extension\Import\AbstractExporter;
use Wepresta_Passwordless_Login\Extension\Import\Parser\CsvParser;

final class ProductExporter extends AbstractExporter
{
    public function __construct()
    {
        parent::__construct(new CsvParser());
    }

    protected function getColumns(): array
    {
        return ['id', 'reference', 'name', 'price', 'quantity'];
    }

    protected function getData(): iterable
    {
        $products = Product::getProducts($this->langId, 0, 0, 'id_product', 'ASC');

        foreach ($products as $product) {
            yield [
                'id' => $product['id_product'],
                'reference' => $product['reference'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => StockAvailable::getQuantityAvailableByProduct($product['id_product']),
            ];
        }
    }
}
```

### Utilisation

```php
$exporter = new ProductExporter();
$exporter->setLangId(1);

// Vers fichier
$count = $exporter->export('/path/to/export.csv');

// Vers téléchargement
header('Content-Type: ' . $exporter->getContentType());
header('Content-Disposition: attachment; filename="products.' . $exporter->getFileExtension() . '"');
echo $exporter->exportToString();
```

---

## Parsers Disponibles

### CSV

```php
$parser = new CsvParser(
    delimiter: ';',      // Par défaut: ;
    enclosure: '"',      // Par défaut: "
    escape: '\\',        // Par défaut: \
    skipEmptyLines: true // Par défaut: true
);
```

### JSON

```php
// Tableau simple: [{...}, {...}]
$parser = new JsonParser();

// Objet avec clé: {"products": [{...}, {...}]}
$parser = new JsonParser(dataKey: 'products');
```

### XML

```php
$parser = new XmlParser(
    rootElement: 'products',  // Élément racine
    rowElement: 'product'     // Élément de chaque ligne
);

// <products>
//     <product><id>1</id><name>A</name></product>
// </products>
```

---

## Gestion des Erreurs

```php
$result = $importer->import($file);

// Erreurs bloquantes
foreach ($result->getErrors() as $error) {
    // ['line' => 15, 'message' => 'Missing required field: price']
}

// Avertissements
foreach ($result->getWarnings() as $warning) {
    // ['line' => 20, 'message' => 'Stock quantity is negative']
}

// Résumé
$result->toArray();
// [
//     'processed' => 100,
//     'created' => 50,
//     'updated' => 45,
//     'skipped' => 5,
//     'errors' => 3,
//     'warnings' => 2,
// ]
```

---

## Import en Background (avec Jobs)

```php
use Wepresta_Passwordless_Login\Extension\Jobs\AbstractJob;

final class ImportProductsJob extends AbstractJob
{
    protected int $timeout = 3600;  // 1 heure

    public function __construct(
        private readonly string $filePath
    ) {}

    public function handle(): void
    {
        $importer = new ProductImporter();
        $result = $importer->import($this->filePath);

        // Notifier l'admin du résultat
        $service = new NotificationService();
        $service->notifyAdmins(
            'Import completed',
            $result->getSummary()
        );
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

// Dispatcher
$dispatcher->dispatch(new ImportProductsJob($uploadedFile));
```

---

## Structure des Fichiers

```
Extension/Import/
├── README.md
├── config/
│   └── services_import.yml
├── Parser/
│   ├── ParserInterface.php
│   ├── CsvParser.php
│   ├── JsonParser.php
│   └── XmlParser.php
├── AbstractExporter.php
├── AbstractImporter.php
└── ImportResult.php
```


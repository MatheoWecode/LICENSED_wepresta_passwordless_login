# Extension Audit WEDEV

Journal d'audit pour traçabilité et conformité RGPD.

## Installation

```bash
wedev ps module new mymodule --ext audit
```

### Configuration Symfony

```yaml
imports:
    - { resource: '../src/Extension/Audit/config/services_audit.yml' }
```

### Installation de la table

Dans le `ModuleInstaller` :

```php
public function installDatabase(): bool
{
    $repository = new AuditRepository();
    return $repository->createTable();
}
```

---

## Utilisation

### Avec le Logger

```php
use Wepresta_Passwordless_Login\Extension\Audit\AuditLogger;
use Wepresta_Passwordless_Login\Extension\Audit\AuditRepository;

$logger = new AuditLogger(new AuditRepository());

// Logger une création
$logger->logCreate('Product', $product->id, [
    'name' => $product->name,
    'price' => $product->price,
    'active' => $product->active,
]);

// Logger une modification
$logger->logUpdate('Product', $product->id, $oldData, $newData);

// Logger une suppression
$logger->logDelete('Product', $product->id, $deletedData);

// Logger une consultation
$logger->logView('Customer', $customer->id);

// Logger un export
$logger->logExport('Order', [
    'count' => 150,
    'format' => 'csv',
    'filters' => ['date_from' => '2024-01-01'],
]);

// Logger un import
$logger->logImport('Product', [
    'file' => 'products.csv',
    'count' => 500,
    'errors' => 3,
]);
```

### Avec le Trait

```php
use Wepresta_Passwordless_Login\Extension\Audit\AuditableTrait;

class ProductService
{
    use AuditableTrait;

    public function updateProduct(int $id, array $data): void
    {
        $product = new Product($id);

        // Capturer l'état avant modification
        $oldData = [
            'name' => $product->name,
            'price' => $product->price,
            'active' => $product->active,
        ];

        // Appliquer les modifications
        $product->name = $data['name'];
        $product->price = $data['price'];
        $product->save();

        // Capturer l'état après modification
        $newData = [
            'name' => $product->name,
            'price' => $product->price,
            'active' => $product->active,
        ];

        // Logger automatiquement
        $this->auditUpdate('Product', $id, $oldData, $newData);
    }

    public function deleteProduct(int $id): void
    {
        $product = new Product($id);
        $data = ['name' => $product->name, 'price' => $product->price];

        $product->delete();

        $this->auditDelete('Product', $id, $data);
    }
}
```

---

## Recherche dans l'Historique

```php
// Historique d'une entité
$history = $logger->getEntityHistory('Product', 123);

foreach ($history as $entry) {
    echo sprintf(
        "%s: %s by %s (%s)\n",
        $entry->getCreatedAt()->format('Y-m-d H:i'),
        $entry->getAction(),
        $entry->getUserName(),
        $entry->getIpAddress()
    );

    // Voir les changements
    foreach ($entry->getChanges() as $field => $change) {
        echo sprintf(
            "  - %s: %s → %s\n",
            $field,
            json_encode($change['old']),
            json_encode($change['new'])
        );
    }
}
```

### Recherche avancée

```php
$entries = $logger->search([
    'action' => 'update',
    'entity_type' => 'Product',
    'user_id' => 5,
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
], limit: 100);
```

### Historique utilisateur

```php
// Toutes les actions d'un employé
$userHistory = $logger->getUserHistory(employeeId: 5, limit: 50);
```

---

## Types d'Actions

| Action | Constante | Description |
|--------|-----------|-------------|
| `create` | `ACTION_CREATE` | Création d'entité |
| `update` | `ACTION_UPDATE` | Modification d'entité |
| `delete` | `ACTION_DELETE` | Suppression d'entité |
| `view` | `ACTION_VIEW` | Consultation d'entité |
| `export` | `ACTION_EXPORT` | Export de données |
| `import` | `ACTION_IMPORT` | Import de données |
| `login` | `ACTION_LOGIN` | Connexion utilisateur |
| `logout` | `ACTION_LOGOUT` | Déconnexion utilisateur |
| `custom` | `ACTION_CUSTOM` | Action personnalisée |

---

## Affichage Back-Office

### Controller Grid

```php
use Wepresta_Passwordless_Login\Extension\Audit\AuditLogger;
use Wepresta_Passwordless_Login\Extension\Audit\AuditRepository;

class AdminAuditController extends AbstractAdminController
{
    public function indexAction(Request $request): Response
    {
        $logger = new AuditLogger(new AuditRepository());

        $entries = $logger->search([
            'entity_type' => $request->get('entity_type'),
        ], limit: 50);

        return $this->render('@MyModule/admin/audit/index.html.twig', [
            'entries' => $entries,
        ]);
    }
}
```

### Template Twig

```twig
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Action</th>
            <th>Entity</th>
            <th>User</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>
        {% for entry in entries %}
        <tr>
            <td>{{ entry.createdAt|date('Y-m-d H:i') }}</td>
            <td>
                <span class="badge badge-{{ entry.action == 'delete' ? 'danger' : 'info' }}">
                    {{ entry.action }}
                </span>
            </td>
            <td>{{ entry.entityType }} #{{ entry.entityId }}</td>
            <td>{{ entry.userName ?: 'System' }}</td>
            <td><code>{{ entry.ipAddress }}</code></td>
        </tr>
        {% endfor %}
    </tbody>
</table>
```

---

## Maintenance

### Nettoyage automatique

```php
// Supprimer les entrées de plus de 365 jours
$deleted = $logger->cleanup(daysToKeep: 365);
```

### Tâche CRON

```php
// Ajouter au CRON hebdomadaire
$logger->cleanup(daysToKeep: Configuration::get('MYMODULE_AUDIT_RETENTION', 365));
```

---

## Conformité RGPD

L'audit trail aide à la conformité RGPD en permettant de :

1. **Tracer les accès** aux données personnelles
2. **Documenter les modifications** de données
3. **Prouver les suppressions** (droit à l'oubli)
4. **Identifier les responsables** (qui a fait quoi)

### Bonnes pratiques

```php
// Ne pas stocker de données sensibles dans l'audit
$auditData = [
    'email' => '***@example.com',  // Masquer partiellement
    'phone' => '****1234',          // Derniers chiffres seulement
    'name' => $customer->name,      // OK
];

$logger->logUpdate('Customer', $id, $oldData, $auditData);
```

---

## Structure des Fichiers

```
Extension/Audit/
├── README.md
├── config/
│   └── services_audit.yml
├── sql/
│   ├── install.sql
│   └── uninstall.sql
├── AuditEntry.php
├── AuditLogger.php
├── AuditRepository.php
└── AuditableTrait.php
```


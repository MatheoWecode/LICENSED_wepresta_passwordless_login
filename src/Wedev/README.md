# WEDEV Core

Shared framework for PrestaShop modules built with WEDEV CLI.

## Version

Current version: **1.0.0** (see `.wedev-version`)

## Structure

```
wedev-core/
├── Core/                    # Core framework
│   ├── Adapter/             # PrestaShop adapters
│   │   ├── ConfigurationAdapter.php
│   │   ├── ContextAdapter.php
│   │   └── ShopAdapter.php
│   ├── Contract/            # Interfaces
│   │   ├── ConfigurableInterface.php
│   │   ├── ExtensionInterface.php
│   │   ├── InstallableInterface.php
│   │   ├── PluginInterface.php      # NEW: Third-party plugins
│   │   ├── RepositoryInterface.php
│   │   └── ServiceInterface.php
│   ├── Exception/           # Exception classes
│   ├── Extension/           # Extension loader
│   ├── Plugin/              # NEW: Plugin system
│   │   ├── PluginDiscovery.php
│   │   ├── PluginInfo.php
│   │   └── PluginRegistry.php
│   ├── Repository/          # Abstract repository
│   ├── Security/            # NEW: Security utilities
│   │   └── InputValidator.php
│   ├── Service/             # Core services
│   └── Trait/               # Reusable traits
├── Extension/               # Optional extensions
│   ├── Audit/               # GDPR audit logging
│   ├── EntityPicker/        # AJAX entity selection
│   ├── Http/                # HTTP client
│   ├── Import/              # CSV/JSON/XML import
│   ├── Jobs/                # Async job queue
│   ├── Notifications/       # Multi-channel notifications
│   ├── Rules/               # Business rules engine
│   └── UI/                  # Twig/Smarty/JS components
├── .wedev-version           # Version tracking
└── CHANGELOG.md             # Change history
```

## Usage in Modules

### Do NOT Modify

The `src/Wedev/` directory in modules is managed by WEDEV CLI. To customize:

```php
// GOOD: Extend in your namespace
namespace MyModule\Infrastructure\Repository;

use MyModule\Wedev\Core\Repository\AbstractRepository;

class MyRepository extends AbstractRepository
{
    // Your customizations
}
```

### Update Core

```bash
# Interactive update
wedev ps module
# Select "🔄 Mettre à jour le Core"

# Or direct command
wedev core sync
```

## New Features in 1.0.0

### Plugin System

Third-party modules can extend WEDEV-based modules:

```php
use MyModule\Wedev\Core\Contract\PluginInterface;

final class MyPlugin implements PluginInterface
{
    public static function getName(): string { return 'MyPlugin'; }
    public static function getVersion(): string { return '1.0.0'; }
    public static function getDependencies(): array { return []; }
    
    public function boot(): void { /* init */ }
    public function getFieldTypes(): array { return []; }
    public function getServices(): array { return []; }
}
```

Plugins are auto-discovered from:
- `modules/[name]/src/Plugin/`
- `themes/[name]/modules/[name]/Plugin/`

### InputValidator

Centralized input validation:

```php
use MyModule\Wedev\Core\Security\InputValidator;

$slug = InputValidator::slug($userInput);
$email = InputValidator::email($userEmail);
$html = InputValidator::html($userHtml);
$page = InputValidator::integer($_GET['page'], 1, 100);
```

## Development

### Editing wedev-core

1. Edit files in `src/wedev-core/`
2. Update `.wedev-version` (bump version)
3. Update `CHANGELOG.md`
4. Sync to template: `wedev core sync`

### Future: Separate Repository

This directory is designed to be extracted to a standalone repo:

```bash
# Future command
git subtree split -P src/wedev-core -b wedev-core
```

This enables:
- Dedicated version control
- PR workflow for improvements
- Independent release cycle

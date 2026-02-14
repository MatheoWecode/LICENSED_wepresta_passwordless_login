# Architecture WEDEV Core + Extensions

## Vue d'Ensemble

```
┌────────────────────────────────────────────────────────────────────────────┐
│                              MODULE PRESTASHOP                              │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │                         CODE MÉTIER DU MODULE                         │ │
│  │                                                                       │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  │ │
│  │  │ Application │  │   Domain    │  │Infrastructure│  │Presentation │  │ │
│  │  │  Services   │  │  Entities   │  │  Adapters   │  │ Controllers │  │ │
│  │  │  Commands   │  │  Repos I/F  │  │  Repos Impl │  │  Templates  │  │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘  │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                      │                                      │
│                                      ▼                                      │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │                    EXTENSIONS WEDEV (optionnelles)                    │ │
│  │                                                                       │ │
│  │  ┌─────┐ ┌──────┐ ┌───────┐ ┌──────┐ ┌───────┐ ┌───────┐ ┌────────┐  │ │
│  │  │ UI  │ │ Http │ │ Rules │ │ Jobs │ │ Audit │ │ Notif │ │ Import │  │ │
│  │  │1200L│ │1350L │ │1850L  │ │1150L │ │ 500L  │ │ 700L  │ │  640L  │  │ │
│  │  └─────┘ └──────┘ └───────┘ └──────┘ └───────┘ └───────┘ └────────┘  │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                      │                                      │
│                                      ▼                                      │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │                         CORE WEDEV (stable)                           │ │
│  │                           ~2,400 lignes                               │ │
│  │                                                                       │ │
│  │  ┌──────────┐  ┌──────────┐  ┌───────────┐  ┌────────┐  ┌─────────┐  │ │
│  │  │ Adapters │  │Contracts │  │ Exceptions│  │ Traits │  │ Services│  │ │
│  │  └──────────┘  └──────────┘  └───────────┘  └────────┘  └─────────┘  │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                      │                                      │
│                                      ▼                                      │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │                          PRESTASHOP 8.x/9.x                           │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────┘
```

---

## Core Stable (~2,400 lignes)

### Adapters

Abstraction des composants PrestaShop pour un code testable.

```
Core/Adapter/
├── ConfigurationAdapter.php   # Wrapper Configuration
├── ContextAdapter.php         # Wrapper Context
└── ShopAdapter.php            # Multi-shop helpers
```

| Classe | Méthodes Principales |
|--------|---------------------|
| `ConfigurationAdapter` | `get()`, `set()`, `delete()`, `hasKey()` |
| `ContextAdapter` | `getShopId()`, `getLangId()`, `getCustomerId()`, `getCartId()` |
| `ShopAdapter` | `isMultiShopActive()`, `forEachShop()`, `getAllShopIds()` |

### Contracts

Interfaces stables garantissant la compatibilité.

```
Core/Contract/
├── ConfigurableInterface.php   # getConfig(), setConfig()
├── ExtensionInterface.php      # getName(), getVersion(), getDependencies()
├── InstallableInterface.php    # install(), uninstall()
├── RepositoryInterface.php     # find(), findAll(), save(), remove()
└── ServiceInterface.php        # Marqueur pour DI
```

### Exceptions

Exceptions typées pour une gestion d'erreurs précise.

```
Core/Exception/
├── ModuleException.php          # Base exception
├── EntityNotFoundException.php  # ::forId(), ::forCriteria()
├── ValidationException.php      # ::forField(), ::forConstraint()
├── ConfigurationException.php   # ::missingKey(), ::invalidValue()
└── DependencyException.php      # ::extensionNotFound()
```

### Traits

Code réutilisable via composition.

```
Core/Trait/
├── LoggerTrait.php        # log($level, $message)
├── ModuleAwareTrait.php   # getModule(), getService()
├── MultiShopTrait.php     # isMultiShopActive(), forEachShop()
└── TranslatorTrait.php    # trans($key, $domain)
```

### Repository

Classe de base pour l'accès aux données.

```
Core/Repository/
└── AbstractRepository.php
    ├── findOneBy(array $criteria)
    ├── findBy(array $criteria, array $orderBy, int $limit)
    ├── insert(array $data): int
    ├── update(int $id, array $data): bool
    ├── deleteBy(array $criteria): int
    └── count(array $criteria): int
```

### Services

Utilitaires partagés.

```
Core/Service/
└── CacheService.php
    ├── get(string $key): mixed
    ├── set(string $key, mixed $value, int $ttl)
    ├── delete(string $key)
    └── clear(string $prefix)
```

### Extension Loader

Détection et chargement des extensions.

```
Core/Extension/
└── ExtensionLoader.php
    ├── isAvailable(string $extension): bool
    ├── require(string $extension): void
    ├── getAvailableExtensions(): array
    └── getExtensionInfo(string $extension): array
```

---

## Extensions

### UI (~1,200 lignes)

Composants d'interface utilisateur.

```
Extension/UI/
├── Twig/
│   ├── UiExtension.php          # wedev_icon(), wedev_alert()
│   └── Macros/
│       ├── admin.html.twig      # card(), button(), status_badge()
│       ├── forms.html.twig      # form_group(), switch()
│       └── grids.html.twig      # grid_header(), pagination()
├── Smarty/
│   ├── function.wedev_icon.php
│   ├── function.wedev_button.php
│   └── function.wedev_alert.php
├── Assets/
│   ├── scss/_variables.scss     # Variables PS9
│   └── js/admin/
│       ├── wedev-core.js        # Alpine components
│       └── utils/               # ajax, confirm, notify
└── Templates/admin/_partials/
```

### Http (~1,350 lignes)

Client HTTP robuste.

```
Extension/Http/
├── HttpClient.php               # Client principal
├── HttpResponse.php             # Réponse typée
├── HttpException.php            # Exceptions HTTP
├── RetryStrategy.php            # Retry avec backoff
├── RateLimitHandler.php         # Rate limiting
└── Auth/
    ├── AuthInterface.php
    ├── BearerAuth.php
    ├── ApiKeyAuth.php
    ├── BasicAuth.php
    └── OAuth2Auth.php
```

### Rules (~1,850 lignes)

Moteur de règles métier.

```
Extension/Rules/
├── RuleEngine.php               # Évaluation
├── RuleBuilder.php              # Builder fluent
├── RuleContext.php              # Contexte
├── Condition/
│   ├── ConditionInterface.php
│   ├── AbstractCondition.php
│   ├── CartCondition.php
│   ├── CustomerCondition.php
│   ├── ProductCondition.php
│   ├── DateCondition.php
│   ├── AndCondition.php
│   ├── OrCondition.php
│   └── NotCondition.php
└── Action/
    ├── ActionInterface.php
    ├── SetContextAction.php
    ├── LogAction.php
    ├── CallableAction.php
    └── CompositeAction.php
```

### Jobs (~1,150 lignes)

File d'attente asynchrone.

```
Extension/Jobs/
├── AbstractJob.php              # Classe de base
├── JobDispatcher.php            # Dispatcher
├── JobEntry.php                 # Entrée de queue
├── JobRepository.php            # Persistance
└── sql/
    ├── install.sql
    └── uninstall.sql
```

### Audit (~500 lignes)

Journal d'audit RGPD.

```
Extension/Audit/
├── AuditLogger.php              # Logger principal
├── AuditEntry.php               # Entrée
├── AuditRepository.php          # Persistance
├── AuditableTrait.php           # Trait
└── sql/
    ├── install.sql
    └── uninstall.sql
```

### Notifications (~700 lignes)

Notifications multi-canal.

```
Extension/Notifications/
├── NotificationService.php      # Service principal
├── Notification.php             # Simple
├── TemplateNotification.php     # Avec template PS
└── Channel/
    ├── ChannelInterface.php
    ├── EmailChannel.php
    ├── SmsChannel.php
    └── PushChannel.php
```

### Import (~640 lignes)

Import/Export de données.

```
Extension/Import/
├── AbstractImporter.php         # Base import
├── AbstractExporter.php         # Base export
├── ImportResult.php             # Résultat
└── Parser/
    ├── ParserInterface.php
    ├── CsvParser.php
    ├── JsonParser.php
    └── XmlParser.php
```

---

## Statistiques Finales

| Composant | Lignes | % du Total |
|-----------|--------|------------|
| **Core** | ~2,400 | 20.5% |
| UI | ~1,200 | 10.3% |
| Http | ~1,350 | 11.5% |
| Rules | ~1,850 | 15.8% |
| Jobs | ~1,150 | 9.8% |
| Audit | ~500 | 4.3% |
| Notifications | ~700 | 6.0% |
| Import | ~640 | 5.5% |
| **Total Extensions** | ~7,390 | 63.2% |
| **TOTAL** | **~11,700** | **100%** |

---

## Flux de Données

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Request   │ ──▶ │  Controller │ ──▶ │   Service   │
└─────────────┘     └─────────────┘     └─────────────┘
                                               │
                                               ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   Entity    │ ◀── │ Repository  │
                    └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ┌─────────────┐
                                        │  Database   │
                                        └─────────────┘
```

---

## Principes de Conception

### 1. Immutabilité du Core

Le Core ne change jamais de manière incompatible. Seules les additions sont permises.

### 2. Extensions Optionnelles

Chaque extension est indépendante et optionnelle. Un module peut fonctionner avec seulement le Core.

### 3. Composition > Héritage

Les traits et interfaces sont préférés à l'héritage profond.

### 4. Dépendances Explicites

Chaque extension déclare ses dépendances via `getDependencies()`.

### 5. Configuration Externe

Aucune configuration hardcodée. Tout passe par `ConfigurationAdapter`.

---

## Versioning

```
Core     : MAJOR.MINOR.PATCH (jamais de breaking change en MINOR/PATCH)
Extension: MAJOR.MINOR.PATCH (breaking changes possibles en MAJOR)

Exemple:
- Core 1.0.0 → 1.1.0 : Nouvelles méthodes (compatible)
- Core 1.0.0 → 2.0.0 : JAMAIS (Core est toujours v1.x)
- Http 1.0.0 → 2.0.0 : Possible si breaking changes
```


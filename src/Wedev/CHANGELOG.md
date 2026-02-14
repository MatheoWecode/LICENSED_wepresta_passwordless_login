# WEDEV Core Changelog

All notable changes to WEDEV Core will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-12-26

### Added
- **Core/Contract/SubPluginInterface**: Generic interface for "plugins that extend plugins" pattern
  - Extends PluginInterface with getType(), getDescription(), getAuthor()
  - Use for ACF Bricks, Payment Gateways, Shipping Carriers, etc.
- **Core/Contract/AssetProviderInterface**: Standard contract for plugins providing frontend/admin assets
  - getAdminJsAssets(), getAdminCssAssets(), getFrontJsAssets(), getFrontCssAssets()
- **Extension/Events**: Full event-driven architecture extension
  - AbstractDomainEvent: Base class for all domain events
  - DomainEventDispatcher: Dispatches events to registered subscribers with priority and wildcard support
  - EventSubscriberInterface: Contract for event listeners
  - Generic/EntityCreatedEvent: Reusable entity creation event
  - Generic/EntityUpdatedEvent: Reusable entity update event with diff helpers
  - Generic/EntityDeletedEvent: Reusable entity deletion event

## [1.0.0] - 2025-12-26

### Added
- Initial release of WEDEV Core
- **Core/Adapter**: ConfigurationAdapter, ContextAdapter, ShopAdapter
- **Core/Contract**: ExtensionInterface, PluginInterface, ConfigurableInterface, InstallableInterface, RepositoryInterface, ServiceInterface
- **Core/Exception**: EntityNotFoundException, ValidationException, ConfigurationException, DependencyException, ModuleException
- **Core/Plugin**: PluginDiscovery, PluginRegistry for third-party extensibility
- **Core/Repository**: AbstractRepository with CRUD and Many-to-Many support
- **Core/Security**: InputValidator for centralized input validation
- **Core/Service**: CacheService
- **Core/Trait**: LoggerTrait, TranslatorTrait, MultiShopTrait, ModuleAwareTrait
- **Extension/Audit**: GDPR-compliant audit logging
- **Extension/EntityPicker**: AJAX entity selection for admin forms
- **Extension/Http**: HTTP client with retry, rate limiting, OAuth2
- **Extension/Import**: CSV, JSON, XML import/export
- **Extension/Jobs**: Async job queue system
- **Extension/Notifications**: Multi-channel notifications (Email, SMS, Push)
- **Extension/Rules**: Business rules engine with conditions and actions
- **Extension/UI**: Twig macros, Smarty functions, Alpine.js components

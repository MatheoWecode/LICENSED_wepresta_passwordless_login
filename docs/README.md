# Documentation Module Starter PRO

Bienvenue dans la documentation du **Module Starter PRO**, un template professionnel pour développer des modules PrestaShop 8.x/9.x.

## Public cible

Cette documentation s'adresse aux :
- **Développeurs web expérimentés** (PHP, Symfony, JS, Git) découvrant PrestaShop
- **Administrateurs PrestaShop** souhaitant créer leur premier module

## Comment utiliser cette documentation

1. **Débutant PrestaShop ?** Commencez par [01-Getting Started](./01-getting-started/) puis [02-PrestaShop Basics](./02-prestashop-basics/)
2. **Développeur confirmé ?** Allez directement à [04-Architecture](./04-architecture/) et [05-Quality Assurance](./05-quality-assurance/)
3. **Besoin d'une référence rapide ?** Consultez [99-Reference](./99-reference/)

## Relation avec les Cursor Rules

Ce module inclut des **règles Cursor** (`.cursor/rules/*.mdc`) contenant des références techniques détaillées avec exemples de code. Cette documentation est **complémentaire** :

| Documentation `docs/` | Cursor Rules `.mdc` |
|----------------------|---------------------|
| Concepts expliqués | Exemples de code |
| Workflows CLI | Patterns techniques |
| Tutoriels pas-à-pas | Checklists |
| Schémas et diagrammes | Anti-patterns |

Chaque page renvoie vers le fichier `.mdc` correspondant pour approfondir.

---

## Sommaire

### [01 - Getting Started](./01-getting-started/)

Démarrage rapide avec WEDEV CLI et DDEV.

- [Installation](./01-getting-started/installation.md) — Installer le module avec wedev_cli, DDEV ou manuellement
- [Prérequis](./01-getting-started/prerequisites.md) — PHP, Node.js, Composer, DDEV
- [Premiers pas](./01-getting-started/first-steps.md) — Vérifications post-installation

### [02 - PrestaShop Basics](./02-prestashop-basics/)

Comprendre PrestaShop pour les développeurs web.

- [Architecture PrestaShop](./02-prestashop-basics/architecture.md) — MVC, dossiers, autoload
- [Cycle de vie d'un module](./02-prestashop-basics/module-lifecycle.md) — Install, enable, hooks
- [Système de Hooks](./02-prestashop-basics/hooks-explained.md) — Action vs Display hooks
- [Configuration](./02-prestashop-basics/configuration.md) — Configuration::get/set, base de données
- [Front vs Admin](./02-prestashop-basics/front-vs-admin.md) — Différences front-office / back-office

### [03 - Development Workflow](./03-development-workflow/)

Workflows de développement quotidiens.

- [Générer un module](./03-development-workflow/generate-module.md) — Via WEDEV CLI
- [Cycle de développement](./03-development-workflow/development-cycle.md) — Watch, dev, debug, build
- [Workflow Assets](./03-development-workflow/assets-workflow.md) — Webpack Encore, SCSS, JS
- [Migrations base de données](./03-development-workflow/database-migrations.md) — SQL, upgrades
- [Mise à jour du Core partagé](./03-development-workflow/update-shared-code.md) — Synchronisation GitHub

### [04 - Architecture](./04-architecture/)

Clean Architecture appliquée à PrestaShop.

- [Clean Architecture](./04-architecture/clean-architecture.md) — Domain, Application, Infrastructure
- [Pattern CQRS](./04-architecture/cqrs-pattern.md) — Commands et Queries
- [Services et DI](./04-architecture/services-di.md) — Injection de dépendances Symfony
- [Grid Framework](./04-architecture/grid-framework.md) — Tableaux admin modernes
- [Form Types](./04-architecture/form-types.md) — Formulaires Symfony

### [05 - Quality Assurance](./05-quality-assurance/)

Qualité de code et tests.

- [Analyse statique](./05-quality-assurance/static-analysis.md) — PHPStan expliqué
- [Tests unitaires](./05-quality-assurance/unit-testing.md) — PHPUnit expliqué
- [Code Coverage](./05-quality-assurance/code-coverage.md) — Mesure de couverture
- [Style de code](./05-quality-assurance/code-style.md) — PHP-CS-Fixer
- [Refactoring](./05-quality-assurance/refactoring.md) — Rector
- [Workflow QA](./05-quality-assurance/qa-workflow.md) — Checklist avant commit

### [06 - CI/CD](./06-ci-cd/)

Intégration et déploiement continus.

- [Git Basics](./06-ci-cd/git-basics.md) — Branches, commits pour ce projet
- [GitHub Actions](./06-ci-cd/github-actions.md) — CI/CD expliqué
- [Tests automatisés](./06-ci-cd/automated-tests.md) — Pipeline de tests
- [Release Process](./06-ci-cd/release-process.md) — Build et publication

### [07 - API Integrations](./07-api-integrations/)

APIs et intégrations externes.

- [API REST](./07-api-integrations/rest-api.md) — Endpoints du module
- [Webhooks](./07-api-integrations/webhooks.md) — Événements sortants
- [Services externes](./07-api-integrations/external-services.md) — Intégrations tierces

### [08 - Advanced](./08-advanced/)

Sujets avancés.

- [Multi-boutique](./08-advanced/multistore.md) — Support multi-shop
- [Performance](./08-advanced/performance.md) — Cache, optimisation
- [Sécurité](./08-advanced/security.md) — Bonnes pratiques
- [PrestaShop 9](./08-advanced/ps9-specifics.md) — Spécificités PS9

### [09 - Troubleshooting](./09-troubleshooting/)

Résolution de problèmes.

- [Erreurs fréquentes](./09-troubleshooting/common-errors.md) — Solutions courantes
- [Debugging](./09-troubleshooting/debugging.md) — Techniques de debug
- [Logs](./09-troubleshooting/logs.md) — Logs PrestaShop et module

### [99 - Reference](./99-reference/)

Références rapides.

- [Commandes CLI](./99-reference/cli-commands.md) — Toutes les commandes
- [Clés de configuration](./99-reference/configuration-keys.md) — Variables de config
- [Référence des Hooks](./99-reference/hooks-reference.md) — Hooks utilisables
- [Glossaire](./99-reference/glossary.md) — Termes techniques

---

## Ressources externes

- [WEDEV CLI](https://github.com/wecode/wedev-cli) — Générateur de modules
- [PrestaShop DevDocs](https://devdocs.prestashop-project.org/) — Documentation officielle
- [Symfony Documentation](https://symfony.com/doc) — Framework PHP
- [PHPStan](https://phpstan.org/) — Analyse statique

---

*Documentation générée avec le template Module Starter PRO — WEDEV CLI*


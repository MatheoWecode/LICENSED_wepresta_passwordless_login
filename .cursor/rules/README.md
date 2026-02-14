# 📚 Règles Cursor pour Module PrestaShop

Ces règles Cursor sont conçues pour accompagner le développement de modules PrestaShop 8.x/9.x avec les meilleures pratiques modernes.

## 🗂️ Liste des Règles

| Fichier | Description | alwaysApply |
|---------|-------------|:-----------:|
| `000-module-base.mdc` | Règles de base, conventions, anti-patterns | ✅ |
| `001-module-architecture.mdc` | Clean Architecture, layers, DDD | ❌ |
| `002-module-hooks.mdc` | Hooks PrestaShop display/action | ❌ |
| `003-module-services.mdc` | Services Symfony, DI | ❌ |
| `004-module-controllers.mdc` | Contrôleurs admin et front | ❌ |
| `005-module-forms.mdc` | Form Types Symfony | ❌ |
| `006-module-database.mdc` | Entities, Repositories, SQL | ❌ |
| `007-module-frontend.mdc` | JS, SCSS, Webpack, Templates | ❌ |
| `008-module-testing.mdc` | Tests PHPUnit | ❌ |
| `009-module-security.mdc` | Sécurité SQL, XSS, CSRF | ❌ |
| `010-module-api.mdc` | API REST | ❌ |
| `011-module-quality.mdc` | PHPStan, PHP-CS-Fixer, CI | ❌ |
| `019-module-grids.mdc` | **Grid Framework PrestaShop** ⚠️ | ❌ |

## 🎯 Comment ça fonctionne

### Règles `alwaysApply: true`
Ces règles sont **toujours actives** quand vous travaillez dans le module:
- `000-module-base.mdc` - Conventions et standards de base

### Règles contextuelles (`alwaysApply: false`)
Ces règles sont activées **automatiquement** selon les fichiers que vous éditez:

- Édition dans `src/Domain/` → Active `001-module-architecture.mdc`
- Édition dans `modulestarter.php` → Active `002-module-hooks.mdc`
- Édition dans `config/services.yml` → Active `003-module-services.mdc`
- Édition dans `src/Presentation/Controller/` → Active `004-module-controllers.mdc`
- Édition dans `src/Application/Form/` → Active `005-module-forms.mdc`
- Édition dans `sql/` → Active `006-module-database.mdc`
- Édition dans `_dev/js/` ou `_dev/scss/` → Active `007-module-frontend.mdc`
- Édition dans `tests/` → Active `008-module-testing.mdc`
- Édition dans tout fichier PHP → Active `009-module-security.mdc`
- Édition dans `src/Infrastructure/Api/` → Active `010-module-api.mdc`
- Édition dans `phpstan.neon` → Active `011-module-quality.mdc`
- Édition dans `src/Presentation/Grid/` → Active `019-module-grids.mdc`

## 🚀 Pour Commencer

### 1. Structure recommandée
Lorsque vous créez un nouveau module, suivez cette structure:

```
mymodule/
├── .cursor/
│   └── rules/           ← Ces règles sont copiées ici
├── config/
│   ├── routes.yml
│   └── services.yml
├── src/
│   ├── Application/
│   ├── Domain/
│   ├── Infrastructure/
│   └── Presentation/
├── controllers/front/
├── views/
├── sql/
├── tests/
├── _dev/
├── mymodule.php
└── composer.json
```

### 2. Commandes utiles

```bash
# Qualité du code
composer cs-check      # Vérifier le style
composer cs-fix        # Corriger le style
composer phpstan       # Analyse statique
composer phpunit       # Tests

# Frontend
npm run dev            # Build dev + watch
npm run build          # Build production
```

### 3. Workflow de développement

1. **Créer l'entité** dans `src/Domain/Entity/`
2. **Définir l'interface** repository dans `src/Domain/Repository/`
3. **Implémenter** le repository dans `src/Infrastructure/Repository/`
4. **Créer le service** dans `src/Application/Service/`
5. **Enregistrer** dans `config/services.yml`
6. **Créer le contrôleur** si nécessaire
7. **Ajouter les templates**
8. **Écrire les tests**

## 📖 Contenu des Règles

### 000 - Base
- Conventions de nommage
- Structure du projet
- Standards PHP obligatoires
- Anti-patterns à éviter

### 001 - Architecture
- Clean Architecture (Domain, Application, Infrastructure, Presentation)
- Entities et Value Objects
- Repository Pattern
- Injection de dépendances

### 002 - Hooks
- Display hooks (displayHeader, displayHome, etc.)
- Action hooks (actionValidateOrder, actionCartSave, etc.)
- Bonnes pratiques (validation, cache, services)

### 003 - Services
- Configuration `services.yml`
- Autowiring et autoconfiguration
- Services PrestaShop disponibles
- Event Subscribers

### 004 - Controllers
- Contrôleurs admin Symfony avec Grid
- Contrôleurs front legacy
- CRUD complet
- Sécurité @AdminSecurity

### 005 - Forms
- Form Types Symfony
- Types PrestaShop (SwitchType, TranslatableType, etc.)
- Validation avec contraintes
- Templates Twig

### 006 - Database
- Scripts SQL install/uninstall
- Entities Doctrine
- Repositories Doctrine et Legacy
- Sécurité SQL (pSQL, cast int)

### 007 - Frontend
- Configuration Webpack
- JavaScript ES6+ avec classes
- SCSS avec BEM
- Templates Smarty et Twig

### 008 - Testing
- Configuration PHPUnit
- Tests unitaires (Value Objects, Entities, Services)
- Tests d'intégration
- Mocks et fixtures

### 009 - Security
- Protection SQL Injection
- Protection XSS
- Protection CSRF
- Validation des entrées
- Upload sécurisé

### 010 - API
- Contrôleur API REST
- Authentification API Key / Bearer
- Format des réponses JSON
- CORS et Rate Limiting

### 011 - Quality
- Configuration PHPStan
- Configuration PHP-CS-Fixer
- Configuration Rector
- GitHub Actions CI/CD

### 019 - Grids ⚠️ IMPORTANT
- **Configuration EXPLICITE obligatoire** (pas d'auto-registration!)
- GridDefinitionFactory avec colonnes, filtres, actions
- GridQueryBuilder avec requêtes SQL
- 4 services à configurer par Grid
- Éviter l'erreur "Cannot autowire $dbPrefix"

## 💡 Tips

### Forcer l'activation d'une règle
Dans le chat Cursor, mentionnez la règle:
```
@001-module-architecture Comment implémenter un repository?
```

### Personnaliser les règles
Éditez les fichiers `.mdc` pour adapter les règles à votre projet.

### Désactiver une règle
Changez `alwaysApply: true` en `alwaysApply: false` ou supprimez le fichier.

## 🔗 Ressources

- [Documentation PrestaShop 8](https://devdocs.prestashop-project.org/)
- [Symfony Form Types](https://symfony.com/doc/current/forms.html)
- [PHPStan](https://phpstan.org/)
- [PHP-CS-Fixer](https://cs.symfony.com/)

---

*Ces règles sont maintenues par l'équipe WEDEV CLI.*


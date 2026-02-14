# Glossaire

Définitions des termes techniques utilisés dans cette documentation.

## A

### API (Application Programming Interface)
Interface permettant à des applications de communiquer entre elles. Une API REST utilise HTTP pour échanger des données en JSON.

### Autoloader
Mécanisme PHP qui charge automatiquement les classes quand elles sont utilisées, sans require manuel.

### Autowiring
Fonctionnalité Symfony qui injecte automatiquement les dépendances en analysant les types des paramètres.

## B

### Back-office
Interface d'administration de PrestaShop, accessible via `/admin-xxx/`.

### Bootstrap
Framework CSS utilisé pour le design du back-office PrestaShop.

## C

### Cache
Stockage temporaire de données pour éviter des calculs ou requêtes répétitifs.

### CI/CD
- **CI** (Continuous Integration) : Tests automatiques à chaque push
- **CD** (Continuous Deployment) : Déploiement automatique après validation

### Clean Architecture
Pattern d'architecture séparant le code en couches (Domain, Application, Infrastructure, Presentation).

### Composer
Gestionnaire de dépendances PHP. Lit `composer.json` et installe les packages dans `vendor/`.

### Context
Singleton PrestaShop contenant l'état de la requête (shop, language, customer, cart, etc.).

### CQRS
Command Query Responsibility Segregation. Sépare les opérations de lecture (Query) et d'écriture (Command).

### CSRF
Cross-Site Request Forgery. Attaque exploitant une session active. Prévenue par des tokens.

## D

### DDEV
Outil de développement local basé sur Docker, simplifiant la gestion d'environnements PHP.

### DI (Dependency Injection)
Pattern où les dépendances sont passées à un objet plutôt que créées par lui.

### Doctrine
ORM (Object-Relational Mapping) utilisé par Symfony pour gérer les entités et la base de données.

### DTO (Data Transfer Object)
Objet simple transportant des données entre les couches de l'application.

## E

### Entity
Classe représentant un objet métier, généralement persisté en base de données.

### Event Subscriber
Classe Symfony écoutant des événements et réagissant à ceux-ci.

## F

### Form Type
Classe Symfony définissant la structure et la validation d'un formulaire.

### Front-office
Interface publique de la boutique PrestaShop, visible par les clients.

## G

### Grid Framework
Système PrestaShop pour créer des tableaux admin avec tri, filtres et pagination.

### GitHub Actions
Service CI/CD de GitHub exécutant des workflows automatisés.

## H

### Handler
Classe exécutant une Command ou Query dans le pattern CQRS.

### Hook
Point d'extension dans PrestaShop où un module peut injecter du code ou du contenu.

## I

### IDE
Integrated Development Environment. Éditeur de code avancé (VS Code, PhpStorm).

### Infrastructure (couche)
Partie de Clean Architecture contenant les implémentations concrètes (BDD, API, etc.).

## J

### JSON
JavaScript Object Notation. Format d'échange de données léger et lisible.

## L

### Legacy
Code ancien utilisant des patterns ou technologies dépassées.

## M

### MVC
Model-View-Controller. Pattern architectural séparant données, logique et présentation.

### Multistore
Fonctionnalité PrestaShop permettant de gérer plusieurs boutiques depuis une installation.

## N

### Namespace
Espace de noms PHP regroupant des classes. Ex: `MonModule\Application\Service`.

### npm
Node Package Manager. Gestionnaire de dépendances JavaScript.

## O

### ObjectModel
Classe de base PrestaShop pour les entités (legacy).

### ORM
Object-Relational Mapping. Technique mappant des objets PHP vers des tables SQL.

## P

### PHPStan
Outil d'analyse statique détectant des erreurs dans le code PHP sans l'exécuter.

### PHPUnit
Framework de tests unitaires pour PHP.

### PSR
PHP Standards Recommendations. Normes de codage PHP (PSR-1, PSR-4, PSR-12...).

## R

### Rector
Outil de refactoring automatisé pour PHP.

### Repository
Classe abstraisant l'accès aux données (lecture/écriture en base).

### REST
Representational State Transfer. Style d'architecture pour les APIs web.

## S

### Smarty
Moteur de templates utilisé par le front-office PrestaShop.

### Symfony
Framework PHP utilisé par PrestaShop depuis la version 1.7.

## T

### Token
Chaîne secrète validant une requête (CSRF, API).

### Twig
Moteur de templates Symfony, utilisé par le back-office PrestaShop.

## U

### Unit Test
Test vérifiant une unité de code isolée (méthode, classe).

## V

### Value Object
Objet immutable représentant une valeur (ex: Money, Email).

### Validator
Composant vérifiant que des données respectent des règles.

## W

### Webpack Encore
Wrapper Symfony autour de Webpack pour compiler CSS/JS.

### Webhook
Requête HTTP envoyée automatiquement lors d'un événement.

### WEDEV CLI
Outil en ligne de commande pour générer et gérer des modules PrestaShop.

## X

### Xdebug
Extension PHP pour le debugging et le profiling.

### XSS
Cross-Site Scripting. Attaque injectant du JavaScript malveillant.

## Y

### YAML
Format de configuration lisible (utilisé par Symfony).

---

## Acronymes courants

| Acronyme | Signification |
|----------|---------------|
| API | Application Programming Interface |
| CI/CD | Continuous Integration / Continuous Deployment |
| CORS | Cross-Origin Resource Sharing |
| CRUD | Create, Read, Update, Delete |
| CSS | Cascading Style Sheets |
| DTO | Data Transfer Object |
| HTML | HyperText Markup Language |
| HTTP | HyperText Transfer Protocol |
| IDE | Integrated Development Environment |
| JS | JavaScript |
| JSON | JavaScript Object Notation |
| MVC | Model-View-Controller |
| ORM | Object-Relational Mapping |
| PHP | PHP: Hypertext Preprocessor |
| PSR | PHP Standards Recommendation |
| REST | Representational State Transfer |
| SQL | Structured Query Language |
| TTL | Time To Live |
| URL | Uniform Resource Locator |
| XML | eXtensible Markup Language |

---

*Fin de la documentation*


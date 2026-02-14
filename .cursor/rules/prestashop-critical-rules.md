# ============================================================================
# RÈGLES CRITIQUES POUR L'AGENT IA - MODULES PRESTASHOP
# ============================================================================
# Document généré après correction de 10+ erreurs sur le module wepresta_stripe_installments
# À ajouter dans .cursor/rules/ pour éviter ces erreurs à l'avenir
# ============================================================================

## ERREUR 1 : HOOKS NON IMPLÉMENTÉS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"Hook displayShoppingCart has been registered but hookDisplayShoppingCart has not been defined"

### Pourquoi j'ai fait cette erreur :
J'ai ajouté des hooks dans la constante HOOKS[] sans créer les méthodes correspondantes.
J'ai supposé que les méthodes existaient ou seraient créées plus tard.

### RÈGLE ABSOLUE :
AVANT d'ajouter un hook dans HOOKS[] :
Créer IMMÉDIATEMENT la méthode hookNomDuHook()
Le nom de la méthode = "hook" + nom du hook en PascalCase
Même si la méthode est vide, elle DOIT exister

Exemple :
HOOKS[] = ['displayShoppingCart']
→ CRÉER : public function hookDisplayShoppingCart(array $params): string { return ''; }

VÉRIFICATION :
grep "public function hook" module.php | wc -l
doit être >= nombre de hooks dans HOOKS[]


## ERREUR 2 : VIOLATION DU LISKOV SUBSTITUTION PRINCIPLE (LSP)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"Declaration of findById(): ?Entity must be compatible with AbstractRepository::findById(): ?array"

### Pourquoi j'ai fait cette erreur :
J'ai surchargé findById() en changeant le type de retour (?Entity au lieu de ?array).
Le parent AbstractRepository retourne ?array, pas une entité.

### RÈGLE ABSOLUE :
NE JAMAIS surcharger findById() avec un type de retour différent.

SOLUTION :
Dans l'interface : définir find(int $id): ?Entity

Dans l'implémentation :
public function find(int $id): ?Entity {
    $data = parent::findById($id); // retourne ?array
    return $data ? $this->hydrateEntity($data) : null;
}

private function hydrateEntity(array $data): Entity {
    return Entity::fromArray($data);
}

VÉRIFICATION :
grep -r "public function findById" src/Infrastructure/Repository/
→ NE DOIT PAS avoir de type de retour Entity


## ERREUR 3 : NOMS DE PARAMÈTRES INCORRECTS DANS SERVICES.YML
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"method __construct() has no argument named $dashboardService"

### Pourquoi j'ai fait cette erreur :
J'ai inventé des noms de paramètres dans services.yml sans vérifier le vrai constructeur.
J'ai supposé que les noms étaient corrects au lieu de les vérifier.

### RÈGLE ABSOLUE :
TOUJOURS lire le constructeur AVANT de configurer services.yml.

PROCESSUS OBLIGATOIRE :
grep -A10 "public function construct" src/Path/To/Class.php
Noter EXACTEMENT les noms des paramètres
Utiliser CES MÊMES NOMS dans services.yml

Exemple :
// PHP
public function construct(TranslatorInterface $translator, MyService $orderService)

// services.yml - CORRECT ✅
arguments:
    $translator: '@translator'
    $orderService: '@my.service'

// services.yml - INCORRECT ❌
arguments:
    $trans: '@translator'      # Mauvais nom !
    $myService: '@my.service'  # Mauvais nom !


## ERREUR 4 : CLASSES DÉCLARÉES MAIS NON CRÉÉES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"class PaymentConfigurationType does not exist"

### Pourquoi j'ai fait cette erreur :
J'ai déclaré des services dans services.yml pour des classes qui n'existaient pas encore.
J'ai planifié de les créer "plus tard" mais oublié.

### RÈGLE ABSOLUE :
CRÉER LA CLASSE AVANT DE LA DÉCLARER DANS SERVICES.YML

PROCESSUS :
Créer le fichier PHP avec le bon namespace
Vérifier la syntaxe : php -l fichier.php
ENSUITE déclarer dans services.yml
Vérifier : php -l config/services.yml

JAMAIS L'INVERSE !


## ERREUR 5 : NOMS DE SERVICES PRESTASHOP INCORRECTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreurs rencontrées :
- "non-existent service prestashop.adapter.legacy.db" → correct: legacy_db
- "non-existent service prestashop.core.grid.query.doctrine_search_criteria_applicator" → correct: sans "grid"

### Pourquoi j'ai fait cette erreur :
J'ai deviné les noms des services PrestaShop au lieu de les vérifier.
Les conventions de nommage PrestaShop ne sont pas intuitives.

### RÈGLE ABSOLUE :
NOMS CORRECTS DES SERVICES PRESTASHOP NATIFS :

✅ CORRECT                                                      ❌ INCORRECT
@prestashop.adapter.legacy_db                                   @prestashop.adapter.legacy.db
@prestashop.core.query.doctrine_search_criteria_applicator      @prestashop.core.grid.query.doctrine_search_criteria_applicator
@doctrine.dbal.default_connection                               @doctrine.connection
@translator                                                     @prestashop.translator
@prestashop.core.hook.dispatcher                                @hook.dispatcher

EN CAS DE DOUTE :
Chercher dans le code PrestaShop ou utiliser l'autocomplétion de Symfony.


## ERREUR 6 : PARAMÈTRES PRESTASHOP INEXISTANTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"You have requested a non-existent parameter prestashop.default_shop_id"

### Pourquoi j'ai fait cette erreur :
J'ai inventé des paramètres %prestashop.xxx% qui n'existent pas.
J'ai supposé que PrestaShop exposait ces valeurs comme paramètres.

### RÈGLE ABSOLUE :
PARAMÈTRES PRESTASHOP VALIDES :
%database_prefix%               ✅ (existe)
%prestashop.default_shop_id%    ❌ (N'EXISTE PAS)
%prestashop.default_language_id% ❌ (N'EXISTE PAS)

SOLUTION pour shop_id et lang_id :
Utiliser une FACTORY qui récupère les valeurs depuis Context::getContext()

public function createMyService(): MyService {
    $shopId = \Context::getContext()->shop->id;
    $langId = \Context::getContext()->language->id;
    return new MyService((int) $shopId, (int) $langId);
}


## ERREUR 7 : GRID DEFINITION FACTORY AVEC MAUVAIS ARGUMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
"AbstractGridDefinitionFactory::__construct() has no argument named $dbPrefix"

### Pourquoi j'ai fait cette erreur :
J'ai ajouté des arguments à GridDefinitionFactory alors que la classe parente n'en accepte pas.
J'ai confondu DefinitionFactory et QueryBuilder.

### RÈGLE ABSOLUE :
CONFIGURATION DES GRIDS PRESTASHOP :

GridDefinitionFactory :
AUCUN argument dans services.yml
Seulement le tag: tags: ['grid.definition_factory']

GridQueryBuilder :
Arguments requis :
    $connection: '@doctrine.dbal.default_connection'
    $dbPrefix: '%database_prefix%'
Pour shopId/langId : utiliser une FACTORY

GridDataFactory :
Arguments : $gridQueryBuilder, $hookDispatcher, $queryParser, $gridId

GridFactory :
Arguments : $definitionFactory, $dataFactory, $filterFormFactory


## ERREUR 8 : CONTRÔLEURS ADMIN SANS $translator
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
Paramètres incorrects pour les contrôleurs Admin.

### Pourquoi j'ai fait cette erreur :
Je n'ai pas vérifié que TOUS les contrôleurs Admin utilisent TranslatorInterface.
J'ai copié-collé des configurations sans vérifier chaque constructeur.

### RÈGLE ABSOLUE :
TOUS LES CONTRÔLEURS ADMIN ONT $translator EN PREMIER PARAMÈTRE

Pattern standard :
public function construct(
    TranslatorInterface $translator, // TOUJOURS en premier
    // ... autres dépendances
) { }

Configuration services.yml :
MyController:
    arguments:
        $translator: '@translator' # TOUJOURS en premier
        # ... autres arguments
    tags: ['controller.service_arguments']


## ERREUR 9 : INSTALLATEURS CONFIGURÉS VIA DI
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
Configuration complexe des installateurs qui ne fonctionne pas.

### Pourquoi j'ai fait cette erreur :
J'ai essayé de configurer les installateurs via DI alors qu'ils se créent eux-mêmes.
ModuleInstaller crée ses sous-installateurs dans son constructeur.

### RÈGLE ABSOLUE :
LES INSTALLATEURS NE SE CONFIGURENT PAS VIA DI

ModuleInstaller crée lui-même :
TabInstaller
HookInstaller
DatabaseInstaller
ConfigurationInstaller

Dans services.yml, NE PAS déclarer les installateurs.
Ils sont instanciés manuellement dans le module principal :
$installer = new ModuleInstaller($this);
$installer->install();


## ERREUR 10 : FACTORY MAL CONFIGURÉE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### Erreur rencontrée :
StripeClient avec mauvais arguments.

### Pourquoi j'ai fait cette erreur :
J'ai passé un adaptateur de configuration au lieu des valeurs brutes.
Le constructeur de StripeClient attend des strings, pas un objet.

### RÈGLE ABSOLUE :
UTILISER UNE FACTORY POUR LES OBJETS AVEC CONFIGURATION COMPLEXE

// INCORRECT ❌
StripeClient:
    arguments:
        $config: '@my.config.adapter' # Le constructeur attend des strings !

// CORRECT ✅
// Dans l'adaptateur :
public function createStripeClient(): StripeClient {
    return new StripeClient(
        $this->getSecretKey(),
        $this->getWebhookSecret(),
        $this->isTestMode()
    );
}

// Dans services.yml :
stripe.client:
    class: StripeClient
    factory: ['@my.config.adapter', 'createStripeClient']


# ============================================================================
# CHECKLIST OBLIGATOIRE AVANT CHAQUE MODIFICATION DE SERVICES.YML
# ============================================================================
☐ 1. Lire le constructeur : grep -A15 "public function __construct" fichier.php
☐ 2. Noter EXACTEMENT les noms des paramètres
☐ 3. Vérifier les types (scalaires vs objets vs interfaces)
☐ 4. Pour les scalaires (int, string) : valeur directe ou factory
☐ 5. Pour les objets : référence service @service.name
☐ 6. Vérifier les noms des services PrestaShop natifs
☐ 7. Créer toutes les classes AVANT de les déclarer
☐ 8. Tester : php -l config/services.yml
☐ 9. GridDefinitionFactory = PAS d'arguments
☐ 10. GridQueryBuilder = factory pour shopId/langId

# ============================================================================
# COMMANDES DE VÉRIFICATION À EXÉCUTER SYSTÉMATIQUEMENT
# ============================================================================
# Vérifier la syntaxe de tous les fichiers
php -l config/services.yml
php -l config/routes.yml
find src -name "*.php" -exec php -l {} \;

# Vérifier les hooks
grep "'[a-zA-Z]*'" module.php | wc -l  # Nombre de hooks déclarés
grep "public function hook" module.php | wc -l  # Nombre de méthodes

# Vérifier les constructeurs avant de configurer
grep -A15 "public function __construct" src/Path/To/Class.php

# ============================================================================
# FIN DU DOCUMENT - À UTILISER COMME RÈGLE CURSOR
# ============================================================================

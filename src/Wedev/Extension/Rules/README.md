# Extension Rules WEDEV

Moteur de règles métier pour modules PrestaShop.

## Installation

```bash
wedev ps module new mymodule --ext rules
```

### Configuration Symfony

```yaml
imports:
    - { resource: '../src/Extension/Rules/config/services_rules.yml' }
```

---

## Concepts

### Règle = Condition + Action

Une règle combine :
- **Condition** : quand appliquer la règle
- **Action** : que faire quand la condition est vraie
- **Priorité** : ordre d'évaluation (plus haut = premier)

### Contexte

Le `RuleContext` contient toutes les données nécessaires :
- Panier actuel
- Client connecté
- Commande (si applicable)
- Données personnalisées

---

## Utilisation Basique

```php
use Wepresta_Passwordless_Login\Extension\Rules\RuleEngine;
use Wepresta_Passwordless_Login\Extension\Rules\RuleBuilder;
use Wepresta_Passwordless_Login\Extension\Rules\RuleContext;
use Wepresta_Passwordless_Login\Extension\Rules\Condition\CartCondition;
use Wepresta_Passwordless_Login\Extension\Rules\Action\SetContextAction;

// Créer une règle
$rule = RuleBuilder::create('free_shipping')
    ->when(new CartCondition('total', '>=', 50))
    ->then(new SetContextAction('show_free_shipping_banner', true))
    ->priority(10)
    ->build();

// Évaluer
$engine = new RuleEngine();
$context = RuleContext::fromCurrentCart();

if ($engine->evaluate($rule, $context)) {
    // La condition est vraie
}

// Ou évaluer et exécuter l'action
$engine->executeFirst([$rule], $context);
```

---

## Conditions Disponibles

### CartCondition

```php
// Total panier
new CartCondition('total', '>=', 100)
new CartCondition('total_without_tax', '<', 50)

// Nombre de produits
new CartCondition('products_count', '>', 3)

// Contient un produit
new CartCondition('has_product', 'in', [123, 456])

// Contient une catégorie
new CartCondition('has_category', 'in', [5, 10])

// Poids
new CartCondition('weight', '<', 5.0)

// Panier vide
new CartCondition('is_empty', '=', false)
```

### CustomerCondition

```php
// Connecté
new CustomerCondition('is_logged', '=', true)

// Groupe client
new CustomerCondition('group', '=', 3)
new CustomerCondition('group', 'in', [3, 4, 5])

// Nombre de commandes
new CustomerCondition('orders_count', '>=', 5)

// Montant total dépensé
new CustomerCondition('total_spent', '>', 500)

// Nouveau client
new CustomerCondition('is_new', '=', true)

// Newsletter
new CustomerCondition('newsletter', '=', true)

// Domaine email
new CustomerCondition('email_domain', '=', 'gmail.com')
```

### ProductCondition

```php
// ID produit
new ProductCondition('id', '=', 123)

// Catégorie
new ProductCondition('category', 'in', [5, 10])

// Prix
new ProductCondition('price', '>', 50)

// Stock
new ProductCondition('stock', '<', 10)

// En promotion
new ProductCondition('on_sale', '=', true)

// Nouveau produit
new ProductCondition('is_new', '=', true)
```

### DateCondition

```php
// Weekend
new DateCondition('is_weekend', '=', true)

// Heures de bureau
new DateCondition('is_business_hours', '=', true)

// Heure spécifique
new DateCondition('hour', '>=', 18)

// Jour de la semaine (0=dim, 6=sam)
new DateCondition('day_of_week', 'in', [0, 6])

// Mois
new DateCondition('month', '=', 12)

// Période
new DateCondition('date_range', 'in', ['2024-12-01', '2024-12-31'])

// Plage horaire
new DateCondition('time_range', 'in', ['09:00', '18:00'])
```

---

## Opérateurs

| Opérateur | Description |
|-----------|-------------|
| `=` | Égalité |
| `!=` | Différent |
| `>` | Supérieur |
| `<` | Inférieur |
| `>=` | Supérieur ou égal |
| `<=` | Inférieur ou égal |
| `in` | Dans la liste |
| `not_in` | Pas dans la liste |
| `contains` | Contient (chaîne) |
| `starts_with` | Commence par |
| `ends_with` | Termine par |

---

## Conditions Composites

### AND (toutes vraies)

```php
// Méthode 1: RuleBuilder
$rule = RuleBuilder::create('vip_promo')
    ->when(new CustomerCondition('group', '=', 3))
    ->and(new CartCondition('total', '>=', 100))
    ->and(new DateCondition('is_weekend', '=', true))
    ->then($action)
    ->build();

// Méthode 2: AndCondition directe
$condition = new AndCondition([
    new CustomerCondition('group', '=', 3),
    new CartCondition('total', '>=', 100),
]);
```

### OR (au moins une vraie)

```php
// Méthode 1: RuleBuilder
$rule = RuleBuilder::create('promo')
    ->when(new DateCondition('is_weekend', '=', true))
    ->or(new CustomerCondition('is_new', '=', true))
    ->then($action)
    ->build();

// Méthode 2: OrCondition directe
$condition = new OrCondition([
    new DateCondition('is_weekend', '=', true),
    new CustomerCondition('is_new', '=', true),
]);
```

### NOT (négation)

```php
$condition = new NotCondition(
    new CustomerCondition('is_logged', '=', true)
);
```

---

## Actions Disponibles

### SetContextAction

```php
// Définit une variable Smarty
new SetContextAction('show_banner', true)
new SetContextAction('promo_message', '10% de réduction !')
```

### LogAction

```php
// Log avec interpolation
new LogAction('VIP discount for customer {customer_id}, cart: {cart_total}€')
```

### CallableAction

```php
// Action personnalisée
new CallableAction(function(RuleContext $context) {
    $cart = $context->getCart();
    // Logique personnalisée...
});
```

### CompositeAction

```php
// Plusieurs actions
new CompositeAction([
    new SetContextAction('promo_applied', true),
    new LogAction('Promo applied'),
]);
```

---

## Exemples Concrets

### Free Shipping Banner

```php
$rules = [
    RuleBuilder::create('free_shipping_eligible')
        ->when(new CartCondition('total', '>=', 50))
        ->then(new SetContextAction('free_shipping_eligible', true))
        ->priority(10)
        ->build(),

    RuleBuilder::create('free_shipping_almost')
        ->when(new CartCondition('total', '>=', 40))
        ->and(new CartCondition('total', '<', 50))
        ->then(new SetContextAction('free_shipping_remaining', 50 - $cartTotal))
        ->priority(5)
        ->build(),
];

$engine = new RuleEngine();
$engine->executeFirst($rules, RuleContext::fromCurrentCart());
```

### VIP Customer Detection

```php
$rule = RuleBuilder::create('vip_customer')
    ->when(new CustomerCondition('group', 'in', [3, 4]))
    ->or(new CustomerCondition('total_spent', '>=', 1000))
    ->then(new CompositeAction([
        new SetContextAction('is_vip', true),
        new SetContextAction('vip_discount', 10),
    ]))
    ->build();
```

### Happy Hour Promotion

```php
$rule = RuleBuilder::create('happy_hour')
    ->when(new DateCondition('hour', '>=', 18))
    ->and(new DateCondition('hour', '<', 20))
    ->and(new DateCondition('day_of_week', 'in', [5])) // Vendredi
    ->then(new SetContextAction('happy_hour_active', true))
    ->build();
```

---

## Structure des Fichiers

```
Extension/Rules/
├── README.md
├── config/
│   └── services_rules.yml
├── RuleInterface.php
├── Rule.php
├── RuleBuilder.php
├── RuleContext.php
├── RuleEngine.php
├── Condition/
│   ├── ConditionInterface.php
│   ├── AbstractCondition.php
│   ├── AndCondition.php
│   ├── OrCondition.php
│   ├── NotCondition.php
│   ├── CartCondition.php
│   ├── CustomerCondition.php
│   ├── ProductCondition.php
│   └── DateCondition.php
└── Action/
    ├── ActionInterface.php
    ├── CallableAction.php
    ├── CompositeAction.php
    ├── SetContextAction.php
    └── LogAction.php
```


# Système de Hooks

> Référence technique détaillée : [.cursor/rules/002-module-hooks.mdc](../../.cursor/rules/002-module-hooks.mdc)

Les **hooks** (crochets) sont le mécanisme central d'extension de PrestaShop.

## Qu'est-ce qu'un hook ?

Un hook est un **point d'accroche** dans le code PrestaShop où votre module peut :
- **Injecter du contenu** (HTML, CSS, JS)
- **Réagir à un événement** (commande validée, produit ajouté)
- **Modifier des données** (prix, panier)

```
┌─────────────────────────────────────────────────────────────┐
│                      Page PrestaShop                        │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ displayHeader ← hook: CSS, JS, meta                 │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ displayTop ← hook: Bannière                          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ displayHome ← hook: Contenu accueil                  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ displayFooter ← hook: Pied de page                   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Types de hooks

### Display Hooks (Affichage)

Les hooks `display*` **retournent du HTML** affiché à l'écran.

| Hook | Emplacement | Usage courant |
|------|-------------|---------------|
| `displayHeader` | `<head>` | CSS, JS, meta |
| `displayTop` | Haut de page | Bannière, notice |
| `displayHome` | Page d'accueil | Contenu promotionnel |
| `displayFooter` | Pied de page | Liens, widgets |
| `displayProductAdditionalInfo` | Fiche produit | Infos supplémentaires |
| `displayShoppingCart` | Panier | Upsell, messages |

### Action Hooks (Événements)

Les hooks `action*` **réagissent à des événements** sans retourner d'HTML.

| Hook | Déclencheur | Usage courant |
|------|-------------|---------------|
| `actionValidateOrder` | Commande validée | Notification, API |
| `actionCartSave` | Panier modifié | Recalcul, tracking |
| `actionCustomerAccountAdd` | Inscription | Newsletter, CRM |
| `actionProductAdd` | Produit créé | Synchronisation |
| `actionFrontControllerSetMedia` | Chargement page | Assets front |
| `actionAdminControllerSetMedia` | Chargement admin | Assets admin |

---

## Enregistrer un hook

### 1. Déclarer les hooks

Dans votre module, définissez la liste des hooks :

```php
private const HOOKS = [
    'displayHeader',
    'displayHome',
    'actionValidateOrder',
];
```

### 2. Enregistrer à l'installation

```php
public function install(): bool
{
    return parent::install()
        && $this->registerHook(self::HOOKS);
}
```

### 3. Implémenter la méthode

Le nom de la méthode = `hook` + nom du hook (camelCase) :

```php
// Pour displayHome
public function hookDisplayHome(array $params): string
{
    // ...
}

// Pour actionValidateOrder
public function hookActionValidateOrder(array $params): void
{
    // ...
}
```

---

## Paramètres des hooks

Chaque hook reçoit un tableau `$params` avec des données contextuelles.

### Exemple : actionValidateOrder

```php
public function hookActionValidateOrder(array $params): void
{
    /** @var Order $order */
    $order = $params['order'];
    
    /** @var Customer $customer */
    $customer = $params['customer'];
    
    /** @var Cart $cart */
    $cart = $params['cart'];
    
    /** @var Currency $currency */
    $currency = $params['currency'];
}
```

### Exemple : displayProductAdditionalInfo

```php
public function hookDisplayProductAdditionalInfo(array $params): string
{
    /** @var array $product */
    $product = $params['product'];
    
    $productId = (int) $product['id_product'];
    // ...
}
```

---

## Bonnes pratiques

### ✅ À faire

1. **Déléguer au service** : Ne pas mettre de logique dans le hook

```php
public function hookDisplayHome(array $params): string
{
    $items = $this->getService(DisplayService::class)->getHomeItems();
    $this->context->smarty->assign(['items' => $items]);
    return $this->fetch('module:monmodule/views/templates/hook/home.tpl');
}
```

2. **Valider les données** avant utilisation

```php
if (!isset($params['order']) || !($params['order'] instanceof Order)) {
    return;
}
```

3. **Utiliser le cache** pour les hooks display fréquents

### ❌ À éviter

1. **Requêtes SQL directes** dans les hooks
2. **Logique métier** dans les hooks (déléguer aux services)
3. **Echo/print** : toujours retourner le HTML

---

## Déboguer les hooks

### Voir les hooks d'une page

Activez le mode debug PrestaShop, puis ajoutez à l'URL :
```
?XDEBUG_TRIGGER=1
```

### Lister les hooks enregistrés

```bash
# Via DDEV
ddev mysql -e "
SELECT h.name, m.name as module, hm.position
FROM ps_hook_module hm
JOIN ps_hook h ON h.id_hook = hm.id_hook
JOIN ps_module m ON m.id_module = hm.id_module
WHERE m.name = 'monmodule'
ORDER BY h.name
"
```

### Logger l'exécution

```php
public function hookActionValidateOrder(array $params): void
{
    PrestaShopLogger::addLog(
        'Hook actionValidateOrder appelé pour commande ' . $params['order']->id,
        1,
        null,
        'Order',
        $params['order']->id
    );
}
```

---

## Créer un hook personnalisé

Vous pouvez créer vos propres hooks :

### 1. Enregistrer le hook

```php
Hook::registerHook('myModuleCustomEvent');
```

### 2. Déclencher le hook

```php
Hook::exec('myModuleCustomEvent', [
    'custom_data' => $data,
]);
```

### 3. D'autres modules peuvent s'y accrocher

```php
public function hookMyModuleCustomEvent(array $params): void
{
    $data = $params['custom_data'];
}
```

---

<details>
<summary>💡 Liste complète des hooks courants</summary>

Consultez [99-Reference/hooks-reference.md](../99-reference/hooks-reference.md) pour la liste complète.

</details>

---

**Prochaine étape** : [Configuration](./configuration.md)


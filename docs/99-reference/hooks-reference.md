# Référence des Hooks

> Référence technique détaillée : [.cursor/rules/002-module-hooks.mdc](../../.cursor/rules/002-module-hooks.mdc)

Liste des hooks PrestaShop les plus utilisés.

## Display Hooks (Front-office)

### Header et Footer

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayHeader` | Dans `<head>` | string (CSS, JS, meta) |
| `displayTop` | Barre supérieure | string (HTML) |
| `displayNav1` | Navigation niveau 1 | string |
| `displayNav2` | Navigation niveau 2 | string |
| `displayFooter` | Pied de page | string |
| `displayFooterAfter` | Après le footer | string |

### Page d'accueil

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayHome` | Zone principale accueil | string |
| `displayHomeTab` | Onglets accueil | string |
| `displayHomeTabContent` | Contenu onglets | string |

### Produit

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayProductAdditionalInfo` | Info supplémentaire | string |
| `displayProductPriceBlock` | Bloc prix | string |
| `displayProductActions` | Boutons d'action | string |
| `displayProductExtraContent` | Onglets supplémentaires | array |
| `displayAfterProductThumbs` | Après miniatures | string |
| `displayProductListReviews` | Liste produits | string |

### Panier

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayShoppingCart` | Page panier | string |
| `displayShoppingCartFooter` | Pied panier | string |
| `displayExpressCheckout` | Checkout express | string |

### Checkout

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayPaymentTop` | Haut page paiement | string |
| `displayPaymentByBinaries` | Options paiement | array |
| `displayOrderConfirmation` | Confirmation commande | string |

---

## Action Hooks (Événements)

### Produits

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionProductAdd` | Produit créé | `product` |
| `actionProductUpdate` | Produit modifié | `product`, `id_product` |
| `actionProductDelete` | Produit supprimé | `product`, `id_product` |
| `actionProductSave` | Produit sauvegardé | `product`, `id_product` |

### Commandes

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionValidateOrder` | Commande validée | `order`, `customer`, `cart`, `currency` |
| `actionOrderStatusUpdate` | Statut modifié | `newOrderStatus`, `id_order` |
| `actionOrderStatusPostUpdate` | Après modif statut | `newOrderStatus`, `id_order` |
| `actionPaymentConfirmation` | Paiement confirmé | `id_order` |

### Panier

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionCartSave` | Panier sauvegardé | `cart` |
| `actionCartUpdateQuantityBefore` | Avant modif quantité | `cart`, `product`, `quantity` |

### Clients

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionCustomerAccountAdd` | Inscription | `newCustomer` |
| `actionCustomerAccountUpdate` | Profil modifié | `customer` |
| `actionAuthentication` | Connexion | `customer` |

### Assets

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionFrontControllerSetMedia` | Chargement page front | - |
| `actionAdminControllerSetMedia` | Chargement page admin | - |

### Objets génériques

| Hook | Déclencheur | Params |
|------|-------------|--------|
| `actionObjectAddBefore` | Avant création objet | `object` |
| `actionObjectAddAfter` | Après création objet | `object` |
| `actionObjectUpdateBefore` | Avant modification | `object` |
| `actionObjectUpdateAfter` | Après modification | `object` |
| `actionObjectDeleteBefore` | Avant suppression | `object` |
| `actionObjectDeleteAfter` | Après suppression | `object` |

---

## Display Hooks (Back-office)

### Administration

| Hook | Emplacement | Retour |
|------|-------------|--------|
| `displayBackOfficeHeader` | Header admin | string |
| `displayAdminNavBarBeforeEnd` | Fin navbar | string |
| `displayDashboardTop` | Haut tableau de bord | string |
| `displayAdminOrderMain` | Page commande | string |
| `displayAdminProductsExtra` | Onglet produit | string |

---

## Enregistrer un hook

### Dans le module

```php
private const HOOKS = [
    'displayHeader',
    'displayHome',
    'displayProductAdditionalInfo',
    'actionValidateOrder',
    'actionFrontControllerSetMedia',
];

public function install(): bool
{
    return parent::install() && $this->registerHook(self::HOOKS);
}
```

### Implémenter

```php
public function hookDisplayHome(array $params): string
{
    return $this->fetch('module:monmodule/views/templates/hook/home.tpl');
}

public function hookActionValidateOrder(array $params): void
{
    $order = $params['order'];
    $customer = $params['customer'];
    // Logique...
}
```

---

## Paramètres courants

### actionValidateOrder

```php
[
    'cart' => Cart,
    'order' => Order,
    'customer' => Customer,
    'currency' => Currency,
    'orderStatus' => OrderState,
]
```

### displayProductAdditionalInfo

```php
[
    'product' => array (données produit),
]
```

### actionFrontControllerSetMedia

```php
[] // Pas de paramètres, utiliser $this->context
```

---

## Vérifier les hooks enregistrés

```sql
SELECT h.name, hm.position
FROM ps_hook_module hm
JOIN ps_hook h ON h.id_hook = hm.id_hook
JOIN ps_module m ON m.id_module = hm.id_module
WHERE m.name = 'monmodule'
ORDER BY h.name;
```

---

**Prochaine étape** : [Glossaire](./glossary.md)


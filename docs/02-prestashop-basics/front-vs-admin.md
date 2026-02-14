# Front-office vs Back-office

Comprendre les différences entre le front-office (boutique) et le back-office (administration).

## Vue d'ensemble

| Aspect | Front-office | Back-office |
|--------|--------------|-------------|
| **URL** | `/` | `/admin-xxx/` |
| **Utilisateur** | Client (Customer) | Employé (Employee) |
| **Thème** | Thème actif | Thème admin PrestaShop |
| **Templates** | Smarty (`.tpl`) | Twig (`.html.twig`) |
| **Contrôleurs** | Legacy PHP | Symfony |
| **Sécurité** | Token optionnel | Token obligatoire |

---

## Détecter le contexte

### Dans le module

```php
// Vérifier si on est en admin
if (defined('_PS_ADMIN_DIR_')) {
    // Back-office
} else {
    // Front-office
}

// Avec l'adapter du module
if ($this->contextAdapter->isAdminContext()) {
    // Back-office
}
```

### Dans un contrôleur

```php
// Front-office
class MonModuleDisplayModuleFrontController extends ModuleFrontController
{
    // Contrôleur front
}

// Back-office
class AdminMonModuleController extends ModuleAdminController
{
    // Contrôleur admin legacy
}

// Back-office Symfony
class ConfigurationController extends FrameworkBundleAdminController
{
    // Contrôleur admin moderne
}
```

---

## Templates

### Front-office : Smarty

Les templates front utilisent **Smarty** (extension `.tpl`) :

```smarty
{* views/templates/front/display.tpl *}
<div class="monmodule-widget">
    <h2>{$title}</h2>
    {foreach $items as $item}
        <div class="monmodule-item">
            {$item.name}
        </div>
    {/foreach}
</div>
```

### Back-office : Twig

Les templates admin utilisent **Twig** (extension `.html.twig`) :

```twig
{# views/templates/admin/configuration.html.twig #}
{% extends '@PrestaShop/Admin/layout.html.twig' %}

{% block content %}
    <div class="card">
        <h2>{{ title }}</h2>
        {{ form_start(form) }}
        {{ form_widget(form) }}
        <button type="submit" class="btn btn-primary">
            {{ 'Save'|trans({}, 'Admin.Actions') }}
        </button>
        {{ form_end(form) }}
    </div>
{% endblock %}
```

---

## Assets (CSS/JS)

### Front-office

Hook : `actionFrontControllerSetMedia`

```php
public function hookActionFrontControllerSetMedia(array $params): void
{
    $this->context->controller->registerStylesheet(
        'monmodule-front-css',
        'modules/' . $this->name . '/views/css/front.css'
    );
    
    $this->context->controller->registerJavascript(
        'monmodule-front-js',
        'modules/' . $this->name . '/views/js/front.js',
        ['position' => 'bottom']
    );
}
```

### Back-office

Hook : `actionAdminControllerSetMedia`

```php
public function hookActionAdminControllerSetMedia(array $params): void
{
    // Seulement sur la page de configuration du module
    if ($this->context->controller->controller_name === 'AdminModules' 
        && Tools::getValue('configure') === $this->name) {
        $this->context->controller->addCSS(
            $this->getPathUri() . 'views/css/admin.css'
        );
    }
}
```

---

## Hooks par contexte

### Hooks Front-office

| Hook | Description |
|------|-------------|
| `displayHeader` | Dans `<head>` |
| `displayTop` | Barre supérieure |
| `displayHome` | Page d'accueil |
| `displayFooter` | Pied de page |
| `displayProductAdditionalInfo` | Fiche produit |
| `displayShoppingCart` | Page panier |
| `displayOrderConfirmation` | Confirmation commande |
| `actionFrontControllerSetMedia` | Chargement des assets |

### Hooks Back-office

| Hook | Description |
|------|-------------|
| `displayBackOfficeHeader` | Header admin |
| `displayAdminNavBarBeforeEnd` | Navbar admin |
| `displayDashboardTop` | Tableau de bord |
| `actionAdminControllerSetMedia` | Assets admin |
| `displayAdminProductsExtra` | Onglet produit |
| `displayAdminOrderMain` | Page commande |

---

## Contrôleurs

### Contrôleur Front-office

```php
// controllers/front/display.php

class MonModuleDisplayModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();
        
        $this->context->smarty->assign([
            'data' => $this->getData(),
        ]);
        
        $this->setTemplate('module:monmodule/views/templates/front/display.tpl');
    }
    
    private function getData(): array
    {
        return $this->module->getService(DisplayService::class)->getData();
    }
}
```

**URL** : `/module/monmodule/display`

### Contrôleur Back-office Symfony

```php
// src/Presentation/Controller/Admin/ConfigurationController.php

class ConfigurationController extends FrameworkBundleAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function index(Request $request): Response
    {
        // Logique de configuration
        return $this->render('@Modules/monmodule/views/templates/admin/configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

**URL** : `/admin-xxx/monmodule/configuration`

---

## Sécurité

### Front-office

- Token optionnel (recommandé pour les formulaires)
- Vérification via `Tools::getToken()`

```php
// Générer
$token = Tools::getToken(false);

// Vérifier
if (!Tools::getValue('token') || Tools::getValue('token') !== Tools::getToken(false)) {
    throw new Exception('Token invalide');
}
```

### Back-office

- Token **obligatoire** pour toutes les actions
- Annotation `@AdminSecurity` sur les contrôleurs Symfony

```php
#[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
public function save(Request $request): Response
{
    // Action protégée
}
```

---

## Traductions

### Front-office

Domaine : `Modules.Monmodule.Shop`

```php
$this->trans('Add to cart', [], 'Modules.Monmodule.Shop');
```

### Back-office

Domaine : `Modules.Monmodule.Admin`

```php
$this->trans('Configuration saved', [], 'Modules.Monmodule.Admin');
```

### Dans les templates

```smarty
{* Smarty (front) *}
{l s='Add to cart' mod='monmodule'}
```

```twig
{# Twig (admin) #}
{{ 'Configuration saved'|trans({}, 'Modules.Monmodule.Admin') }}
```

---

<details>
<summary>💡 Créer une page accessible des deux côtés</summary>

Pour une fonctionnalité accessible en front et en admin :

1. Créez un **service** partagé dans `src/Application/Service/`
2. Créez deux contrôleurs distincts (front et admin)
3. Chaque contrôleur appelle le même service
4. Utilisez des templates différents selon le contexte

</details>

---

**Prochaine section** : [Development Workflow](../03-development-workflow/)


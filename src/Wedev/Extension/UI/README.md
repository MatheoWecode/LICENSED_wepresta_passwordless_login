# Extension UI WEDEV

Composants UI partagés pour modules PrestaShop 9, 100% alignés sur le design system natif.

## Installation

L'extension est automatiquement copiée lors de la génération d'un module avec WEDEV CLI.

### Configuration Symfony

Ajouter l'import dans `config/services.yml` du module :

```yaml
imports:
    - { resource: '../src/Extension/UI/config/services_ui.yml' }
```

### Chargement des Assets

Dans le contrôleur admin, charger les assets :

```php
public function someAction(): Response
{
    // Charger Alpine.js et WEDEV UI
    $this->addJs([
        'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
        $this->getModuleAssetPath('js/admin.js'),
    ]);

    return $this->render('@Modules/modulename/views/templates/admin/page.html.twig');
}
```

---

## Back-Office

### Macros Twig

Import des macros :

```twig
{% import '@WedevUI/Macros/admin.html.twig' as ui %}
{% import '@WedevUI/Macros/forms.html.twig' as forms %}
{% import '@WedevUI/Macros/grids.html.twig' as grids %}
```

#### Composants disponibles

```twig
{# Card #}
{{ ui.card('Settings', 'settings', cardContent, cardFooter) }}

{# Button #}
{{ ui.button('Save', 'primary', 'save', {'data-action': 'submit'}) }}

{# Button Link #}
{{ ui.button_link('View', path('route'), 'primary', 'visibility') }}

{# Status Badge #}
{{ ui.status_badge(item.active) }}
{{ ui.status_badge(item.active, {true: 'Enabled', false: 'Disabled'}) }}

{# Generic Badge #}
{{ ui.badge('New', 'info', true) }}

{# Alert #}
{{ ui.alert('Success!', 'success', 'check_circle', true) }}

{# Empty State #}
{{ ui.empty_state('inbox', 'No items yet', 'Create your first item', actionButton) }}

{# Loader #}
{{ ui.loader('sm', 'primary') }}

{# Icon #}
{{ ui.icon('settings', 'lg', 'text-primary') }}

{# Dropdown #}
{{ ui.dropdown('Actions', 'more_vert', [
    {'label': 'Edit', 'href': '#', 'icon': 'edit'},
    {'label': 'Delete', 'href': '#', 'icon': 'delete', 'danger': true}
]) }}

{# Progress Bar #}
{{ ui.progress(75, 'success', true) }}

{# Breadcrumb #}
{{ ui.breadcrumb([{'label': 'Home', 'href': '/'}, {'label': 'Current'}]) }}

{# Tooltip #}
{{ ui.tooltip('Hover me', 'This is a tooltip', 'top') }}
```

#### Formulaires

```twig
{# Form Group standard #}
{{ forms.group(form.title, 'Title', 'Enter the title', true) }}

{# Switch Toggle #}
{{ forms.switch(form.active, 'Enable feature') }}

{# Textarea with counter #}
{{ forms.textarea_counter(form.description, 'Description', 500) }}

{# Color Picker #}
{{ forms.color_picker(form.color, 'Theme Color') }}

{# File Upload #}
{{ forms.file_upload(form.image, 'Image', 'Max 2MB, JPG/PNG') }}

{# Input Group with prefix/suffix #}
{{ forms.input_group(form.price, 'Price', null, '€') }}

{# Submit Buttons #}
{{ forms.submit_buttons('Save', 'Cancel', cancelUrl) }}
```

#### Grilles

```twig
{# Row Actions #}
{{ grids.row_actions(editHref, deleteHref, extraActions, item.id) }}

{# Position Column (drag & drop) #}
{{ grids.position_column(item.position, item.id) }}

{# Image Column #}
{{ grids.image_column(item.image_url, 'Thumbnail', 50) }}

{# Toggle Column #}
{{ grids.toggle_column(item.active, toggleUrl, item.id) }}

{# Pagination #}
{{ grids.pagination(currentPage, totalPages, baseUrl) }}

{# Sort Header #}
{{ grids.sort_header('Name', 'name', currentSort, currentOrder, baseUrl) }}

{# Filter Form #}
{{ grids.filter_form(filters, submitUrl, resetUrl, 'grid_id') }}
```

### Twig Functions (UiExtension)

```twig
{# Icon #}
{{ wedev_icon('settings', 'lg', 'text-muted') }}

{# Alert #}
{{ wedev_alert('Success!', 'success', true, 'check_circle') }}

{# Badge #}
{{ wedev_badge('New', 'info', true) }}

{# Button #}
{{ wedev_button('Save', 'primary', 'save', 'md', {'id': 'save-btn'}) }}

{# Spinner #}
{{ wedev_spinner('border', 'sm', 'primary') }}

{# Status Filter #}
{{ item.active|wedev_status }}
{{ item.active|wedev_status({'true': 'On', 'false': 'Off'}) }}

{# Truncate #}
{{ item.description|wedev_truncate(100) }}
```

### Partials à inclure

Dans votre layout principal, inclure une seule fois :

```twig
{# Modal de confirmation #}
{% include '@WedevUI/admin/_partials/confirm-modal.html.twig' %}

{# Container des toasts #}
{% include '@WedevUI/admin/_partials/toasts.html.twig' %}
```

---

## JavaScript Back-Office

### API Wedev

```javascript
// Import (si bundler)
import { Wedev, WedevAjax, notify } from './Extension/UI/Assets/js/admin/index.js';

// Ou global (via script tag)
// window.Wedev est disponible
```

### AJAX

```javascript
// GET
const data = await Wedev.ajax.get('/api/endpoint', { id: 123 });

// POST
const result = await Wedev.ajax.post('/api/endpoint', { name: 'Test' });

// POST avec JSON
const result = await Wedev.ajax.post('/api/endpoint', { name: 'Test' }, true);

// POST avec token CSRF automatique
const result = await Wedev.ajax.postWithToken('/api/endpoint', { id: 123 });

// Upload avec progression
const result = await Wedev.ajax.upload('/api/upload', formData, (percent) => {
    console.log(`Upload: ${percent}%`);
});
```

### Confirmation

```javascript
// Confirmation simple
const confirmed = await Wedev.confirm({
    title: 'Delete item?',
    message: 'This action cannot be undone.',
    dangerous: true
});

if (confirmed) {
    // Procéder à la suppression
}

// Raccourcis
await Wedev.confirmDelete('Product #123');
await Wedev.confirmSave('Save changes before leaving?');
await Wedev.confirmLeave();
await Wedev.confirmDisable('Newsletter subscription');
```

### Notifications

```javascript
// Types de notifications
Wedev.notify.success('Item saved successfully!');
Wedev.notify.error('An error occurred');
Wedev.notify.warning('Please check your input');
Wedev.notify.info('Processing...');

// Durée personnalisée (ms)
Wedev.notify.show('Custom message', 'info', 10000);

// Notification persistante
Wedev.notify.persistent('Important message', 'warning');

// Avec promesse
const result = await Wedev.notify.promise(
    fetch('/api/save'),
    {
        loading: 'Saving...',
        success: 'Saved!',
        error: 'Failed to save'
    }
);
```

### Alpine Components

```html
<!-- Toggle -->
<div x-data="wedevToggle(true)" @toggle-changed="savePreference($event.detail.value)">
    <button @click="toggle()" :class="enabled ? 'btn-success' : 'btn-secondary'">
        <span x-text="enabled ? 'Enabled' : 'Disabled'"></span>
    </button>
</div>

<!-- AJAX Form -->
<form x-data="wedevAjaxForm('/api/save', { successMessage: 'Saved!' })"
      @submit.prevent="submit(new FormData($el))">
    <input name="title" :class="{ 'is-invalid': hasError('title') }">
    <span class="invalid-feedback" x-text="getError('title')"></span>
    <button type="submit" :disabled="loading">
        <span x-show="!loading">Save</span>
        <span x-show="loading">Saving...</span>
    </button>
</form>

<!-- Character Counter -->
<div x-data="wedevCharCounter(500)">
    <textarea x-model="text" :maxlength="max"></textarea>
    <small :class="{ 'text-danger': isNearLimit }">
        <span x-text="remaining"></span> characters remaining
    </small>
</div>

<!-- Copy to Clipboard -->
<button x-data="wedevClipboard()" @click="copy('text to copy')">
    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
</button>
```

---

## Front-Office

### Smarty Functions

```smarty
{* Icon *}
{wedev_icon name="check" size="md"}
{wedev_icon name="shopping_cart" size="lg" class="text-primary"}

{* Button *}
{wedev_button label="Add to cart" type="primary" icon="shopping_cart"}
{wedev_button label="Learn more" href="/page" type="outline-primary"}
{wedev_button label="Delete" type="danger" icon="delete" size="sm" disabled=true}

{* Alert *}
{wedev_alert message="Success!" type="success"}
{wedev_alert message="Warning!" type="warning" icon="warning" dismissible=true}

{* Badge *}
{wedev_badge text="New" variant="success"}
{wedev_badge text="Sale" variant="danger" pill=true}
```

### Smarty Modifiers

```smarty
{* Truncate *}
{$description|wedev_truncate:50}
{$text|wedev_truncate:100:'...':true}
```

### JavaScript Front-Office

```javascript
// AJAX avec token automatique
const result = await WedevFront.ajax(
    prestashop.urls.base_url + 'module/mymodule/action',
    { id_product: 123 }
);

// Lazy loading
WedevFront.lazyLoad('.lazy-image', (img) => {
    img.src = img.dataset.src;
    img.classList.add('loaded');
});

// Debounce
const debouncedSearch = WedevFront.debounce((query) => {
    console.log('Search:', query);
}, 300);

// Add to cart
await WedevFront.addToCart(productId, attributeId, quantity);

// Format price
const formatted = WedevFront.formatPrice(29.99); // "€ 29.99"

// Scroll to element
WedevFront.scrollTo('#section', 80); // 80px offset for fixed header

// Copy to clipboard
await WedevFront.copyToClipboard('Text to copy');

// Storage with expiration
WedevFront.storage('key', 'value', 3600); // Expire in 1 hour
const value = WedevFront.storage('key'); // Read

// URL params
const param = WedevFront.getUrlParam('id');

// Device detection
if (WedevFront.isMobile()) {
    // Mobile specific logic
}
```

---

## Variables SCSS

### Back-Office (PS9)

```scss
@import 'Extension/UI/Assets/scss/_variables';

// Couleurs
color: $ps-primary;      // #25b9d7
color: $ps-danger;       // #e15d5d
color: $ps-success;      // #70b580

// Gris
color: $ps-gray-800;     // #363a41
background: $ps-gray-100; // #fafbfc

// Spacing
padding: $ps-spacer-3;   // 16px
margin: $ps-spacer-4;    // 24px

// Border radius
border-radius: $ps-border-radius;    // 4px
border-radius: $ps-border-radius-lg; // 8px

// Shadows
box-shadow: $ps-shadow-sm;  // subtle
box-shadow: $ps-shadow;     // normal
box-shadow: $ps-shadow-lg;  // prominent
```

### Front-Office (Hummingbird)

```scss
@import 'Extension/UI/Assets/scss/front/_hummingbird';

// Couleurs
color: $hb-primary;   // #2fb5d2
color: $hb-secondary; // #f39d72

// Typography
font-family: $hb-font-family;
font-size: $hb-font-size-base; // 1rem

// Buttons
padding: $hb-btn-padding-y $hb-btn-padding-x;
```

---

## Principes de Design

1. **Zéro composant custom** - Utilisation exclusive des classes Bootstrap 4 / UIKit PS9
2. **Variables PS9 exactes** - Copie directe des variables officielles
3. **Pas de framework CSS additionnel** - Juste SCSS avec variables
4. **Alpine.js léger** - ~15kb, pas de build complexe
5. **Rétrocompatibilité** - Fonctionne avec jQuery natif PS si Alpine non disponible
6. **Séparation front/admin** - Bundles distincts, pas de pollution

---

## Structure des fichiers

```
Extension/UI/
├── README.md                    # Cette documentation
├── config/
│   └── services_ui.yml          # Services Symfony
├── Twig/
│   ├── UiExtension.php          # Fonctions Twig
│   └── Macros/
│       ├── admin.html.twig      # Macros back-office
│       ├── forms.html.twig      # Macros formulaires
│       └── grids.html.twig      # Macros grilles
├── Smarty/
│   ├── function.wedev_icon.php
│   ├── function.wedev_button.php
│   ├── function.wedev_alert.php
│   ├── function.wedev_badge.php
│   └── modifier.wedev_truncate.php
├── Assets/
│   ├── scss/
│   │   ├── _variables.scss      # Variables PS9
│   │   ├── admin/
│   │   │   └── _utilities.scss
│   │   └── front/
│   │       └── _hummingbird.scss
│   └── js/
│       ├── admin/
│       │   ├── index.js         # Point d'entrée
│       │   ├── wedev-core.js    # Alpine components
│       │   └── utils/
│       │       ├── ajax.js
│       │       ├── confirm.js
│       │       └── notifications.js
│       └── front/
│           └── wedev-front.js
└── Templates/
    └── admin/
        └── _partials/
            ├── confirm-modal.html.twig
            ├── toasts.html.twig
            └── loading-overlay.html.twig
```


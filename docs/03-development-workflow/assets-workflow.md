# Workflow Assets

> Référence technique détaillée : [.cursor/rules/007-module-frontend.mdc](../../.cursor/rules/007-module-frontend.mdc)

Gérer les CSS, JavaScript et images avec Webpack Encore.

## Structure des assets

```
monmodule/
├── _dev/                    # Sources (éditer ici)
│   ├── js/
│   │   ├── front.js         # JS front-office
│   │   └── admin.js         # JS back-office
│   ├── scss/
│   │   ├── front.scss       # Styles front
│   │   └── admin.scss       # Styles admin
│   └── images/              # Images sources
│
└── views/                   # Compilé (ne pas éditer)
    ├── css/                 # CSS compilé
    ├── js/                  # JS compilé
    └── dist/                # Build Webpack
```

> ⚠️ **Ne modifiez jamais** les fichiers dans `views/css/`, `views/js/` ou `views/dist/`. Éditez uniquement dans `_dev/`.

---

## Commandes npm

### Développement avec watch

```bash
npm run watch
```

- Compile à chaque sauvegarde
- Génère des sourcemaps (debug facile)
- Ne minifie pas (lisible)

### Build de développement

```bash
npm run dev
```

Compile une fois sans minification.

### Build de production

```bash
npm run build
```

- Minifie CSS et JS
- Optimise les images
- Génère des hashes pour le cache busting

---

## Webpack Encore

Le module utilise **Webpack Encore** (version Symfony) pour la compilation.

### Configuration

Fichier `webpack.config.js` :

```javascript
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./views/dist/')
    .setPublicPath('/modules/monmodule/views/dist')
    
    // Entries
    .addEntry('front', './_dev/js/front.js')
    .addEntry('admin', './_dev/js/admin.js')
    
    // SCSS
    .addStyleEntry('front-style', './_dev/scss/front.scss')
    .addStyleEntry('admin-style', './_dev/scss/admin.scss')
    
    // Options
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .disableSingleRuntimeChunk();

module.exports = Encore.getWebpackConfig();
```

---

## SCSS

### Structure recommandée

```
_dev/scss/
├── front.scss            # Point d'entrée front
├── admin.scss            # Point d'entrée admin
├── _variables.scss       # Variables (couleurs, fonts)
├── _mixins.scss          # Mixins réutilisables
├── components/
│   ├── _button.scss
│   └── _card.scss
└── pages/
    ├── _home.scss
    └── _product.scss
```

### Exemple front.scss

```scss
// Variables et mixins
@import 'variables';
@import 'mixins';

// Composants
@import 'components/button';
@import 'components/card';

// Pages
@import 'pages/home';
```

### Convention BEM

```scss
.monmodule {
    &-widget {
        padding: 20px;
        
        &__title {
            font-size: 1.5rem;
        }
        
        &__content {
            margin-top: 10px;
        }
        
        &--featured {
            border: 2px solid gold;
        }
    }
}
```

---

## JavaScript

### Structure recommandée

```
_dev/js/
├── front.js              # Point d'entrée front
├── admin.js              # Point d'entrée admin
├── components/
│   ├── Modal.js
│   └── Slider.js
└── utils/
    ├── api.js
    └── helpers.js
```

### Exemple front.js

```javascript
// Imports
import './components/Modal';
import { initSlider } from './components/Slider';

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
    console.log('Module initialisé');
    initSlider();
});
```

### ES6+ supporté

```javascript
// Classes
class MonModule {
    constructor(element) {
        this.element = element;
    }
    
    init() {
        // ...
    }
}

// Arrow functions
const items = data.map(item => item.name);

// Async/await
async function fetchData() {
    const response = await fetch('/api/endpoint');
    return response.json();
}

// Destructuring
const { id, name } = product;
```

---

## Charger les assets

### Front-office

Hook `actionFrontControllerSetMedia` :

```php
public function hookActionFrontControllerSetMedia(array $params): void
{
    // CSS
    $this->context->controller->registerStylesheet(
        'monmodule-front-css',
        'modules/' . $this->name . '/views/dist/front-style.css'
    );
    
    // JavaScript
    $this->context->controller->registerJavascript(
        'monmodule-front-js',
        'modules/' . $this->name . '/views/dist/front.js',
        ['position' => 'bottom', 'priority' => 100]
    );
}
```

### Back-office

Hook `actionAdminControllerSetMedia` :

```php
public function hookActionAdminControllerSetMedia(array $params): void
{
    // Seulement sur la page du module
    $controller = $this->context->controller->controller_name ?? '';
    
    if ($controller === 'AdminModules' && Tools::getValue('configure') === $this->name) {
        $this->context->controller->addCSS(
            $this->getPathUri() . 'views/dist/admin-style.css'
        );
        $this->context->controller->addJS(
            $this->getPathUri() . 'views/dist/admin.js'
        );
    }
}
```

---

## Images

### Optimisation automatique

Les images dans `_dev/images/` sont optimisées lors du build :

```bash
npm run build
```

### Utilisation dans SCSS

```scss
.monmodule-logo {
    background-image: url('../images/logo.png');
}
```

### Utilisation dans les templates

```smarty
<img src="{$module_dir}views/dist/images/logo.png" alt="Logo">
```

---

## Dépannage

| Problème | Solution |
|----------|----------|
| CSS non appliqué | `npm run build` puis vider le cache |
| Erreur de compilation | Vérifier la syntaxe SCSS/JS |
| Module non trouvé | `npm install` |
| Changements non visibles | Ctrl+F5 (hard refresh) |
| Erreur node-sass | `npm rebuild node-sass` |

### Nettoyer et reconstruire

```bash
rm -rf node_modules
rm package-lock.json
npm install
npm run build
```

---

**Prochaine étape** : [Migrations base de données](./database-migrations.md)


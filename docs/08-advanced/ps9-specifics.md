# Spécificités PrestaShop 9

Différences et nouveautés de PrestaShop 9 par rapport à PS 8.

## Versions PHP

| PrestaShop | PHP minimum | PHP maximum |
|------------|-------------|-------------|
| 8.0 | 7.4 | 8.1 |
| 8.1 | 8.0 | 8.2 |
| **9.0** | **8.1** | **8.3** |

---

## Symfony

| PrestaShop | Version Symfony |
|------------|-----------------|
| 8.x | 4.4 LTS |
| **9.x** | **6.4 LTS** |

### Changements majeurs

- Annotations → Attributes PHP 8
- Nouvelles contraintes de validation
- Nouvelles fonctionnalités de routing

### Migration des annotations

```php
// PS8 (Annotations)
/**
 * @Route("/admin/monmodule/items", name="monmodule_items")
 * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
 */
public function indexAction(): Response

// PS9 (Attributes PHP 8)
#[Route('/admin/monmodule/items', name: 'monmodule_items')]
#[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
public function indexAction(): Response
```

---

## Bootstrap

| PrestaShop | Bootstrap |
|------------|-----------|
| 8.x | 4.6 |
| **9.x** | **5.3** |

### Changements CSS

```scss
// PS8 (Bootstrap 4)
.ml-2 { }
.mr-3 { }
.pl-4 { }

// PS9 (Bootstrap 5)
.ms-2 { }  // margin-start
.me-3 { }  // margin-end
.ps-4 { }  // padding-start
```

### Changements JS

```javascript
// PS8 (jQuery requis)
$('#myModal').modal('show');

// PS9 (jQuery optionnel)
const modal = bootstrap.Modal.getInstance(document.getElementById('myModal'));
modal.show();
```

---

## Twig

### Nouvelles fonctionnalités

```twig
{# Null-safe operator #}
{{ user?.name }}

{# Enum support #}
{{ status.value }}
```

---

## Smarty déprécié

PS9 encourage la migration vers Twig pour le front-office.

### Migration progressive

```php
// Supporter les deux
public function hookDisplayHome(array $params): string
{
    $templatePath = $this->getTemplatePath('hook/home');
    
    if (file_exists($templatePath . '.tpl')) {
        // Smarty
        return $this->fetch($templatePath . '.tpl');
    }
    
    // Twig
    return $this->get('twig')->render('@Modules/monmodule/views/templates/hook/home.html.twig');
}
```

---

## API Platform

PS9 introduit potentiellement **API Platform** pour les APIs REST.

### Structure

```php
// Entity avec attributs API Platform
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource]
class Item
{
    // ...
}
```

---

## Nouveaux hooks

PS9 peut introduire de nouveaux hooks. Consultez le changelog officiel.

### Vérifier la disponibilité

```php
public function install(): bool
{
    $hooks = self::HOOKS;
    
    // Ajouter les hooks PS9 si disponibles
    if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
        $hooks[] = 'displayNewHookPS9';
    }
    
    return parent::install() && $this->registerHook($hooks);
}
```

---

## Compatibilité multi-version

### Vérifier la version

```php
// Vérifier la version PrestaShop
if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
    // Code spécifique PS9
} else {
    // Code PS8
}
```

### Déclarer la compatibilité

```php
public function __construct()
{
    // ...
    $this->ps_versions_compliancy = [
        'min' => '8.0.0',
        'max' => '9.99.99',  // Compatible PS8 et PS9
    ];
}
```

---

## Migration PS8 → PS9

### Checklist

- [ ] PHP 8.1+ requis
- [ ] Annotations → Attributes
- [ ] Bootstrap 4 → Bootstrap 5
- [ ] Tests sur PS9
- [ ] Smarty → Twig (optionnel)
- [ ] jQuery → Vanilla JS (optionnel)

### Outils

```bash
# Rector pour la migration
composer require rector/rector --dev

# Configuration pour PS9
# rector.php
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ]);
};
```

---

## Ressources

- [Changelog PrestaShop 9](https://github.com/PrestaShop/PrestaShop/releases)
- [Guide de migration](https://devdocs.prestashop-project.org/)
- [Symfony 6.4 Migration](https://symfony.com/doc/6.4/setup/upgrade_major.html)
- [Bootstrap 5 Migration](https://getbootstrap.com/docs/5.0/migration/)

---

**Prochaine section** : [Troubleshooting](../09-troubleshooting/)


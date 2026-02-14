# Sécurité

> Référence technique détaillée : [.cursor/rules/009-module-security.mdc](../../.cursor/rules/009-module-security.mdc)

Bonnes pratiques de sécurité pour les modules PrestaShop.

## Injection SQL

### Prévention

```php
// ❌ DANGEREUX: Injection possible
$name = $_GET['name'];
$sql = "SELECT * FROM ps_item WHERE name = '$name'";

// ✅ SÉCURISÉ: Utiliser pSQL()
$name = pSQL(Tools::getValue('name'));
$sql = "SELECT * FROM ps_item WHERE name = '$name'";

// ✅ MIEUX: Cast pour les entiers
$id = (int) Tools::getValue('id');
$sql = "SELECT * FROM ps_item WHERE id = $id";
```

### DbQuery

```php
// ✅ DbQuery avec pSQL
$query = new DbQuery();
$query->select('*')
      ->from('monmodule_item')
      ->where("name = '" . pSQL($name) . "'")
      ->where('id_category = ' . (int) $categoryId);
```

### Doctrine (préféré)

```php
// ✅ Requêtes paramétrées
$qb = $this->entityManager->createQueryBuilder();
$qb->select('i')
   ->from(Item::class, 'i')
   ->where('i.name = :name')
   ->setParameter('name', $name);  // Échappé automatiquement
```

---

## XSS (Cross-Site Scripting)

### Échapper les sorties

```php
// ❌ DANGEREUX
echo $userInput;

// ✅ SÉCURISÉ: Échapper HTML
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ✅ PrestaShop
echo Tools::safeOutput($userInput);
```

### Dans Smarty

```smarty
{* ❌ DANGEREUX *}
{$userInput nofilter}

{* ✅ SÉCURISÉ (par défaut) *}
{$userInput}

{* ✅ Explicite *}
{$userInput|escape:'html':'UTF-8'}
```

### Dans Twig

```twig
{# ✅ SÉCURISÉ (par défaut) #}
{{ userInput }}

{# ❌ DANGEREUX si nécessaire #}
{{ userInput|raw }}
```

---

## CSRF (Cross-Site Request Forgery)

### Tokens PrestaShop

```php
// Générer un token
$token = Tools::getAdminTokenLite('AdminModules');

// Vérifier le token
if (!Tools::getIsset('token') || Tools::getValue('token') !== $token) {
    throw new PrestaShopException('Invalid token');
}
```

### Formulaires Symfony

```php
// Les formulaires Symfony incluent automatiquement un token CSRF
$form = $this->createForm(ItemFormType::class);

// Vérification automatique
if ($form->isSubmitted() && $form->isValid()) {
    // Token vérifié automatiquement
}
```

### Front-office

```php
// Générer
$token = Tools::getToken(false);

// Vérifier
if (Tools::getValue('token') !== Tools::getToken(false)) {
    throw new Exception('Invalid security token');
}
```

---

## Validation des entrées

### Toujours valider

```php
// Type
$id = (int) Tools::getValue('id');
$price = (float) Tools::getValue('price');
$active = (bool) Tools::getValue('active');

// Format
$email = Tools::getValue('email');
if (!Validate::isEmail($email)) {
    throw new InvalidArgumentException('Invalid email');
}

// Longueur
$name = Tools::getValue('name');
if (strlen($name) > 255) {
    throw new InvalidArgumentException('Name too long');
}
```

### Validators PrestaShop

| Méthode | Valide |
|---------|--------|
| `Validate::isEmail()` | Format email |
| `Validate::isUrl()` | URL |
| `Validate::isInt()` | Entier |
| `Validate::isFloat()` | Décimal |
| `Validate::isCleanHtml()` | HTML sans script |
| `Validate::isGenericName()` | Nom générique |
| `Validate::isPrice()` | Prix |

---

## Upload de fichiers

### Vérifications obligatoires

```php
public function handleUpload(array $file): string
{
    // 1. Vérifier les erreurs
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new UploadException('Upload failed');
    }
    
    // 2. Vérifier la taille
    $maxSize = 2 * 1024 * 1024; // 2 Mo
    if ($file['size'] > $maxSize) {
        throw new UploadException('File too large');
    }
    
    // 3. Vérifier le type MIME réel
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new UploadException('Invalid file type');
    }
    
    // 4. Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        throw new UploadException('Invalid extension');
    }
    
    // 5. Générer un nom sécurisé
    $newName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = _PS_MODULE_DIR_ . $this->name . '/uploads/' . $newName;
    
    // 6. Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new UploadException('Move failed');
    }
    
    return $newName;
}
```

---

## Permissions admin

### AdminSecurity

```php
use PrestaShopBundle\Security\Annotation\AdminSecurity;

class ItemController extends FrameworkBundleAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))")]
    public function indexAction(): Response
    {
        // Lecture seule
    }
    
    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))")]
    public function editAction(): Response
    {
        // Modification
    }
    
    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")]
    public function deleteAction(): Response
    {
        // Suppression
    }
}
```

### Vérification manuelle

```php
// Vérifier les permissions
if (!$this->context->employee->hasAccess($this->tabAccess, Profile::PERMISSION_VIEW)) {
    throw new PrestaShopException('Access denied');
}
```

---

## Données sensibles

### Ne jamais exposer

```php
// ❌ DANGEREUX
return json_encode([
    'api_key' => Configuration::get('MONMODULE_API_KEY'),
    'password' => $user->password,
]);

// ✅ SÉCURISÉ
return json_encode([
    'api_key_configured' => !empty(Configuration::get('MONMODULE_API_KEY')),
]);
```

### Masquer dans les logs

```php
$this->logger->info('API call', [
    'endpoint' => $endpoint,
    'api_key' => '***REDACTED***',  // Jamais la vraie clé
]);
```

---

## Headers de sécurité

### Dans les réponses API

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');
```

---

## Checklist sécurité

### Avant chaque release

- [ ] Toutes les entrées sont validées
- [ ] SQL utilise `pSQL()` ou paramètres bindés
- [ ] Sorties HTML échappées
- [ ] Tokens CSRF vérifiés
- [ ] Uploads validés (type, taille, extension)
- [ ] Permissions admin vérifiées
- [ ] Pas de données sensibles exposées
- [ ] Pas de `var_dump()`, `dd()` oubliés

---

**Prochaine étape** : [PrestaShop 9](./ps9-specifics.md)


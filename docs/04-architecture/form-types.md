# Form Types Symfony

> Référence technique détaillée : [.cursor/rules/005-module-forms.mdc](../../.cursor/rules/005-module-forms.mdc)

Symfony Form permet de créer des formulaires validés côté serveur.

## Pourquoi Symfony Form ?

| Avantage | Description |
|----------|-------------|
| **Validation** | Contraintes côté serveur |
| **Sécurité** | Protection CSRF intégrée |
| **Réutilisabilité** | Formulaires composables |
| **Rendu** | Templates Twig automatiques |

---

## Créer un Form Type

### Structure de base

```php
// src/Application/Form/ItemFormType.php

namespace MonModule\Application\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Monmodule.Admin',
        ]);
    }
}
```

> [!IMPORTANT]
> Pour garantir la traduction correcte des labels et options, l'injection du service `TranslatorInterface` est recommandée.
> Consultez le [Guide de traduction des FormTypes](./form-translations.md) pour les détails d'implémentation.

---

## Types de champs courants

### Types standards

| Type | Usage |
|------|-------|
| `TextType` | Champ texte simple |
| `TextareaType` | Zone de texte |
| `EmailType` | Email avec validation |
| `IntegerType` | Nombre entier |
| `NumberType` | Nombre décimal |
| `ChoiceType` | Select / Radio / Checkbox |
| `DateType` | Sélecteur de date |
| `FileType` | Upload de fichier |

### Types PrestaShop

| Type | Usage |
|------|-------|
| `SwitchType` | Toggle on/off PrestaShop |
| `TranslatableType` | Champ multilingue |
| `CategoryChoiceTreeType` | Arbre de catégories |
| `ColorPickerType` | Sélecteur de couleur |
| `MaterialChoiceTableType` | Tableau de choix |

---

## Exemple avec types PrestaShop

```php
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        // Champ multilingue
        ->add('name', TranslatableType::class, [
            'label' => 'Nom',
            'type' => TextType::class,
            'required' => true,
        ])
        
        // Switch PrestaShop
        ->add('active', SwitchType::class, [
            'label' => 'Actif',
            'required' => false,
        ])
        
        // Sélecteur de catégorie
        ->add('id_category', CategoryChoiceTreeType::class, [
            'label' => 'Catégorie',
            'required' => true,
        ]);
}
```

---

## Validation

### Contraintes courantes

```php
use Symfony\Component\Validator\Constraints as Assert;

->add('email', EmailType::class, [
    'constraints' => [
        new Assert\NotBlank(['message' => 'L\'email est requis']),
        new Assert\Email(['message' => 'Email invalide']),
    ],
])

->add('price', NumberType::class, [
    'constraints' => [
        new Assert\NotBlank(),
        new Assert\Positive(['message' => 'Le prix doit être positif']),
        new Assert\LessThan(['value' => 10000]),
    ],
])

->add('slug', TextType::class, [
    'constraints' => [
        new Assert\Regex([
            'pattern' => '/^[a-z0-9-]+$/',
            'message' => 'Caractères autorisés : a-z, 0-9, -',
        ]),
    ],
])
```

### Liste des contraintes

| Contrainte | Validation |
|------------|------------|
| `NotBlank` | Non vide |
| `NotNull` | Non null |
| `Length` | Longueur min/max |
| `Email` | Format email |
| `Url` | Format URL |
| `Regex` | Expression régulière |
| `Range` | Plage de valeurs |
| `Positive` | Nombre positif |
| `GreaterThan` | Supérieur à |
| `Choice` | Valeur parmi une liste |
| `File` | Fichier (type, taille) |

---

## Utiliser le formulaire

### Dans le contrôleur

```php
public function createAction(Request $request): Response
{
    $form = $this->createForm(ItemFormType::class);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        
        // Créer l'item
        $command = new CreateItemCommand(
            name: $data['name'],
            description: $data['description']
        );
        $this->commandHandler->handle($command);
        
        $this->addFlash('success', 'Item créé avec succès');
        return $this->redirectToRoute('monmodule_item_list');
    }
    
    return $this->render('@Modules/monmodule/views/templates/admin/item/create.html.twig', [
        'form' => $form->createView(),
    ]);
}
```

### Avec données initiales

```php
$item = $this->repository->findById($id);

$form = $this->createForm(ItemFormType::class, [
    'name' => $item->getName(),
    'description' => $item->getDescription(),
    'active' => $item->isActive(),
]);
```

---

## Rendu Twig

### Template complet

```twig
{% extends '@PrestaShop/Admin/layout.html.twig' %}

{% block content %}
<div class="card">
    <h2 class="card-header">{{ 'Create Item'|trans({}, 'Modules.Monmodule.Admin') }}</h2>
    <div class="card-body">
        {{ form_start(form) }}
        
        <div class="form-group row">
            {{ form_label(form.name, null, {'label_attr': {'class': 'col-sm-2'}}) }}
            <div class="col-sm-10">
                {{ form_widget(form.name) }}
                {{ form_errors(form.name) }}
            </div>
        </div>
        
        <div class="form-group row">
            {{ form_label(form.description, null, {'label_attr': {'class': 'col-sm-2'}}) }}
            <div class="col-sm-10">
                {{ form_widget(form.description) }}
            </div>
        </div>
        
        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <button type="submit" class="btn btn-primary">
                    {{ 'Save'|trans({}, 'Admin.Actions') }}
                </button>
            </div>
        </div>
        
        {{ form_end(form) }}
    </div>
</div>
{% endblock %}
```

### Rendu automatique

```twig
{{ form_start(form) }}
{{ form_widget(form) }}
<button type="submit" class="btn btn-primary">Enregistrer</button>
{{ form_end(form) }}
```

---

## Formulaire de configuration

Pour la page de configuration du module :

```php
// src/Application/Form/ConfigurationFormType.php

class ConfigurationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('MONMODULE_ACTIVE', SwitchType::class, [
                'label' => 'Activer le module',
                'data' => (bool) Configuration::get('MONMODULE_ACTIVE'),
            ])
            ->add('MONMODULE_TITLE', TextType::class, [
                'label' => 'Titre',
                'data' => Configuration::get('MONMODULE_TITLE'),
            ])
            ->add('MONMODULE_LIMIT', IntegerType::class, [
                'label' => 'Limite',
                'data' => (int) Configuration::get('MONMODULE_LIMIT'),
                'constraints' => [
                    new Assert\Positive(),
                ],
            ]);
    }
}
```

---

<details>
<summary>💡 Formulaires imbriqués</summary>

Pour des formulaires complexes, vous pouvez imbriquer des FormTypes :

```php
// AddressFormType
$builder
    ->add('street', TextType::class)
    ->add('city', TextType::class)
    ->add('zipcode', TextType::class);

// CustomerFormType
$builder
    ->add('name', TextType::class)
    ->add('address', AddressFormType::class);  // Imbriqué
```

</details>

---

**Prochaine section** : [Quality Assurance](../05-quality-assurance/)


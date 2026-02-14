# Guide de traduction des FormTypes Symfony dans les modules PrestaShop 8/9

## Principe général

Les FormTypes Symfony dans PrestaShop nécessitent une injection manuelle du `TranslatorInterface` pour traduire correctement les labels, help texts et choices. Le `translation_domain` seul ne suffit pas pour les labels.

---

## Structure des fichiers

```
modules/mon_module/
├── src/
│   └── Application/
│       └── Form/
│           └── MyFormType.php
├── config/
│   └── services.yml
└── translations/
    └── fr-FR/
        └── ModulesMonmoduleAdmin.fr-FR.xlf
```

---

## Convention de nommage du domaine de traduction

- Domaine : `Modules.Monmodule.Admin`
- Fichier XLIFF : `ModulesMonmoduleAdmin.fr-FR.xlf`
- Les points dans le domaine deviennent une concaténation CamelCase dans le nom du fichier

---

## Template de FormType avec traductions

```php
<?php

declare(strict_types=1);

namespace MonModule\Application\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MyFormType extends AbstractType
{
    private const TRANSLATION_DOMAIN = 'Modules.Monmodule.Admin';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->trans('Name'),
                'required' => true,
                'help' => $this->trans('Enter the name'),
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    $this->trans('Option A') => 'option_a',
                    $this->trans('Option B') => 'option_b',
                ],
                'label' => $this->trans('Type'),
            ])
            ->add('active', CheckboxType::class, [
                'label' => $this->trans('Active'),
                'required' => false,
            ]);
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], self::TRANSLATION_DOMAIN);
    }

    public function getBlockPrefix(): string
    {
        return 'my_form';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => self::TRANSLATION_DOMAIN,
        ]);
    }
}
```

---

## Déclaration du service (services.yml)

```yaml
services:
  MonModule\Application\Form\MyFormType:
    arguments:
      - '@translator'
    tags:
      - { name: form.type }
```

---

## Structure du fichier XLIFF

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en-US" target-language="fr-FR" datatype="plaintext" original="Modules.Monmodule.Admin">
    <body>
      <trans-unit id="1" resname="Name">
        <source>Name</source>
        <target>Nom</target>
      </trans-unit>
      <trans-unit id="2" resname="Enter the name">
        <source>Enter the name</source>
        <target>Entrez le nom</target>
      </trans-unit>
      <trans-unit id="3" resname="Type">
        <source>Type</source>
        <target>Type</target>
      </trans-unit>
      <trans-unit id="4" resname="Option A">
        <source>Option A</source>
        <target>Option A</target>
      </trans-unit>
      <trans-unit id="5" resname="Option B">
        <source>Option B</source>
        <target>Option B</target>
      </trans-unit>
      <trans-unit id="6" resname="Active">
        <source>Active</source>
        <target>Actif</target>
      </trans-unit>
    </body>
  </file>
</xliff>
```

---

## Règles importantes

1. **Toujours injecter le `TranslatorInterface`** via le constructeur
2. **Toujours déclarer le FormType comme service** avec le tag `form.type`
3. **Utiliser `$this->trans()` pour tous les textes visibles** : labels, help, choices, messages de validation
4. **Le code source reste en anglais** : les clés de traduction sont les textes anglais
5. **Un seul fichier XLIFF par domaine** : regrouper toutes les traductions Admin dans `ModulesMonmoduleAdmin.fr-FR.xlf`
6. **L'attribut `original` du XLIFF doit correspondre exactement au domaine** utilisé dans le PHP

---

## Ce qui NE fonctionne PAS

```php
// ❌ Ne traduit PAS les labels automatiquement
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'translation_domain' => 'Modules.Monmodule.Admin',
    ]);
}

// ❌ choice_translation_domain ne traduit que les choices, pas les labels
->add('type', ChoiceType::class, [
    'label' => 'Type',  // Non traduit !
    'choice_translation_domain' => 'Modules.Monmodule.Admin',
])
```

---

## Ce qui FONCTIONNE

```php
// ✅ Injection du translator + appel manuel
->add('type', ChoiceType::class, [
    'label' => $this->trans('Type'),  // Traduit !
    'choices' => [
        $this->trans('Option A') => 'option_a',  // Traduit !
    ],
])
```

---

## Checklist avant de livrer un FormType

- [ ] TranslatorInterface injecté dans le constructeur
- [ ] Service déclaré dans services.yml avec '@translator' et tag 'form.type'
- [ ] Tous les labels utilisent $this->trans()
- [ ] Tous les help texts utilisent $this->trans()
- [ ] Toutes les clés de choices utilisent $this->trans()
- [ ] Tous les messages de validation utilisent $this->trans()
- [ ] Fichier XLIFF créé avec le bon nom (CamelCase)
- [ ] Attribut 'original' du XLIFF correspond au domaine PHP
- [ ] Cache vidé après modification

---

## Commandes utiles

# Vider le cache PrestaShop
php bin/console cache:clear

# Supprimer manuellement le cache si problème
rm -rf var/cache/*

# Forcer le rechargement des traductions
# Aller dans : International > Traductions > Modifier les traductions
# Sélectionner : Traductions des modules installés > [votre module] > Français

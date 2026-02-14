<?php
/**
 * WEDEV Extension - EntityPicker
 *
 * ⚠️ NE PAS MODIFIER - Géré par WEDEV CLI
 * Mise à jour via: wedev ps module --update-core
 *
 * @version 1.0.0
 */

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\EntityPicker\Form;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type de formulaire pour la sélection d'entités via recherche AJAX.
 *
 * Utilisation dans un FormType:
 * ```php
 * $builder->add('products', EntityPickerType::class, [
 *     'entity_type' => 'product',
 *     'search_url' => $this->router->generate('my_module_search_products'),
 *     'multiple' => true,
 *     'label' => 'Produits associés',
 * ]);
 * ```
 */
class EntityPickerType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Le champ hidden stocke les IDs sélectionnés en JSON
        $builder->add('ids', HiddenType::class, [
            'required' => false,
            'attr' => [
                'class' => 'entity-picker-ids',
                'data-entity-type' => $options['entity_type'],
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['entity_type'] = $options['entity_type'];
        $view->vars['search_url'] = $options['search_url'];
        $view->vars['fetch_url'] = $options['fetch_url'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['min_chars'] = $options['min_chars'];
        $view->vars['max_results'] = $options['max_results'];
        $view->vars['allow_clear'] = $options['allow_clear'];
        $view->vars['initial_entities'] = $options['initial_entities'];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entity_type' => 'product',
            'search_url' => '',
            'fetch_url' => '',
            'multiple' => true,
            'placeholder' => 'Rechercher...',
            'min_chars' => 2,
            'max_results' => 20,
            'allow_clear' => true,
            'initial_entities' => [],
            'compound' => true,
        ]);

        $resolver->setAllowedTypes('entity_type', 'string');
        $resolver->setAllowedTypes('search_url', 'string');
        $resolver->setAllowedTypes('fetch_url', 'string');
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedTypes('placeholder', 'string');
        $resolver->setAllowedTypes('min_chars', 'int');
        $resolver->setAllowedTypes('max_results', 'int');
        $resolver->setAllowedTypes('allow_clear', 'bool');
        $resolver->setAllowedTypes('initial_entities', 'array');
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'wedev_entity_picker';
    }
}


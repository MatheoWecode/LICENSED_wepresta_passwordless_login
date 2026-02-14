<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;
use Product;

/**
 * Condition basée sur un produit.
 *
 * Utilisée principalement dans les contextes où un produit est disponible
 * (page produit, hook produit, etc.).
 *
 * @example
 * // Produit dans une catégorie spécifique
 * new ProductCondition('category', '=', 5)
 *
 * // Produit d'un fabricant spécifique
 * new ProductCondition('manufacturer', 'in', [10, 11, 12])
 *
 * // Prix supérieur à 50€
 * new ProductCondition('price', '>', 50.00)
 *
 * // Stock inférieur à 10
 * new ProductCondition('stock', '<', 10)
 *
 * // Produit en promotion
 * new ProductCondition('on_sale', '=', true)
 */
final class ProductCondition extends AbstractCondition
{
    private const SUPPORTED_FIELDS = [
        'id',
        'category',
        'categories',
        'manufacturer',
        'supplier',
        'price',
        'price_without_tax',
        'stock',
        'weight',
        'on_sale',
        'is_new',
        'is_virtual',
        'reference',
        'ean13',
    ];

    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed $value
    ) {
        if (!in_array($this->field, self::SUPPORTED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported product field: "%s". Supported: %s',
                $this->field,
                implode(', ', self::SUPPORTED_FIELDS)
            ));
        }
    }

    public function evaluate(RuleContext $context): bool
    {
        // Récupérer le produit depuis le contexte personnalisé
        $product = $context->get('product');

        if (!$product instanceof Product) {
            return false;
        }

        $productValue = $this->getProductValue($product, $context);

        return $this->compare($productValue, $this->operator, $this->value);
    }

    /**
     * Extrait la valeur du produit pour le champ demandé.
     */
    private function getProductValue(Product $product, RuleContext $context): mixed
    {
        $langId = $context->getLanguageId() ?: 1;

        return match ($this->field) {
            'id' => (int) $product->id,
            'category' => (int) $product->id_category_default,
            'categories' => $product->getCategories(),
            'manufacturer' => (int) $product->id_manufacturer,
            'supplier' => (int) $product->id_supplier,
            'price' => (float) $product->getPrice(true),
            'price_without_tax' => (float) $product->getPrice(false),
            'stock' => (int) \StockAvailable::getQuantityAvailableByProduct($product->id),
            'weight' => (float) $product->weight,
            'on_sale' => (bool) $product->on_sale,
            'is_new' => (bool) $product->isNew(),
            'is_virtual' => (bool) $product->is_virtual,
            'reference' => (string) $product->reference,
            'ean13' => (string) $product->ean13,
            default => null,
        };
    }
}


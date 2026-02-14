<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules\Condition;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Wepresta_Passwordless_Login\Wedev\Extension\Rules\RuleContext;

/**
 * Condition basée sur le panier.
 *
 * @example
 * // Total supérieur à 100€
 * new CartCondition('total', '>=', 100.00)
 *
 * // Plus de 3 produits
 * new CartCondition('products_count', '>', 3)
 *
 * // Contient un produit spécifique
 * new CartCondition('has_product', 'in', [123, 456])
 *
 * // Contient une catégorie
 * new CartCondition('has_category', 'in', [5, 10])
 *
 * // Poids inférieur à 5kg
 * new CartCondition('weight', '<', 5.0)
 */
final class CartCondition extends AbstractCondition
{
    private const SUPPORTED_FIELDS = [
        'total',
        'total_without_tax',
        'products_count',
        'has_product',
        'has_category',
        'has_manufacturer',
        'weight',
        'carrier_id',
        'is_empty',
    ];

    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed $value
    ) {
        if (!in_array($this->field, self::SUPPORTED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported cart field: "%s". Supported: %s',
                $this->field,
                implode(', ', self::SUPPORTED_FIELDS)
            ));
        }
    }

    public function evaluate(RuleContext $context): bool
    {
        $cart = $context->getCart();
        if ($cart === null) {
            return false;
        }

        $cartValue = $this->getCartValue($cart);

        return $this->compare($cartValue, $this->operator, $this->value);
    }

    /**
     * Extrait la valeur du panier pour le champ demandé.
     */
    private function getCartValue(\Cart $cart): mixed
    {
        return match ($this->field) {
            'total' => (float) $cart->getOrderTotal(true),
            'total_without_tax' => (float) $cart->getOrderTotal(false),
            'products_count' => (int) $cart->nbProducts(),
            'has_product' => $this->getProductIds($cart),
            'has_category' => $this->getCategoryIds($cart),
            'has_manufacturer' => $this->getManufacturerIds($cart),
            'weight' => (float) $cart->getTotalWeight(),
            'carrier_id' => (int) $cart->id_carrier,
            'is_empty' => $cart->nbProducts() === 0,
            default => null,
        };
    }

    /**
     * @return array<int>
     */
    private function getProductIds(\Cart $cart): array
    {
        $products = $cart->getProducts();

        return array_map(
            static fn (array $p): int => (int) $p['id_product'],
            $products
        );
    }

    /**
     * @return array<int>
     */
    private function getCategoryIds(\Cart $cart): array
    {
        $products = $cart->getProducts();

        return array_unique(array_map(
            static fn (array $p): int => (int) $p['id_category_default'],
            $products
        ));
    }

    /**
     * @return array<int>
     */
    private function getManufacturerIds(\Cart $cart): array
    {
        $products = $cart->getProducts();

        return array_unique(array_filter(array_map(
            static fn (array $p): int => (int) ($p['id_manufacturer'] ?? 0),
            $products
        )));
    }
}


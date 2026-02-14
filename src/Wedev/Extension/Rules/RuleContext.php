<?php

declare(strict_types=1);

namespace Wepresta_Passwordless_Login\Wedev\Extension\Rules;
if (!defined('_PS_VERSION_')) {
    exit;
}


use Cart;
use Context;
use Customer;
use Order;
use Product;

/**
 * Contexte d'évaluation des règles.
 *
 * Encapsule toutes les données nécessaires pour évaluer les conditions.
 *
 * @example
 * // Depuis le panier courant
 * $context = RuleContext::fromCurrentCart();
 *
 * // Depuis un panier spécifique
 * $context = RuleContext::fromCart($cart);
 *
 * // Depuis une commande
 * $context = RuleContext::fromOrder($order);
 *
 * // Données personnalisées
 * $context = $context->with('custom_key', $value);
 */
final class RuleContext
{
    /** @var array<string, mixed> */
    private array $data = [];

    private function __construct(
        private readonly ?Cart $cart = null,
        private readonly ?Customer $customer = null,
        private readonly ?Order $order = null,
        private readonly int $shopId = 0,
        private readonly int $languageId = 0
    ) {
    }

    /**
     * Crée un contexte depuis le contexte PrestaShop courant.
     */
    public static function fromCurrentCart(): self
    {
        $psContext = Context::getContext();

        return new self(
            cart: $psContext->cart,
            customer: $psContext->customer,
            shopId: (int) $psContext->shop->id,
            languageId: (int) $psContext->language->id
        );
    }

    /**
     * Crée un contexte depuis un panier.
     */
    public static function fromCart(Cart $cart): self
    {
        $customer = null;
        if ($cart->id_customer > 0) {
            $customer = new Customer((int) $cart->id_customer);
        }

        return new self(
            cart: $cart,
            customer: $customer,
            shopId: (int) $cart->id_shop,
            languageId: (int) $cart->id_lang
        );
    }

    /**
     * Crée un contexte depuis une commande.
     */
    public static function fromOrder(Order $order): self
    {
        $cart = null;
        if ($order->id_cart > 0) {
            $cart = new Cart((int) $order->id_cart);
        }

        $customer = null;
        if ($order->id_customer > 0) {
            $customer = new Customer((int) $order->id_customer);
        }

        return new self(
            cart: $cart,
            customer: $customer,
            order: $order,
            shopId: (int) $order->id_shop,
            languageId: (int) $order->id_lang
        );
    }

    /**
     * Crée un contexte vide (pour tests).
     */
    public static function empty(): self
    {
        return new self();
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    /**
     * Retourne les produits du panier.
     *
     * @return array<array<string, mixed>>
     */
    public function getCartProducts(): array
    {
        if ($this->cart === null) {
            return [];
        }

        return $this->cart->getProducts();
    }

    /**
     * Retourne le total du panier.
     */
    public function getCartTotal(bool $withTax = true): float
    {
        if ($this->cart === null) {
            return 0.0;
        }

        return (float) $this->cart->getOrderTotal($withTax);
    }

    /**
     * Vérifie si le client est connecté.
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->customer !== null && $this->customer->isLogged();
    }

    /**
     * Retourne le groupe par défaut du client.
     */
    public function getCustomerGroupId(): int
    {
        if ($this->customer === null) {
            return 0;
        }

        return (int) $this->customer->id_default_group;
    }

    // -------------------------------------------------------------------------
    // Données personnalisées
    // -------------------------------------------------------------------------

    /**
     * Retourne une valeur personnalisée.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Crée un nouveau contexte avec une valeur additionnelle.
     */
    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->data[$key] = $value;

        return $clone;
    }

    /**
     * Crée un nouveau contexte avec plusieurs valeurs.
     *
     * @param array<string, mixed> $data
     */
    public function withData(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_merge($clone->data, $data);

        return $clone;
    }
}


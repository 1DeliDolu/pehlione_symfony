<?php

namespace App\Service\Cart;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Session-based shopping cart service.
 * Stores cart data in session with structure: ['product_id' => quantity, ...]
 */
final class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    /**
     * Add or update product quantity in cart.
     */
    public function addProduct(int $productId, int $qty = 1): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::SESSION_KEY, []);

        if (isset($cart[$productId])) {
            $cart[$productId] += $qty;
        } else {
            $cart[$productId] = $qty;
        }

        $session->set(self::SESSION_KEY, $cart);
    }

    /**
     * Remove product from cart.
     */
    public function removeProduct(int $productId): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get(self::SESSION_KEY, []);

        unset($cart[$productId]);

        $session->set(self::SESSION_KEY, $cart);
    }

    /**
     * Get cart contents: ['product_id' => quantity, ...]
     *
     * @return array<int, int>
     */
    public function getCart(): array
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    /**
     * Alias for getCart() - returns all cart items.
     *
     * @return array<int, int>
     */
    public function all(): array
    {
        return $this->getCart();
    }

    /**
     * Count total items in cart.
     */
    public function countItems(): int
    {
        return array_sum($this->getCart());
    }

    /**
     * Clear the entire cart.
     */
    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }
}

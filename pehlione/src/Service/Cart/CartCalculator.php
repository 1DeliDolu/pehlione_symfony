<?php

namespace App\Service\Cart;

use App\Repository\ProductRepository;

/**
 * Calculates cart summary: lines (with product data from DB), totals, etc.
 */
final class CartCalculator
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ProductRepository $productRepository
    ) {}

    /**
     * Get cart summary with full product data.
     *
     * @return array{lines: array, totalAmount: int, currency: string}
     */
    public function getSummary(): array
    {
        $cartData = $this->cart->getCart();
        $lines = [];
        $totalAmount = 0;

        foreach ($cartData as $productId => $qty) {
            $product = $this->productRepository->find($productId);

            if (!$product) {
                // Product deleted, skip
                continue;
            }

            $lineTotal = $product->getPriceAmount() * $qty;
            $totalAmount += $lineTotal;

            $lines[] = [
                'product' => $product,
                'qty' => $qty,
                'lineTotal' => $lineTotal,
            ];
        }

        return [
            'lines' => $lines,
            'totalAmount' => $totalAmount,
            'currency' => 'USD', // Adjust to your currency
        ];
    }
}

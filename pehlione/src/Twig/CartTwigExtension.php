<?php

namespace App\Twig;

use App\Service\Cart\CartCalculator;
use App\Service\Cart\CartService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class CartTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CartCalculator $calculator
    ) {}

    public function getGlobals(): array
    {
        // Make cart_count available on every page for the header icon
        return [
            'cart_count' => $this->cart->countItems(),
        ];
    }

    public function getFunctions(): array
    {
        // Make cart_summary available in templates for the drawer component
        return [
            new TwigFunction('cart_summary', [$this, 'summary']),
        ];
    }

    /**
     * @return array{lines: array, totalAmount: int, currency: string}
     */
    public function summary(): array
    {
        return $this->calculator->getSummary();
    }
}

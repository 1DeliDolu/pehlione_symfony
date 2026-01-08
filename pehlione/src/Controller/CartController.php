<?php

namespace App\Controller;

use App\Service\Cart\CartCalculator;
use App\Service\Cart\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CartCalculator $cartCalculator
    ) {}

    #[Route('', name: 'cart_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('cart/show.html.twig');
    }

    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        // Get quantity from request (form field is 'quantity'), default to 1
        $qty = (int) $request->request->get('quantity', 1);
        $qty = max(1, $qty); // Ensure minimum 1

        $this->cart->addProduct($id, $qty);

        // Add flash message
        $this->addFlash('success', 'Product added to cart!');

        // Redirect to referrer or product page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('app_product_index'));
    }

    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('cart_remove_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->cart->removeProduct($id);

        // Redirect to referrer or cart page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('cart_show'));
    }

    #[Route('/clear', name: 'cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $this->cart->clear();

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/update-quantity/{id}', name: 'cart_update_quantity', methods: ['POST'])]
    public function updateQuantity(int $id, Request $request): Response
    {
        $qty = (int) $request->getPayload()->get('qty', 1);
        $qty = max(1, min(10, $qty)); // Clamp between 1 and 10

        // Get current cart
        $cart = $this->cart->getCart();
        $currentQty = $cart[$id] ?? 0;

        // Calculate difference and update
        $diff = $qty - $currentQty;
        if ($diff !== 0) {
            $this->cart->addProduct($id, $diff);
        }

        // Return updated cart summary as JSON
        $summary = $this->cartCalculator->getSummary();

        return $this->json([
            'success' => true,
            'cartCount' => $this->cart->countItems(),
            'lines' => array_map(function ($line) {
                return [
                    'id' => $line['product']->getId(),
                    'qty' => $line['qty'],
                    'lineTotal' => $line['lineTotal'],
                    'price' => $line['product']->getPriceAmount(),
                ];
            }, $summary['lines']),
            'subtotal' => $summary['totalAmount'],
            'shipping' => 500, // $5.00 in cents
            'tax' => intval($summary['totalAmount'] * 0.08),
            'total' => intval($summary['totalAmount'] + 500 + ($summary['totalAmount'] * 0.08)),
        ]);
    }
}

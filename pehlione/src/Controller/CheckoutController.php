<?php

namespace App\Controller;

use App\Service\Order\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    #[Route('', name: 'checkout', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('checkout/index.html.twig');
    }

    #[Route('/process', name: 'checkout_process', methods: ['POST'])]
    public function process(): Response
    {
        $user = $this->getUser();

        try {
            $order = $this->orderService->createOrderFromCart($user);

            $this->addFlash('success', 'Order created successfully! Order #' . $order->getOrderNumber());

            return $this->redirectToRoute('order_confirmation', ['id' => $order->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error creating order: ' . $e->getMessage());

            return $this->redirectToRoute('cart_show');
        }
    }

    #[Route('/confirmation/{id}', name: 'order_confirmation', methods: ['GET'])]
    public function confirmation(int $id): Response
    {
        // Ensure order belongs to user
        $order = $this->orderService->getUserOrders($this->getUser());
        $order = array_filter($order, fn($o) => $o->getId() === $id);
        $order = reset($order);

        if (!$order) {
            throw $this->createAccessDeniedException('Order not found');
        }

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}

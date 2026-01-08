<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\Cart\CartCalculator;
use App\Service\Cart\CartService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service untuk membuat order dari cart.
 */
final class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CartCalculator $calculator,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Buat order dari cart data user.
     */
    public function createOrderFromCart(User $user): Order
    {
        $summary = $this->calculator->getSummary();
        $lines = $summary['lines'];
        $subtotal = $summary['totalAmount'];

        // Calculate totals (in cents)
        $shipping = 500; // $5.00
        $tax = intval($subtotal * 0.08); // 8% tax
        $totalAmount = $subtotal + $shipping + $tax;

        // Generate order number (e.g., ORD-20260108-123456)
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        // Create order
        $order = new Order();
        $order->setOrderNumber($orderNumber);
        $order->setUser($user);
        $order->setTotalAmount($totalAmount);
        $order->setStatus('pending'); // pending, processing, shipped, delivered, cancelled
        $order->setCurrency('USD');
        $order->setCreatedAt(new \DateTimeImmutable());

        // Add items to order
        foreach ($lines as $line) {
            $product = $line['product'];
            $qty = $line['qty'];
            $lineTotal = $line['lineTotal'];

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setProductName($product->getName());
            $item->setProductSlug($product->getSlug());
            $item->setQuantity($qty);
            $item->setUnitPriceAmount($product->getPriceAmount());
            $item->setLineTotalAmount($lineTotal);
            $item->setCurrency('USD');
            $item->setOrderRef($order);

            $order->addOrderItem($item);
        }

        // Persist order (items cascade)
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Clear cart after successful order
        $this->cart->clear();

        return $order;
    }

    /**
     * Get user orders.
     *
     * @return Order[]
     */
    public function getUserOrders(User $user): array
    {
        return $this->orderRepository->findByUserId($user->getId());
    }
}

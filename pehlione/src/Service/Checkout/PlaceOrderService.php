<?php

namespace App\Service\Checkout;

use App\Entity\ShopOrder;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\Cart\CartService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class PlaceOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartService $cart,
        private readonly ProductRepository $products
    ) {}

    public function place(User $user): ShopOrder
    {
        $cartItems = $this->cart->all();
        if (!$cartItems) {
            throw new \RuntimeException('Sepet boş.');
        }

        $address = $user->getAddress();
        if (!$address) {
            throw new \RuntimeException('Checkout için adres gerekli.');
        }

        return $this->em->wrapInTransaction(function () use ($user, $address, $cartItems) {
            $ids = array_map('intval', array_keys($cartItems));

            // Ürünleri kilitle (stok düşümü için)
            $q = $this->products->createQueryBuilder('p')
                ->andWhere('p.isActive = :active')
                ->andWhere('p.id IN (:ids)')
                ->setParameter('active', true)
                ->setParameter('ids', $ids)
                ->getQuery();

            $q->setLockMode(LockMode::PESSIMISTIC_WRITE);
            $products = $q->getResult();

            $map = [];
            foreach ($products as $p) {
                $map[$p->getId()] = $p;
            }

            $order = new ShopOrder();
            $order->setUser($user);
            $order->setStatus('pending');
            $order->setOrderNumber($this->generateOrderNumber());

            // Address snapshot - User firstname/lastname + Address alanları
            $order->setShippingFirstName($user->getFirstname() ?? '');
            $order->setShippingLastName($user->getLastname() ?? '');
            $order->setShippingPhone($address->getPhone());
            $order->setShippingLine1($address->getStrasse());
            $order->setShippingLine2($address->getHausnummer());
            $order->setShippingCity($address->getCity());
            $order->setShippingPostalCode($address->getPostalCode());
            $order->setShippingRegion($address->getRegion());
            $order->setShippingCountryCode($address->getCountryCode());

            $subtotal = 0;
            $currency = 'EUR';

            foreach ($cartItems as $productId => $qty) {
                $productId = (int) $productId;
                $qty = max(1, (int) $qty);

                $product = $map[$productId] ?? null;
                if (!$product) {
                    throw new \RuntimeException('Sepetteki ürün artık mevcut değil.');
                }

                if ($qty > $product->getStockQuantity()) {
                    throw new \RuntimeException('Stok yetersiz: ' . $product->getName());
                }

                $currency = $product->getCurrency();
                $unit = $product->getPriceAmount();
                $lineTotal = $unit * $qty;

                $item = new OrderItem();
                $item->setOrderRef($order);
                $item->setProduct($product);

                // Snapshot
                $item->setProductName($product->getName());
                $item->setProductSlug($product->getSlug());
                $item->setUnitPriceAmount($unit);
                $item->setCurrency($currency);
                $item->setQuantity($qty);
                $item->setLineTotalAmount($lineTotal);

                $order->addItem($item);

                // stok düş
                $product->setStockQuantity($product->getStockQuantity() - $qty);

                $subtotal += $lineTotal;
            }

            $order->setCurrency($currency);
            $order->setSubtotalAmount($subtotal);
            $order->setTotalAmount($subtotal);

            $this->em->persist($order);
            $this->em->flush();

            $this->cart->clear();

            // status update
            $order->setStatus('placed');
            $this->em->flush();

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        return 'PO-' . (new \DateTimeImmutable())->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}

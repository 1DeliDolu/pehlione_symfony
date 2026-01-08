<?php

namespace App\Controller;

use App\Entity\ShopOrder;
use App\Entity\User;
use App\Repository\ShopOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/orders', name: 'orders_list', methods: ['GET'])]
    public function list(ShopOrderRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $orders = $repo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/list.html.twig', ['orders' => $orders]);
    }

    #[Route('/orders/{id}', name: 'order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ShopOrder $order): Response
    {
        $user = $this->getUser();
        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/show.html.twig', ['order' => $order]);
    }
}


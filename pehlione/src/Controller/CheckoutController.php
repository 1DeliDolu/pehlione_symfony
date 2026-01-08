<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Service\Cart\CartCalculator;
use App\Service\Checkout\PlaceOrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    #[Route('/checkout/address', name: 'checkout_address', methods: ['GET','POST'])]
    public function address(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = $user->getAddress();
        if (!$address) {
            $address = new Address();
            $address->setUser($user);
            $user->setAddress($address);
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($address);
            $em->flush();

            return $this->redirectToRoute('checkout_review');
        }

        return $this->render('checkout/address.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/checkout/review', name: 'checkout_review', methods: ['GET'])]
    public function review(CartCalculator $calc): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getAddress()) {
            $this->addFlash('warning', 'Checkout için adres gerekli.');
            return $this->redirectToRoute('checkout_address');
        }

        $summary = $calc->getSummary();
        if (count($summary['lines']) === 0) {
            $this->addFlash('warning', 'Sepet boş.');
            return $this->redirectToRoute('cart_show');
        }

        return $this->render('checkout/review.html.twig', [
            'lines' => $summary['lines'],
            'totalAmount' => $summary['totalAmount'],
            'currency' => $summary['currency'],
            'address' => $user->getAddress(),
        ]);
    }

    #[Route('/checkout/place', name: 'checkout_place', methods: ['POST'])]
    public function place(Request $request, PlaceOrderService $service): Response
    {
        if (!$this->isCsrfTokenValid('checkout_place', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $order = $service->place($user);
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('checkout_review');
        }
    }
}

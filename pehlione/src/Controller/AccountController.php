<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Address;
use App\Form\AddressType;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/profile', name: 'app_account_profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('account/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/address', name: 'app_account_address', methods: ['GET', 'POST'])]
    public function address(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $address = $user->getAddress();
        $isNew = false;

        // Create new address if doesn't exist
        if (!$address) {
            $address = new Address();
            $address->setUser($user);
            $isNew = true;
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Always ensure user is set
            $address->setUser($user);

            if ($isNew) {
                $entityManager->persist($address);
                // Also persist the user to update the relationship
                $entityManager->persist($user);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Address saved successfully!');
            return $this->redirectToRoute('app_account_address');
        }

        return $this->render('account/address.html.twig', [
            'address' => $address,
            'form' => $form,
        ]);
    }
}

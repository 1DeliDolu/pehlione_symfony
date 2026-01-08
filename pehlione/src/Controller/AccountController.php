<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\ChangePasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\Upload\AvatarUploader;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/profile', name: 'app_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, AvatarUploader $uploader): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('avatar');

            if ($uploadedFile) {
                // Upload the file
                $avatarPath = $uploader->upload($user->getId(), $uploadedFile);

                // Update user avatar path
                $user->setAvatarPath($avatarPath);
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Avatar updated successfully!');
                return $this->redirectToRoute('app_account_profile');
            }
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/address', name: 'app_account_address', methods: ['GET', 'POST'])]
    public function address(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
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

    #[Route('/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_change_password');
            }

            // Set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('app_account_profile');
        }

        return $this->render('account/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}

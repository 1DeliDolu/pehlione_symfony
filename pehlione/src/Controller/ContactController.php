<?php

namespace App\Controller;

use App\Entity\SupportMessage;
use App\Entity\User;
use App\Form\Support\SupportRequestType;
use App\Service\Support\SupportMailer;
use App\Service\Support\TicketSlaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        SupportMailer $supportMailer,
        TicketSlaService $slaService,
        RateLimiterFactory $contactFormLimiter
    ): Response {
        $user = $this->getUser();

        $form = $this->createForm(SupportRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limiter = $contactFormLimiter->create($request->getClientIp() ?? 'anon');
            $limit = $limiter->consume(1);
            if (!$limit->isAccepted()) {
                $this->addFlash('error', 'Çok fazla deneme yapıldı. Lütfen daha sonra tekrar deneyin.');
                return $this->redirectToRoute('app_contact');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $customerName = $data['customerName'] ?? null;
            $customerEmail = $data['customerEmail'] ?? null;

            if ($user instanceof User) {
                $customerName = $customerName ?: $user->getName();
                $customerEmail = $customerEmail ?: $user->getEmail();
            }

            $thread = (new SupportMessage())
                ->setType(SupportMessage::TYPE_CUSTOMER)
                ->setDepartment($data['department'])
                ->setSubject($data['subject'])
                ->setMessage($data['message'])
                ->setCustomerName($customerName)
                ->setCustomerEmail($customerEmail);

            if ($user instanceof User) {
                $thread->setCreatedBy($user);
            }

            $slaService->onTicketCreated($thread);

            $em->persist($thread);
            $em->flush();

            $supportMailer->notifyNewThread($thread);

            $this->addFlash('success', 'Mesajınız alındı. En kısa sürede dönüş yapacağız.');

            if ($user instanceof User) {
                return $this->redirectToRoute('account_mail');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

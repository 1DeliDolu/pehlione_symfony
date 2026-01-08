<?php

namespace App\Controller\Support;

use App\Entity\SupportMessage;
use App\Form\Support\InternalThreadType;
use App\Service\Support\SupportMailer;
use App\Service\Support\TicketSlaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InternalThreadController extends AbstractController
{
    #[Route('/support/internal/new', name: 'support_internal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SupportMailer $mailer, TicketSlaService $slaService): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->isStaff() || !$user->getSupportDepartment()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(InternalThreadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $thread = (new SupportMessage())
                ->setType(SupportMessage::TYPE_INTERNAL)
                ->setFromDepartment($user->getSupportDepartment())
                ->setDepartment($data['department'])
                ->setSubject($data['subject'])
                ->setMessage($data['message'])
                ->setCreatedBy($user);

            $slaService->onTicketCreated($thread);

            $em->persist($thread);
            $em->flush();

            $mailer->notifyNewThread($thread);

            $this->addFlash('success', 'İç mesaj gönderildi.');
            return $this->redirectToRoute('support_thread_show', ['id' => $thread->getId()]);
        }

        return $this->render('support/internal/new.html.twig', [
            'form' => $form,
        ]);
    }
}

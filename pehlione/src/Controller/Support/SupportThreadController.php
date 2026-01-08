<?php

namespace App\Controller\Support;

use App\Entity\SupportReply;
use App\Form\Support\SupportReplyType;
use App\Repository\SupportMessageRepository;
use App\Service\Support\SupportMailer;
use App\Service\Support\TicketSlaService;
use App\Service\Support\TicketStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SupportThreadController extends AbstractController
{
    #[Route('/support/thread/{id}', name: 'support_thread_show', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        SupportMessageRepository $repo,
        EntityManagerInterface $em,
        SupportMailer $supportMailer,
        TicketSlaService $slaService,
        TicketStateManager $stateManager
    ): Response {
        $thread = $repo->find($id);
        if (!$thread) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('SUPPORT_THREAD_VIEW', $thread);

        $reply = new SupportReply();
        $form = $this->createForm(SupportReplyType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('SUPPORT_THREAD_REPLY', $thread);

            $reply
                ->setAuthor($this->getUser())
                ->setSupportMessage($thread);

            if (!($this->getUser()?->isStaff() ?? false)) {
                $reply->setInternal(false);
            }

            $thread->addReply($reply);

            if (!$reply->isInternal()) {
                $isStaffReply = $reply->getAuthor()?->isStaff() ?? false;
                if ($isStaffReply) {
                    $slaService->onStaffReply($thread);
                    $stateManager->markPending($thread);
                } else {
                    $slaService->onCustomerReply($thread);
                    $stateManager->reopen($thread);
                }
            }

            $em->persist($reply);
            $em->flush();

            if (!$reply->isInternal()) {
                $supportMailer->notifyNewReply($thread, $reply);
            }

            $this->addFlash('success', 'Mesaj gönderildi.');
            return $this->redirectToRoute('support_thread_show', ['id' => $thread->getId()]);
        }

        return $this->render('support/thread/show.html.twig', [
            'thread' => $thread,
            'form' => $form,
        ]);
    }
}

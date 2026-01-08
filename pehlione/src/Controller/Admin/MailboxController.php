<?php

namespace App\Controller\Admin;

use App\Entity\Support\SupportMessage;
use App\Repository\Support\SupportMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/mail', name: 'admin_mail_')]
final class MailboxController extends AbstractController
{
    #[Route('', name: 'inbox', methods: ['GET'])]
    public function inbox(Request $request, SupportMessageRepository $repo): Response
    {
        $status = $request->query->get('status'); // new/read/archived
        $q = $request->query->get('q');

        // For now, admin can see all messages regardless of department
        // departmentId = null (don't filter by single dept)
        // allowedDepartmentIds = null (no department filtering - admin sees all)
        $messages = $repo->findForInbox($status, $q, null, null, 100);

        return $this->render('admin/mail/inbox.html.twig', [
            'messages' => $messages,
            'status' => $status,
            'q' => $q,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(SupportMessage $message, EntityManagerInterface $em): Response
    {
        // Detaya girince otomatik okundu işaretle
        $message->markRead($this->getUser());
        $em->flush();

        return $this->render('admin/mail/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/{id<\d+>}/archive', name: 'archive', methods: ['POST'])]
    public function archive(SupportMessage $message, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('archive_mail_'.$message->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $message->archive($this->getUser());
        $em->flush();

        $this->addFlash('success', 'Mesaj arşivlendi.');
        return $this->redirectToRoute('admin_mail_inbox');
    }
}

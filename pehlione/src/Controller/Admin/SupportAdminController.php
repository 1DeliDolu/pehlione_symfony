<?php

namespace App\Controller\Admin;

use App\Form\Support\Admin\SupportAdminUpdateType;
use App\Repository\SupportDepartmentRepository;
use App\Repository\SupportMessageRepository;
use App\Repository\SupportTagRepository;
use App\Service\Support\TicketStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/support', name: 'admin_support_')]
final class SupportAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        SupportMessageRepository $repo,
        SupportDepartmentRepository $departmentRepository,
        SupportTagRepository $tagRepository
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $filters = $request->query->all();

        $qb = $repo->createAdminSearchQuery($filters);
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        $total = count($paginator);
        $pages = (int) max(1, ceil($total / $limit));

        return $this->render('support/admin/index.html.twig', [
            'items' => $paginator,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'filters' => $filters,
            'departments' => $departmentRepository->findBy([], ['name' => 'ASC']),
            'tags' => $tagRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        SupportMessageRepository $repo,
        EntityManagerInterface $em,
        TicketStateManager $stateManager
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException();
        }

        $thread = $repo->find($id) ?? throw $this->createNotFoundException();

        $action = (string) $request->request->get('_action', '');
        if ($action && $request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('support_action_'.$thread->getId(), $token)) {
                throw $this->createAccessDeniedException();
            }

            if ($action === 'close') {
                $stateManager->close($thread);
            } elseif ($action === 'reopen') {
                $stateManager->reopen($thread);
            } elseif ($action === 'assign_to_me') {
                $thread->assignTo($this->getUser());
            }

            $em->flush();
            return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
        }

        $form = $this->createForm(SupportAdminUpdateType::class, $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ticket güncellendi.');
            return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
        }

        return $this->render('support/admin/show.html.twig', [
            'thread' => $thread,
            'form' => $form,
        ]);
    }
}

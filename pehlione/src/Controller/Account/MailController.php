<?php

namespace App\Controller\Account;

use App\Repository\SupportMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MailController extends AbstractController
{
    #[Route('/account/mail', name: 'account_mail', methods: ['GET'])]
    public function index(SupportMessageRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $myThreads = $repo->findForUser($user);

        $deptInbox = [];
        $internalThreads = [];
        if ($user->isStaff() && $user->getSupportDepartment()) {
            $deptInbox = $repo->findDepartmentInbox($user->getSupportDepartment());
            $internalThreads = $repo->findInternalThreadsForDepartment($user->getSupportDepartment());
        }

        return $this->render('account/mail/index.html.twig', [
            'myThreads' => $myThreads,
            'deptInbox' => $deptInbox,
            'internalThreads' => $internalThreads,
        ]);
    }
}

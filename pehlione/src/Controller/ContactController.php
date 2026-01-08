<?php

namespace App\Controller;

use App\Entity\Support\SupportMessage;
use App\Entity\User;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(
        Request $request,
        MailerInterface $mailer,
        NotifierInterface $notifier,
        EntityManagerInterface $em,
        string $contact_to_email,
        string $contact_from_email,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Güvenlik açısından ROLE_USER zaten şart ama ekstra garanti:
        if (!$user || !method_exists($user, 'getEmail')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $userEmail = (string) $user->getEmail();
            $userName = $user->getName() ?? $userEmail;
            $subject = isset($data['subject']) ? (string) $data['subject'] : 'No subject';
            $message = isset($data['message']) ? (string) $data['message'] : '';
            $department = $data['department'] ?? null;

            // 1) DB'ye Inbox kaydı
            $supportMessage = (new SupportMessage())
                ->setFromUser($user)
                ->setFromEmail($userEmail)
                ->setFromName($userName)
                ->setSubject($subject)
                ->setBody($message)
                ->setDepartment($department);

            $em->persist($supportMessage);
            $em->flush();

            // Departmandan hedef e-posta adresini al
            $toEmail = $department ? $department->getRecipientEmail() : $contact_to_email;
            $departmentName = $department ? $department->getName() : 'Genel';

            // 2) Çalışana detaylı email (Mailer)
            $email = (new TemplatedEmail())
                ->from(new Address($contact_from_email, 'PehliONE'))
                ->to($toEmail)
                ->replyTo(new Address($userEmail, $userName))
                ->subject('[Contact] [' . $departmentName . '] ' . $subject)
                ->htmlTemplate('emails/contact.html.twig')
                ->textTemplate('emails/contact.txt.twig')
                ->context([
                    'subject' => $subject,
                    'message' => $message,
                    'userEmail' => $userEmail,
                    'userName' => $userName,
                    'userId' => $user->getId(),
                    'departmentName' => $departmentName,
                    'supportMessageId' => $supportMessage->getId(),
                    'sentAt' => new \DateTimeImmutable(),
                ]);

            $mailer->send($email);

            // 3) Notifier ile "yeni mesaj var" bildirimi (email channel ile)
            $notification = (new Notification('Yeni Contact Mesajı: ' . $departmentName, ['email']))
                ->content(sprintf('#%d [%s] %s (%s): %s', $supportMessage->getId(), $departmentName, $userName, $userEmail, $subject))
                ->importance(Notification::IMPORTANCE_HIGH);

            $notifier->send($notification, new Recipient($toEmail));

            $this->addFlash('success', 'Mesajınız başarıyla iletildi. Teşekkürler.');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

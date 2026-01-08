<?php

namespace App\Service\Support;

use App\Entity\SupportMessage;
use App\Entity\SupportReply;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SupportMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router,
        private string $fromAddress = 'no-reply@pehlione.local',
        private string $fromName = 'PehliONE'
    ) {}

    public function notifyNewThread(SupportMessage $thread): void
    {
        $deptTo = $thread->getDepartment()?->getRecipientEmail();
        if (!$deptTo) {
            return;
        }

        $subjectPrefix = $this->subjectPrefix($thread);
        $staffUrl = $this->router->generate('support_thread_show', ['id' => $thread->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $emailToDept = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($deptTo)
            ->subject($subjectPrefix.' Yeni Mesaj')
            ->htmlTemplate('emails/support/new_to_department.html.twig')
            ->textTemplate('emails/support/new_to_department.txt.twig')
            ->context([
                'thread' => $thread,
                'staffUrl' => $staffUrl,
            ]);

        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            $replyTo = $thread->getCreatedBy()?->getEmail() ?? $thread->getCustomerEmail();
            if ($replyTo) {
                $emailToDept->replyTo($replyTo);
            }
        }

        $this->mailer->send($emailToDept);

        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            $customerEmail = $thread->getCreatedBy()?->getEmail() ?? $thread->getCustomerEmail();
            if ($customerEmail) {
                $customerUrl = $this->router->generate('account_mail', [], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailToCustomer = (new TemplatedEmail())
                    ->from(new Address($this->fromAddress, $this->fromName))
                    ->to($customerEmail)
                    ->subject($subjectPrefix.' Talebiniz alındı')
                    ->htmlTemplate('emails/support/receipt_to_customer.html.twig')
                    ->textTemplate('emails/support/receipt_to_customer.txt.twig')
                    ->context([
                        'thread' => $thread,
                        'customerUrl' => $customerUrl,
                    ]);

                $this->mailer->send($emailToCustomer);
            }
        }
    }

    public function notifyNewReply(SupportMessage $thread, SupportReply $reply): void
    {
        $subjectPrefix = $this->subjectPrefix($thread);

        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            $isStaffReply = $reply->getAuthor()?->isStaff() ?? false;

            if ($isStaffReply) {
                $customerEmail = $thread->getCreatedBy()?->getEmail() ?? $thread->getCustomerEmail();
                if (!$customerEmail) {
                    return;
                }

                $customerUrl = $this->router->generate('support_thread_show', ['id' => $thread->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                $email = (new TemplatedEmail())
                    ->from(new Address($this->fromAddress, $this->fromName))
                    ->to($customerEmail)
                    ->subject($subjectPrefix.' Yanıt var')
                    ->htmlTemplate('emails/support/reply_to_customer.html.twig')
                    ->textTemplate('emails/support/reply_to_customer.txt.twig')
                    ->context([
                        'thread' => $thread,
                        'reply' => $reply,
                        'customerUrl' => $customerUrl,
                    ]);

                $this->mailer->send($email);
            } else {
                $deptTo = $thread->getDepartment()?->getRecipientEmail();
                if (!$deptTo) {
                    return;
                }

                $staffUrl = $this->router->generate('support_thread_show', ['id' => $thread->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                $email = (new TemplatedEmail())
                    ->from(new Address($this->fromAddress, $this->fromName))
                    ->to($deptTo)
                    ->subject($subjectPrefix.' Kullanıcı yanıtladı')
                    ->htmlTemplate('emails/support/reply_to_department.html.twig')
                    ->textTemplate('emails/support/reply_to_department.txt.twig')
                    ->context([
                        'thread' => $thread,
                        'reply' => $reply,
                        'staffUrl' => $staffUrl,
                    ]);

                $this->mailer->send($email);
            }

            return;
        }

        $deptTo = $thread->getDepartment()?->getRecipientEmail();
        if (!$deptTo) {
            return;
        }

        $staffUrl = $this->router->generate('support_thread_show', ['id' => $thread->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($deptTo)
            ->subject($subjectPrefix.' İç mesaj güncellendi')
            ->htmlTemplate('emails/support/internal_reply_to_department.html.twig')
            ->textTemplate('emails/support/internal_reply_to_department.txt.twig')
            ->context([
                'thread' => $thread,
                'reply' => $reply,
                'staffUrl' => $staffUrl,
            ]);

        $this->mailer->send($email);
    }

    private function subjectPrefix(SupportMessage $thread): string
    {
        return sprintf('[PehliONE #%06d]', (int) $thread->getId());
    }
}

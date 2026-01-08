Aşağıdaki tasarım “kullanıcı formdan gönderdi → seçilen birime mail gitsin → aynı anda sistemde ticket oluşsun → ilgili birimdeki çalışan login olunca ticket’ı ve thread’i görsün → tüm kullanıcı menüsünde Mail görünsün → çalışanlar departmanlar arası sistem içinden mesajlaşsın” ihtiyacını **Symfony Mailer + Mime + Twig TemplatedEmail** yaklaşımıyla uçtan uca karşılar.

Symfony dokümanlarındaki temel noktaları baz alıyorum:

* Mailer DSN ile yapılandırılır (`MAILER_DSN=smtp://...`).
* E-posta içeriğini Twig ile üretmek için `TemplatedEmail` + `htmlTemplate()` + `context()` kullanılır.
* Messenger ile e-posta gönderimi async yapılabilir ve `SendEmailMessage` route edilir.
* Email nesneleri Mime tarafında `Email` high-level API ile kurulur; Mailer bunları gönderir.

---

## 1) Hedef mimari (önerilen)

### Kavramlar

* **SupportDepartment** : (sizde var) “Fatura ve Ödemeler / Müşteri Hizmetleri / Satış / Teknik Destek”
* **SupportMessage (Thread/Ticket başlığı)** : ilk mesaj + meta (type, status, subject, department, fromDepartment)
* **SupportReply (Thread mesajları)** : kullanıcı/çalışan yanıtları (thread görünümü)

### Thread tipleri

* `type = customer`
  Kullanıcı → departman. Çalışan cevaplayınca kullanıcıya bildirim gider.
* `type = internal`
  Çalışan (A departmanı) → (B departmanı). İki departman da thread’i görür, sistem içinde yazışır.

### E-posta stratejisi

Sistem “source of truth” olur (DB). E-posta **bildirim** olur.

* Kullanıcı ticket açınca:
  1. DB’ye yaz
  2. Departman e-postasına “Yeni Talep” bildirimi
  3. Kullanıcıya “Talebiniz alındı” bildirimi
* Reply olunca:
  * customer reply → departmana bildirim
  * staff reply → kullanıcıya bildirim
  * internal reply → karşı departmana bildirim

---

## 2) Kurulum / config (Mailer + Messenger + Mailhog)

### Paketler

```bash
composer require symfony/mailer symfony/messenger symfony/twig-bundle
```

### .env (dev için mailhog örneği)

```dotenv
MAILER_DSN=smtp://mailhog:1025
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

### config/packages/mailer.yaml

```yaml
framework:
  mailer:
    dsn: '%env(MAILER_DSN)%'
```

### config/packages/messenger.yaml (Mailer async)

Dokümandaki routing yaklaşımıyla:

```yaml
framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'Symfony\Component\Mailer\Messenger\SendEmailMessage': async
```

Worker:

```bash
php bin/console messenger:consume async -vv
```

---

## 3) Doctrine model (mevcut SupportMessage’ı genişletip “thread” yapısı)

> Sizde `SupportDepartment` var ve `SupportMessage`’a `department` ekleme aşamasındaydınız. Aşağıdakiyle SupportMessage’ı “thread/ticket” haline getiriyoruz ve `SupportReply` ekliyoruz.

### src/Entity/SupportMessage.php

```php
<?php

namespace App\Entity;

use App\Repository\SupportMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
#[ORM\Table(name: 'support_message')]
#[ORM\Index(columns: ['type', 'status'], name: 'idx_support_type_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_support_created_at')]
class SupportMessage
{
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_INTERNAL = 'internal';

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_CUSTOMER;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 180)]
    private string $subject = '';

    // İlk mesaj (thread’in başlangıcı)
    #[ORM\Column(type: 'text')]
    private string $message = '';

    // customer ticket’ta hedef departman; internal thread’te "toDepartment" gibi düşünün
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SupportDepartment $department = null;

    // internal thread’te gönderen departman; customer ticket’ta null
    #[ORM\ManyToOne]
    private ?SupportDepartment $fromDepartment = null;

    // Giriş yapmış kullanıcı (customer veya staff)
    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    // Guest destek formu için snapshot (opsiyonel)
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'supportMessage', targetEntity: SupportReply::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $replies;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->replies = new ArrayCollection();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $subject): self { $this->subject = $subject; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getDepartment(): ?SupportDepartment { return $this->department; }
    public function setDepartment(?SupportDepartment $department): self { $this->department = $department; return $this; }

    public function getFromDepartment(): ?SupportDepartment { return $this->fromDepartment; }
    public function setFromDepartment(?SupportDepartment $fromDepartment): self { $this->fromDepartment = $fromDepartment; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $customerName): self { $this->customerName = $customerName; return $this; }

    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $customerEmail): self { $this->customerEmail = $customerEmail; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int, SupportReply> */
    public function getReplies(): Collection { return $this->replies; }

    public function addReply(SupportReply $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setSupportMessage($this);
            $this->touch();
        }
        return $this;
    }
}
```

### src/Entity/SupportReply.php

```php
<?php

namespace App\Entity;

use App\Repository\SupportReplyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportReplyRepository::class)]
#[ORM\Table(name: 'support_reply')]
#[ORM\Index(columns: ['created_at'], name: 'idx_support_reply_created_at')]
class SupportReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SupportMessage $supportMessage = null;

    #[ORM\ManyToOne]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private string $body = '';

    // staff “internal note” veya internal thread mesajlarında kullanılabilir
    #[ORM\Column(options: ['default' => false])]
    private bool $internal = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSupportMessage(): ?SupportMessage { return $this->supportMessage; }
    public function setSupportMessage(?SupportMessage $supportMessage): self { $this->supportMessage = $supportMessage; return $this; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): self { $this->author = $author; return $this; }

    public function getBody(): string { return $this->body; }
    public function setBody(string $body): self { $this->body = $body; return $this; }

    public function isInternal(): bool { return $this->internal; }
    public function setInternal(bool $internal): self { $this->internal = $internal; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
```

### User’a departman bağlama (çalışanlar için)

```php
// src/Entity/User.php içinde

#[ORM\ManyToOne]
private ?SupportDepartment $supportDepartment = null;

public function getSupportDepartment(): ?SupportDepartment { return $this->supportDepartment; }
public function setSupportDepartment(?SupportDepartment $supportDepartment): self { $this->supportDepartment = $supportDepartment; return $this; }

public function isStaff(): bool
{
    return \in_array('ROLE_ADMIN', $this->getRoles(), true)
        || \in_array('ROLE_STAFF', $this->getRoles(), true);
}
```

### Migration (özet)

* support_message’a: `type`, `status`, `subject`, `message`, `from_department_id`, `created_by_id`, `customer_name`, `customer_email`, `created_at`, `updated_at`
* support_reply tablosu
* user tablosuna `support_department_id` (nullable)

---

## 4) Formlar

### src/Form/Support/SupportRequestType.php (Contact sayfası)

```php
<?php

namespace App\Form\Support;

use App\Entity\SupportDepartment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SupportRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('department', EntityType::class, [
                'class' => SupportDepartment::class,
                'choice_label' => 'name',
                'placeholder' => 'Bir birim seçin...',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('subject', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10)],
                'attr' => ['rows' => 6],
            ])
            // guest kullanımına da açık tutmak için:
            ->add('customerName', TextType::class, [
                'required' => false,
                'constraints' => [new Assert\Length(max: 180)],
            ])
            ->add('customerEmail', EmailType::class, [
                'required' => false,
                'constraints' => [new Assert\Length(max: 180)],
            ])
        ;
    }
}
```

### src/Form/Support/SupportReplyType.php

```php
<?php

namespace App\Form\Support;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SupportReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
                'attr' => ['rows' => 4],
            ])
            // sadece staff için template’te gösterin
            ->add('internal', CheckboxType::class, [
                'required' => false,
            ])
        ;
    }
}
```

### src/Form/Support/InternalThreadType.php (departmanlar arası)

```php
<?php

namespace App\Form\Support;

use App\Entity\SupportDepartment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class InternalThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('department', EntityType::class, [
                'class' => SupportDepartment::class,
                'choice_label' => 'name',
                'placeholder' => 'Hedef birim seçin...',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('subject', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
                'attr' => ['rows' => 5],
            ])
        ;
    }
}
```

---

## 5) Mailer servisleri (Twig template’li)

`TemplatedEmail` kullanımı dokümana uygun şekilde:

### src/Service/Support/SupportMailer.php

```php
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
        // 1) Departmana bildirim
        $deptTo = $thread->getDepartment()->getEmail();
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

        // customer ticket’ta reply-to’yu kullanıcıya bağlamak isterseniz:
        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            $replyTo = $thread->getCreatedBy()?->getEmail() ?? $thread->getCustomerEmail();
            if ($replyTo) {
                $emailToDept->replyTo($replyTo);
            }
        }

        $this->mailer->send($emailToDept);

        // 2) Kullanıcıya alındı maili (customer thread ise)
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
            // staff -> customer veya customer -> staff ayrımı
            $isStaffReply = $reply->getAuthor()?->isStaff() ?? false;

            if ($isStaffReply) {
                $customerEmail = $thread->getCreatedBy()?->getEmail() ?? $thread->getCustomerEmail();
                if (!$customerEmail) { return; }

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
                $deptTo = $thread->getDepartment()->getEmail();
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

        // internal thread: karşı departmana bildir
        $deptTo = $thread->getDepartment()->getEmail();
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
```

### services.yaml parametreleri

```yaml
parameters:
  app.mail_from_address: 'no-reply@pehlione.local'
  app.mail_from_name: 'PehliONE'

services:
  App\Service\Support\SupportMailer:
    arguments:
      $fromAddress: '%app.mail_from_address%'
      $fromName: '%app.mail_from_name%'
```

---

## 6) Controller akışları

### A) Kullanıcı Contact form → SupportMessage oluştur

```php
<?php

namespace App\Controller;

use App\Entity\SupportMessage;
use App\Form\Support\SupportRequestType;
use App\Service\Support\SupportMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET','POST'])]
    public function contact(Request $request, EntityManagerInterface $em, SupportMailer $supportMailer): Response
    {
        $form = $this->createForm(SupportRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $thread = (new SupportMessage())
                ->setType(SupportMessage::TYPE_CUSTOMER)
                ->setDepartment($data['department'])
                ->setSubject($data['subject'])
                ->setMessage($data['message'])
                ->setCreatedBy($this->getUser())
                ->setCustomerName($data['customerName'] ?? null)
                ->setCustomerEmail($data['customerEmail'] ?? null);

            $em->persist($thread);
            $em->flush();

            $supportMailer->notifyNewThread($thread);

            $this->addFlash('success', 'Mesajınız alındı. En kısa sürede dönüş yapacağız.');
            return $this->redirectToRoute('account_mail');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
```

### B) “Mail” ekranı (tüm kullanıcılara görünecek)

* Normal kullanıcı: kendi ticket’ları
* Staff: kendi ticket’ları + departman inbox + internal thread’ler

```php
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
```

### C) Thread görüntüleme + reply (hem user hem staff)

Bu aksiyonda **Voter** ile yetkilendirin (aşağıda var).

```php
<?php

namespace App\Controller\Support;

use App\Entity\SupportReply;
use App\Repository\SupportMessageRepository;
use App\Form\Support\SupportReplyType;
use App\Service\Support\SupportMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SupportThreadController extends AbstractController
{
    #[Route('/support/thread/{id}', name: 'support_thread_show', methods: ['GET','POST'])]
    public function show(
        int $id,
        Request $request,
        SupportMessageRepository $repo,
        EntityManagerInterface $em,
        SupportMailer $supportMailer
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

            // normal kullanıcı internal note atamasın
            if (!($this->getUser()?->isStaff() ?? false)) {
                $reply->setInternal(false);
            }

            $thread->addReply($reply);

            $em->persist($reply);
            $em->flush();

            // internal notelar için mail atmak istemiyorsanız burada filtreleyin
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
```

### D) Staff internal thread oluşturma

```php
<?php

namespace App\Controller\Support;

use App\Entity\SupportMessage;
use App\Form\Support\InternalThreadType;
use App\Service\Support\SupportMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InternalThreadController extends AbstractController
{
    #[Route('/support/internal/new', name: 'support_internal_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, SupportMailer $mailer): Response
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
```

---

## 7) Repository sorguları

### src/Repository/SupportMessageRepository.php

```php
<?php

namespace App\Repository;

use App\Entity\SupportDepartment;
use App\Entity\SupportMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SupportMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMessage::class);
    }

    /** @return SupportMessage[] */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.createdBy = :u')
            ->setParameter('u', $user)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SupportMessage[] */
    public function findDepartmentInbox(SupportDepartment $dept): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :t')
            ->andWhere('m.department = :d')
            ->setParameter('t', SupportMessage::TYPE_CUSTOMER)
            ->setParameter('d', $dept)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SupportMessage[] */
    public function findInternalThreadsForDepartment(SupportDepartment $dept): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :t')
            ->andWhere('m.department = :d OR m.fromDepartment = :d')
            ->setParameter('t', SupportMessage::TYPE_INTERNAL)
            ->setParameter('d', $dept)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

---

## 8) Güvenlik (Voter ile doğru yetki)

### src/Security/Voter/SupportThreadVoter.php

```php
<?php

namespace App\Security\Voter;

use App\Entity\SupportMessage;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SupportThreadVoter extends Voter
{
    public const VIEW = 'SUPPORT_THREAD_VIEW';
    public const REPLY = 'SUPPORT_THREAD_REPLY';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::REPLY], true)
            && $subject instanceof SupportMessage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var SupportMessage $thread */
        $thread = $subject;

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Customer erişimi
        if ($thread->getType() === SupportMessage::TYPE_CUSTOMER) {
            if ($thread->getCreatedBy() && $thread->getCreatedBy()->getId() === $user->getId()) {
                return $attribute === self::VIEW || ($attribute === self::REPLY && $thread->getStatus() !== SupportMessage::STATUS_CLOSED);
            }
        }

        // Staff erişimi
        if ($user->isStaff() && $user->getSupportDepartment()) {
            $dept = $user->getSupportDepartment();

            $canSee =
                ($thread->getDepartment()?->getId() === $dept->getId())
                || ($thread->getFromDepartment()?->getId() === $dept->getId());

            if (!$canSee) {
                return false;
            }

            return $attribute === self::VIEW || ($attribute === self::REPLY);
        }

        return false;
    }
}
```

services.yaml autowire ile voter otomatik bulunur.

---

## 9) “Mail” menüsü (her kullanıcıda gözüksün)

Sizdeki dropdown partial’ında (ör: `templates/partials/_account_menu.html.twig`) “Mail” linkini **role bağımsız** ekleyin:

```twig
<a href="{{ path('account_mail') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50">
  <span class="i-lucide-mail w-4 h-4"></span>
  <span>Mail</span>
</a>
```

(“Admin Panel” sadece ROLE_ADMIN/ROLE_STAFF ise görünmeye devam edebilir; ama “Mail” herkes için görünür.)

---

## 10) Twig ekranları (in-app mailbox + thread)

### templates/account/mail/index.html.twig (Tailwind)

* “Benim Ticketlarım”
* Staff ise “Birim Inbox” ve “İç Mesajlar”
* Staff için “Yeni İç Mesaj” butonu (`support_internal_new`)

### templates/support/thread/show.html.twig

* İlk mesaj (SupportMessage.message)
* Reply listesi (SupportReply)
* Reply formu
* Staff ise “Internal note” checkbox’ı göster

---

## 11) E-posta template’leri (Twig)

`TemplatedEmail` template erişimi ve `context()` değişkenleri dokümana uygun.

### templates/emails/support/_layout.html.twig

```twig
<!doctype html>
<html>
  <body style="font-family: Arial, sans-serif; background:#f6f7fb; padding:24px;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
      <div style="font-size:18px;font-weight:700;margin-bottom:12px;">PehliONE</div>
      {% block content %}{% endblock %}
      <hr style="border:none;border-top:1px solid #eee;margin:18px 0;">
      <div style="font-size:12px;color:#666;">
        Bu e-posta otomatik bildirimdir.
      </div>
    </div>
  </body>
</html>
```

### 1) Yeni ticket → departman

**templates/emails/support/new_to_department.html.twig**

```twig
{% extends 'emails/support/_layout.html.twig' %}
{% block content %}
  <p><strong>Yeni mesaj</strong> ({{ thread.type }})</p>
  <p><strong>Konu:</strong> {{ thread.subject }}</p>
  <p><strong>Birim:</strong> {{ thread.department.name }}</p>

  {% if thread.type == 'internal' %}
    <p><strong>Gönderen Birim:</strong> {{ thread.fromDepartment ? thread.fromDepartment.name : '-' }}</p>
  {% endif %}

  <p><strong>Mesaj:</strong></p>
  <div style="white-space:pre-wrap;border:1px solid #eee;border-radius:10px;padding:12px;">
    {{ thread.message }}
  </div>

  <p style="margin-top:14px;">
    <a href="{{ staffUrl }}">Sistemde görüntüle</a>
  </p>
{% endblock %}
```

**templates/emails/support/new_to_department.txt.twig**

```twig
Yeni mesaj ({{ thread.type }})
Konu: {{ thread.subject }}
Birim: {{ thread.department.name }}
{% if thread.type == 'internal' %}Gonderen Birim: {{ thread.fromDepartment ? thread.fromDepartment.name : '-' }}{% endif %}

Mesaj:
{{ thread.message }}

Sistemde görüntüle: {{ staffUrl }}
```

### 2) Ticket alındı → kullanıcı

**templates/emails/support/receipt_to_customer.html.twig**

```twig
{% extends 'emails/support/_layout.html.twig' %}
{% block content %}
  <p>Talebiniz alındı. En kısa sürede dönüş yapacağız.</p>
  <p><strong>Konu:</strong> {{ thread.subject }}</p>
  <p><strong>Birim:</strong> {{ thread.department.name }}</p>
  <p>
    Ticket’larınızı buradan takip edebilirsiniz:
    <a href="{{ customerUrl }}">{{ customerUrl }}</a>
  </p>
{% endblock %}
```

**templates/emails/support/receipt_to_customer.txt.twig**

```twig
Talebiniz alindi.
Konu: {{ thread.subject }}
Birim: {{ thread.department.name }}

Takip: {{ customerUrl }}
```

### 3) Staff reply → kullanıcı

**templates/emails/support/reply_to_customer.html.twig**

```twig
{% extends 'emails/support/_layout.html.twig' %}
{% block content %}
  <p>Talebinize yeni bir yanıt var.</p>
  <p><strong>Konu:</strong> {{ thread.subject }}</p>
  <div style="white-space:pre-wrap;border:1px solid #eee;border-radius:10px;padding:12px;">
    {{ reply.body }}
  </div>
  <p><a href="{{ customerUrl }}">Sistemde görüntüle</a></p>
{% endblock %}
```

### 4) Customer reply → departman

**templates/emails/support/reply_to_department.html.twig**

```twig
{% extends 'emails/support/_layout.html.twig' %}
{% block content %}
  <p>Kullanıcı talebe yanıt yazdı.</p>
  <p><strong>Konu:</strong> {{ thread.subject }}</p>
  <div style="white-space:pre-wrap;border:1px solid #eee;border-radius:10px;padding:12px;">
    {{ reply.body }}
  </div>
  <p><a href="{{ staffUrl }}">Sistemde görüntüle</a></p>
{% endblock %}
```

### 5) Internal reply → departman (opsiyonel)

**templates/emails/support/internal_reply_to_department.html.twig** benzeri şekilde.

---

## 12) Çalışanların “birimler arası mailleşmesi” nasıl çalışır?

* Staff kullanıcıya `ROLE_STAFF` verilir ve `user.supportDepartment` atanır.
* Staff “Yeni İç Mesaj” ekranından hedef birimi seçer.
* Oluşan thread `type=internal`, `fromDepartment=staffDept`, `department=targetDept` olur.
* Her iki departman da /support/thread/{id} ekranında thread’i görür ve reply atar.
* İsterseniz internal mesajların tamamı **Mail menüsünde “İç Mesajlar”** tabında listelenir.

---

## 13) Sizin ekranda görünen ihtiyaçlara göre “tamamlanma checklist”

1. DB migration + entity’ler (SupportReply + User.supportDepartment + SupportMessage genişletme)
2. Contact form submit → DB + departman mail + receipt mail
3. Mail ekranı `/account/mail` (her kullanıcı)
4. Thread ekranı + reply (Voter ile)
5. Staff internal compose
6. Navbar dropdown’a “Mail” linki (role bağımsız)
7. Messenger async worker (prod için supervisor/systemd)

---

İsterseniz bir sonraki adımda ben bunu daha da “ürretime yakın” hale getirip:

* Ticket’a **state machine** (open/pending/closed), SLA alanları,
* Staff için “Assign to me”, “Tag”, “Priority”,
* Mail gönderiminde **failures transport** ve retry,
* Contact için  **RateLimiter** ,
* Admin tarafına (ürün CRUD gibi) benzer şekilde **Support admin ekranı** (filtreleme, arama)
  şeklinde genişletecek şekilde net dosya/dizin planını da çıkarabilirim.

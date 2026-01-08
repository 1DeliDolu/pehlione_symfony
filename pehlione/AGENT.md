Aşağıdaki genişletme seti “production-grade Support” hedefiniz için doğru sırada, net dosya/dizin planı ve çalışır iskelet kodlarla birlikte gelir:

* Ticket state machine (open/pending/closed) + transition guard’ları
* SLA alanları + otomatik due-at hesaplama
* Staff “Assign to me”, “Tag”, “Priority”
* Mailer için failures transport + retry
* Contact form RateLimiter
* Admin Support ekranı (arama/filtre/paging + thread detayı + durum/atama/tag yönetimi)

---

## 0) Paketler

```bash
composer require symfony/workflow symfony/rate-limiter symfony/messenger symfony/mailer symfony/twig-bundle
```

(Async mail için Messenger zaten öneriliyor; workflow + ratelimiter ekledik.)

---

## 1) Net klasör/dizin planı

Symfony standartlarını bozmayacak şekilde “Support” bounded context’i mantıksal olarak gruplayalım:

```
src/
  Support/
    Controller/
      Admin/SupportAdminController.php
      Admin/SupportTagAdminController.php (opsiyonel)
      SupportThreadController.php
      InternalThreadController.php
    Entity/
      SupportMessage.php
      SupportReply.php
      SupportTag.php
    Enum/
      TicketPriority.php
    Form/
      SupportRequestType.php
      SupportReplyType.php
      InternalThreadType.php
      Admin/SupportAdminUpdateType.php
    Repository/
      SupportMessageRepository.php
      SupportTagRepository.php
    Security/
      Voter/SupportThreadVoter.php
    Service/
      SupportMailer.php
      TicketSlaService.php
      TicketStateManager.php
templates/
  account/mail/index.html.twig
  support/thread/show.html.twig
  support/admin/index.html.twig
  support/admin/show.html.twig
  support/admin/_filters.html.twig
  emails/support/*.twig
config/packages/
  workflows.yaml
  messenger.yaml
  rate_limiter.yaml
```

Not: İsterseniz `src/Support/*` yerine doğrudan `src/Entity`, `src/Controller` altında da tutabiliriz; fakat bu yapı büyürken bakım maliyetini ciddi düşürür.

---

## 2) Domain genişletmeleri

### 2.1 TicketPriority enum

**src/Support/Enum/TicketPriority.php**

```php
<?php

namespace App\Support\Enum;

enum TicketPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function multiplier(): float
    {
        return match ($this) {
            self::LOW => 1.5,
            self::NORMAL => 1.0,
            self::HIGH => 0.6,
            self::URGENT => 0.35,
        };
    }
}
```

### 2.2 SupportTag entity (ManyToMany)

**src/Support/Entity/SupportTag.php**

```php
<?php

namespace App\Support\Entity;

use App\Support\Repository\SupportTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportTagRepository::class)]
#[ORM\Table(name: 'support_tag')]
#[ORM\UniqueConstraint(name: 'uniq_support_tag_slug', columns: ['slug'])]
class SupportTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name = '';

    #[ORM\Column(length: 80)]
    private string $slug = '';

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
}
```

### 2.3 SupportMessage’e production alanlar (priority/assign/SLA)

**src/Support/Entity/SupportMessage.php** (mevcut modelinizin üstüne ekleyin; yalnızca ek alanları veriyorum)

```php
use App\Support\Enum\TicketPriority;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Column(length: 20)]
private string $status = self::STATUS_OPEN; // workflow marking store için

#[ORM\Column(length: 20)]
private string $priority = TicketPriority::NORMAL->value;

#[ORM\ManyToOne]
private ?\App\Entity\User $assignedTo = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $assignedAt = null;

// SLA timestamps
#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $firstResponseDueAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $firstResponseAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $resolutionDueAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $closedAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $lastCustomerMessageAt = null;

#[ORM\Column(nullable: true)]
private ?\DateTimeImmutable $lastStaffMessageAt = null;

// Tags
#[ORM\ManyToMany(targetEntity: \App\Support\Entity\SupportTag::class)]
#[ORM\JoinTable(name: 'support_message_tag')]
private Collection $tags;

public function __construct()
{
    // ...
    $this->tags = new ArrayCollection();
}

// Priority helpers
public function getPriority(): string { return $this->priority; }
public function setPriority(string $priority): self { $this->priority = $priority; return $this; }
public function getPriorityEnum(): TicketPriority { return TicketPriority::from($this->priority); }

// Assign
public function getAssignedTo(): ?\App\Entity\User { return $this->assignedTo; }
public function assignTo(?\App\Entity\User $user): self
{
    $this->assignedTo = $user;
    $this->assignedAt = $user ? new \DateTimeImmutable() : null;
    return $this;
}

// Tags helpers
/** @return Collection<int, \App\Support\Entity\SupportTag> */
public function getTags(): Collection { return $this->tags; }

public function addTag(\App\Support\Entity\SupportTag $tag): self
{
    if (!$this->tags->contains($tag)) {
        $this->tags->add($tag);
    }
    return $this;
}

public function removeTag(\App\Support\Entity\SupportTag $tag): self
{
    $this->tags->removeElement($tag);
    return $this;
}

// SLA getters/setters (kısaltıyorum)
public function setFirstResponseDueAt(?\DateTimeImmutable $dt): self { $this->firstResponseDueAt = $dt; return $this; }
public function getFirstResponseDueAt(): ?\DateTimeImmutable { return $this->firstResponseDueAt; }
public function setFirstResponseAt(?\DateTimeImmutable $dt): self { $this->firstResponseAt = $dt; return $this; }
public function getFirstResponseAt(): ?\DateTimeImmutable { return $this->firstResponseAt; }
public function setResolutionDueAt(?\DateTimeImmutable $dt): self { $this->resolutionDueAt = $dt; return $this; }
public function getResolutionDueAt(): ?\DateTimeImmutable { return $this->resolutionDueAt; }
public function setClosedAt(?\DateTimeImmutable $dt): self { $this->closedAt = $dt; return $this; }
public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
public function setLastCustomerMessageAt(?\DateTimeImmutable $dt): self { $this->lastCustomerMessageAt = $dt; return $this; }
public function setLastStaffMessageAt(?\DateTimeImmutable $dt): self { $this->lastStaffMessageAt = $dt; return $this; }
```

### 2.4 SupportDepartment’a SLA policy alanı

Departman bazlı SLA en pratik ve anlaşılır çözümdür.

**SupportDepartment.php** içine ekleyin:

```php
#[ORM\Column(options: ['default' => 1440])]
private int $slaFirstResponseMinutes = 1440; // 24h

#[ORM\Column(options: ['default' => 4320])]
private int $slaResolutionMinutes = 4320; // 72h

public function getSlaFirstResponseMinutes(): int { return $this->slaFirstResponseMinutes; }
public function setSlaFirstResponseMinutes(int $m): self { $this->slaFirstResponseMinutes = $m; return $this; }

public function getSlaResolutionMinutes(): int { return $this->slaResolutionMinutes; }
public function setSlaResolutionMinutes(int $m): self { $this->slaResolutionMinutes = $m; return $this; }
```

---

## 3) Ticket state machine (Workflow)

### 3.1 config/packages/workflows.yaml

```yaml
framework:
  workflows:
    support_ticket:
      type: state_machine
      supports:
        - App\Support\Entity\SupportMessage
      marking_store:
        type: method
        property: status
      places:
        - open
        - pending
        - closed
      transitions:
        mark_pending:
          from: open
          to: pending
        reopen:
          from: pending
          to: open
        close:
          from: [open, pending]
          to: closed
        reopen_closed:
          from: closed
          to: open
```

### 3.2 State manager (tek noktadan uygula)

**src/Support/Service/TicketStateManager.php**

```php
<?php

namespace App\Support\Service;

use App\Support\Entity\SupportMessage;
use Symfony\Component\Workflow\WorkflowInterface;

final class TicketStateManager
{
    public function __construct(private WorkflowInterface $supportTicketWorkflow) {}

    public function markPending(SupportMessage $t): void
    {
        if ($this->supportTicketWorkflow->can($t, 'mark_pending')) {
            $this->supportTicketWorkflow->apply($t, 'mark_pending');
        }
    }

    public function reopen(SupportMessage $t): void
    {
        if ($this->supportTicketWorkflow->can($t, 'reopen')) {
            $this->supportTicketWorkflow->apply($t, 'reopen');
        } elseif ($this->supportTicketWorkflow->can($t, 'reopen_closed')) {
            $this->supportTicketWorkflow->apply($t, 'reopen_closed');
        }
    }

    public function close(SupportMessage $t): void
    {
        if ($this->supportTicketWorkflow->can($t, 'close')) {
            $this->supportTicketWorkflow->apply($t, 'close');
            $t->setClosedAt(new \DateTimeImmutable());
        }
    }
}
```

> Not: `WorkflowInterface $supportTicketWorkflow` injection’ı için service alias gerekebilir. Alternatif: `workflow.support_ticket` servis id’sini argümanla bağlayın.

**config/services.yaml**

```yaml
services:
  App\Support\Service\TicketStateManager:
    arguments:
      $supportTicketWorkflow: '@workflow.support_ticket'
```

---

## 4) SLA hesaplama servisi

**src/Support/Service/TicketSlaService.php**

```php
<?php

namespace App\Support\Service;

use App\Support\Entity\SupportMessage;

final class TicketSlaService
{
    public function onTicketCreated(SupportMessage $t): void
    {
        $dept = $t->getDepartment();
        $now = new \DateTimeImmutable();

        $mult = $t->getPriorityEnum()->multiplier();

        $first = (int) round($dept->getSlaFirstResponseMinutes() * $mult);
        $res = (int) round($dept->getSlaResolutionMinutes() * $mult);

        $t->setFirstResponseDueAt($now->modify("+{$first} minutes"));
        $t->setResolutionDueAt($now->modify("+{$res} minutes"));

        // İlk mesajı kimin attığına göre last* alanlarını set edelim
        // (contact form -> customer)
        $t->setLastCustomerMessageAt($now);
    }

    public function onCustomerReply(SupportMessage $t): void
    {
        $now = new \DateTimeImmutable();
        $t->setLastCustomerMessageAt($now);

        // Ticket yeniden "open" olacaksa staff’in cevap SLA’sını yeniden başlatın:
        $dept = $t->getDepartment();
        $mult = $t->getPriorityEnum()->multiplier();
        $first = (int) round($dept->getSlaFirstResponseMinutes() * $mult);

        $t->setFirstResponseDueAt($now->modify("+{$first} minutes"));
    }

    public function onStaffReply(SupportMessage $t): void
    {
        $now = new \DateTimeImmutable();
        $t->setLastStaffMessageAt($now);

        // first response KPI
        if ($t->getFirstResponseAt() === null) {
            $t->setFirstResponseAt($now);
        }
    }
}
```

---

## 5) “Assign to me”, “Tag”, “Priority” admin formu

### 5.1 Admin update form

**src/Support/Form/Admin/SupportAdminUpdateType.php**

```php
<?php

namespace App\Support\Form\Admin;

use App\Entity\User;
use App\Support\Entity\SupportMessage;
use App\Support\Entity\SupportTag;
use App\Support\Enum\TicketPriority;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class SupportAdminUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => TicketPriority::LOW->value,
                    'Normal' => TicketPriority::NORMAL->value,
                    'High' => TicketPriority::HIGH->value,
                    'Urgent' => TicketPriority::URGENT->value,
                ],
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'choice_label' => fn(User $u) => trim(($u->getEmail() ?? '').' '.$u->getUserIdentifier()),
            ])
            ->add('tags', EntityType::class, [
                'class' => SupportTag::class,
                'multiple' => true,
                'required' => false,
                'choice_label' => 'name',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'support_admin_update';
    }
}
```

---

## 6) Mailer: failures transport + retry

**config/packages/messenger.yaml** (örnek – doctrine transport ile)

```yaml
framework:
  messenger:
    failure_transport: failed

    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        retry_strategy:
          max_retries: 5
          delay: 1000
          multiplier: 2
          max_delay: 60000
      failed:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          queue_name: failed

    routing:
      'Symfony\Component\Mailer\Messenger\SendEmailMessage': async
```

Operasyon komutları:

```bash
# failed queue listesini gör
php bin/console messenger:failed:show

# retry (tek tek veya hepsi)
php bin/console messenger:failed:retry --force
```

> Üretimde transport olarak Redis/AMQP tercih edilir; Doctrine prod’da çalışır ama yüksek hacimde sınırlayıcıdır.

---

## 7) Contact RateLimiter

### 7.1 config/packages/rate_limiter.yaml

```yaml
framework:
  rate_limiter:
    contact_form:
      policy: 'sliding_window'
      limit: 10
      interval: '10 minutes'
```

### 7.2 Controller’da tüketim

Contact submit içinde (form valid’den önce) ekleyin:

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

// action signature'a ek:
public function contact(..., RateLimiterFactory $contactFormLimiter): Response

// submit kontrolünde:
$limiter = $contactFormLimiter->create($request->getClientIp() ?? 'anon');
$limit = $limiter->consume(1);

if (!$limit->isAccepted()) {
    $this->addFlash('error', 'Çok fazla deneme yapıldı. Lütfen daha sonra tekrar deneyin.');
    return $this->redirectToRoute('contact');
}
```

**services.yaml** ile factory alias:

```yaml
services:
  Symfony\Component\RateLimiter\RateLimiterFactory $contactFormLimiter:
    alias: 'limiter.contact_form'
```

---

## 8) Reply akışında state + SLA otomasyonu

### 8.1 SupportThreadController’da reply sonrası

Reply persist etmeden önce:

* customer reply => reopen (pending/closed -> open) + SLA reset
* staff reply => firstResponseAt set + genelde pending’e al (kullanıcıdan aksiyon bekleniyor varsayımıyla)

Örnek:

```php
use App\Support\Service\TicketSlaService;
use App\Support\Service\TicketStateManager;

// inject: TicketSlaService $sla, TicketStateManager $state

$isStaffReply = $reply->getAuthor()?->isStaff() ?? false;

if (!$reply->isInternal()) {
    if ($isStaffReply) {
        $sla->onStaffReply($thread);
        // staff cevapladıysa çoğunlukla kullanıcıdan dönüş beklenir => pending
        $state->markPending($thread);
    } else {
        $sla->onCustomerReply($thread);
        $state->reopen($thread); // pending -> open
    }
}
```

---

## 9) Admin Support ekranı (arama/filtre/paging)

### 9.1 Admin controller

**src/Support/Controller/Admin/SupportAdminController.php**

```php
<?php

namespace App\Support\Controller\Admin;

use App\Support\Form\Admin\SupportAdminUpdateType;
use App\Support\Repository\SupportMessageRepository;
use App\Support\Service\TicketStateManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/support')]
final class SupportAdminController extends AbstractController
{
    #[Route('', name: 'admin_support_index', methods: ['GET'])]
    public function index(Request $request, SupportMessageRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $qb = $repo->createAdminSearchQuery($request->query->all());
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        $total = count($paginator);
        $pages = (int) max(1, ceil($total / $limit));

        return $this->render('support/admin/index.html.twig', [
            'items' => $paginator,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'filters' => $request->query->all(),
        ]);
    }

    #[Route('/{id}', name: 'admin_support_show', methods: ['GET','POST'])]
    public function show(
        int $id,
        Request $request,
        SupportMessageRepository $repo,
        EntityManagerInterface $em,
        TicketStateManager $state
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $thread = $repo->find($id) ?? throw $this->createNotFoundException();

        $form = $this->createForm(SupportAdminUpdateType::class, $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // assignTo değiştiyse assignedAt setlemek için:
            $thread->assignTo($thread->getAssignedTo());

            $em->flush();
            $this->addFlash('success', 'Ticket güncellendi.');
            return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
        }

        // transition butonları (close / reopen)
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('_action', '');
            if ($action === 'close') {
                $state->close($thread);
                $em->flush();
                return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
            }
            if ($action === 'reopen') {
                $state->reopen($thread);
                $em->flush();
                return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
            }
            if ($action === 'assign_to_me') {
                $thread->assignTo($this->getUser());
                $em->flush();
                return $this->redirectToRoute('admin_support_show', ['id' => $thread->getId()]);
            }
        }

        return $this->render('support/admin/show.html.twig', [
            'thread' => $thread,
            'form' => $form,
        ]);
    }
}
```

### 9.2 Repository: filtre + arama

**src/Support/Repository/SupportMessageRepository.php** içine:

```php
public function createAdminSearchQuery(array $f)
{
    $qb = $this->createQueryBuilder('m')
        ->leftJoin('m.department', 'd')->addSelect('d')
        ->leftJoin('m.fromDepartment', 'fd')->addSelect('fd')
        ->leftJoin('m.assignedTo', 'a')->addSelect('a')
        ->leftJoin('m.tags', 't')->addSelect('t')
        ->orderBy('m.updatedAt', 'DESC');

    if (!empty($f['q'])) {
        $q = '%'.mb_strtolower(trim((string) $f['q'])).'%';
        $qb->andWhere('LOWER(m.subject) LIKE :q OR LOWER(m.message) LIKE :q OR LOWER(m.customerEmail) LIKE :q')
           ->setParameter('q', $q);
    }

    if (!empty($f['status'])) {
        $qb->andWhere('m.status = :status')->setParameter('status', $f['status']);
    }

    if (!empty($f['priority'])) {
        $qb->andWhere('m.priority = :priority')->setParameter('priority', $f['priority']);
    }

    if (!empty($f['type'])) {
        $qb->andWhere('m.type = :type')->setParameter('type', $f['type']);
    }

    if (!empty($f['department'])) {
        $qb->andWhere('d.id = :dept')->setParameter('dept', (int) $f['department']);
    }

    if (!empty($f['assigned'])) {
        if ($f['assigned'] === 'unassigned') {
            $qb->andWhere('m.assignedTo IS NULL');
        } elseif ($f['assigned'] === 'assigned') {
            $qb->andWhere('m.assignedTo IS NOT NULL');
        }
    }

    if (!empty($f['tag'])) {
        $qb->andWhere('t.id = :tag')->setParameter('tag', (int) $f['tag']);
    }

    return $qb;
}
```

### 9.3 Admin Twig (Tailwind) iskelet

**templates/support/admin/_filters.html.twig**

```twig
<form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3 bg-white p-4 rounded-xl border">
  <input name="q" value="{{ filters.q|default('') }}" placeholder="Ara: konu, email, içerik"
         class="md:col-span-2 rounded-lg border px-3 py-2" />

  <select name="status" class="rounded-lg border px-3 py-2">
    <option value="">Status</option>
    {% for s in ['open','pending','closed'] %}
      <option value="{{ s }}" {% if filters.status|default('') == s %}selected{% endif %}>{{ s }}</option>
    {% endfor %}
  </select>

  <select name="priority" class="rounded-lg border px-3 py-2">
    <option value="">Priority</option>
    {% for p in ['low','normal','high','urgent'] %}
      <option value="{{ p }}" {% if filters.priority|default('') == p %}selected{% endif %}>{{ p }}</option>
    {% endfor %}
  </select>

  <select name="assigned" class="rounded-lg border px-3 py-2">
    <option value="">Assignee</option>
    <option value="assigned" {% if filters.assigned|default('') == 'assigned' %}selected{% endif %}>Assigned</option>
    <option value="unassigned" {% if filters.assigned|default('') == 'unassigned' %}selected{% endif %}>Unassigned</option>
  </select>

  <select name="type" class="rounded-lg border px-3 py-2">
    <option value="">Type</option>
    <option value="customer" {% if filters.type|default('') == 'customer' %}selected{% endif %}>customer</option>
    <option value="internal" {% if filters.type|default('') == 'internal' %}selected{% endif %}>internal</option>
  </select>

  <div class="md:col-span-6 flex gap-2">
    <button class="rounded-lg bg-indigo-600 text-white px-4 py-2">Filter</button>
    <a href="{{ path('admin_support_index') }}" class="rounded-lg border px-4 py-2">Reset</a>
  </div>
</form>
```

**templates/support/admin/index.html.twig**

```twig
{% extends 'base.html.twig' %}
{% block body %}
<div class="max-w-7xl mx-auto px-4 py-8 space-y-4">
  <h1 class="text-2xl font-semibold">Support Tickets ({{ total }})</h1>

  {% include 'support/admin/_filters.html.twig' with {filters: filters} %}

  <div class="bg-white border rounded-xl overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-3 text-left">#</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-left">Priority</th>
          <th class="p-3 text-left">Dept</th>
          <th class="p-3 text-left">Subject</th>
          <th class="p-3 text-left">Assignee</th>
          <th class="p-3 text-left">Updated</th>
        </tr>
      </thead>
      <tbody>
      {% for t in items %}
        <tr class="border-t hover:bg-gray-50">
          <td class="p-3">
            <a class="text-indigo-600" href="{{ path('admin_support_show', {id: t.id}) }}">#{{ "%06d"|format(t.id) }}</a>
          </td>
          <td class="p-3">{{ t.status }}</td>
          <td class="p-3">{{ t.priority }}</td>
          <td class="p-3">{{ t.department.name }}</td>
          <td class="p-3">{{ t.subject }}</td>
          <td class="p-3">{{ t.assignedTo ? t.assignedTo.userIdentifier : '—' }}</td>
          <td class="p-3">{{ t.updatedAt|date('Y-m-d H:i') }}</td>
        </tr>
      {% else %}
        <tr><td class="p-6 text-center text-gray-500" colspan="7">Kayıt yok.</td></tr>
      {% endfor %}
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between">
    <div>Page {{ page }} / {{ pages }}</div>
    <div class="flex gap-2">
      {% if page > 1 %}
        <a class="border rounded-lg px-3 py-1" href="{{ path('admin_support_index', filters|merge({page: page-1})) }}">Prev</a>
      {% endif %}
      {% if page < pages %}
        <a class="border rounded-lg px-3 py-1" href="{{ path('admin_support_index', filters|merge({page: page+1})) }}">Next</a>
      {% endif %}
    </div>
  </div>
</div>
{% endblock %}
```

**templates/support/admin/show.html.twig** (özet)

* Ticket bilgisi + SLA due-at
* “Assign to me”, “Close/Reopen”
* Admin update form (priority/assignee/tags)
* Thread mesajları

---

## 10) DB indeks önerileri (kritik performans)

`support_message` için:

* `(status, updated_at)`
* `(priority, updated_at)`
* `(department_id, status, updated_at)`
* `(assigned_to_id, updated_at)`
* `(type, updated_at)`

`support_message_tag` join tablosu için:

* `(support_message_id, support_tag_id)` unique
* `(support_tag_id)`

---

## 11) Migration notları (uygulama adımı)

1. Entity değişiklikleri sonrası:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

2. Department SLA default’larını fixture’larda set edebilirsiniz (Müşteri Hizmetleri 24h/72h, Teknik Destek 8h/48h gibi).

---

## 12) “Done” kabul kriterleri (production yakın)

* Ticket create/reply akışında state otomatik ilerliyor (open/pending/closed)
* SLA alanları create + staff first response + reopen senaryosunda setleniyor
* Admin ekranında filtre/arama/paging çalışıyor
* Assign to me + priority + tags değişiklikleri logik olarak doğru (DB’ye yansıyor)
* Mail send async, hata olursa failed queue’ya düşüyor ve retry edilebiliyor
* Contact rate limit devrede (abuse engelleniyor)

---

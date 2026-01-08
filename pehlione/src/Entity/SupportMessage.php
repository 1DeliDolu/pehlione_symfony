<?php

namespace App\Entity;

use App\Enum\TicketPriority;
use App\Repository\SupportMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
#[ORM\Table(name: 'support_message')]
#[ORM\Index(columns: ['type', 'status'], name: 'idx_support_type_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_support_created_at')]
#[ORM\Index(columns: ['status', 'updated_at'], name: 'idx_support_status_updated')]
#[ORM\Index(columns: ['priority', 'updated_at'], name: 'idx_support_priority_updated')]
#[ORM\Index(columns: ['department_id', 'status', 'updated_at'], name: 'idx_support_dept_status_updated')]
#[ORM\Index(columns: ['assigned_to_id', 'updated_at'], name: 'idx_support_assigned_updated')]
#[ORM\Index(columns: ['type', 'updated_at'], name: 'idx_support_type_updated')]
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

    #[ORM\Column(length: 20)]
    private string $priority = TicketPriority::NORMAL->value;

    #[ORM\Column(length: 180)]
    private string $subject = '';

    // İlk mesaj (thread’in başlangıcı)
    #[ORM\Column(type: 'text', name: 'body')]
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

    #[ORM\ManyToOne]
    private ?User $assignedTo = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $assignedAt = null;

    // Guest destek formu için snapshot (opsiyonel)
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

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

    #[ORM\OneToMany(mappedBy: 'supportMessage', targetEntity: SupportReply::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $replies;

    #[ORM\ManyToMany(targetEntity: SupportTag::class)]
    #[ORM\JoinTable(name: 'support_message_tag')]
    private Collection $tags;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->replies = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; $this->touch(); return $this; }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): self { $this->priority = $priority; $this->touch(); return $this; }
    public function getPriorityEnum(): TicketPriority { return TicketPriority::from($this->priority); }

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

    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $user): self { return $this->assignTo($user); }
    public function getAssignedAt(): ?\DateTimeImmutable { return $this->assignedAt; }

    public function assignTo(?User $user): self
    {
        $this->assignedTo = $user;
        $this->assignedAt = $user ? new \DateTimeImmutable() : null;
        $this->touch();
        return $this;
    }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $customerName): self { $this->customerName = $customerName; return $this; }

    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $customerEmail): self { $this->customerEmail = $customerEmail; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function setFirstResponseDueAt(?\DateTimeImmutable $dt): self { $this->firstResponseDueAt = $dt; return $this; }
    public function getFirstResponseDueAt(): ?\DateTimeImmutable { return $this->firstResponseDueAt; }
    public function setFirstResponseAt(?\DateTimeImmutable $dt): self { $this->firstResponseAt = $dt; return $this; }
    public function getFirstResponseAt(): ?\DateTimeImmutable { return $this->firstResponseAt; }
    public function setResolutionDueAt(?\DateTimeImmutable $dt): self { $this->resolutionDueAt = $dt; return $this; }
    public function getResolutionDueAt(): ?\DateTimeImmutable { return $this->resolutionDueAt; }
    public function setClosedAt(?\DateTimeImmutable $dt): self { $this->closedAt = $dt; return $this; }
    public function getClosedAt(): ?\DateTimeImmutable { return $this->closedAt; }
    public function setLastCustomerMessageAt(?\DateTimeImmutable $dt): self { $this->lastCustomerMessageAt = $dt; return $this; }
    public function getLastCustomerMessageAt(): ?\DateTimeImmutable { return $this->lastCustomerMessageAt; }
    public function setLastStaffMessageAt(?\DateTimeImmutable $dt): self { $this->lastStaffMessageAt = $dt; return $this; }
    public function getLastStaffMessageAt(): ?\DateTimeImmutable { return $this->lastStaffMessageAt; }

    /** @return Collection<int, SupportReply> */
    public function getReplies(): Collection { return $this->replies; }

    /** @return Collection<int, SupportTag> */
    public function getTags(): Collection { return $this->tags; }

    public function addTag(SupportTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $this->touch();
        }

        return $this;
    }

    public function removeTag(SupportTag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $this->touch();
        }

        return $this;
    }

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

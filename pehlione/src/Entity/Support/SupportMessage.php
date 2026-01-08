<?php

namespace App\Entity\Support;

use App\Entity\User;
use App\Entity\SupportDepartment;
use App\Repository\Support\SupportMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
#[ORM\Table(name: 'support_message')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_support_status_created')]
class SupportMessage
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $fromUser = null;

    #[ORM\Column(length: 180)]
    private string $fromEmail;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $fromName = null;

    #[ORM\Column(length: 140)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $handledBy = null;

    #[ORM\ManyToOne(targetEntity: SupportDepartment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?SupportDepartment $department = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromUser(): ?User
    {
        return $this->fromUser;
    }

    public function setFromUser(?User $u): self
    {
        $this->fromUser = $u;
        return $this;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(string $e): self
    {
        $this->fromEmail = $e;
        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $n): self
    {
        $this->fromName = $n;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $s): self
    {
        $this->subject = $s;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $b): self
    {
        $this->body = $b;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function getHandledBy(): ?User
    {
        return $this->handledBy;
    }

    public function markRead(?User $admin = null): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->status = self::STATUS_READ;
            $this->readAt = new \DateTimeImmutable();
            $this->handledBy = $admin;
        }
    }

    public function archive(?User $admin = null): void
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->handledBy = $admin;
        if (!$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
    }

    public function getDepartment(): ?SupportDepartment
    {
        return $this->department;
    }

    public function setDepartment(?SupportDepartment $department): self
    {
        $this->department = $department;
        return $this;
    }
}

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

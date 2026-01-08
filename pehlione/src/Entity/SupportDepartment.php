<?php

namespace App\Entity;

use App\Repository\SupportDepartmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportDepartmentRepository::class)]
#[ORM\Table(name: 'support_department')]
class SupportDepartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'dept_code', length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 420)]
    private ?string $name = null;

    #[ORM\Column(length: 150)]
    private ?string $recipientEmail = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $requiredRole = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => 1440])]
    private int $slaFirstResponseMinutes = 1440;

    #[ORM\Column(options: ['default' => 4320])]
    private int $slaResolutionMinutes = 4320;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getRequiredRole(): ?string
    {
        return $this->requiredRole;
    }

    public function setRequiredRole(string $requiredRole): static
    {
        $this->requiredRole = $requiredRole;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSlaFirstResponseMinutes(): int
    {
        return $this->slaFirstResponseMinutes;
    }

    public function setSlaFirstResponseMinutes(int $minutes): static
    {
        $this->slaFirstResponseMinutes = $minutes;

        return $this;
    }

    public function getSlaResolutionMinutes(): int
    {
        return $this->slaResolutionMinutes;
    }

    public function setSlaResolutionMinutes(int $minutes): static
    {
        $this->slaResolutionMinutes = $minutes;

        return $this;
    }
}

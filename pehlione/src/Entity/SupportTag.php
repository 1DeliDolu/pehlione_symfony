<?php

namespace App\Entity;

use App\Repository\SupportTagRepository;
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

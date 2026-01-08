<?php

namespace App\Entity;

use App\Repository\ShopOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopOrderRepository::class)]
#[ORM\Table(name: 'shop_order')]
#[ORM\UniqueConstraint(name: 'uniq_shop_order_number', columns: ['order_number'])]
#[ORM\HasLifecycleCallbacks]
class ShopOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column]
    private ?int $subtotalAmount = null;

    #[ORM\Column]
    private ?int $totalAmount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    // Shipping address snapshot
    #[ORM\Column(length: 100)]
    private ?string $shippingFirstName = null;

    #[ORM\Column(length: 100)]
    private ?string $shippingLastName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $shippingPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingLine2 = null;

    #[ORM\Column(length: 120)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 20)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $shippingRegion = null;

    #[ORM\Column(length: 2)]
    private ?string $shippingCountryCode = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getSubtotalAmount(): ?int
    {
        return $this->subtotalAmount;
    }

    public function setSubtotalAmount(int $subtotalAmount): static
    {
        $this->subtotalAmount = $subtotalAmount;

        return $this;
    }

    public function getTotalAmount(): ?int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderRef($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }

        return $this;
    }

    public function getShippingFirstName(): ?string
    {
        return $this->shippingFirstName;
    }

    public function setShippingFirstName(string $shippingFirstName): static
    {
        $this->shippingFirstName = $shippingFirstName;

        return $this;
    }

    public function getShippingLastName(): ?string
    {
        return $this->shippingLastName;
    }

    public function setShippingLastName(string $shippingLastName): static
    {
        $this->shippingLastName = $shippingLastName;

        return $this;
    }

    public function getShippingPhone(): ?string
    {
        return $this->shippingPhone;
    }

    public function setShippingPhone(?string $shippingPhone): static
    {
        $this->shippingPhone = $shippingPhone;

        return $this;
    }

    public function getShippingLine1(): ?string
    {
        return $this->shippingLine1;
    }

    public function setShippingLine1(string $shippingLine1): static
    {
        $this->shippingLine1 = $shippingLine1;

        return $this;
    }

    public function getShippingLine2(): ?string
    {
        return $this->shippingLine2;
    }

    public function setShippingLine2(?string $shippingLine2): static
    {
        $this->shippingLine2 = $shippingLine2;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(string $shippingPostalCode): static
    {
        $this->shippingPostalCode = $shippingPostalCode;

        return $this;
    }

    public function getShippingRegion(): ?string
    {
        return $this->shippingRegion;
    }

    public function setShippingRegion(?string $shippingRegion): static
    {
        $this->shippingRegion = $shippingRegion;

        return $this;
    }

    public function getShippingCountryCode(): ?string
    {
        return $this->shippingCountryCode;
    }

    public function setShippingCountryCode(string $shippingCountryCode): static
    {
        $this->shippingCountryCode = $shippingCountryCode;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}

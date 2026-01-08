<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $productName = null;

    #[ORM\Column(length: 220)]
    private ?string $productSlug = null;

    #[ORM\Column]
    private ?int $unitPriceAmount = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $lineTotalAmount = null;

    #[ORM\ManyToOne(targetEntity: ShopOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShopOrder $orderRef = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductSlug(): ?string
    {
        return $this->productSlug;
    }

    public function setProductSlug(string $productSlug): static
    {
        $this->productSlug = $productSlug;

        return $this;
    }

    public function getUnitPriceAmount(): ?int
    {
        return $this->unitPriceAmount;
    }

    public function setUnitPriceAmount(int $unitPriceAmount): static
    {
        $this->unitPriceAmount = $unitPriceAmount;

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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getLineTotalAmount(): ?int
    {
        return $this->lineTotalAmount;
    }

    public function setLineTotalAmount(int $lineTotalAmount): static
    {
        $this->lineTotalAmount = $lineTotalAmount;

        return $this;
    }

    public function getOrderRef(): ?ShopOrder
    {
        return $this->orderRef;
    }

    public function setOrderRef(?ShopOrder $orderRef): static
    {
        $this->orderRef = $orderRef;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}

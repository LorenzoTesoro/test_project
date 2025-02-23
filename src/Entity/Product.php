<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $stockLevel = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\ManyToMany(targetEntity: 'App\Entity\Order', mappedBy: 'products')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function decreaseStock(int $quantity): self
    {
        if ($quantity > $this->stockLevel) {
            throw new \InvalidArgumentException('Insufficient stock available');
        }
        $this->stockLevel -= $quantity;
        return $this;
    }

    public function increaseStock(int $quantity): self
    {
        $this->stockLevel += $quantity;
        return $this;
    }

    public function getStockLevel(): int
    {
        return $this->stockLevel;
    }

    public function setStockLevel(int $stockLevel): self
    {
        $this->stockLevel = $stockLevel;
        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->addProduct($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            $order->removeProduct($this);
        }

        return $this;
    }
}

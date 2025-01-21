<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ShippingRepository;

#[ORM\Entity(repositoryClass: ShippingRepository::class)]
class Shipping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingMethod = null;

    #[ORM\Column(length: 255)]
    private ?string $withdrawal = null;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: "shipping")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    public function __construct()
    {
    }

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(string $shippingMethod): self
    {
        $this->shippingMethod = $shippingMethod;
        return $this;
    }

    public function getWithdrawal(): ?string
    {
        return $this->withdrawal;
    }

    public function setWithdrawal(?string $withdrawal): self
    {
        $this->withdrawal = $withdrawal;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }
}

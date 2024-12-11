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
    private ?string $method = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private ?float $cost = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $estimatedDelivery = null;

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

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function getEstimatedDelivery(): ?\DateTime
    {
        return $this->estimatedDelivery;
    }

    public function setEstimatedDelivery(?\DateTime $estimatedDelivery): self
    {
        $this->estimatedDelivery = $estimatedDelivery;
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

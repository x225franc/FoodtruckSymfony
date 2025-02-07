<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column(type: "decimal", precision: 5, scale: 2)]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Category $category = null;

    #[ORM\ManyToMany(targetEntity: Menu::class, mappedBy: "products")]
    private Collection $menus;

    #[ORM\OneToMany(mappedBy: "product", targetEntity: Review::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: "product", targetEntity: OrderProduct::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $orderProducts;

    public function __construct()
    {
        $this->menus = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->orderProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getMenus(): Collection
    {
        return $this->menus;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }
}

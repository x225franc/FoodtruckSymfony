<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
// use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
// #[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
// #[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    // #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    private ?string $email = null;

    // #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères')]
    // #[Assert\Regex(
    //     pattern: '/[A-Z]/',
    //     match: true,
    //     message: 'Le mot de passe doit contenir au moins une lettre majuscule.'
    // )]
    // #[Assert\Regex(
    //     pattern: '/\d/',
    //     match: true,
    //     message: 'Le mot de passe doit contenir au moins un chiffre.'
    // )]
    // #[Assert\Regex(
    //     pattern: '/[\W_]/',
    //     match: true,
    //     message: 'Le mot de passe doit contenir au moins un caractère spécial.'
    // )]
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, unique: true)]
    // #[Assert\NotBlank(message: 'Le nom d\'utilisateur ne peut pas être vide.')]
    // #[Assert\Length(min: 3, minMessage: 'Le nom d\'utilisateur doit comporter au moins {{ limit }} caractères.')]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdat = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedat = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Review::class)]
    private Collection $reviews;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->createdat = new \DateTime();
        $this->updatedat = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCreatedat(): ?\DateTimeInterface
    {
        return $this->createdat;
    }

    public function setCreatedat(\DateTimeInterface $createdat): static
    {
        $this->createdat = $createdat;
        return $this;
    }

    public function getUpdatedat(): ?\DateTimeInterface
    {
        return $this->updatedat;
    }

    public function setUpdatedat(\DateTimeInterface $updatedat): static
    {
        $this->updatedat = $updatedat;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->resetTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER'; // role par défaut
        }
        return $roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }
}

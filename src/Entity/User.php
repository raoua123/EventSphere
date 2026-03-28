<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isEmailVerified = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerificationExpiry = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WebauthnCredential::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $webauthnCredentials;

    public function __construct(?string $email = null)
    {
        $this->id = Uuid::v4();
        if ($email) {
            $this->email = $email;
        }
        $this->webauthnCredentials = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): static { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function getWebauthnCredentials(): Collection { return $this->webauthnCredentials; }

    public function isEmailVerified(): bool { return $this->isEmailVerified; }
    public function setEmailVerified(bool $verified): static { $this->isEmailVerified = $verified; return $this; }

    public function getEmailVerificationToken(): ?string { return $this->emailVerificationToken; }
    public function setEmailVerificationToken(?string $token): static { $this->emailVerificationToken = $token; return $this; }

    public function getEmailVerificationExpiry(): ?\DateTimeInterface { return $this->emailVerificationExpiry; }
    public function setEmailVerificationExpiry(?\DateTimeInterface $expiry): static { $this->emailVerificationExpiry = $expiry; return $this; }

   public function generateEmailVerificationToken(): string
{
    $token = bin2hex(random_bytes(32));
    $this->emailVerificationToken = $token;
    $this->emailVerificationExpiry = new \DateTime('+24 hours');
    
    return $token;
}
}

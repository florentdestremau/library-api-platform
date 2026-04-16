<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_LIBRARIAN = 'ROLE_LIBRARIAN';
    public const ROLE_MEMBER = 'ROLE_MEMBER';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['user:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Member $member = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read'])]
    private string $email = '';

    #[ORM\Column]
    private string $passwordHash = '';

    #[ORM\Column(length: 30, options: ['default' => self::ROLE_MEMBER])]
    #[Assert\Choice(choices: [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_LIBRARIAN,
        self::ROLE_MEMBER,
    ])]
    #[Groups(['user:read'])]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(?Member $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = [$this->role];

        // Hiérarchie des rôles
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            $roles[] = self::ROLE_ADMIN;
        }
        if (in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN])) {
            $roles[] = self::ROLE_LIBRARIAN;
        }
        if (in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_LIBRARIAN])) {
            $roles[] = self::ROLE_MEMBER;
        }

        return array_unique($roles);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer car on ne stocke pas de mot de passe en clair
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

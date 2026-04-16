<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\MemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(fields: ['memberNumber'])]
#[ORM\Index(fields: ['email'])]
#[UniqueEntity(fields: ['email'])]
#[UniqueEntity(fields: ['memberNumber'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_LIBRARIAN')"),
        new Get(security: "is_granted('ROLE_LIBRARIAN')"),
        new Post(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_LIBRARIAN') or (is_granted('ROLE_MEMBER') and object == user.getMember())"),
        new Delete(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['member:read']],
    denormalizationContext: ['groups' => ['member:write']],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'firstName' => 'partial',
    'lastName' => 'partial',
    'email' => 'partial',
    'memberNumber' => 'exact',
    'status' => 'exact',
])]
class Member
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['member:read', 'loan:read', 'reservation:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['member:read'])]
    private string $memberNumber = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['member:read', 'member:write', 'loan:read', 'reservation:read'])]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['member:read', 'member:write', 'loan:read', 'reservation:read'])]
    private string $lastName = '';

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['member:read', 'member:write'])]
    private string $email = '';

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['member:read', 'member:write'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['member:read', 'member:write'])]
    private ?string $address = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['member:read', 'member:write'])]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_ACTIVE])]
    #[Assert\Choice(choices: [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_EXPIRED,
        self::STATUS_ARCHIVED,
    ])]
    #[Groups(['member:read', 'member:write'])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(options: ['default' => 5])]
    #[Assert\Positive]
    #[Groups(['member:read', 'member:write'])]
    private int $maxLoans = 5;

    #[ORM\Column(options: ['default' => 3])]
    #[Assert\Positive]
    #[Groups(['member:read', 'member:write'])]
    private int $maxReservations = 3;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    #[Groups(['member:read', 'member:write'])]
    private \DateTimeInterface $membershipExpiry;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['member:read', 'member:write'])]
    private ?string $photoPath = null;

    #[ORM\Column]
    #[Groups(['member:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'member', targetEntity: Loan::class)]
    private Collection $loans;

    #[ORM\OneToMany(mappedBy: 'member', targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->membershipExpiry = new \DateTimeImmutable('+1 year');
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

    public function getMemberNumber(): string
    {
        return $this->memberNumber;
    }

    public function setMemberNumber(string $memberNumber): static
    {
        $this->memberNumber = $memberNumber;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMaxLoans(): int
    {
        return $this->maxLoans;
    }

    public function setMaxLoans(int $maxLoans): static
    {
        $this->maxLoans = $maxLoans;

        return $this;
    }

    public function getMaxReservations(): int
    {
        return $this->maxReservations;
    }

    public function setMaxReservations(int $maxReservations): static
    {
        $this->maxReservations = $maxReservations;

        return $this;
    }

    public function getMembershipExpiry(): \DateTimeInterface
    {
        return $this->membershipExpiry;
    }

    public function setMembershipExpiry(\DateTimeInterface $membershipExpiry): static
    {
        $this->membershipExpiry = $membershipExpiry;

        return $this;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): static
    {
        $this->photoPath = $photoPath;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function canBorrow(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->membershipExpiry >= new \DateTimeImmutable();
    }

    public function getActiveLoansCount(): int
    {
        return $this->loans->filter(
            fn (Loan $loan) => $loan->getReturnedAt() === null
        )->count();
    }

    public function getActiveReservationsCount(): int
    {
        return $this->reservations->filter(
            fn (Reservation $r) => in_array($r->getStatus(), [Reservation::STATUS_PENDING, Reservation::STATUS_NOTIFIED])
        )->count();
    }
}

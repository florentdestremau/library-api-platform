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
use App\Repository\BookCopyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookCopyRepository::class)]
#[ORM\Index(fields: ['status'])]
#[UniqueEntity(fields: ['barcode'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_LIBRARIAN')"),
        new Delete(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['book_copy:read']],
    denormalizationContext: ['groups' => ['book_copy:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['book' => 'exact', 'status' => 'exact'])]
class BookCopy
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BORROWED = 'borrowed';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_REPAIR = 'repair';
    public const STATUS_LOST = 'lost';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const CONDITION_GOOD = 'good';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_POOR = 'poor';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['book_copy:read', 'book:read', 'loan:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'copies')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['book_copy:read', 'book_copy:write', 'loan:read'])]
    private ?Book $book = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['book_copy:read', 'book_copy:write', 'loan:read'])]
    private string $barcode = '';

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['book_copy:read', 'book_copy:write'])]
    private ?string $location = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_AVAILABLE])]
    #[Assert\Choice(choices: [
        self::STATUS_AVAILABLE,
        self::STATUS_BORROWED,
        self::STATUS_RESERVED,
        self::STATUS_REPAIR,
        self::STATUS_LOST,
        self::STATUS_WITHDRAWN,
    ])]
    #[Groups(['book_copy:read', 'book_copy:write', 'book:read', 'loan:read'])]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(length: 10, options: ['default' => self::CONDITION_GOOD])]
    #[Assert\Choice(choices: [self::CONDITION_GOOD, self::CONDITION_DAMAGED, self::CONDITION_POOR])]
    #[Groups(['book_copy:read', 'book_copy:write'])]
    private string $condition = self::CONDITION_GOOD;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['book_copy:read', 'book_copy:write'])]
    private ?\DateTimeInterface $acquiredAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['book_copy:read', 'book_copy:write'])]
    private ?string $notes = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): static
    {
        $this->barcode = $barcode;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

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

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function getAcquiredAt(): ?\DateTimeInterface
    {
        return $this->acquiredAt;
    }

    public function setAcquiredAt(?\DateTimeInterface $acquiredAt): static
    {
        $this->acquiredAt = $acquiredAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function isAvailableForLoan(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}

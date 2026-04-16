<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Dto\Input\LoanCreateInput;
use App\Dto\Input\LoanReturnInput;
use App\Repository\LoanRepository;
use App\State\Processor\LoanCreateProcessor;
use App\State\Processor\LoanRenewProcessor;
use App\State\Processor\LoanReturnProcessor;
use App\State\Provider\MyLoansProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Index(fields: ['dueDate', 'returnedAt'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_LIBRARIAN')"),
        new Get(security: "is_granted('ROLE_LIBRARIAN')"),
        new Post(
            security: "is_granted('ROLE_LIBRARIAN')",
            input: LoanCreateInput::class,
            processor: LoanCreateProcessor::class,
        ),
        new Post(
            uriTemplate: '/loans/{id}/return',
            security: "is_granted('ROLE_LIBRARIAN')",
            input: LoanReturnInput::class,
            processor: LoanReturnProcessor::class,
        ),
        new Post(
            uriTemplate: '/loans/{id}/renew',
            security: "is_granted('ROLE_LIBRARIAN') or is_granted('ROLE_MEMBER')",
            processor: LoanRenewProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/my_loans',
            security: "is_granted('ROLE_MEMBER')",
            provider: MyLoansProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['loan:read']],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: ['member' => 'exact', 'bookCopy.book' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['dueDate', 'borrowedAt'])]
class Loan
{
    public const RETURN_CONDITION_GOOD = 'good';
    public const RETURN_CONDITION_DAMAGED = 'damaged';
    public const RETURN_CONDITION_LOST = 'lost';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['loan:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: BookCopy::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?BookCopy $bookCopy = null;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?Member $member = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['loan:read'])]
    private ?User $librarian = null;

    #[ORM\Column]
    #[Groups(['loan:read'])]
    private \DateTimeImmutable $borrowedAt;

    #[ORM\Column(type: 'date')]
    #[Groups(['loan:read'])]
    private \DateTimeInterface $dueDate;

    #[ORM\Column(nullable: true)]
    #[Groups(['loan:read'])]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['loan:read'])]
    private int $renewedCount = 0;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Choice(choices: [
        self::RETURN_CONDITION_GOOD,
        self::RETURN_CONDITION_DAMAGED,
        self::RETURN_CONDITION_LOST,
    ])]
    #[Groups(['loan:read'])]
    private ?string $returnCondition = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['loan:read'])]
    private string $lateFee = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['loan:read'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->borrowedAt = new \DateTimeImmutable();
        $this->dueDate = new \DateTimeImmutable('+21 days');
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getBookCopy(): ?BookCopy
    {
        return $this->bookCopy;
    }

    public function setBookCopy(?BookCopy $bookCopy): static
    {
        $this->bookCopy = $bookCopy;

        return $this;
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

    public function getLibrarian(): ?User
    {
        return $this->librarian;
    }

    public function setLibrarian(?User $librarian): static
    {
        $this->librarian = $librarian;

        return $this;
    }

    public function getBorrowedAt(): \DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function setBorrowedAt(\DateTimeImmutable $borrowedAt): static
    {
        $this->borrowedAt = $borrowedAt;

        return $this;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }

    public function getRenewedCount(): int
    {
        return $this->renewedCount;
    }

    public function setRenewedCount(int $renewedCount): static
    {
        $this->renewedCount = $renewedCount;

        return $this;
    }

    public function getReturnCondition(): ?string
    {
        return $this->returnCondition;
    }

    public function setReturnCondition(?string $returnCondition): static
    {
        $this->returnCondition = $returnCondition;

        return $this;
    }

    public function getLateFee(): string
    {
        return $this->lateFee;
    }

    public function setLateFee(string $lateFee): static
    {
        $this->lateFee = $lateFee;

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

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdue(): bool
    {
        if ($this->returnedAt !== null) {
            return false;
        }

        return new \DateTimeImmutable() > \DateTimeImmutable::createFromInterface($this->dueDate);
    }

    public function calculateLateFee(float $feePerDay): float
    {
        if (!$this->isOverdue()) {
            return 0.0;
        }

        $now = new \DateTimeImmutable();
        $dueDate = \DateTimeImmutable::createFromInterface($this->dueDate);
        $days = (int) ceil(($now->getTimestamp() - $dueDate->getTimestamp()) / 86400);

        return max(0, $days * $feePerDay);
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Dto\Input\ReservationCreateInput;
use App\Repository\ReservationRepository;
use App\State\Processor\ReservationCreateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Index(fields: ['status'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_LIBRARIAN')"),
        new Get(security: "is_granted('ROLE_LIBRARIAN') or (is_granted('ROLE_MEMBER') and object.getMember() == user.getMember())"),
        new Post(
            security: "is_granted('ROLE_MEMBER')",
            input: ReservationCreateInput::class,
            processor: ReservationCreateProcessor::class,
        ),
        new Delete(security: "is_granted('ROLE_LIBRARIAN') or (is_granted('ROLE_MEMBER') and object.getMember() == user.getMember())"),
    ],
    normalizationContext: ['groups' => ['reservation:read']],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: ['book' => 'exact', 'member' => 'exact', 'status' => 'exact'])]
class Reservation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['reservation:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reservation:read'])]
    private ?Book $book = null;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reservation:read'])]
    private ?Member $member = null;

    #[ORM\Column]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $notifiedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['reservation:read'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_PENDING])]
    #[Groups(['reservation:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['reservation:read'])]
    private int $queuePosition = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(?Member $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): static
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

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

    public function getQueuePosition(): int
    {
        return $this->queuePosition;
    }

    public function setQueuePosition(int $queuePosition): static
    {
        $this->queuePosition = $queuePosition;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null
            && $this->status === self::STATUS_NOTIFIED
            && new \DateTimeImmutable() > $this->expiresAt;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['notification:read']],
    paginationItemsPerPage: 50,
)]
class Notification
{
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_OVERDUE = 'overdue';
    public const TYPE_RESERVATION_READY = 'reservation_ready';
    public const TYPE_RESERVATION_CANCELLED = 'reservation_cancelled';
    public const TYPE_RENEWAL = 'renewal';
    public const TYPE_MEMBERSHIP_EXPIRY = 'membership_expiry';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['notification:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['notification:read'])]
    private ?Member $member = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:read'])]
    private string $type = self::TYPE_WELCOME;

    #[ORM\Column(length: 255)]
    #[Groups(['notification:read'])]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    #[Groups(['notification:read'])]
    private string $body = '';

    #[ORM\Column(nullable: true)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(length: 10, options: ['default' => self::STATUS_PENDING])]
    #[Groups(['notification:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    #[Groups(['notification:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

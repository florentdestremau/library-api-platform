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
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\BookRepository;
use App\State\Processor\BookImportProcessor;
use App\State\Provider\BookExportProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['isbn'], ignoreNull: true, message: 'Cet ISBN est déjà utilisé')]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_LIBRARIAN')"),
        new Delete(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['book:read']],
    denormalizationContext: ['groups' => ['book:write']],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'isbn' => 'exact',
    'language' => 'exact',
    'authors.lastName' => 'partial',
    'genres.slug' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['title', 'publishedYear', 'createdAt'])]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['book:read', 'loan:read', 'reservation:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['book:read', 'book:write', 'loan:read', 'reservation:read'])]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 13, nullable: true, unique: true)]
    #[Assert\Length(max: 13)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?int $publishedYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $publisher = null;

    #[ORM\Column(length: 10, options: ['default' => 'fr'])]
    #[Groups(['book:read', 'book:write'])]
    private string $language = 'fr';

    #[ORM\Column(nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?int $pageCount = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $coverImagePath = null;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'book_author')]
    #[Groups(['book:read', 'book:write'])]
    private Collection $authors;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'books', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'book_genre')]
    #[Groups(['book:read', 'book:write'])]
    private Collection $genres;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookCopy::class)]
    #[Groups(['book:read'])]
    private Collection $copies;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->copies = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getPublishedYear(): ?int
    {
        return $this->publishedYear;
    }

    public function setPublishedYear(?int $publishedYear): static
    {
        $this->publishedYear = $publishedYear;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(?string $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): static
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    public function getCoverImagePath(): ?string
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(?string $coverImagePath): static
    {
        $this->coverImagePath = $coverImagePath;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
        }

        return $this;
    }

    public function removeAuthor(Author $author): static
    {
        $this->authors->removeElement($author);

        return $this;
    }

    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    public function getCopies(): Collection
    {
        return $this->copies;
    }

    #[Groups(['book:read'])]
    public function getAvailableCopiesCount(): int
    {
        return $this->copies->filter(
            fn (BookCopy $copy) => $copy->getStatus() === BookCopy::STATUS_AVAILABLE
        )->count();
    }

    #[Groups(['book:read'])]
    public function getTotalCopiesCount(): int
    {
        return $this->copies->filter(
            fn (BookCopy $copy) => $copy->getStatus() !== BookCopy::STATUS_WITHDRAWN
        )->count();
    }
}

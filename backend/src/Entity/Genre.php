<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\GenreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[UniqueEntity(fields: ['slug'])]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_LIBRARIAN')"),
        new Delete(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['genre:read']],
    denormalizationContext: ['groups' => ['genre:write']],
    paginationItemsPerPage: 100,
)]
class Genre
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['genre:read', 'book:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['genre:read', 'genre:write', 'book:read'])]
    private string $name = '';

    #[ORM\Column(length: 110, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 110)]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Le slug ne doit contenir que des lettres minuscules, chiffres et tirets')]
    #[Groups(['genre:read', 'genre:write', 'book:read'])]
    private string $slug = '';

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['genre:read', 'genre:write'])]
    private ?Genre $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[Groups(['genre:read'])]
    private Collection $children;

    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'genres')]
    private Collection $books;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->books = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getParent(): ?Genre
    {
        return $this->parent;
    }

    public function setParent(?Genre $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }
}

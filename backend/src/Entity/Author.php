<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_LIBRARIAN')"),
        new Delete(security: "is_granted('ROLE_LIBRARIAN')"),
    ],
    normalizationContext: ['groups' => ['author:read']],
    denormalizationContext: ['groups' => ['author:write']],
    paginationItemsPerPage: 50,
)]
class Author
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['author:read', 'book:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['author:read', 'author:write', 'book:read'])]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['author:read', 'author:write', 'book:read'])]
    private string $lastName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['author:read', 'author:write'])]
    private ?string $biography = null;

    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors')]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;

        return $this;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->addAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            $book->removeAuthor($this);
        }

        return $this;
    }
}

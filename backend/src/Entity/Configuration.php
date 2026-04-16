<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_LIBRARIAN')"),
        new Get(security: "is_granted('ROLE_LIBRARIAN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['config:read']],
    denormalizationContext: ['groups' => ['config:write']],
)]
class Configuration
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    #[Groups(['config:read'])]
    private string $key = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotNull]
    #[Groups(['config:read', 'config:write'])]
    private string $value = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['config:read'])]
    private ?string $description = null;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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
}

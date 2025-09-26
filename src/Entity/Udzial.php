<?php

namespace App\Entity;

use App\Repository\UdzialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: UdzialRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['udzial:read']],
    denormalizationContext: ['groups' => ['udzial:write']]
)]
class Udzial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['udzial:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['udzial:read', 'udzial:write', 'apartment:read'])]
    #[MaxDepth(1)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Apartment::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['udzial:read', 'udzial:write', 'user:list', 'user:details', 'user:me'])]
    #[MaxDepth(1)]
    private ?Apartment $apartment = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['udzial:read', 'udzial:write', 'apartment:read', 'user:details', 'user:list', 'user:me'])]
    private ?string $procent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    public function setApartment(?Apartment $apartment): static
    {
        $this->apartment = $apartment;
        return $this;
    }

    public function getProcent(): ?string
    {
        return $this->procent;
    }

    public function setProcent(string $procent): static
    {
        $this->procent = $procent;
        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\ApartmentRepository;
use App\Controller\CreateApartmentController;
use App\Controller\UpdateApartmentController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: ApartmentRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(
            uriTemplate: "/apartments/{id}",
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['apartment:read', 'user:me']]
        ),
        new Get(
            uriTemplate: "/admin/apartments/{id}",
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['apartment:read']]
        ),
        new Put(
            controller: UpdateApartmentController::class,
            security: "is_granted('ROLE_ADMIN')",
            deserialize: false
        ),
        new Post(
            controller: CreateApartmentController::class,
            security: "is_granted('ROLE_ADMIN')",
            deserialize: false
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['apartment:read']],
    denormalizationContext: ['groups' => ['apartment:write']]
)]
class Apartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['apartment:read', 'user:details', 'user:list', 'user:me'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['apartment:read', 'apartment:write', 'user:list', 'user:details', 'user:me'])]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['apartment:read', 'apartment:write', 'user:me'])]
    private ?string $priceForClean = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['apartment:read', 'apartment:write', 'user:me'])]
    private ?string $picture = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['apartment:read', 'apartment:write'])]
    private ?string $vat = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['apartment:read', 'apartment:write'])]
    private ?bool $canFaktura = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['apartment:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'apartment', targetEntity: Udzial::class, cascade: ['persist', 'remove'])]
    #[Groups(['apartment:read'])]
    #[MaxDepth(1)]
    private $udzialy;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->udzialy = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPriceForClean(): ?string
    {
        return $this->priceForClean;
    }

    public function setPriceForClean(string $priceForClean): static
    {
        $this->priceForClean = $priceForClean;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(string $vat): static
    {
        $this->vat = $vat;

        return $this;
    }

    public function getCanFaktura(): ?bool
    {
        return $this->canFaktura;
    }

    public function setCanFaktura(bool $canFaktura): static
    {
        $this->canFaktura = $canFaktura;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUdzialy()
    {
        return $this->udzialy;
    }

    public function addUdzial(Udzial $udzial): static
    {
        if (!$this->udzialy->contains($udzial)) {
            $this->udzialy->add($udzial);
            $udzial->setApartment($this);
        }

        return $this;
    }

    public function removeUdzial(Udzial $udzial): static
    {
        if ($this->udzialy->removeElement($udzial)) {
            if ($udzial->getApartment() === $this) {
                $udzial->setApartment(null);
            }
        }

        return $this;
    }
}

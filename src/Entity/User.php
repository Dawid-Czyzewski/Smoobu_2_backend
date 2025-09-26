<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use App\Controller\RegisterUserController;
use App\Controller\UpdateUserController;
use App\State\MeProvider;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: "/users/register",
            controller: RegisterUserController::class
        ),
        new Get(
            uriTemplate: "/me",
            security: "is_granted('ROLE_USER')",
            securityMessage: "You must be authenticated to access your profile.",
            normalizationContext: ['groups' => ['user:me']],
            provider: MeProvider::class
        ),
        new Get(
            uriTemplate: "/users/{id}",
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "You need administrator privileges to access user details.",
            normalizationContext: ['groups' => ['user:details']]
        ),
        new GetCollection(
            uriTemplate: "/users",
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "You need administrator privileges to access users list.",
            normalizationContext: ['groups' => ['user:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 10
        ),
        new Put(
            uriTemplate: "/users/{id}",
            controller: UpdateUserController::class,
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "You need administrator privileges to update users."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "You need administrator privileges to delete users."
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:me', 'user:list', 'user:details', 'apartment:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:details', 'apartment:read'])]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:me', 'user:list', 'user:details', 'apartment:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 150)]
    #[Groups(['user:me', 'user:list', 'user:details', 'apartment:read'])]
    private ?string $surname = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:details'])]
    private ?string $phone = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:me', 'user:list', 'user:details'])]
    private ?string $email = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    #[Groups(['user:me', 'user:list', 'user:details'])]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    #[Groups(['user:list', 'user:details'])]
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }


    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: InvoiceInfo::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:details'])]
    private ?InvoiceInfo $invoiceInfo = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Udzial::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:details', 'user:list'])]
    private $udzialy;

    public function __construct()
    {
        $this->udzialy = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getUdzialy()
    {
        return $this->udzialy;
    }

    public function addUdzial(Udzial $udzial): static
    {
        if (!$this->udzialy->contains($udzial)) {
            $this->udzialy->add($udzial);
            $udzial->setUser($this);
        }

        return $this;
    }

    public function removeUdzial(Udzial $udzial): static
    {
        if ($this->udzialy->removeElement($udzial)) {
            if ($udzial->getUser() === $this) {
                $udzial->setUser(null);
            }
        }

        return $this;
    }

    public function getInvoiceInfo(): ?InvoiceInfo
    {
        return $this->invoiceInfo;
    }

    public function setInvoiceInfo(?InvoiceInfo $invoiceInfo): static
    {
        if ($invoiceInfo === null && $this->invoiceInfo !== null) {
            $this->invoiceInfo->setUser(null);
        }

        if ($invoiceInfo !== null && $invoiceInfo->getUser() !== $this) {
            $invoiceInfo->setUser($this);
        }

        $this->invoiceInfo = $invoiceInfo;

        return $this;
    }
}

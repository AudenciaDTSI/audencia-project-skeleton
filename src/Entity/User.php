<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Personne connectée via le SSO Microsoft. Créée à sa première connexion,
 * mise à jour à chaque connexion suivante depuis son compte Entra ID.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_security_user')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Identifiant immuable du compte Entra ID (claim « oid »). */
    #[ORM\Column(length: 64, unique: true)]
    private string $azureId;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    private string $lastName = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(string $azureId, string $email)
    {
        $this->id = Uuid::v7();
        $this->azureId = $azureId;
        $this->email = $email;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAzureId(): string
    {
        return $this->azureId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getDisplayName(): string
    {
        $name = trim($this->firstName.' '.$this->lastName);

        return '' !== $name ? $name : $this->email;
    }

    /**
     * Initiales affichées dans l'avatar (« CM » pour Camille Martin, sinon première lettre de l'e-mail).
     */
    public function getInitials(): string
    {
        $initials = mb_substr($this->firstName, 0, 1).mb_substr($this->lastName, 0, 1);

        return mb_strtoupper('' !== $initials ? $initials : mb_substr($this->email, 0, 1));
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Recopie les informations du compte Microsoft à chaque connexion.
     */
    public function recordLogin(string $email, string $firstName, string $lastName, \DateTimeImmutable $loggedAt): void
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->lastLoginAt = $loggedAt;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('Un utilisateur doit avoir une adresse e-mail.');
        }

        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\AzureSsoConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;

/**
 * Retrouve (ou crée à la première connexion) l'utilisateur correspondant à un compte Microsoft.
 */
final readonly class AzureUserProvisioner
{
    public function __construct(
        private AzureSsoConfiguration $configuration,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function provision(AzureResourceOwner $owner): User
    {
        if (!$this->configuration->belongsToTenant(self::claim($owner, 'tid'))) {
            throw new CustomUserMessageAuthenticationException('Ce compte Microsoft n’appartient pas à l’organisation autorisée.');
        }

        $azureId = self::claim($owner, 'oid');
        $email = self::claim($owner, 'upn') ?? self::claim($owner, 'preferred_username') ?? self::claim($owner, 'email');

        if (null === $azureId || null === $email) {
            throw new CustomUserMessageAuthenticationException('Votre compte Microsoft ne fournit pas d’identifiant ou d’adresse e-mail.');
        }

        $email = mb_strtolower($email);
        $user = $this->userRepository->findOneByAzureId($azureId) ?? new User($azureId, $email);
        $user->recordLogin(
            $email,
            self::claim($owner, 'given_name') ?? '',
            self::claim($owner, 'family_name') ?? '',
            $this->clock->now(),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private static function claim(AzureResourceOwner $owner, string $name): ?string
    {
        $value = $owner->claim($name);

        return \is_string($value) && '' !== trim($value) ? trim($value) : null;
    }
}

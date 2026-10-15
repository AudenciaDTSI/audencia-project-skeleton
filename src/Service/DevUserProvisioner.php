<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Utilisateur de développement, créé à sa première connexion (dev uniquement).
 */
#[When(env: 'dev')]
final readonly class DevUserProvisioner
{
    public const string AZURE_ID = 'dev-audencia';
    public const string EMAIL = 'audencia@localhost';

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function provision(): User
    {
        $user = $this->userRepository->findOneByAzureId(self::AZURE_ID) ?? new User(self::AZURE_ID, self::EMAIL);
        $user->recordLogin(self::EMAIL, 'Audencia', 'Développement', $this->clock->now());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}

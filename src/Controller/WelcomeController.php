<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\AzureSsoConfiguration;
use App\Security\DevLoginAuthenticator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page d'accueil du skeleton : à supprimer dès la première vraie route
 * (ce fichier + templates/welcome/ + tests/Functional/WelcomeTest.php).
 */
#[When(env: 'dev')]
#[When(env: 'test')]
final class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_welcome', methods: ['GET'], env: ['dev', 'test'])]
    public function __invoke(Connection $connection, AzureSsoConfiguration $sso): Response
    {
        return $this->render('welcome/index.html.twig', [
            'symfony_version' => Kernel::VERSION,
            'symfony_eom' => Kernel::END_OF_MAINTENANCE,
            'symfony_eol' => Kernel::END_OF_LIFE,
            'php_version' => \PHP_VERSION,
            'worker_mode' => isset($_SERVER['FRANKENPHP_WORKER']),
            'database' => $this->databaseStatus($connection),
            'sso_configured' => $sso->isConfigured(),
            'dev_username' => DevLoginAuthenticator::USERNAME,
            'dev_password' => DevLoginAuthenticator::PASSWORD,
        ]);
    }

    /**
     * @return array{driver: string, ok: bool}
     */
    private function databaseStatus(Connection $connection): array
    {
        $driver = $connection->getParams()['driver'] ?? null;
        $driver = \is_string($driver) ? $driver : 'inconnu';

        try {
            $connection->executeQuery('SELECT 1');

            return ['driver' => $driver, 'ok' => true];
        } catch (\Throwable) {
            return ['driver' => $driver, 'ok' => false];
        }
    }
}

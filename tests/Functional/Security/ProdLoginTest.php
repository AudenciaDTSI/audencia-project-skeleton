<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Garde-fou : en prod, seule la connexion SSO Microsoft existe (pas de formulaire audencia / audencia).
 */
final class ProdLoginTest extends KernelTestCase
{
    #[Test]
    public function prodOnlyOffersMicrosoftLogin(): void
    {
        $kernel = self::bootKernel(['environment' => 'prod', 'debug' => false]);

        $router = $kernel->getContainer()->get('router');
        $routes = $router->getRouteCollection();
        self::assertNull($routes->get('app_login'), 'Le formulaire de connexion de dev ne doit pas exister en prod.');
        self::assertNotNull($routes->get('connect_azure_start'));

        $response = $kernel->handle(Request::create('/mon-compte'));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/connect/azure', $response->headers->get('Location'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Garde-fou : aucune page ne doit être accessible sans connexion SSO.
 * Toute nouvelle route est vérifiée automatiquement.
 */
final class AccessControlTest extends KernelTestCase
{
    /** Seules routes publiques autorisées. En ajouter une se valide avec la DSI. */
    private const array PUBLIC_ROUTES = [
        'connect_azure_start', // redirection vers Microsoft
        'connect_azure_check', // retour de Microsoft
        '_logout_main', // déconnexion, gérée par le pare-feu
        'app_login', // formulaire audencia / audencia, dev uniquement
        'app_welcome', // page d'accueil du skeleton, dev/test uniquement
    ];

    #[Test]
    public function everyRouteRequiresAnSsoLogin(): void
    {
        $router = self::getContainer()->get(RouterInterface::class);
        $accessMap = self::getContainer()->get('security.access_map');

        $checked = 0;
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            if (\in_array($name, self::PUBLIC_ROUTES, true) || str_starts_with($name, '_profiler') || str_starts_with($name, '_wdt')) {
                continue;
            }

            $path = preg_replace('/\{[^}]+\}/', 'x', $route->getPath()) ?? $route->getPath();
            $request = Request::create($path, $route->getMethods()[0] ?? 'GET');
            $request->attributes->set('_route', $name);

            [$attributes] = $accessMap->getPatterns($request);

            self::assertNotEmpty($attributes, \sprintf('La route « %s » (%s) n’est couverte par aucune règle access_control.', $name, $path));
            self::assertNotContains(AuthenticatedVoter::PUBLIC_ACCESS, $attributes, \sprintf('La route « %s » (%s) est accessible sans connexion.', $name, $path));
            ++$checked;
        }

        self::assertGreaterThan(0, $checked);
    }
}

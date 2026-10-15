<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\DevLoginAuthenticator;
use App\Service\DevUserProvisioner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Connexion de dev (audencia / audencia). Elle n'existe qu'en dev : l'environnement de test
 * utilise le faux SSO, l'authentificateur est donc construit à la main.
 */
final class DevLoginAuthenticatorTest extends KernelTestCase
{
    #[Test]
    public function validCredentialsLogInTheDevelopmentUser(): void
    {
        $passport = $this->authenticator()->authenticate($this->loginRequest('audencia', 'audencia'));

        $user = $passport->getBadge(UserBadge::class)?->getUser();
        self::assertInstanceOf(User::class, $user);
        self::assertSame(DevUserProvisioner::EMAIL, $user->getEmail());
        self::assertSame('Audencia Développement', $user->getDisplayName());
    }

    #[Test]
    public function wrongPasswordIsRefused(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Identifiants invalides.');

        $this->authenticator()->authenticate($this->loginRequest('audencia', 'mauvais'));
    }

    #[Test]
    public function successReturnsToTheRequestedPage(): void
    {
        $request = $this->loginRequest('audencia', 'audencia');
        $request->getSession()->set('_security.main.target_path', 'http://localhost/mon-compte');

        $response = $this->authenticator()->onAuthenticationSuccess($request, new NullToken(), 'main');

        self::assertSame('http://localhost/mon-compte', $response->headers->get('Location'));
    }

    private function authenticator(): DevLoginAuthenticator
    {
        $container = self::getContainer();

        return new DevLoginAuthenticator(
            new DevUserProvisioner($container->get(UserRepository::class), $container->get(EntityManagerInterface::class), new NativeClock()),
            $container->get(UrlGeneratorInterface::class),
        );
    }

    private function loginRequest(string $username, string $password): Request
    {
        $request = Request::create('/connexion', 'POST', ['_username' => $username, '_password' => $password, '_csrf_token' => 'jeton']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}

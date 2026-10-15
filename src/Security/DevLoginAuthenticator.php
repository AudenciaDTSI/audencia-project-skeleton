<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\DevUserProvisioner;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Connexion de développement (dev uniquement) : identifiants fixes audencia / audencia,
 * à la place du SSO Microsoft. Ce service n'existe qu'en dev.
 */
#[When(env: 'dev')]
final class DevLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string USERNAME = 'audencia';
    public const string PASSWORD = 'audencia';

    public function __construct(
        private readonly DevUserProvisioner $userProvisioner,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $username = $request->request->getString('_username');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        if (!hash_equals(self::USERNAME, $username) || !hash_equals(self::PASSWORD, $request->request->getString('_password'))) {
            throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
        }

        return new SelfValidatingPassport(
            new UserBadge(DevUserProvisioner::EMAIL, fn () => $this->userProvisioner->provision()),
            [new CsrfTokenBadge('authenticate', $request->request->getString('_csrf_token'))],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        $this->removeTargetPath($request->getSession(), $firewallName);

        return new RedirectResponse($targetPath ?? $this->urlGenerator->generate(AzureAuthenticator::DEFAULT_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}

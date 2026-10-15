<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\AzureUserProvisioner;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use Twig\Environment;

/**
 * Connexion par SSO Microsoft : toute personne non connectée est envoyée vers Microsoft,
 * puis revient sur /connect/azure/check où ce service la connecte.
 */
final class AzureAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const string DEFAULT_ROUTE = 'app_account';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly AzureUserProvisioner $userProvisioner,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($request->hasSession() && $request->isMethod(Request::METHOD_GET) && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
        }

        return new RedirectResponse($this->urlGenerator->generate('connect_azure_start'));
    }

    public function supports(Request $request): bool
    {
        return 'connect_azure_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('azure');
        $owner = $client->fetchUserFromToken($this->fetchAccessToken($client));

        if (!$owner instanceof AzureResourceOwner || !\is_string($owner->getId()) || '' === $owner->getId()) {
            throw new CustomUserMessageAuthenticationException('Votre compte Microsoft ne fournit pas d’identifiant.');
        }

        return new SelfValidatingPassport(
            new UserBadge($owner->getId(), fn () => $this->userProvisioner->provision($owner)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        $this->removeTargetPath($request->getSession(), $firewallName);

        return new RedirectResponse($targetPath ?? $this->urlGenerator->generate(self::DEFAULT_ROUTE));
    }

    /**
     * Affiche une page d'erreur au lieu de renvoyer vers Microsoft (évite une boucle de redirections).
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->logger->warning('Échec de connexion SSO Microsoft.', ['exception' => $exception]);

        $message = $exception instanceof CustomUserMessageAuthenticationException
            ? $exception->getMessageKey()
            : 'La connexion avec Microsoft a échoué.';

        return new Response(
            $this->twig->render('security/login_failure.html.twig', ['message' => $message]),
            Response::HTTP_FORBIDDEN,
        );
    }
}

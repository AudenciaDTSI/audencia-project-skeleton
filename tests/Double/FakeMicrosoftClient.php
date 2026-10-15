<?php

declare(strict_types=1);

namespace App\Tests\Double;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use TheNetworg\OAuth2\Client\Provider\Azure;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;

/**
 * Faux SSO Microsoft de l'environnement de test (config/services.yaml, when@test).
 *
 * /connect/azure renvoie directement vers /connect/azure/check, comme si la personne
 * s'était connectée chez Microsoft. L'état OAuth (anti-CSRF) est vérifié comme en vrai.
 */
final class FakeMicrosoftClient implements OAuth2ClientInterface
{
    private const string STATE_SESSION_KEY = 'fake_microsoft.state';

    /** @var array<string, string|null> */
    private array $claims = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Compte Microsoft renvoyé à la prochaine connexion (voir FakeAzureAccount::owner()).
     * Nécessite $client->disableReboot() pour être conservé d'une requête à l'autre.
     *
     * @param array<string, string|null> $claims
     */
    public function signInAs(array $claims): void
    {
        $this->claims = $claims;
    }

    public function setAsStateless(): void
    {
    }

    /**
     * @param list<string>         $scopes
     * @param array<string, mixed> $options
     */
    public function redirect(array $scopes, array $options): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));
        $this->requestStack->getSession()->set(self::STATE_SESSION_KEY, $state);

        return new RedirectResponse($this->urlGenerator->generate('connect_azure_check', [
            'code' => 'code-fictif',
            'state' => $state,
        ]));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getAccessToken(array $options = []): AccessToken
    {
        $request = $this->requestStack->getCurrentRequest();
        $expectedState = $this->requestStack->getSession()->remove(self::STATE_SESSION_KEY);

        if (null === $request || !\is_string($expectedState) || $expectedState !== $request->query->get('state')) {
            throw new InvalidStateException('État OAuth invalide.');
        }

        if (!$request->query->has('code')) {
            throw new MissingAuthorizationCodeException('Code d’autorisation manquant.');
        }

        return FakeAzureAccount::accessToken();
    }

    public function fetchUserFromToken(AccessToken $accessToken): AzureResourceOwner
    {
        return FakeAzureAccount::owner($this->claims);
    }

    public function fetchUser(): AzureResourceOwner
    {
        return $this->fetchUserFromToken($this->getAccessToken());
    }

    public function getOAuth2Provider(): AbstractProvider
    {
        return new Azure();
    }
}

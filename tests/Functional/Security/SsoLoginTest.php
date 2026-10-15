<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Double\FakeAzureAccount;
use App\Tests\Double\FakeMicrosoftClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Parcours SSO complet, avec le faux Microsoft de l'environnement de test (tests/Double/FakeMicrosoftClient.php).
 */
final class SsoLoginTest extends WebTestCase
{
    #[Test]
    public function anonymousVisitorIsSentToMicrosoft(): void
    {
        $client = self::createClient();
        $client->request('GET', '/mon-compte');

        self::assertResponseRedirects('/connect/azure');
    }

    #[Test]
    public function microsoftLoginCreatesTheUserAndReturnsToTheRequestedPage(): void
    {
        $client = $this->signInAs(['oid' => 'oid-sso-login', 'upn' => 'SSO.Login@audencia.com']);

        $client->request('GET', '/mon-compte');
        $client->followRedirect(); // /connect/azure
        self::assertResponseRedirects();
        self::assertStringStartsWith('/connect/azure/check?', (string) $client->getResponse()->headers->get('Location'));

        $client->followRedirect(); // retour de Microsoft
        self::assertResponseRedirects('http://localhost/mon-compte');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="display-name"]', 'Camille Martin');
        self::assertSelectorTextContains('main', 'sso.login@audencia.com');
    }

    #[Test]
    public function forgedMicrosoftResponseIsRefusedWithoutLooping(): void
    {
        $client = self::createClient();
        $client->request('GET', '/connect/azure');

        $client->request('GET', '/connect/azure/check', ['code' => 'code-fictif', 'state' => 'falsifié']);

        self::assertResponseStatusCodeSame(403);
        self::assertSelectorTextContains('h1', 'Connexion impossible');
        self::assertSelectorTextContains('main', 'La connexion avec Microsoft a échoué.');
        self::assertSelectorExists('a[href="/connect/azure"]');
    }

    #[Test]
    public function accountFromAnotherOrganisationIsRefused(): void
    {
        $client = $this->signInAs(['tid' => '11111111-0000-0000-0000-000000000000']);

        $client->request('GET', '/connect/azure');
        $client->followRedirect();

        self::assertResponseStatusCodeSame(403);
        self::assertSelectorTextContains('main', 'n’appartient pas à l’organisation autorisée');
    }

    #[Test]
    public function logoutAlsoSignsOutOfMicrosoft(): void
    {
        $client = $this->signInAs(['oid' => 'oid-logout', 'upn' => 'logout@audencia.com']);
        $this->logIn($client);

        $client->request('GET', '/mon-compte');
        $client->submitForm('Se déconnecter');

        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringStartsWith('https://login.microsoftonline.com/'.FakeAzureAccount::TENANT_ID.'/oauth2/v2.0/logout?', $location);
        self::assertStringContainsString('post_logout_redirect_uri='.urlencode('http://localhost/connect/azure'), $location);

        $client->request('GET', '/mon-compte');
        self::assertResponseRedirects('/connect/azure');
    }

    #[Test]
    public function logoutWithoutCsrfTokenIsRejected(): void
    {
        $client = $this->signInAs(['oid' => 'oid-logout-csrf', 'upn' => 'logout.csrf@audencia.com']);
        $this->logIn($client);

        $client->request('POST', '/deconnexion');

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @param array<string, string|null> $claims compte renvoyé par le faux Microsoft (voir FakeAzureAccount::owner())
     */
    private function signInAs(array $claims): KernelBrowser
    {
        $client = self::createClient();
        $client->disableReboot();

        // En test, le client « azure » est le faux Microsoft (config/services.yaml).
        $microsoft = self::getContainer()->get(ClientRegistry::class)->getClient('azure');
        self::assertInstanceOf(FakeMicrosoftClient::class, $microsoft);
        $microsoft->signInAs($claims);

        return $client;
    }

    private function logIn(KernelBrowser $client): void
    {
        $client->request('GET', '/connect/azure');
        $client->followRedirect();
        self::assertResponseRedirects();
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Security\AzureSsoConfiguration;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WelcomeTest extends WebTestCase
{
    #[Test]
    public function welcomePageDisplaysProjectStatus(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'audencia-project-skeleton');
        self::assertSelectorExists('[data-check="database"][data-ok="1"]');
        self::assertSelectorExists('[data-check="login"][data-ok="1"]');
        self::assertSelectorTextContains('[data-check="login"]', 'Vos pages sont protégées');
        self::assertSelectorExists('button.theme-toggle[aria-label="Mode sombre"]');
    }

    #[Test]
    public function welcomePageWarnsWhenMicrosoftSsoIsNotConfigured(): void
    {
        $client = self::createClient();
        self::getContainer()->set(AzureSsoConfiguration::class, new AzureSsoConfiguration('', '', ''));
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-check="login"][data-ok="warn"]');
        self::assertSelectorTextContains('[data-check="login"]', 'Connexion Microsoft pas encore configurée');
        self::assertSelectorTextNotContains('[data-check="login"]', 'Vos pages sont protégées');
    }
}

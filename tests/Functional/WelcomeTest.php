<?php

declare(strict_types=1);

namespace App\Tests\Functional;

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
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AzureSsoConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AzureSsoConfigurationTest extends TestCase
{
    private const string TENANT = '7a879e87-0000-0000-0000-000000000000';

    #[Test]
    public function isConfiguredWithAllCredentialsAndADedicatedTenant(): void
    {
        self::assertTrue(new AzureSsoConfiguration('client', 'secret', self::TENANT)->isConfigured());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function incompleteConfigurations(): iterable
    {
        yield 'client id manquant' => ['', 'secret', self::TENANT];
        yield 'secret manquant' => ['client', ' ', self::TENANT];
        yield 'tenant manquant' => ['client', 'secret', ''];
        yield 'tenant partagé common' => ['client', 'secret', 'common'];
        yield 'tenant partagé organizations' => ['client', 'secret', 'Organizations'];
    }

    #[Test]
    #[DataProvider('incompleteConfigurations')]
    public function isNotConfiguredWhenACredentialIsMissingOrTheTenantIsShared(string $clientId, string $secret, string $tenant): void
    {
        self::assertFalse(new AzureSsoConfiguration($clientId, $secret, $tenant)->isConfigured());
    }

    #[Test]
    public function acceptsOnlyAccountsFromTheConfiguredTenant(): void
    {
        $configuration = new AzureSsoConfiguration('client', 'secret', self::TENANT);

        self::assertTrue($configuration->belongsToTenant(strtoupper(self::TENANT)));
        self::assertFalse($configuration->belongsToTenant('11111111-0000-0000-0000-000000000000'));
        self::assertFalse($configuration->belongsToTenant(null));
    }

    #[Test]
    public function refusesEveryAccountWhenNotConfigured(): void
    {
        self::assertFalse(new AzureSsoConfiguration('', '', 'common')->belongsToTenant('common'));
    }
}

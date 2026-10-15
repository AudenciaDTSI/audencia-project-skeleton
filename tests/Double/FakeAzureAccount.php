<?php

declare(strict_types=1);

namespace App\Tests\Double;

use TheNetworg\OAuth2\Client\Provider\Azure;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use TheNetworg\OAuth2\Client\Token\AccessToken;

/**
 * Compte Microsoft fictif, tel que renvoyé par Entra ID après connexion.
 */
final class FakeAzureAccount
{
    /** Même valeur que AZURE_TENANT_ID dans .env.test. */
    public const string TENANT_ID = '00000000-0000-0000-0000-00000000000a';

    /**
     * @param array<string, string|null> $claims surcharge les claims par défaut ; null retire un claim
     */
    public static function owner(array $claims = []): AzureResourceOwner
    {
        $claims = array_filter([
            'oid' => 'oid-camille',
            'tid' => self::TENANT_ID,
            'upn' => 'Camille.Martin@audencia.com',
            'given_name' => 'Camille',
            'family_name' => 'Martin',
            ...$claims,
        ], static fn (?string $value): bool => null !== $value);

        return new AzureResourceOwner($claims, self::accessToken());
    }

    public static function accessToken(): AccessToken
    {
        return new AccessToken(['access_token' => 'jeton-de-test'], new Azure());
    }
}

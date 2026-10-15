<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Repository\UserRepository;
use App\Service\AzureUserProvisioner;
use App\Tests\Double\FakeAzureAccount;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class AzureUserProvisionerTest extends KernelTestCase
{
    #[Test]
    public function createsTheUserOnFirstLogin(): void
    {
        $user = $this->provisioner()->provision(FakeAzureAccount::owner(['oid' => 'oid-first-login', 'upn' => 'First.Login@audencia.com']));

        $saved = self::getContainer()->get(UserRepository::class)->findOneByAzureId('oid-first-login');
        self::assertSame($user, $saved);
        self::assertSame('first.login@audencia.com', $user->getEmail());
        self::assertSame('Camille Martin', $user->getDisplayName());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNotNull($user->getLastLoginAt());
    }

    #[Test]
    public function updatesTheExistingUserFromTheMicrosoftAccount(): void
    {
        $first = $this->provisioner()->provision(FakeAzureAccount::owner(['oid' => 'oid-update', 'upn' => 'old@audencia.com']));
        $second = $this->provisioner()->provision(FakeAzureAccount::owner([
            'oid' => 'oid-update',
            'upn' => 'new@audencia.com',
            'family_name' => 'Durand',
        ]));

        self::assertSame($first->getId()->toRfc4122(), $second->getId()->toRfc4122());
        self::assertSame('new@audencia.com', $second->getEmail());
        self::assertSame('Camille Durand', $second->getDisplayName());
    }

    #[Test]
    public function fallsBackToPreferredUsernameWhenUpnIsMissing(): void
    {
        $user = $this->provisioner()->provision(FakeAzureAccount::owner([
            'oid' => 'oid-no-upn',
            'upn' => null,
            'preferred_username' => 'guest@audencia.com',
        ]));

        self::assertSame('guest@audencia.com', $user->getEmail());
    }

    #[Test]
    public function refusesAnAccountFromAnotherTenant(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('n’appartient pas à l’organisation autorisée');

        $this->provisioner()->provision(FakeAzureAccount::owner(['tid' => '11111111-0000-0000-0000-000000000000']));
    }

    #[Test]
    public function refusesAnAccountWithoutEmail(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->provisioner()->provision(FakeAzureAccount::owner(['oid' => 'oid-no-email', 'upn' => null]));
    }

    private function provisioner(): AzureUserProvisioner
    {
        return self::getContainer()->get(AzureUserProvisioner::class);
    }
}

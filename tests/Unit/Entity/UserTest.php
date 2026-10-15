<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function displayNameAndInitialsComeFromTheMicrosoftAccount(): void
    {
        $user = new User('oid', 'camille.martin@audencia.com');
        $user->recordLogin('camille.martin@audencia.com', 'Camille', 'Émery', new \DateTimeImmutable());

        self::assertSame('Camille Émery', $user->getDisplayName());
        self::assertSame('CÉ', $user->getInitials());
    }

    #[Test]
    public function emailIsUsedWhenTheNameIsUnknown(): void
    {
        $user = new User('oid', 'guest@audencia.com');

        self::assertSame('guest@audencia.com', $user->getDisplayName());
        self::assertSame('G', $user->getInitials());
    }

    #[Test]
    public function everyUserHasTheUserRole(): void
    {
        $user = new User('oid', 'admin@audencia.com');
        $user->setRoles(['ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}

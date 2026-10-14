<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DatabaseTest extends KernelTestCase
{
    #[Test]
    public function defaultDatabaseIsSqlite(): void
    {
        $connection = self::getContainer()->get(Connection::class);

        self::assertInstanceOf(SQLitePlatform::class, $connection->getDatabasePlatform());
        self::assertSame(1, $connection->fetchOne('SELECT 1'));
    }
}

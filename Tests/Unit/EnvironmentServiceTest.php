<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Portrino\CmsObserverClient\EnvironmentService;

final class EnvironmentServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{\wpdb|null, string, string|null}>
     */
    public static function databases(): iterable
    {
        yield 'MariaDB' => [new \wpdb(true, '10.11.19-MariaDB-ubu2204-log'), '10.11.19-MariaDB-ubu2204-log', 'mariadb'];
        yield 'MySQL' => [new \wpdb(true, '8.0.46-0ubuntu0.24.04.5'), '8.0.46-0ubuntu0.24.04.5', 'mysql'];
        yield 'not MySQL, e.g. SQLite' => [new \wpdb(false, ''), 'unknown', null];
        yield 'no database connection' => [null, 'unknown', null];
    }

    #[DataProvider('databases')]
    public function testReportsTheDatabase(?\wpdb $database, string $expectedVersion, ?string $expectedPlatform): void
    {
        $service = new EnvironmentService($database);

        self::assertSame($expectedVersion, $service->getDatabaseVersion());
        self::assertSame($expectedPlatform, $service->getDatabasePlatform());
    }
}

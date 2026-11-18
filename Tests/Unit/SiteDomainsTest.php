<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use Portrino\CmsObserverClient\SiteDomains;

final class SiteDomainsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function singleSiteUrls(): iterable
    {
        yield 'same host' => ['https://Example.com', 'https://example.com/wp', ['example.com']];
        yield 'different hosts, with port' => ['https://www.example.com/blog', 'https://admin.example.com:8443', ['admin.example.com', 'www.example.com']];
        yield 'no host' => ['', '', []];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('singleSiteUrls')]
    public function testReportsTheHostsOfASingleSite(string $homeUrl, string $siteUrl, array $expected): void
    {
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('home_url')->justReturn($homeUrl);
        Functions\when('site_url')->justReturn($siteUrl);
        Functions\when('wp_parse_url')->alias('parse_url');

        self::assertSame($expected, (new SiteDomains())->get());
    }

    public function testReportsTheDomainsOfAllSitesOfANetwork(): void
    {
        Functions\when('is_multisite')->justReturn(true);
        Functions\expect('get_sites')->once()->with(['number' => 0])->andReturn([
            new \WP_Site('b.example.com'),
            new \WP_Site('A.example.com'),
            new \WP_Site('a.example.com'),
            new \WP_Site('localhost:8080'),
        ]);
        Functions\when('wp_parse_url')->alias('parse_url');

        self::assertSame(['a.example.com', 'b.example.com', 'localhost'], (new SiteDomains())->get());
    }
}

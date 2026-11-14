<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\ActivePluginFiles;
use Portrino\CmsObserverClient\PluginInventoryService;
use Portrino\CmsObserverClient\PluginSlugs;

final class PluginInventoryServiceTest extends TestCase
{
    public function testReportsAllPluginsByTheirWordpressOrgSlug(): void
    {
        $this->mockPlugins([
            'hello.php' => ['Name' => 'Hello Dolly', 'Version' => '1.7.2'],
            'akismet/akismet.php' => ['Name' => 'Akismet', 'Version' => '5.3'],
            'custom/custom.php' => ['Name' => 'Custom', 'Version' => '2.0.0'],
            'single-file.php' => ['Name' => 'Single file', 'Version' => '1.0'],
        ], ['akismet/akismet.php'], (object)[
            'response' => ['akismet/akismet.php' => (object)['slug' => 'akismet']],
            'no_update' => ['hello.php' => (object)['slug' => 'hello-dolly']],
        ]);

        self::assertSame([
            ['name' => 'hello-dolly', 'title' => 'Hello Dolly', 'version' => '1.7.2', 'active' => false],
            ['name' => 'akismet', 'title' => 'Akismet', 'version' => '5.3', 'active' => true],
            ['name' => 'custom', 'title' => 'Custom', 'version' => '2.0.0', 'active' => false],
            ['name' => 'single-file', 'title' => 'Single file', 'version' => '1.0', 'active' => false],
        ], $this->createService()->getInstalledPlugins());
    }

    public function testFallsBackForEmptyHeadersTheObserverWouldReject(): void
    {
        $this->mockPlugins(['bare/bare.php' => ['Name' => ' ', 'Version' => '']], [], false);

        self::assertSame(
            [['name' => 'bare', 'title' => 'bare', 'version' => '0.0.0', 'active' => false]],
            $this->createService()->getInstalledPlugins()
        );
    }

    public function testCountsAPluginActiveOnAnySiteOfAMultisiteNetwork(): void
    {
        $this->mockPlugins([
            'network/network.php' => ['Name' => 'Network', 'Version' => '1.0'],
            'subsite/subsite.php' => ['Name' => 'Subsite', 'Version' => '1.0'],
            'unused/unused.php' => ['Name' => 'Unused', 'Version' => '1.0'],
        ], [], false, multisite: true);
        Functions\when('get_site_option')->justReturn(['network/network.php' => 1700000000]);
        Functions\when('get_sites')->justReturn([1, 2]);
        Functions\when('get_blog_option')->alias(static fn(int $siteId): array => $siteId === 2 ? ['subsite/subsite.php'] : []);

        $active = array_column($this->createService()->getInstalledPlugins(), 'active', 'name');

        self::assertSame(['network' => true, 'subsite' => true, 'unused' => false], $active);
    }

    /**
     * @param array<string, array{Name: string, Version: string}> $plugins
     * @param list<string> $activePlugins
     */
    private function mockPlugins(array $plugins, array $activePlugins, object|false $updates, bool $multisite = false): void
    {
        Functions\when('get_plugins')->justReturn($plugins);
        Functions\when('get_option')->justReturn($activePlugins);
        Functions\when('get_site_transient')->justReturn($updates);
        Functions\when('is_multisite')->justReturn($multisite);
    }

    private function createService(): PluginInventoryService
    {
        return new PluginInventoryService(new PluginSlugs(), new ActivePluginFiles());
    }
}

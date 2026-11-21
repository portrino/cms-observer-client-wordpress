<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\ThemeInventoryService;

final class ThemeInventoryServiceTest extends TestCase
{
    public function testReportsAllThemesAndCountsTheParentOfAnActiveChildThemeAsActive(): void
    {
        Functions\when('wp_get_themes')->justReturn([
            'astra-child' => new \WP_Theme(['Name' => 'Astra Child', 'Version' => '1.0.0']),
            'astra' => new \WP_Theme(['Name' => 'Astra', 'Version' => '4.8.1']),
            'twentytwentyfour' => new \WP_Theme(['Name' => 'Twenty Twenty-Four', 'Version' => '1.2']),
        ]);
        $this->mockActiveTheme('astra-child', 'astra');
        Functions\when('is_multisite')->justReturn(false);

        self::assertSame([
            ['name' => 'astra-child', 'type' => 'theme', 'title' => 'Astra Child', 'version' => '1.0.0', 'active' => true],
            ['name' => 'astra', 'type' => 'theme', 'title' => 'Astra', 'version' => '4.8.1', 'active' => true],
            ['name' => 'twentytwentyfour', 'type' => 'theme', 'title' => 'Twenty Twenty-Four', 'version' => '1.2', 'active' => false],
        ], (new ThemeInventoryService())->getInstalledThemes());
    }

    public function testFallsBackForEmptyHeadersTheObserverWouldReject(): void
    {
        Functions\when('wp_get_themes')->justReturn(['bare' => new \WP_Theme(['Name' => ' ', 'Version' => false])]);
        $this->mockActiveTheme('other', 'other');
        Functions\when('is_multisite')->justReturn(false);

        self::assertSame(
            [['name' => 'bare', 'type' => 'theme', 'title' => 'bare', 'version' => '0.0.0', 'active' => false]],
            (new ThemeInventoryService())->getInstalledThemes()
        );
    }

    public function testCountsAThemeActiveOnAnySiteOfAMultisiteNetwork(): void
    {
        Functions\when('wp_get_themes')->justReturn([
            'main' => new \WP_Theme(['Name' => 'Main', 'Version' => '1.0']),
            'subsite' => new \WP_Theme(['Name' => 'Subsite', 'Version' => '1.0']),
            'unused' => new \WP_Theme(['Name' => 'Unused', 'Version' => '1.0']),
        ]);
        $this->mockActiveTheme('main', 'main');
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('get_sites')->justReturn([1, 2]);
        Functions\when('get_blog_option')->alias(static fn(int $siteId): string => $siteId === 2 ? 'subsite' : 'main');

        $active = array_column((new ThemeInventoryService())->getInstalledThemes(), 'active', 'name');

        self::assertSame(['main' => true, 'subsite' => true, 'unused' => false], $active);
    }

    private function mockActiveTheme(string $stylesheet, string $template): void
    {
        Functions\when('get_option')->alias(static fn(string $option): string|false => match ($option) {
            'stylesheet' => $stylesheet,
            'template' => $template,
            default => false,
        });
    }
}

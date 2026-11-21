<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Command;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\ActivePluginFiles;
use Portrino\CmsObserverClient\Command\PushCommand;
use Portrino\CmsObserverClient\Configuration;
use Portrino\CmsObserverClient\EnvironmentService;
use Portrino\CmsObserverClient\ObserverApiClient;
use Portrino\CmsObserverClient\PluginInventoryService;
use Portrino\CmsObserverClient\PluginSlugs;
use Portrino\CmsObserverClient\ReportBuilder;
use Portrino\CmsObserverClient\SiteDomains;
use Portrino\CmsObserverClient\Tests\Unit\TestCase;
use Portrino\CmsObserverClient\ThemeInventoryService;

final class PushCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \WP_CLI::$lines = [];
        Functions\when('get_bloginfo')->justReturn('7.1');
        Functions\when('wp_get_environment_type')->justReturn('production');
        Functions\when('get_file_data')->justReturn(['Version' => '1.0.0']);
        Functions\when('get_plugins')->justReturn(['akismet/akismet.php' => ['Name' => 'Akismet', 'Version' => '5.3']]);
        Functions\when('get_option')->alias(static fn(string $option): mixed => match ($option) {
            'active_plugins' => ['akismet/akismet.php'],
            'stylesheet', 'template' => 'twentytwentyfour',
            default => false,
        });
        Functions\when('wp_get_themes')->justReturn(['twentytwentyfour' => new \WP_Theme(['Name' => 'Twenty Twenty-Four', 'Version' => '1.2'])]);
        Functions\when('get_site_transient')->justReturn(false);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('site_url')->justReturn('https://example.com');
        Functions\when('update_site_option')->justReturn(true);
        Functions\when('delete_site_option')->justReturn(true);
    }

    public function testDryRunPrintsTheReportWithoutSendingIt(): void
    {
        Functions\expect('wp_remote_post')->never();

        $this->createCommand()->push([], ['dry-run' => true]);

        self::assertSame([
            'coreVersion' => '7.1',
            'phpVersion' => PHP_VERSION,
            'dbVersion' => '10.11.19-MariaDB',
            'dbPlatform' => 'mariadb',
            'clientVersion' => '1.0.0',
            'applicationContext' => 'production',
            'domains' => ['example.com'],
            'packages' => [
                ['name' => 'akismet', 'type' => 'plugin', 'title' => 'Akismet', 'version' => '5.3', 'active' => true],
                ['name' => 'twentytwentyfour', 'type' => 'theme', 'title' => 'Twenty Twenty-Four', 'version' => '1.2', 'active' => true],
            ],
        ], json_decode(\WP_CLI::$lines[0], true, flags: JSON_THROW_ON_ERROR));
    }

    public function testPushesTheReport(): void
    {
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(202);

        $this->createCommand()->push([], []);

        self::assertSame(['Success: Reported WordPress 7.1, PHP ' . PHP_VERSION . ', 1 plugins and 1 themes to the CMS Observer.'], \WP_CLI::$lines);
    }

    public function testFailsWithTheErrorMessageWhenSendingFails(): void
    {
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(401);

        $this->expectExceptionMessage('WP_CLI::error: CMS Observer rejected the report (HTTP 401).');
        $this->createCommand()->push([], []);
    }

    public function testShowsTheObserversMessageOnAConflict(): void
    {
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(409);
        Functions\when('wp_remote_retrieve_body')->justReturn('{"message":"Domains don\'t match the site URL."}');

        $this->expectExceptionMessage("WP_CLI::error: CMS Observer rejected the report (HTTP 409): Domains don't match the site URL.");
        $this->createCommand()->push([], []);
    }

    private function createCommand(): PushCommand
    {
        Functions\when('get_site_option')->alias(static fn(string $option, mixed $default = false): mixed => match ($option) {
            Configuration::OPTION_API_URL => 'https://observer.test',
            Configuration::OPTION_TOKEN => 'secret',
            default => $default,
        });

        return new PushCommand(
            new ReportBuilder(
                new EnvironmentService(new \wpdb(true, '10.11.19-MariaDB')),
                new PluginInventoryService(new PluginSlugs(), new ActivePluginFiles()),
                new ThemeInventoryService(),
                new SiteDomains(),
                '/path/to/cms-observer-client.php'
            ),
            new ObserverApiClient(new Configuration())
        );
    }
}

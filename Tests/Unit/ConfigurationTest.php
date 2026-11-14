<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Portrino\CmsObserverClient\Configuration;

final class ConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(Configuration::API_URL);
        putenv(Configuration::TOKEN);
        parent::tearDown();
    }

    public function testReadsTheSettings(): void
    {
        $this->mockSettings(['cms_observer_client_api_url' => ' https://observer.test ', 'cms_observer_client_token' => 'secret']);
        $configuration = new Configuration();

        self::assertSame('https://observer.test', $configuration->getApiUrl());
        self::assertSame('secret', $configuration->getToken());
        self::assertSame(Configuration::SOURCE_SETTINGS, $configuration->getSource(Configuration::TOKEN));
    }

    public function testEnvironmentVariablesWinOverTheSettings(): void
    {
        putenv('CMS_OBSERVER_TOKEN=env-token');
        $this->mockSettings(['cms_observer_client_token' => 'secret']);
        $configuration = new Configuration();

        self::assertSame('env-token', $configuration->getToken());
        self::assertSame(Configuration::SOURCE_ENVIRONMENT, $configuration->getSource(Configuration::TOKEN));
    }

    #[RunInSeparateProcess]
    public function testConstantsWinOverTheSettingsButNotOverTheEnvironment(): void
    {
        define('CMS_OBSERVER_API_URL', 'https://constant.test');
        define('CMS_OBSERVER_TOKEN', 'constant-token');
        putenv('CMS_OBSERVER_TOKEN=env-token');
        $this->mockSettings(['cms_observer_client_api_url' => 'https://observer.test', 'cms_observer_client_token' => 'secret']);
        $configuration = new Configuration();

        self::assertSame('https://constant.test', $configuration->getApiUrl());
        self::assertSame(Configuration::SOURCE_CONSTANT, $configuration->getSource(Configuration::API_URL));
        self::assertSame('env-token', $configuration->getToken());
    }

    public function testIsEmptyWhenNothingIsConfigured(): void
    {
        $this->mockSettings([]);

        self::assertSame('', (new Configuration())->getApiUrl());
    }

    /**
     * @param array<string, string> $options
     */
    private function mockSettings(array $options): void
    {
        Functions\when('get_site_option')->alias(static fn(string $option, mixed $default = false): mixed => $options[$option] ?? $default);
    }
}

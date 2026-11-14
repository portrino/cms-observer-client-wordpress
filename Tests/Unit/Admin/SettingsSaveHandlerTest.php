<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\Admin\SettingsSaveHandler;
use Portrino\CmsObserverClient\Configuration;
use Portrino\CmsObserverClient\Tests\Unit\TestCase;

final class SettingsSaveHandlerTest extends TestCase
{
    /** @var array<string, string> */
    private array $savedOptions = [];

    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('admin_url')->returnArg();
        Functions\when('add_query_arg')->alias(static fn(array $args, string $url): string => $url . '?' . http_build_query($args));
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('update_site_option')->alias(function (string $option, string $value): bool {
            $this->savedOptions[$option] = $value;

            return true;
        });
        // Stops the handler where it would redirect and exit
        Functions\when('wp_safe_redirect')->alias(static function (string $url): never {
            throw new \RuntimeException('redirect: ' . $url);
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function testSavesApiUrlAndToken(): void
    {
        $_POST = ['api_url' => 'https://observer.test', 'token' => 'secret'];

        self::assertStringEndsWith('result=updated', $this->save());
        self::assertSame([Configuration::OPTION_API_URL => 'https://observer.test', Configuration::OPTION_TOKEN => 'secret'], $this->savedOptions);
    }

    public function testRejectsAPlainHttpApiUrl(): void
    {
        $_POST = ['api_url' => 'http://observer.test', 'token' => ''];

        self::assertStringEndsWith('result=https-required', $this->save());
        self::assertSame([], $this->savedOptions);
    }

    public function testKeepsTheTokenWhenTheFieldIsLeftEmpty(): void
    {
        $_POST = ['token' => ''];

        $this->save();

        self::assertSame([], $this->savedOptions);
    }

    public function testRefusesUsersWithoutTheCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('esc_html__')->returnArg();
        Functions\expect('wp_die')->once()->andThrow(new \RuntimeException('wp_die'));
        $_POST = ['api_url' => 'https://evil.test'];

        $this->expectExceptionMessage('wp_die');
        (new SettingsSaveHandler())->save();
    }

    private function save(): string
    {
        try {
            (new SettingsSaveHandler())->save();
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }
        self::fail('Expected the handler to redirect');
    }
}

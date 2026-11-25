<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\LastPushError;
use Portrino\CmsObserverClient\Plugin;

final class PluginTest extends TestCase
{
    public function testTheCronPushKeepsAnyErrorForTheSettingsPage(): void
    {
        /** @var array<string, callable> $actions */
        $actions = [];
        $savedOptions = [];
        Functions\when('register_deactivation_hook')->justReturn(true);
        Functions\when('is_admin')->justReturn(false);
        Functions\when('add_action')->alias(static function (string $hook, callable $callback) use (&$actions): bool {
            $actions[$hook] = $callback;

            return true;
        });
        // Building the report fails before the API client is involved
        Functions\when('get_bloginfo')->alias(static function (): never {
            throw new \Error('Report failed');
        });
        Functions\when('update_site_option')->alias(static function (string $option, mixed $value) use (&$savedOptions): bool {
            $savedOptions[$option] = $value;

            return true;
        });

        Plugin::register('/path/to/cms-observer-client.php');
        $actions[Plugin::CRON_HOOK]();

        self::assertSame('Report failed', $savedOptions[LastPushError::OPTION]);
    }
}

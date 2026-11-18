<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * The message of the last failed push, for the settings page: a push from WP-Cron has
 * no other visible output. A network option, removed by the next successful push.
 */
final class LastPushError
{
    public const OPTION = 'cms_observer_client_last_error';

    public static function get(): string
    {
        $error = get_site_option(self::OPTION, '');

        return is_string($error) ? $error : '';
    }

    public static function save(string $message): void
    {
        update_site_option(self::OPTION, $message);
    }

    public static function clear(): void
    {
        delete_site_option(self::OPTION);
    }
}

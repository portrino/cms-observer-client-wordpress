<?php

declare(strict_types=1);

use Portrino\CmsObserverClient\Configuration;
use Portrino\CmsObserverClient\LastPushError;
use Portrino\CmsObserverClient\Plugin;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/Classes/Configuration.php';
require_once __DIR__ . '/Classes/LastPushError.php';
require_once __DIR__ . '/Classes/Plugin.php';

delete_site_option(Configuration::OPTION_API_URL);
delete_site_option(Configuration::OPTION_TOKEN);
delete_site_option(LastPushError::OPTION);
wp_clear_scheduled_hook(Plugin::CRON_HOOK);

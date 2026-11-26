<?php

/**
 * Plugin Name:       CMS Observer Client
 * Plugin URI:        https://github.com/portrino/cms-observer-client-wordpress
 * Description:       Reports the WordPress core, PHP and database versions plus the installed plugins to a CMS Observer instance.
 * Version:           2.0.4
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            portrino GmbH
 * Author URI:        https://www.portrino.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cms-observer-client
 * Network:           true
 * Update URI:        https://github.com/portrino/cms-observer-client-wordpress
 */

declare(strict_types=1);

use Portrino\CmsObserverClient\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

// No Composer autoloader at runtime: ZIP installs ship without vendor/
spl_autoload_register(static function (string $class): void {
    $prefix = 'Portrino\\CmsObserverClient\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/Classes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

Plugin::register(__FILE__);

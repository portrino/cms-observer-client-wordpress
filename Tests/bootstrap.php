<?php

declare(strict_types=1);

require __DIR__ . '/../.Build/vendor/autoload.php';

// Minimal stand-ins for the WordPress and WP-CLI classes the plugin uses;
// WordPress functions are mocked per test with Brain Monkey.
define('ABSPATH', __DIR__ . '/');

require __DIR__ . '/Unit/Fixtures/WordpressClasses.php';

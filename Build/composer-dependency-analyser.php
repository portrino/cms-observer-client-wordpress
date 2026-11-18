<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToScan(__DIR__ . '/../Classes', isDev: false)
    ->addPathToScan(__DIR__ . '/../Tests', isDev: true)
    // Provided by WordPress and WP-CLI at runtime
    ->ignoreUnknownClasses(['WP_CLI', 'wpdb', 'WP_Error', 'WP_Site'])
    // Composer plugin that installs the package into wp-content/plugins/cms-observer-client
    ->ignoreErrorsOnPackage('composer/installers', [ErrorType::UNUSED_DEPENDENCY]);

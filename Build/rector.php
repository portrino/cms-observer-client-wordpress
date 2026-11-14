<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        dirname(__DIR__) . '/Classes',
        dirname(__DIR__) . '/Tests',
        dirname(__DIR__) . '/cms-observer-client.php',
        dirname(__DIR__) . '/uninstall.php',
    ])
    // The lowest supported PHP version, newer sets would introduce syntax PHP 8.1 lacks
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withPhpSets(php81: true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);

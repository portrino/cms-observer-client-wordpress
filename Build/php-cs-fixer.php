<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()
    ->in(['Classes', 'Tests'])
    ->append(['cms-observer-client.php', 'uninstall.php']);
$config->setCacheFile(__DIR__ . '/../.Build/.php-cs-fixer.cache');

return $config;

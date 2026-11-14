<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Command;

use Portrino\CmsObserverClient\ObserverApiClient;
use Portrino\CmsObserverClient\ReportBuilder;

/**
 * Reports this site to the CMS Observer.
 */
final class PushCommand
{
    public function __construct(
        private readonly ReportBuilder $reportBuilder,
        private readonly ObserverApiClient $apiClient,
    ) {}

    /**
     * Pushes the WordPress core, PHP and database versions plus the installed plugins to the CMS Observer.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Print the report as JSON instead of sending it.
     *
     * ## EXAMPLES
     *
     *     wp cms-observer push
     *     wp cms-observer push --dry-run
     *
     * @param list<string> $args
     * @param array<string, bool|string> $assocArgs
     */
    public function push(array $args, array $assocArgs): void
    {
        $report = $this->reportBuilder->build();

        if (isset($assocArgs['dry-run'])) {
            \WP_CLI::line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return;
        }

        try {
            $this->apiClient->pushReport($report);
        } catch (\RuntimeException $e) {
            \WP_CLI::error($e->getMessage());
        }

        \WP_CLI::success(sprintf(
            'Reported WordPress %s, PHP %s, %d plugins to the CMS Observer.',
            $report['coreVersion'],
            $report['phpVersion'],
            count($report['extensions'])
        ));
    }
}

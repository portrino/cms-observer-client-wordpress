<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

use Portrino\CmsObserverClient\Admin\SettingsPage;
use Portrino\CmsObserverClient\Command\PushCommand;

final class Plugin
{
    public const CRON_HOOK = 'cms_observer_client_push';

    private function __construct(
        private readonly string $pluginFile,
    ) {}

    public static function register(string $pluginFile): void
    {
        $plugin = new self($pluginFile);

        register_deactivation_hook($pluginFile, [self::class, 'deactivate']);
        add_action('init', $plugin->scheduleCron(...));
        add_action(self::CRON_HOOK, $plugin->pushFromCron(...));

        if (is_admin()) {
            (new SettingsPage(new Configuration()))->register();
        }
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('cms-observer', new PushCommand($plugin->createReportBuilder(), $plugin->createApiClient()));
        }
    }

    /**
     * Scheduled on init rather than on activation: the activation hook doesn't fire when
     * a plugin gets activated by the database or by Composer-based setups (e.g. Bedrock).
     * Only the main site schedules, so a multisite network sends one report, not one per site.
     */
    public function scheduleCron(): void
    {
        if (is_main_site() && wp_next_scheduled(self::CRON_HOOK) === false) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function pushFromCron(): void
    {
        try {
            $this->createApiClient()->pushReport($this->createReportBuilder()->build());
        } catch (\Throwable $e) {
            error_log('CMS Observer Client: ' . $e->getMessage());
        }
    }

    private function createReportBuilder(): ReportBuilder
    {
        global $wpdb;

        return new ReportBuilder(
            new EnvironmentService($wpdb instanceof \wpdb ? $wpdb : null),
            new PluginInventoryService(new PluginSlugs(), new ActivePluginFiles()),
            $this->pluginFile,
        );
    }

    private function createApiClient(): ObserverApiClient
    {
        return new ObserverApiClient(new Configuration());
    }
}

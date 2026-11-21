<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * All installed plugins, active or not: a vulnerable plugin can be exploited
 * without being active.
 */
final class PluginInventoryService
{
    public function __construct(
        private readonly PluginSlugs $pluginSlugs,
        private readonly ActivePluginFiles $activePluginFiles,
    ) {}

    /**
     * @return list<array{name: string, type: string, title: string, version: string, active: bool}>
     */
    public function getInstalledPlugins(): array
    {
        // get_plugins() is only loaded in wp-admin, not in cron or WP-CLI requests
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $activeFiles = $this->activePluginFiles->get();
        $plugins = [];
        foreach (get_plugins() as $file => $data) {
            $slug = $this->pluginSlugs->get($file);
            // The Observer rejects a report with an empty title or version
            $plugins[] = [
                'name' => $slug,
                'type' => 'plugin',
                'title' => $this->getHeader($data, 'Name', $slug),
                'version' => $this->getHeader($data, 'Version', '0.0.0'),
                'active' => in_array($file, $activeFiles, true),
            ];
        }

        return $plugins;
    }

    /**
     * @param array<mixed> $data
     */
    private function getHeader(array $data, string $header, string $fallback): string
    {
        $value = is_string($data[$header] ?? null) ? trim($data[$header]) : '';

        return $value !== '' ? $value : $fallback;
    }
}

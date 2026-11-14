<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * On multisite, a plugin counts as active when it's network-activated or active
 * on at least one site of the network.
 */
final class ActivePluginFiles
{
    /**
     * @return list<string> plugin files, e.g. "classic-editor/classic-editor.php"
     */
    public function get(): array
    {
        $files = $this->getList(get_option('active_plugins', []));
        if (is_multisite()) {
            $files = array_merge($files, array_keys($this->getArray(get_site_option('active_sitewide_plugins', []))));
            foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $siteId) {
                $files = array_merge($files, $this->getList(get_blog_option($siteId, 'active_plugins', [])));
            }
        }

        return array_values(array_unique(array_map(strval(...), $files)));
    }

    /**
     * @return list<string>
     */
    private function getList(mixed $value): array
    {
        return array_values(array_filter($this->getArray($value), is_string(...)));
    }

    /**
     * @return array<mixed>
     */
    private function getArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}

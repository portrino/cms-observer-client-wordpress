<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * All installed themes, active or not: like a plugin, a vulnerable theme can be
 * exploited without being active.
 */
final class ThemeInventoryService
{
    /**
     * @return list<array{name: string, type: string, title: string, version: string, active: bool}>
     */
    public function getInstalledThemes(): array
    {
        $active = $this->getActiveDirectories();
        $themes = [];
        // keyed by the theme's directory, which is its wordpress.org slug
        foreach (wp_get_themes() as $directory => $theme) {
            $slug = (string)$directory;
            // The Observer rejects a report with an empty title or version
            $themes[] = [
                'name' => $slug,
                'type' => 'theme',
                'title' => $this->getHeader($theme, 'Name', $slug),
                'version' => $this->getHeader($theme, 'Version', '0.0.0'),
                'active' => in_array($slug, $active, true),
            ];
        }

        return $themes;
    }

    /**
     * The active theme and, for a child theme, its parent, whose code runs too. On
     * multisite those of every site of the network.
     *
     * @return list<string>
     */
    private function getActiveDirectories(): array
    {
        $directories = [get_option('stylesheet'), get_option('template')];
        if (is_multisite()) {
            foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $siteId) {
                $directories[] = get_blog_option($siteId, 'stylesheet');
                $directories[] = get_blog_option($siteId, 'template');
            }
        }

        return array_values(array_unique(array_filter($directories, is_string(...))));
    }

    private function getHeader(\WP_Theme $theme, string $header, string $fallback): string
    {
        $value = $theme->get($header);
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : $fallback;
    }
}

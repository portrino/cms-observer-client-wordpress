<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * The Observer matches plugins by their wordpress.org slug, which is not always the
 * directory name (Hello Dolly is hello.php, slug "hello-dolly"). WordPress' last update
 * check stores the slug of every plugin it found on wordpress.org; for the others the
 * directory (or file) name is the best guess.
 */
final class PluginSlugs
{
    /**
     * @var array<array-key, string>|null plugin file => slug
     */
    private ?array $wordpressOrgSlugs = null;

    public function get(string $file): string
    {
        $this->wordpressOrgSlugs ??= $this->loadWordpressOrgSlugs();

        return $this->wordpressOrgSlugs[$file] ?? $this->guess($file);
    }

    /**
     * @return array<array-key, string>
     */
    private function loadWordpressOrgSlugs(): array
    {
        $updates = get_site_transient('update_plugins');
        if (!is_object($updates)) {
            return [];
        }

        return array_merge($this->getSlugs($updates->no_update ?? []), $this->getSlugs($updates->response ?? []));
    }

    /**
     * @return array<array-key, string>
     */
    private function getSlugs(mixed $entries): array
    {
        $slugs = array_map(
            static fn(mixed $entry): mixed => is_object($entry) ? ($entry->slug ?? null) : null,
            is_array($entries) ? $entries : []
        );

        return array_filter(array_filter($slugs, is_string(...)), static fn(string $slug): bool => $slug !== '');
    }

    private function guess(string $file): string
    {
        $directory = dirname($file);

        return $directory !== '.' ? $directory : basename($file, '.php');
    }
}

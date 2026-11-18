<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * All hosts of this instance, lowercase, without scheme, port or path, unique and sorted:
 * home_url() and site_url() on a single site, the domains of all sites on multisite.
 * The Observer rejects a report whose domains don't match the site URL, so a staging copy
 * with the production database (and thus the production token) can't overwrite production.
 */
final class SiteDomains
{
    /**
     * @return list<string>
     */
    public function get(): array
    {
        $hosts = array_unique(array_filter(array_map($this->getHost(...), $this->getUrls()), static fn(string $host): bool => $host !== ''));
        sort($hosts);

        return $hosts;
    }

    /**
     * @return list<string>
     */
    private function getUrls(): array
    {
        if (!is_multisite()) {
            return [home_url(), site_url()];
        }

        // number 0: all sites, not the first 100
        return array_values(array_map(static fn(\WP_Site $site): string => 'http://' . $site->domain, get_sites(['number' => 0])));
    }

    private function getHost(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }
}

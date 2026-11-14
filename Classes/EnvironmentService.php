<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

final class EnvironmentService
{
    public function __construct(
        private readonly ?\wpdb $database,
    ) {}

    public function getCoreVersion(): string
    {
        return get_bloginfo('version');
    }

    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * The full server version (e.g. "10.11.19-MariaDB-ubu2204-log"), not wpdb::db_version(),
     * which strips the MariaDB marker the Observer needs.
     */
    public function getDatabaseVersion(): string
    {
        $version = $this->isMysql() ? trim((string)$this->database?->db_server_info()) : '';

        // The Observer rejects a report with an empty dbVersion
        return $version !== '' ? $version : 'unknown';
    }

    /**
     * endoflife.date product slug, null for databases the Observer doesn't evaluate
     * (e.g. the SQLite Database Integration plugin).
     */
    public function getDatabasePlatform(): ?string
    {
        if (!$this->isMysql()) {
            return null;
        }

        return stripos($this->getDatabaseVersion(), 'mariadb') !== false ? 'mariadb' : 'mysql';
    }

    /**
     * "local", "development", "staging" or "production"
     */
    public function getApplicationContext(): string
    {
        return wp_get_environment_type();
    }

    private function isMysql(): bool
    {
        return $this->database !== null && $this->database->is_mysql === true;
    }
}

<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

final class ReportBuilder
{
    public function __construct(
        private readonly EnvironmentService $environmentService,
        private readonly PluginInventoryService $pluginInventoryService,
        private readonly ThemeInventoryService $themeInventoryService,
        private readonly SiteDomains $siteDomains,
        private readonly string $pluginFile,
    ) {}

    /**
     * "packages" holds plugins and themes, each with its "type" (needs CMS Observer 0.7.0 or newer).
     *
     * @return array{coreVersion: string, phpVersion: string, dbVersion: string, dbPlatform: string|null, clientVersion: string, applicationContext: string, domains: list<string>, packages: list<array{name: string, type: string, title: string, version: string, active: bool}>}
     */
    public function build(): array
    {
        return [
            'coreVersion' => $this->environmentService->getCoreVersion(),
            'phpVersion' => $this->environmentService->getPhpVersion(),
            'dbVersion' => $this->environmentService->getDatabaseVersion(),
            'dbPlatform' => $this->environmentService->getDatabasePlatform(),
            'clientVersion' => $this->getClientVersion(),
            'applicationContext' => $this->environmentService->getApplicationContext(),
            'domains' => $this->siteDomains->get(),
            'packages' => [
                ...$this->pluginInventoryService->getInstalledPlugins(),
                ...$this->themeInventoryService->getInstalledThemes(),
            ],
        ];
    }

    private function getClientVersion(): string
    {
        $version = get_file_data($this->pluginFile, ['Version' => 'Version'])['Version'] ?? '';

        return $version !== '' ? $version : '0.0.0';
    }
}

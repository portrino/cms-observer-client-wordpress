<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

/**
 * API URL and token. Each comes from, in this order: the environment variable,
 * the constant of the same name (wp-config.php), the plugin's settings page.
 * Settings are network options, so a multisite network shares one configuration;
 * on a single site WordPress stores them as regular options.
 */
final class Configuration
{
    public const API_URL = 'CMS_OBSERVER_API_URL';
    public const TOKEN = 'CMS_OBSERVER_TOKEN';

    public const OPTION_API_URL = 'cms_observer_client_api_url';
    public const OPTION_TOKEN = 'cms_observer_client_token';

    public const SOURCE_ENVIRONMENT = 'environment';
    public const SOURCE_CONSTANT = 'constant';
    public const SOURCE_SETTINGS = 'settings';

    private const OPTIONS = [
        self::API_URL => self::OPTION_API_URL,
        self::TOKEN => self::OPTION_TOKEN,
    ];

    public function getApiUrl(): string
    {
        return $this->get(self::API_URL);
    }

    public function getToken(): string
    {
        return $this->get(self::TOKEN);
    }

    /**
     * @param self::API_URL|self::TOKEN $name
     * @return self::SOURCE_*
     */
    public function getSource(string $name): string
    {
        if ($this->fromEnvironment($name) !== '') {
            return self::SOURCE_ENVIRONMENT;
        }

        return $this->fromConstant($name) !== '' ? self::SOURCE_CONSTANT : self::SOURCE_SETTINGS;
    }

    /**
     * @param self::API_URL|self::TOKEN $name
     */
    private function get(string $name): string
    {
        return match ($this->getSource($name)) {
            self::SOURCE_ENVIRONMENT => $this->fromEnvironment($name),
            self::SOURCE_CONSTANT => $this->fromConstant($name),
            self::SOURCE_SETTINGS => $this->fromSettings(self::OPTIONS[$name]),
        };
    }

    private function fromEnvironment(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? trim($value) : '';
    }

    private function fromConstant(string $name): string
    {
        $value = defined($name) ? constant($name) : null;

        return is_string($value) ? trim($value) : '';
    }

    private function fromSettings(string $option): string
    {
        $value = get_site_option($option, '');

        return is_string($value) ? trim($value) : '';
    }
}

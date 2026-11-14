<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

final class ObserverApiClient
{
    // WordPress' default of 5 s is short for a report with many plugins
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly Configuration $configuration,
    ) {}

    /**
     * @param array<string, mixed> $report
     * @throws \RuntimeException when the client isn't configured or the Observer doesn't accept the report
     */
    public function pushReport(array $report): void
    {
        $apiUrl = $this->configuration->getApiUrl();
        $token = $this->configuration->getToken();
        if ($apiUrl === '' || $token === '') {
            throw new \RuntimeException('CMS Observer client is not configured: set the API URL and token on the settings page, as constants in wp-config.php or as the environment variables CMS_OBSERVER_API_URL and CMS_OBSERVER_TOKEN.', 1763150400);
        }
        // The token and the version inventory must never travel in cleartext
        if (strtolower((string)wp_parse_url($apiUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \RuntimeException(sprintf('CMS Observer API URL must use https, got "%s".', $apiUrl), 1763150401);
        }

        $response = wp_remote_post(rtrim($apiUrl, '/') . '/api/v1/sites/report', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($report, JSON_THROW_ON_ERROR),
            'timeout' => self::TIMEOUT_SECONDS,
            // Following a redirect would hand the bearer token to wherever it points
            'redirection' => 0,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException('Sending the report to the CMS Observer failed: ' . $response->get_error_message(), 1763150402);
        }
        $status = (int)wp_remote_retrieve_response_code($response);
        if ($status !== 202) {
            throw new \RuntimeException(sprintf('CMS Observer rejected the report (HTTP %d).', $status), 1763150403);
        }
    }
}

<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient;

final class ObserverApiClient
{
    // WordPress' default of 5 s is short for a report with many plugins
    private const TIMEOUT_SECONDS = 10;

    // A 409 means the reported domains don't match the site URL, e.g. a staging copy with the production database
    private const ERROR_CODES = [409 => 1763150404];
    private const FALLBACK_REASONS = [409 => ': the reported domains don\'t match the site URL registered on the Observer.'];

    public function __construct(
        private readonly Configuration $configuration,
    ) {}

    /**
     * @param array<string, mixed> $report
     * @throws \RuntimeException when the client isn't configured or the Observer doesn't accept the report
     */
    public function pushReport(array $report): void
    {
        try {
            $this->send($report);
        } catch (\RuntimeException $e) {
            LastPushError::save($e->getMessage());
            throw $e;
        }
        LastPushError::clear();
    }

    /**
     * @param array<string, mixed> $report
     */
    private function send(array $report): void
    {
        $apiUrl = $this->configuration->getApiUrl();
        $token = $this->configuration->getToken();
        if ($apiUrl === '' || $token === '') {
            throw new \RuntimeException('CMS Observer client is not configured: set the API URL and token on the settings page, as constants in wp-config.php or as the environment variables CMS_OBSERVER_API_URL and CMS_OBSERVER_TOKEN.', 1763150400);
        }
        // The token and the version inventory must never travel in cleartext
        if (strtolower((string)wp_parse_url($apiUrl, PHP_URL_SCHEME)) !== 'https') {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it's output (settings page), WP-CLI shows plain text
            throw new \RuntimeException(sprintf('CMS Observer API URL must use https, got "%s".', $apiUrl), 1763150401);
        }

        $response = wp_remote_post(rtrim($apiUrl, '/') . '/api/v1/sites/report', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                // without it the Observer answers a validation error (422) with an HTML page
                'Accept' => 'application/json',
            ],
            'body' => json_encode($report, JSON_THROW_ON_ERROR),
            'timeout' => self::TIMEOUT_SECONDS,
            // Following a redirect would hand the bearer token to wherever it points
            'redirection' => 0,
        ]);

        $this->checkResponse($response, $token);
    }

    /**
     * @param array<string, mixed>|\WP_Error $response
     */
    private function checkResponse(array|\WP_Error $response, string $token): void
    {
        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it's output (settings page), WP-CLI shows plain text
            throw new \RuntimeException('Sending the report to the CMS Observer failed: ' . $response->get_error_message(), 1763150402);
        }
        $status = (int)wp_remote_retrieve_response_code($response);
        if ($status === 202) {
            return;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- escaped where it's output (settings page), WP-CLI shows plain text
        throw new \RuntimeException(sprintf('CMS Observer rejected the report (HTTP %d)%s', $status, $this->reason($response, $status, $token)), self::ERROR_CODES[$status] ?? 1763150403);
    }

    /**
     * "message" says why a 409 was rejected, "detail" names the invalid field of a 422.
     *
     * @param array<string, mixed> $response
     */
    private function reason(array $response, int $status, string $token): string
    {
        $body = (array)json_decode(wp_remote_retrieve_body($response), true);
        $reason = $body['message'] ?? $body['detail'] ?? null;

        // Never show the token, even if the response echoes it
        return is_string($reason) && trim($reason) !== ''
            ? ': ' . str_replace($token, '***', trim($reason))
            : self::FALLBACK_REASONS[$status] ?? '.';
    }
}

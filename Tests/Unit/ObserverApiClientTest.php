<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\Configuration;
use Portrino\CmsObserverClient\ObserverApiClient;

final class ObserverApiClientTest extends TestCase
{
    /** @var array{0: string, 1: array<string, mixed>}|null */
    private ?array $sentRequest = null;

    public function testSendsTheReportWithoutFollowingRedirects(): void
    {
        $this->createClient('https://observer.test/', 'secret')->pushReport(['coreVersion' => '7.1']);

        [$url, $args] = $this->sentRequest;
        self::assertSame('https://observer.test/api/v1/sites/report', $url);
        self::assertSame('Bearer secret', $args['headers']['Authorization']);
        self::assertSame('{"coreVersion":"7.1"}', $args['body']);
        self::assertSame(0, $args['redirection']);
        self::assertGreaterThan(5, $args['timeout']);
    }

    public function testThrowsWhenNothingIsConfigured(): void
    {
        $this->expectExceptionCode(1763150400);
        $this->createClient('', '')->pushReport([]);
    }

    public function testRefusesToSendTheTokenOverPlainHttp(): void
    {
        try {
            $this->createClient('http://observer.test', 'secret')->pushReport([]);
            self::fail('Expected an exception for a non-https API URL');
        } catch (\RuntimeException $e) {
            self::assertSame(1763150401, $e->getCode());
        }
        self::assertNull($this->sentRequest);
    }

    public function testThrowsWhenTheRequestFails(): void
    {
        $this->expectExceptionMessage('Connection refused');
        $this->createClient('https://observer.test', 'secret', new \WP_Error('Connection refused'))->pushReport([]);
    }

    public function testThrowsWithTheStatusCodeWhenTheObserverDoesNotAccept(): void
    {
        $this->expectExceptionMessage('HTTP 401');
        $this->createClient('https://observer.test', 'secret', ['response' => ['code' => 401]])->pushReport([]);
    }

    /**
     * @param array<string, mixed>|\WP_Error $response
     */
    private function createClient(string $apiUrl, string $token, array|\WP_Error $response = ['response' => ['code' => 202]]): ObserverApiClient
    {
        Functions\when('get_site_option')->alias(static fn(string $option): string => match ($option) {
            Configuration::OPTION_API_URL => $apiUrl,
            Configuration::OPTION_TOKEN => $token,
            default => '',
        });
        Functions\when('wp_parse_url')->alias('parse_url');
        Functions\when('wp_remote_post')->alias(function (string $url, array $args) use ($response): array|\WP_Error {
            $this->sentRequest = [$url, $args];

            return $response;
        });
        Functions\when('is_wp_error')->alias(static fn(mixed $value): bool => $value instanceof \WP_Error);
        Functions\when('wp_remote_retrieve_response_code')->alias(static fn(array $response): int => $response['response']['code']);

        return new ObserverApiClient(new Configuration());
    }
}

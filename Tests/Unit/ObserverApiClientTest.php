<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit;

use Brain\Monkey\Functions;
use Portrino\CmsObserverClient\Configuration;
use Portrino\CmsObserverClient\LastPushError;
use Portrino\CmsObserverClient\ObserverApiClient;

final class ObserverApiClientTest extends TestCase
{
    /** @var array{0: string, 1: array<string, mixed>}|null */
    private ?array $sentRequest = null;

    /** @var array<string, mixed> */
    private array $savedOptions = [];

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

    public function testShowsTheObserversMessageWithoutTheTokenOnAConflict(): void
    {
        $body = '{"message":"Domains staging.example.com don\'t match the site URL (token secret)."}';

        try {
            $this->createClient('https://observer.test', 'secret', ['response' => ['code' => 409], 'body' => $body])->pushReport([]);
            self::fail('Expected an exception for HTTP 409');
        } catch (\RuntimeException $e) {
            self::assertSame(1763150404, $e->getCode());
            self::assertSame('CMS Observer rejected the report (HTTP 409): Domains staging.example.com don\'t match the site URL (token ***).', $e->getMessage());
        }
    }

    public function testExplainsAConflictWithoutMessage(): void
    {
        $this->expectExceptionMessage("HTTP 409): the reported domains don't match the site URL registered on the Observer.");
        $this->createClient('https://observer.test', 'secret', ['response' => ['code' => 409], 'body' => 'Conflict'])->pushReport([]);
    }

    public function testKeepsTheLastErrorUntilThePushSucceeds(): void
    {
        try {
            $this->createClient('https://observer.test', 'secret', ['response' => ['code' => 401]])->pushReport([]);
        } catch (\RuntimeException) {
        }
        self::assertSame('CMS Observer rejected the report (HTTP 401).', $this->savedOptions[LastPushError::OPTION]);

        $this->createClient('https://observer.test', 'secret')->pushReport([]);
        self::assertArrayNotHasKey(LastPushError::OPTION, $this->savedOptions);
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
        Functions\when('wp_remote_retrieve_body')->alias(static fn(array $response): string => $response['body'] ?? '');
        Functions\when('update_site_option')->alias(function (string $option, mixed $value): bool {
            $this->savedOptions[$option] = $value;

            return true;
        });
        Functions\when('delete_site_option')->alias(function (string $option): bool {
            unset($this->savedOptions[$option]);

            return true;
        });

        return new ObserverApiClient(new Configuration());
    }
}
